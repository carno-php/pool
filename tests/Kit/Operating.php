<?php
/**
 * Operating kit
 * User: moyo
 * Date: 2018/4/13
 * Time: 3:25 PM
 */

namespace Carno\Pool\Tests\Kit;

use Carno\Pool\Pool;
use Carno\Pool\Poolable;
use Carno\Promise\Promised;

trait Operating
{
    /**
     * @var Promised[]
     */
    private $blocks = [];

    protected function connSelect(Pool $pool, int $num) : void
    {
        for ($i = 0; $i < $num; $i ++) {
            $this->blocks[] = $pool->select();
        }
    }

    protected function connRecycle(Pool $pool, int $num) : void
    {
        for ($i = 0; $i < $num; $i ++) {
            $conn = array_pop($this->blocks);
            if ($conn instanceof Poolable) {
                $pool->recycle($conn);
            } elseif ($conn instanceof Promised) {
                $conn->then(function (Poolable $ins) use ($pool) {
                    $pool->recycle($ins);
                });
            }
        }
    }
}
