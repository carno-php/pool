<?php
/**
 * Poolable API
 * User: moyo
 * Date: 09/08/2017
 * Time: 3:08 PM
 */

namespace Carno\Pool;

use Carno\Promise\Promised;

interface Poolable
{
    /**
     * Managed EV keys
     */
    public const RELEASED = 0xE2;

    /**
     * Connect to remote
     * Promise resolve/reject syntax:
     *  success -> resolve [none]
     *  failure -> throw Exception (string message, int code)
     * @return Promised
     */
    public function connect() : Promised;

    /**
     * Trigger heartbeat op
     * @return Promised
     */
    public function heartbeat() : Promised;

    /**
     * Disconnect from remote
     * @return Promised
     */
    public function close() : Promised;

    /**
     * [MANAGED] Indicator that conn is closed
     * @see Managed::closed
     * @return Promised
     */
    public function closed() : Promised;

    /**
     * [MANAGED] Pool conn ID get/set
     * @see Managed::cid
     * @param string $id
     * @return string
     */
    public function cid(string $id = null) : string;

    /**
     * [MANAGED] Pool instance set
     * @see Managed::pool
     * @param Pool $pool
     */
    public function pool(Pool $pool) : void;

    /**
     * [MANAGED] Pool action scheduled
     * @see Managed::schedule
     * @param int $evk
     * @param Promised $future
     */
    public function schedule(int $evk, Promised $future) : void;

    /**
     * [MANAGED] Release from busy stack
     * @see Managed::release
     */
    public function release() : void;

    /**
     * [MANAGED] Remove from pool stack
     * @see Managed::destroy
     */
    public function destroy() : void;
}
