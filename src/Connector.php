<?php
/**
 * Managed instance creator
 * User: moyo
 * Date: 09/08/2017
 * Time: 5:43 PM
 */

namespace Carno\Pool;

use function Carno\Coroutine\async;
use Carno\Pool\Exception\InvalidConnectorException;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Closure;
use Generator;

class Connector
{
    /**
     * @var Pool
     */
    private $pool = null;

    /**
     * @var Closure
     */
    private $dialer = null;

    /**
     * @var string
     */
    private $identify = null;

    /**
     * Connector constructor.
     * @param Pool $pool
     * @param Closure $dialer
     * @param string $identify
     */
    public function __construct(Pool $pool, Closure $dialer, string $identify)
    {
        $this->pool = $pool;
        $this->dialer = $dialer;
        $this->identify = $identify;
    }

    /**
     */
    public function cleanup() : void
    {
        $this->pool = null;
        $this->dialer = null;
    }

    /**
     * @return string
     */
    public function identify() : string
    {
        return $this->identify;
    }

    /**
     * @return Promised|Poolable
     */
    public function created() : Promised
    {
        $created = ($this->dialer)();

        if ($created instanceof Generator) {
            $created = async($created);
            goto PROMISED;
        }

        if ($created instanceof Poolable) {
            // currently usable instance
            $created->pool($this->pool);
            return Promise::resolved($created);
        }

        PROMISED:

        if ($created instanceof Promised) {
            // deferred create instance
            $created->then(function (Poolable $created) {
                $created->pool($this->pool);
            });
            return $created;
        }

        throw new InvalidConnectorException($this->identify());
    }
}
