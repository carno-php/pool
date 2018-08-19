<?php
/**
 * Conn mock
 * User: moyo
 * Date: 18/08/2017
 * Time: 6:00 PM
 */

namespace Carno\Pool\Tests\Kit;

use function Carno\Coroutine\await;
use Carno\Pool\Exception\SelectWaitTimeoutException;
use Carno\Pool\Managed;
use Carno\Pool\Poolable;
use Carno\Promise\Promise;
use Carno\Promise\Promised;

class MockedConn implements Poolable
{
    use Managed;

    /**
     * @return Promised
     */
    public function connect() : Promised
    {
        return Promise::resolved();
    }

    /**
     * @return Promised
     */
    public function heartbeat() : Promised
    {
        return Promise::resolved();
    }

    /**
     * @return Promised
     */
    public function close() : Promised
    {
        $this->closed()->resolve();
        return $this->closed();
    }

    /**
     * @return Promised
     */
    public function ok() : Promised
    {
        $executor = static function ($fn) {
            $fn();
        };

        $receiver = static function () {
            return 'ok';
        };

        return await($executor, $receiver, 0);
    }

    /**
     * @param Promised $w
     * @return Promised
     */
    public function wait(Promised $w) : Promised
    {
        $executor = static function ($fn) use ($w) {
            $w->then(static function () use ($fn) {
                $fn();
            });
        };

        $receiver = static function () {
            return 'ok';
        };

        return await($executor, $receiver, 0);
    }

    /**
     * @return Promised
     */
    public function timeout() : Promised
    {
        return new Promise(static function (Promised $promised) {
            $promised->throw(new SelectWaitTimeoutException);
        });
    }
}
