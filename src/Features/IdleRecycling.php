<?php
/**
 * Idle conn recycling
 * User: moyo
 * Date: 09/08/2017
 * Time: 5:54 PM
 */

namespace Carno\Pool\Features;

use Carno\Pool\Chips\CWorker;
use Carno\Pool\Chips\ESJudger;
use Carno\Pool\Connections;
use Carno\Pool\Options;

class IdleRecycling
{
    use CWorker, ESJudger;

    /**
     * @var Options
     */
    private $options = null;

    /**
     * @var array
     */
    private $idles = [];

    /**
     * IdleRecycling constructor.
     * @param Options $options
     * @param Connections $connections
     */
    public function __construct(Options $options, Connections $connections)
    {
        $this->options = $options;

        $this->track($connections);
        $this->ticker($options->icInterval, [$this, 'checking']);
    }

    /**
     * @param string $cid
     */
    public function busying(string $cid) : void
    {
        isset($this->idles[$cid]) && $this->idles[$cid] = 0;
    }

    /**
     * @param string $cid
     * @return bool
     */
    public function idling(string $cid) : bool
    {
        return ($last = $this->idles[$cid] ?? PHP_INT_MAX) ? time() - $last >= $this->options->idleTimeout : false;
    }

    /**
     */
    public function checking() : void
    {
        $this->marking();

        ($targetC = $this->verdict(
            $this->options,
            $this->conn()->cIdleCount(),
            $this->conn()->cBusyCount()
        )) ? $this->conn()->resizing($targetC, 'ir-checking') : $this->conn()->exit(false, 'recycling');
    }

    /**
     */
    private function marking() : void
    {
        foreach ($this->conn()->getIdles() as $ic) {
            ($this->idles[$ic->cid()] ?? 0) === 0 && $this->idles[$ic->cid()] = time();
        }
    }
}
