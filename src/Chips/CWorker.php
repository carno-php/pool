<?php
/**
 * Connections related worker
 * User: moyo
 * Date: 2018/5/23
 * Time: 10:17 AM
 */

namespace Carno\Pool\Chips;

use Carno\Pool\Connections;
use Carno\Timer\Timer;

trait CWorker
{
    /**
     * @var Connections
     */
    private $connections = null;

    /**
     * @var string
     */
    private $timer = null;

    /**
     * @var int
     */
    private $ivs = 0;

    /**
     * @var callable
     */
    private $ex = null;

    /**
     * @return Connections
     */
    protected function conn() : Connections
    {
        return $this->connections;
    }

    /**
     * @param Connections $connections
     */
    protected function track(Connections $connections) : void
    {
        $this->connections = $connections;
    }

    /**
     */
    public function untrack() : void
    {
        $this->ex = null;
        $this->connections = null;
    }

    /**
     * @param int $ivs
     * @param callable $processor
     */
    protected function ticker(int $ivs, callable $processor) : void
    {
        $ivs > 0 && $this->timer = Timer::loop(($this->ivs = $ivs) * 1000, $this->ex = $processor);
    }

    /**
     */
    public function pause() : void
    {
        $this->timer && Timer::clear($this->timer) && $this->timer = null;
    }

    /**
     */
    public function resume() : void
    {
        (is_null($this->timer) && $this->ex && $this->connections) && $this->ticker($this->ivs, $this->ex);
    }

    /**
     */
    public function shutdown() : void
    {
        $this->pause();
        $this->untrack();
    }
}
