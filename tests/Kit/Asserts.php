<?php
/**
 * Asserts kit
 * User: moyo
 * Date: 2018/4/13
 * Time: 3:15 PM
 */

namespace Carno\Pool\Tests\Kit;

use Carno\Pool\Tests\Pool\ExposedPool;
use PHPUnit\Framework\TestCase;

trait Asserts
{
    protected function assertConn(ExposedPool $pool, int $idle, int $busy, int $wait = null) : void
    {
        TestCase::assertEquals($idle, $pool->getConnections()->cIdleCount());
        TestCase::assertEquals($busy, $pool->getConnections()->cBusyCount());
        is_null($wait) || TestCase::assertEquals($wait, $pool->getConnections()->cWaitCount());
    }
}
