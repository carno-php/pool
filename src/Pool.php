<?php
/**
 * Conn pool
 * User: moyo
 * Date: 09/08/2017
 * Time: 3:05 PM
 */

namespace Carno\Pool;

use function Carno\Coroutine\race;
use function Carno\Coroutine\timeout;
use Carno\Pool\Contracts\Select;
use Carno\Pool\Exception\SelectUnavailableException;
use Carno\Pool\Exception\ShutdownTimeoutException;
use Carno\Pool\Features\Heartbeat;
use Carno\Pool\Features\IdleRecycling;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Closure;

class Pool
{
    /**
     * @var string
     */
    protected $identify = null;

    /**
     * @var Connections
     */
    protected $connections = null;

    /**
     * @var IdleRecycling
     */
    protected $recycling = null;

    /**
     * @var Heartbeat
     */
    protected $heartbeat = null;

    /**
     * @var bool
     */
    protected $stopped = false;

    /**
     * @var Promised
     */
    protected $closed = null;

    /**
     * @var Stats
     */
    private $stats = null;

    /**
     * Pool constructor.
     * @param Options $options
     * @param Closure $dialer
     * @param string $identify
     */
    public function __construct(Options $options, Closure $dialer, string $identify = 'conn')
    {
        $this->connections = new Connections(
            $this,
            $options,
            new Connector($this, $dialer, $this->identify = $identify)
        );

        if ($options->icInterval > 0) {
            $this->connections->setIRecycling(
                $this->recycling = new IdleRecycling($options, $this->connections)
            );
        }

        if ($options->hbInterval > 0) {
            $this->heartbeat = new Heartbeat($options, $this->connections);
        }

        $this->closed()->then(function () {
            $this->stopping();
            $this->stats->untrack();
            $this->connections->cleanup();
            Observer::closed($this);
        });

        $this->stats = new Stats($this->connections);

        Observer::created($this);
    }

    /**
     * @return string
     */
    public function resource() : string
    {
        return $this->identify;
    }

    /**
     * @return Stats
     */
    public function stats() : Stats
    {
        return $this->stats;
    }

    /**
     * @param int $mode
     * @return Poolable
     */
    public function select(int $mode = Select::IDLING)
    {
        if ($this->stopped) {
            throw new SelectUnavailableException('This pool has been stopped');
        }

        switch ($mode) {
            case Select::IDLING:
                return $this->connections->getIdled();
            case Select::RANDOM:
                return $this->connections->getLived();
            default:
                return null;
        }
    }

    /**
     * @param Poolable $conn
     */
    public function recycle(Poolable $conn) : void
    {
        $this->connections->putIdled($conn);
    }

    /**
     * @param Poolable $conn
     */
    public function release(Poolable $conn) : void
    {
        $this->connections->released($conn);
    }

    /**
     * @return Promised
     */
    public function shutdown() : Promised
    {
        $this->stopping();

        $this->connections->exit(true, 'pool-shutdown');

        return race($this->closed(), timeout(45000, ShutdownTimeoutException::class, $this->identify));
    }

    /**
     */
    public function stopping() : void
    {
        if ($this->stopped) {
            return;
        }

        $this->heartbeat && $this->heartbeat->shutdown();
        $this->recycling && $this->recycling->shutdown();

        $this->stopped = true;
    }

    /**
     * @return Promised
     */
    public function closed() : Promised
    {
        return $this->closed ?? $this->closed = Promise::deferred();
    }
}
