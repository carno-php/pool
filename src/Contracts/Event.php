<?php
/**
 * Resource events
 * User: moyo
 * Date: 2018/5/20
 * Time: 8:52 PM
 */

namespace Carno\Pool\Contracts;

interface Event
{
    /**
     * Pool has been created
     */
    public const CREATED = 0xE1;

    /**
     * Pool has been closed
     */
    public const CLOSED = 0xE9;
}
