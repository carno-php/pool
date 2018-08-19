<?php
/**
 * Connections creating policy
 * User: moyo
 * Date: 2018/6/11
 * Time: 3:21 PM
 */

namespace Carno\Pool\Chips;

use Carno\Pool\Exceptions;
use Carno\Pool\Options;

trait CCPolicy
{
    use ESJudger;

    /**
     * @var int
     */
    private $swTimeouts = 0;

    /**
     * @param Options $options
     * @param string $identify
     * @param int $idle
     * @param int $busy
     * @param int $waits
     */
    protected function ccDecision(Options $options, string $identify, int $idle, int $busy, int $waits) : void
    {
        $current = Exceptions::happened($identify, Exceptions::SW_TIMEOUT);

        if ($current > $this->swTimeouts) {
            $increased = $current - $this->swTimeouts;
            $this->swTimeouts = $current;
        }

        $score = $waits / $options->getWaitQMax;
        $factor = (float) $options->scaleFactor;
        $threshold = $factor / (($increased ?? 0) + 1);

        if ($score < $threshold) {
            return;
        }

        ($target = $this->verdict($options, $idle, $busy)) && $this->resizing($target, 'policy-decision');
    }
}
