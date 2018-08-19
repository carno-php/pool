<?php
/**
 * Exposed pool
 * User: moyo
 * Date: 2018/4/13
 * Time: 3:04 PM
 */

namespace Carno\Pool\Tests\Pool;

use Carno\Pool\Connections;
use Carno\Pool\Features\IdleRecycling;
use Carno\Pool\Pool;

class ExposedPool extends Pool
{
    /**
     * @return IdleRecycling
     */
    public function getIRecycling() : IdleRecycling
    {
        return $this->recycling;
    }

    /**
     * @return Connections
     */
    public function getConnections() : Connections
    {
        return $this->connections;
    }
}
