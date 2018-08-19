<?php
/**
 * Stats exporter
 * User: moyo
 * Date: 2018/5/19
 * Time: 10:19 PM
 */

namespace Carno\Pool;

class Stats
{
    /**
     * @var Connections
     */
    private $c = null;

    /**
     * Stats constructor.
     * @param Connections $c
     */
    public function __construct(Connections $c)
    {
        $this->c = $c;
    }

    /**
     */
    public function untrack() : void
    {
        $this->c = null;
    }

    /**
     * @return int
     */
    public function cIdling() : int
    {
        return $this->c->cIdleCount();
    }

    /**
     * @return int
     */
    public function cBusying() : int
    {
        return $this->c->cBusyCount();
    }

    /**
     * @return int
     */
    public function sPending() : int
    {
        return $this->c->cWaitCount();
    }
}
