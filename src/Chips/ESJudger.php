<?php
/**
 * Expand/Shrink judger
 * User: moyo
 * Date: 2018/6/11
 * Time: 4:08 PM
 */

namespace Carno\Pool\Chips;

use Carno\Pool\Options;

trait ESJudger
{
    /**
     * @param Options $options
     * @param int $idle
     * @param int $busy
     * @return int
     */
    protected function verdict(Options $options, int $idle, int $busy) : int
    {
        if ($options->maxIdle < $options->minIdle) {
            $options->maxIdle = $options->minIdle;
        }

        if ($idle === $options->minIdle || $idle === $options->maxIdle) {
            goto KEEPS;
        }

        if ($idle < $options->minIdle) {
            return min($busy + $options->minIdle, $options->overall);
        }

        if ($idle > $options->maxIdle) {
            return min($busy + $options->maxIdle, $options->overall);
        }

        KEEPS:

        if ($options->minIdle === 0) {
            return $busy ? $busy + $options->maxIdle : ($idle ? 0 : $options->initial);
        }

        return min($idle + $busy, $options->overall);
    }
}
