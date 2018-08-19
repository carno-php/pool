<?php
/**
 * Event observer
 * User: moyo
 * Date: 2018/5/20
 * Time: 8:48 PM
 */

namespace Carno\Pool;

use Carno\Channel\Chan;
use Carno\Channel\Channel;
use Carno\Channel\Worker;
use Carno\Pool\Contracts\Event;
use Closure;

class Observer
{
    /**
     * @var Chan
     */
    private static $chan = null;

    /**
     * @param Pool $pool
     */
    public static function created(Pool $pool) : void
    {
        self::$chan && self::$chan->send([Event::CREATED, $pool]);
    }

    /**
     * @param Pool $pool
     */
    public static function closed(Pool $pool) : void
    {
        self::$chan && self::$chan->send([Event::CLOSED, $pool]);
    }

    /**
     * @param Closure $changed
     */
    public static function watch(Closure $changed) : void
    {
        new Worker(self::$chan ?? self::$chan = new Channel, static function (array $recv) use ($changed) {
            $changed(...$recv);
        });
    }
}
