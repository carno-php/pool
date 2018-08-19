<?php
/**
 * Pool exceptions counter
 * User: moyo
 * Date: 2018/6/11
 * Time: 3:34 PM
 */

namespace Carno\Pool;

use Carno\Pool\Exception\SelectWaitOverflowException;
use Carno\Pool\Exception\SelectWaitTimeoutException;

class Exceptions
{
    /**
     * SelectWaitTimeout
     */
    public const SW_TIMEOUT = 0xE1;

    /**
     * SelectWaitOverflow
     */
    public const SW_OVERFLOW = 0xE2;

    /**
     * @var array
     */
    private static $happens = [];

    /**
     * @param string $type
     * @param string $identify
     */
    public static function selectWait(string $type, string $identify) : void
    {
        switch ($type) {
            case SelectWaitTimeoutException::class:
                $slot = self::SW_TIMEOUT;
                break;
            case SelectWaitOverflowException::class:
                $slot = self::SW_OVERFLOW;
                break;
        }

        if (isset($slot)) {
            self::$happens[$slot][$identify] = (self::$happens[$slot][$identify] ?? 0) + 1;
        }
    }

    /**
     * @param string $identify
     * @param string $type
     * @return int
     */
    public static function happened(string $identify, string $type) : int
    {
        return self::$happens[$type][$identify] ?? 0;
    }
}
