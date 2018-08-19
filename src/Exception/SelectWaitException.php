<?php
/**
 * Select wait exception base
 * User: moyo
 * Date: 28/11/2017
 * Time: 5:17 PM
 */

namespace Carno\Pool\Exception;

use Carno\Pool\Exceptions;
use RuntimeException;

abstract class SelectWaitException extends RuntimeException
{
    /**
     * SelectWaitException constructor.
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
        Exceptions::selectWait(static::class, $message);
    }
}
