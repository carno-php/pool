<?php
/**
 * Idle conn recycling test
 * User: moyo
 * Date: 2018/4/13
 * Time: 2:18 PM
 */

namespace Carno\Pool\Tests\Features;

use Carno\Pool\Features\IdleRecycling;
use Carno\Pool\Options;
use Carno\Pool\Tests\Kit\Asserts;
use Carno\Pool\Tests\Kit\MockedConn;
use Carno\Pool\Tests\Kit\Operating;
use Carno\Pool\Tests\Pool\ExposedPool;
use PHPUnit\Framework\TestCase;

class IdleRecyclingTest extends TestCase
{
    use Asserts, Operating;

    public function testScaling()
    {
        // initial, overall, max-idle, min-idle, idle-timeout, recycle, heartbeat
        $o = new Options(1, 100, 10, 20, 0, 999, 0, 10000, 2000, 1);

        $p = new ExposedPool($o, function () {
            return new MockedConn();
        }, 'ns1');

        ($ir = $p->getIRecycling())->pause();

        $this->assertConn($p, 1, 0);

        $this->connSelect($p, 50);
        $this->assertConn($p, 0, 1, 49);

        $ir->checking();
        $this->assertConn($p, 0, 21, 29);

        $ir->checking();
        $this->assertConn($p, 0, 41, 9);

        $ir->checking();
        $this->assertConn($p, 11, 50, 0);

        $this->connRecycle($p, 50);
        $this->assertConn($p, 61, 0, 0);

        $ir->checking();
        $this->assertConn($p, 20, 0, 0);

        $this->connSelect($p, 1000);
        $this->assertConn($p, 0, 20, 980);

        $ir->checking();
        $this->assertConn($p, 0, 40, 960);

        $o->minIdle = 500;

        $ir->checking();
        $this->assertConn($p, 0, 100, 900);

        $o->overall = 1000;

        $ir->checking();
        $this->assertConn($p, 0, 600, 400);

        $ir->checking();
        $this->assertConn($p, 0, 1000, 0);

        $this->connRecycle($p, 1000);
        $this->assertConn($p, 1000, 0, 0);

        $o->maxIdle = 10;
        $o->minIdle = 10;

        $ir->checking();
        $this->assertConn($p, 10, 0, 0);

        $o->overall = 100;

        $this->idleTesting($p, $o, $ir, 1, 11, 12, 11, 1);
        $this->idleTesting($p, $o, $ir, 2, 14, 13, 14, 2);
        $this->idleTesting($p, $o, $ir, 3, 15, 15, 15, 3);
        $this->idleTesting($p, $o, $ir, 4, 99, 20, 96, 4);

        $o->maxIdle = 20;
        $o->minIdle = 20;

        $ir->checking();
        $this->assertConn($p, 20, 0, 0);

        $o->minIdle = 0;
        $o->maxIdle = 10;

        $ir->checking();
        $this->assertConn($p, 10, 0, 0);

        $closed = false;

        $p->closed()->then(function () use (&$closed) {
            $closed = true;
        });

        $this->assertFalse($closed);

        $ir->checking();
        $this->assertConn($p, 0, 0, 0);

        $this->assertTrue($closed);
    }

    private function idleTesting(
        ExposedPool $pool,
        Options $opt,
        IdleRecycling $ir,
        int $select,
        int $min,
        int $max,
        int $idle,
        int $busy
    ) {
        $this->connSelect($pool, $select);

        $opt->minIdle = $min;
        $opt->maxIdle = $max;

        for ($i = 0; $i < ceil($select / min($min, $max)); $i ++) {
            $ir->checking();
        }

        $this->assertConn($pool, $idle, $busy);

        $this->connRecycle($pool, $select);
    }
}
