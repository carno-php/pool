<?php
/**
 * AutoSAR test
 * User: moyo
 * Date: 23/08/2017
 * Time: 12:05 PM
 */

namespace Carno\Pool\Tests\Wrapper;

use function Carno\Coroutine\async;
use Carno\Pool\Exception\SelectWaitTimeoutException;
use Carno\Pool\Tests\Pool\SimplePoolBase;
use Throwable;

class AutoSARTest extends SimplePoolBase
{
    private $sar = null;

    /**
     * @return AutoSAR
     */
    private function sar()
    {
        if (is_null($this->sar)) {
            $this->sar = new AutoSAR($this->pool());
        }
        return $this->sar;
    }

    public function testSARNormal()
    {
        async(function () {
            $this->assertEquals('ok', yield $this->sar()->ok());
            $this->assertEquals(1, $this->pool()->stats()->cIdling());
            $this->assertEquals(0, $this->pool()->stats()->cBusying());
        })->catch(function (Throwable $e) {
            $this->fail('E::'.get_class($e).'::'.$e->getMessage());
        });
    }

    public function testSARTimeout()
    {
        async(function () {
            yield $this->sar()->timeout();
            $this->fail('This branch is non-reachable');
        })->catch(function (Throwable $e) {
            $this->assertEquals(1, $this->pool()->stats()->cIdling());
            $this->assertEquals(0, $this->pool()->stats()->cBusying());
            $this->assertTrue($e instanceof SelectWaitTimeoutException);
        });
    }
}
