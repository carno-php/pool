<?php
/**
 * (S)elect (R)an(D)om
 * User: moyo
 * Date: 22/12/2017
 * Time: 4:58 PM
 */

namespace Carno\Pool\Wrapper;

use Carno\Pool\Contracts\Select;
use Carno\Pool\Pool;

trait SRD
{
    /**
     * @deprecated
     * @param Pool $pool
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    protected function rndRun(Pool $pool, string $method, array $arguments)
    {
        return yield $pool->select(Select::RANDOM)->$method(...$arguments);
    }
}
