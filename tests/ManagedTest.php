<?php
/**
 * Managed test
 * User: moyo
 * Date: 2018/8/13
 * Time: 2:32 PM
 */

namespace Carno\Pool\Tests;

use Carno\Pool\Options;
use Carno\Pool\Pool;
use Carno\Pool\Tests\Kit\ManagedConn;
use PHPUnit\Framework\TestCase;

class ManagedTest extends TestCase
{
    /**
     * @var ManagedConn
     */
    private $last = null;

    public function testFlow()
    {
        // start new pool

        $pool = new Pool(new Options(1, 1, 1, 1, 0, 0), function () {
            return $this->last = new ManagedConn;
        });

        // current conn 1

        $c1 = $this->last;

        $this->assertEquals(1, $c1->connecting());
        $this->assertEquals(0, $c1->closing());

        // close case 1 - manual close

        $c1->close();
        $this->assertEquals(1, $c1->connecting());
        $this->assertEquals(1, $c1->closing());

        // current conn 2

        $c2 = $this->last;

        // close case 2 - passive close

        $c2->closed()->resolve();
        $this->assertEquals(1, $c2->connecting());
        $this->assertEquals(1, $c2->closing());

        // final shutdown

        $pool->shutdown();
    }
}
