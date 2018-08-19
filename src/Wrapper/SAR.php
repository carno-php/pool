<?php
/**
 * (S)elect (A)nd (R)elease
 * User: moyo
 * Date: 02/11/2017
 * Time: 6:10 PM
 */

namespace Carno\Pool\Wrapper;

use function Carno\Coroutine\defer;
use Carno\Pool\Contracts\Broken;
use Carno\Pool\Pool;
use Carno\Pool\Poolable;
use Carno\Promise\Promised;

trait SAR
{
    /**
     * @param Pool $pool
     * @param string $method
     * @param array $arguments
     * @param Promised $interrupter
     * @return mixed
     */
    protected function sarRun(Pool $pool, string $method, array $arguments, Promised $interrupter = null)
    {
        /**
         * @var Poolable $conn
         */
        $conn = null;

        // MUST use defer because it can exec whatever job be FIN or KILL
        // otherwise conn will never be released
        yield defer(function ($stage) use (&$conn) {
            if ($conn instanceof Poolable) {
                $stage instanceof Broken ? $conn->destroy() : $conn->release();
            } elseif ($stage instanceof Poolable) {
                $stage->release();
            }
        });

        // pick out
        $conn = yield $pool->select();

        // check interrupter
        if ($interrupter) {
            $interrupter->then(function () use ($conn) {
                return $conn;
            });
        }

        // execute
        return yield $conn->$method(...$arguments);
    }
}
