<?php
/**
 * Conn managed
 * User: moyo
 * Date: 09/08/2017
 * Time: 3:37 PM
 */

namespace Carno\Pool;

use Carno\Promise\Promise;
use Carno\Promise\Promised;

trait Managed
{
    /**
     * @var string
     */
    private $cid = 'c--';

    /**
     * @var Pool
     */
    private $pool = null;

    /**
     * @var Promised
     */
    private $closed = null;

    /**
     * @var Promised[]
     */
    private $schedules = [];

    /**
     * @see Poolable::cid
     * @param string $set
     * @return string
     */
    final public function cid(string $set = null) : string
    {
        return $set ? $this->cid = $set : $this->cid;
    }

    /**
     * @see Poolable::pool
     * @param Pool $pool
     */
    final public function pool(Pool $pool) : void
    {
        $this->pool = $pool;
    }

    /**
     * @see Poolable::closed
     * @return Promised
     */
    final public function closed() : Promised
    {
        if (isset($this->closed)) {
            return $this->closed;
        }

        ($this->closed = Promise::deferred())->then(function () {
            $this->destroy();
        }, function () {
            $this->destroy();
        });

        return $this->closed;
    }

    /**
     * @see Poolable::schedule
     * @param int $evk
     * @param Promised $future
     */
    final public function schedule(int $evk, Promised $future) : void
    {
        $this->schedules[$evk] = $future;
    }

    /**
     * @see Poolable::release
     */
    final public function release() : void
    {
        if (isset($this->pool)) {
            $this->scheduled() || $this->pool->recycle($this);
        }
    }

    /**
     * @see Poolable::destroy
     */
    final public function destroy() : void
    {
        if (isset($this->pool)) {
            $pool = $this->pool;
            unset($this->pool);
            $this->scheduled();
            $pool->release($this);
        }
    }

    /**
     * @return bool
     */
    final private function scheduled() : bool
    {
        if (($w = $this->schedules[Poolable::RELEASED] ?? null) && $w->pended()) {
            $w->resolve($this);
            return true;
        }
        return false;
    }
}
