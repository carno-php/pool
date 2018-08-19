<?php
/**
 * Managed conn tests
 * User: moyo
 * Date: 2018/8/13
 * Time: 2:58 PM
 */

namespace Carno\Pool\Tests\Kit;

use Carno\Pool\Managed;
use Carno\Pool\Poolable;
use Carno\Promise\Promise;
use Carno\Promise\Promised;

class ManagedConn implements Poolable
{
    use Managed;

    private $connected = null;
    private $closed = null;

    private $connecting = 0;
    private $closing = 0;

    public function __construct()
    {
        $this->closed()->then(function () {
            $this->closing ++;
        });
    }

    public function connect() : Promised
    {
        $w = $this->connected();
        $w->resolve();
        return $w;
    }

    public function close() : Promised
    {
        $w = $this->closed();
        $w->resolve();
        return $w;
    }

    public function heartbeat() : Promised
    {
        return Promise::resolved();
    }

    public function connecting() : int
    {
        return $this->connecting;
    }

    public function closing() : int
    {
        return $this->closing;
    }

    public function connected() : Promised
    {
        ($nx = Promise::deferred())->then(function () {
            $this->connecting ++;
        });
        return $this->connected ?? $this->connected = Promise::deferred()->sync($nx);
    }
}
