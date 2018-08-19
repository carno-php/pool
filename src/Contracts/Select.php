<?php
/**
 * Select mode
 * User: moyo
 * Date: 22/12/2017
 * Time: 5:02 PM
 */

namespace Carno\Pool\Contracts;

interface Select
{
    /**
     * Get idled conn
     * MUST in coroutine
     */
    public const IDLING = 0xA0;

    /**
     * Get random conn (ignore state)
     * @deprecated
     */
    public const RANDOM = 0xA5;
}
