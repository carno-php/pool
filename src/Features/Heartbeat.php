<?php
/**
 * Heartbeats manager
 * User: moyo
 * Date: 09/08/2017
 * Time: 5:47 PM
 */

namespace Carno\Pool\Features;

use Carno\Pool\Chips\CWorker;
use Carno\Pool\Connections;
use Carno\Pool\Options;
use Carno\Pool\Poolable;

class Heartbeat
{
    use CWorker;

    /**
     * Heartbeat constructor.
     * @param Options $options
     * @param Connections $connections
     */
    public function __construct(Options $options, Connections $connections)
    {
        $this->track($connections);
        $this->ticker($options->hbInterval, [$this, 'keeping']);
    }

    /**
     */
    public function keeping() : void
    {
        /**
         * @var Poolable $conn
         */
        while (null !== $conn = $this->conn()->getIdled(false, false)) {
            $conn->heartbeat()->then(static function () use ($conn) {
                $conn->release();
            }, static function () use ($conn) {
                $conn->destroy();
            });
        }
    }
}
