<?php
/**
 * Connections
 * User: moyo
 * Date: 09/08/2017
 * Time: 6:00 PM
 */

namespace Carno\Pool;

use function Carno\Coroutine\go;
use function Carno\Coroutine\race;
use function Carno\Coroutine\timeout;
use Carno\Pool\Chips\CCPolicy;
use Carno\Pool\Exception\SelectWaitOverflowException;
use Carno\Pool\Exception\SelectWaitTimeoutException;
use Carno\Pool\Features\IdleRecycling;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use SplQueue;
use SplStack;
use Throwable;

class Connections
{
    use CCPolicy;

    /**
     * @var Pool
     */
    private $pool = null;

    /**
     * @var Options
     */
    private $options = null;

    /**
     * @var Connector
     */
    private $connector = null;

    /**
     * @var IdleRecycling
     */
    private $recycling = null;

    /**
     * @var int
     */
    private $connIDA = 0;

    /**
     * @var SplStack
     */
    private $staIdling = null;

    /**
     * @var Poolable[]
     */
    private $staBusying = [];

    /**
     * @var int
     */
    private $rsExpanding = 0;

    /**
     * @var SplQueue
     */
    private $getWaitQ = null;

    /**
     * @var Promised[]
     */
    private $liveGetQ = [];

    /**
     * @var bool
     */
    private $exiting = false;

    /**
     * Connections constructor.
     * @param Pool $pool
     * @param Options $options
     * @param Connector $connector
     */
    public function __construct(
        Pool $pool,
        Options $options,
        Connector $connector
    ) {
        $this->pool = $pool;
        $this->options = $options;
        $this->connector = $connector;

        $this->staIdling = new SplStack;
        $this->getWaitQ = new SplQueue;

        $this->resizing($this->options->initial, 'initialize');
    }

    /**
     */
    public function cleanup() : void
    {
        $this->pool = null;
        $this->options = null;

        $this->getWaitQ = null;

        $this->connector->cleanup();
    }

    /**
     * @param IdleRecycling $recycling
     */
    public function setIRecycling(IdleRecycling $recycling) : void
    {
        $this->recycling = $recycling;
    }

    /**
     * @return int
     */
    public function cBusyCount() : int
    {
        return count($this->staBusying);
    }

    /**
     * @return int
     */
    public function cIdleCount() : int
    {
        return $this->staIdling->count();
    }

    /**
     * @return int
     */
    public function cWaitCount() : int
    {
        return $this->getWaitQ ? $this->getWaitQ->count() : 0;
    }

    /**
     * @deprecated
     * @return Promised|Poolable
     */
    public function getLived()
    {
        if (!$this->staIdling->isEmpty()) {
            return $this->staIdling->current();
        } elseif ($this->staBusying) {
            return current($this->staBusying);
        } else {
            return $this->liveGetQ[] = Promise::deferred();
        }
    }

    /**
     * @return SplStack|Poolable[]
     */
    public function getIdles() : SplStack
    {
        return $this->staIdling;
    }

    /**
     * @param bool $wait
     * @param bool $work
     * @return Promised|Poolable
     */
    public function getIdled(bool $wait = true, bool $work = true)
    {
        if ($this->staIdling->isEmpty()) {
            if ($wait) {
                if ($this->getWaitQ->count() > $this->options->getWaitQMax) {
                    throw new SelectWaitOverflowException($this->connector->identify());
                }

                $this->getWaitQ->enqueue($waiting = Promise::deferred());

                $this->ccDecision(
                    $this->options,
                    $this->connector->identify(),
                    $this->cIdleCount(),
                    $this->cBusyCount(),
                    $this->cWaitCount()
                );

                return race(
                    $waiting,
                    timeout(
                        $this->options->getWaitTimeout,
                        SelectWaitTimeoutException::class,
                        $this->connector->identify()
                    )
                );
            }

            return null;
        }

        return $this->setBusying(null, $work);
    }

    /**
     * @param Poolable $conn
     */
    public function putIdled(Poolable $conn) : void
    {
        /**
         * @var Promised $wait
         */

        // checking in liveGetQ
        while ($this->liveGetQ && null !== $wait = array_pop($this->liveGetQ)) {
            $wait->pended() && $wait->resolve($conn);
        }

        // checking in getWaitQ
        while (($this->getWaitQ ?! $this->getWaitQ->isEmpty() : false) && $wait = $this->getWaitQ->dequeue()) {
            if ($wait->pended()) {
                $wait->resolve($this->setBusying($conn));
                return;
            }
        }

        // finally set idle
        $this->setIdling($conn);

        // check exiting
        $this->exiting && $conn->destroy();
    }

    /**
     * @param Poolable $conn
     */
    public function released(Poolable $conn) : void
    {
        $hit = false;

        $cid = $conn->cid();

        // searching in "busying" stack
        if (isset($this->staBusying[$cid])) {
            unset($this->staBusying[$cid]);
            $this->setClosing($conn);
            $hit = true;
        }

        // searching in "idling" stack
        $hit || $this->removeIdling($conn);

        // finally checking conn state
        $this->checking();
    }

    /**
     * @param bool $forced
     * @param string $reason
     */
    public function exit(bool $forced = true, string $reason = 'exiting') : void
    {
        $this->exiting = true;
        $this->resizing(0, $reason, $forced);
        $this->checking();
    }

    /**
     * conn sta checking
     */
    private function checking() : void
    {
        $cleared = ! ($this->cIdleCount() + $this->cBusyCount());

        if ($this->exiting) {
            if ($this->pool && ($closed = $this->pool->closed())->pended() && $cleared) {
                $closed->resolve();
            }
            return;
        }

        $cleared && $this->resizing(
            max(
                1,
                $this->options->initial,
                min($this->getWaitQ->count(), $this->options->maxIdle)
            ),
            'minimum-scaling'
        );
    }

    /**
     * @param int $target
     * @param string $reason
     * @param bool $forced
     * @return int
     */
    public function resizing(int $target, string $reason = 'none', bool $forced = false) : int
    {
        $busySize = $this->cBusyCount();
        $idleSize = $this->cIdleCount();

        if ($busySize + $idleSize < $target && !$this->rsExpanding) {
            logger('pool')->debug(
                'Expanding',
                [
                    'id' => $this->connector->identify(),
                    'idle' => $idleSize,
                    'busy' => $busySize,
                    'target' => $target,
                    'reason' => $reason,
                ]
            );
            $this->rsExpanding = $expandSize = $target - $busySize - $idleSize;
            while ($expandSize -- > 0) {
                go(function () {
                    try {
                        /**
                         * @var Poolable $poolable
                         */
                        $poolable = yield $this->connector->created();
                        yield $poolable->connect();
                        $poolable->cid(sprintf('c-%d', ++ $this->connIDA));
                        $this->putIdled($poolable);
                    } catch (Throwable $e) {
                        logger('pool')->warning(
                            'Connecting failed',
                            [
                                'id' => $this->connector->identify(),
                                'error' => sprintf('#%d->%s::%s', $e->getCode(), get_class($e), $e->getMessage()),
                            ]
                        );
                    } finally {
                        $this->rsExpanding --;
                    }
                });
            }
            return $expandSize;
        } elseif ($busySize + $idleSize > $target) {
            logger('pool')->debug(
                'Shrinking',
                [
                    'id' => $this->connector->identify(),
                    'idle' => $idleSize,
                    'busy' => $busySize,
                    'target' => $target,
                    'reason' => $reason,
                ]
            );
            $shrinkSize = ($busySize + $idleSize) - $target;
            while ($shrinkSize -- > 0) {
                if ($this->staIdling->count() > 0) {
                    if ($this->removeIdling(null, function ($_, Poolable $found) use ($forced) {
                        if (($this->recycling && $this->recycling->idling($found->cid())) || $forced) {
                            return true;
                        }
                        return false;
                    })) {
                        // removed ... checking next
                        continue;
                    } else {
                        // give up shrinking if all idles conn not accord
                        logger('pool')->debug('Idles conn not according', ['id' => $this->connector->identify()]);
                        break;
                    }
                }
                ($closer = Promise::deferred())->then(function (Poolable $conn) {
                    $this->setClosing($conn);
                });
                ($got = array_shift($this->staBusying)) && $got->schedule(Poolable::RELEASED, $closer);
            }
            return - $shrinkSize;
        }

        return 0;
    }

    /**
     * @param Poolable $conn
     * @param bool $work
     * @return Poolable
     */
    private function setBusying(Poolable $conn = null, bool $work = true) : Poolable
    {
        is_null($conn) && $conn = $this->staIdling->shift();

        $cid = $conn->cid();

        if ($this->recycling && $work) {
            $this->recycling->busying($cid);
        }

        return $this->staBusying[$cid] = $conn;
    }

    /**
     * @param Poolable $conn
     */
    private function setIdling(Poolable $conn) : void
    {
        $cid = $conn->cid();

        if (isset($this->staBusying[$cid])) {
            unset($this->staBusying[$cid]);
        }

        $this->staIdling->unshift($conn);
    }

    /**
     * @param Poolable $conn
     */
    private function setClosing(Poolable $conn) : void
    {
        $conn->closed()->pended() && $conn->close();
    }

    /**
     * @param Poolable $any
     * @param callable $matcher
     * @param bool $close
     * @return bool
     */
    private function removeIdling(Poolable $any = null, callable $matcher = null, bool $close = true) : bool
    {
        if ($this->staIdling->isEmpty()) {
            return false;
        }

        if (is_null($matcher)) {
            $matcher = static function (Poolable $present, Poolable $found) {
                return $present->cid() === $found->cid();
            };
        }

        $searched = 0;
        $connections = $this->staIdling->count();

        while ($searched ++ < $connections) {
            $conn = $this->staIdling->pop();
            if ($matcher($any, $conn)) {
                $close && $this->setClosing($conn);
                return true;
            } else {
                $this->staIdling->unshift($conn);
            }
        }

        return false;
    }
}
