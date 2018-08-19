<?php
/**
 * ESJudger tests
 * User: moyo
 * Date: 2018/7/27
 * Time: 2:46 PM
 */

namespace Carno\Pool\Tests\Chips;

use Carno\Pool\Chips\ESJudger;
use Carno\Pool\Options;
use PHPUnit\Framework\TestCase;

class ESJudgerTest extends TestCase
{
    use ESJudger;

    public function testVerdict()
    {
        // idles=2 -> idle.min=2
        $this->asserting(32, 4, 2, 0, 0, 2);
        // idles=8 -> idles.max=4
        $this->asserting(32, 4, 2, 8, 0, 4);
        // idles=2 -> idles.min=2
        $this->asserting(32, 4, 2, 2, 0, 2);
        // idles=4 -> idles.max=4
        $this->asserting(32, 4, 2, 4, 0, 4);

        // idles=0,busy=4 -> busy+idles.min=6
        $this->asserting(32, 4, 2, 0, 4, 6);
        // idles=1,busy=3 -> busy+idles.min=5
        $this->asserting(32, 4, 2, 1, 3, 5);

        // idles=0,busy=128 -> conn.overall=32
        $this->asserting(32, 4, 2, 0, 128, 32);
        // idles=128,busy=128 -> conn.overall=32
        $this->asserting(32, 4, 2, 128, 128, 32);
        // idles=2,busy=128 -> conn.overall=32
        $this->asserting(32, 4, 2, 2, 128, 32);
        // idles=4,busy=128 -> conn.overall=32
        $this->asserting(32, 4, 2, 4, 128, 32);

        // idles=2,busy=2 -> busy+idles.min=5
        $this->asserting(32, 1, 3, 2, 2, 5);
        // idles=4,busy=2 -> busy+idles.max(~idles.min)=5
        $this->asserting(32, 1, 3, 4, 2, 5);

        // -> conn.initial=1
        $this->asserting(32, 2, 0, 0, 0, 1);
        // -> idles.max=2
        $this->asserting(32, 2, 0, 3, 0, 2);
        // -> idles.min=0
        $this->asserting(32, 2, 0, 2, 0, 0);
        // -> idles.min=0
        $this->asserting(32, 2, 0, 1, 0, 0);
        // -> busy+idles.max=3
        $this->asserting(32, 2, 0, 0, 1, 3);
    }

    /**
     * @param int $overall
     * @param int $maxIdle
     * @param int $minIdle
     * @param int $connIdle
     * @param int $connBusy
     * @param int $expect
     */
    private function asserting(int $overall, int $maxIdle, int $minIdle, int $connIdle, int $connBusy, int $expect)
    {
        $this->assertEquals(
            $expect,
            $this->verdict(new Options(1, $overall, $maxIdle, $minIdle), $connIdle, $connBusy)
        );
    }
}
