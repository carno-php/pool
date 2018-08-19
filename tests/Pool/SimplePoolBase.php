<?php
/**
 * Pure pool test
 * User: moyo
 * Date: 23/08/2017
 * Time: 12:07 PM
 */

namespace Carno\Pool\Tests\Pool;

use Carno\Pool\Options;
use Carno\Pool\Pool;
use Carno\Pool\Tests\Kit\MockedConn;
use PHPUnit\Framework\TestCase;

abstract class SimplePoolBase extends TestCase
{
    private $pool = null;

    protected function pool()
    {
        if (is_null($this->pool)) {
            $this->pool = new Pool(new Options(1, 2, 1, 1, 60, 0), static function () {
                return new MockedConn;
            });
        }
        return $this->pool;
    }
}
