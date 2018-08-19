<?php
/**
 * Pool options
 * User: moyo
 * Date: 14/08/2017
 * Time: 2:33 PM
 */

namespace Carno\Pool;

class Options
{
    /**
     * how many connections created for initial
     * @var int
     */
    public $initial = 0;

    /**
     * how many connections allows overall
     * @var int
     */
    public $overall = 0;

    /**
     * max idle connections allowed and automatic recycling when pool is idle
     * @var int
     */
    public $maxIdle = 0;

    /**
     * min idle connections keeps and automatic increasing when pool is busy
     * @var int
     */
    public $minIdle = 0;

    /**
     * time(seconds) to idling(real) state
     * @var int
     */
    public $idleTimeout = 0;

    /**
     * idle check interval in seconds
     * @var int
     */
    public $icInterval = 0;

    /**
     * heartbeat interval in seconds
     * @var int
     */
    public $hbInterval = 0;

    /**
     * select wait queue max
     * @var int
     */
    public $getWaitQMax = 0;

    /**
     * select wait timeout
     * @var int
     */
    public $getWaitTimeout = 0;

    /**
     * wait conn scale factor
     * @var float
     */
    public $scaleFactor = 0.0;

    /**
     * Options constructor.
     * @param int $initial
     * @param int $overall
     * @param int $maxIdle
     * @param int $minIdle
     * @param int $idleTimeout
     * @param int $recyclingInv
     * @param int $heartbeatInv
     * @param int $getWaitQMax
     * @param int $getWaitTimeout
     * @param float $scaleFactor
     */
    public function __construct(
        int $initial = 1,
        int $overall = 32,
        int $maxIdle = 4,
        int $minIdle = 2,
        int $idleTimeout = 65,
        int $recyclingInv = 15,
        int $heartbeatInv = 0,
        int $getWaitQMax = 15000,
        int $getWaitTimeout = 2000,
        float $scaleFactor = 0.008
    ) {
        $this->initial = $initial;
        $this->overall = $overall;
        $this->maxIdle = $maxIdle;
        $this->minIdle = $minIdle;
        $this->idleTimeout = $idleTimeout;
        $this->icInterval = $recyclingInv;
        $this->hbInterval = $heartbeatInv;
        $this->getWaitQMax = $getWaitQMax;
        $this->getWaitTimeout = $getWaitTimeout;
        $this->scaleFactor = $scaleFactor;
    }
}
