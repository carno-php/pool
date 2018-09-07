<?php
/**
 * Pool test
 * User: moyo
 * Date: 18/08/2017
 * Time: 5:52 PM
 */

namespace Carno\Pool\Tests;

use function Carno\Coroutine\async;
use Carno\Pool\Options;
use Carno\Pool\Tests\Kit\MockedConn;
use Carno\Pool\Tests\Pool\ExposedPool;
use Carno\Pool\Tests\Pool\SimplePoolBase;
use Throwable;

class PoolTest extends SimplePoolBase
{
    public function testSelect()
    {
        async(function () {
            /**
             * @var MockedConn $conn
             */
            $conn = yield $this->pool()->select();
            $this->assertTrue($conn instanceof MockedConn);

            $this->assertEquals(0, $this->pool()->stats()->cIdling());
            $this->assertEquals(1, $this->pool()->stats()->cBusying());

            $this->assertEquals('ok', yield $conn->ok());

            $conn->release();

            $this->assertEquals(1, $this->pool()->stats()->cIdling());
            $this->assertEquals(0, $this->pool()->stats()->cBusying());
        })->catch(function (Throwable $e) {
            $this->fail('E::'.get_class($e).'::'.$e->getMessage());
        });
    }

    public function testDestruct()
    {
        $pool = new ExposedPool(new Options(1, 2, 1, 1, 60, 1, 1), static function () {
            return new MockedConn;
        });

        $pool->shutdown();

        $pool = null;

        $this->assertNoGC();
    }

    public function testIdlesConnOps()
    {
        $pool = new ExposedPool(new Options(2, 2, 1, 1, 0, 0, 0), static function () {
            return new MockedConn;
        });

        /**
         * @var MockedConn $conn1
         * @var MockedConn $conn2
         */

        $id1 = ($conn1 = $pool->select())->cid();
        $conn1->release();

        $id2 = ($conn1 = $pool->select())->cid();
        $this->assertEquals($id1, $id2);

        $id3 = ($conn2 = $pool->select())->cid();
        $this->assertNotEquals($id2, $id3);

        $pool->shutdown();
        $pool = null;

        $this->assertNoGC();
    }

    private function assertNoGC()
    {
        if (!(extension_loaded('xdebug') && xdebug_code_coverage_started())) {
            $this->assertEquals(0, gc_collect_cycles());
        }
    }
}
