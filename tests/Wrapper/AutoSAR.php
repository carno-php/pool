<?php
/**
 * AutoSAR
 * User: moyo
 * Date: 20/01/2018
 * Time: 2:16 PM
 */

namespace Carno\Pool\Tests\Wrapper;

use Carno\Pool\Pool;
use Carno\Pool\Wrapper\SAR;

class AutoSAR
{
    use SAR;

    private $pool = null;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function ok()
    {
        return $this->sarRun($this->pool, 'ok', []);
    }

    public function timeout()
    {
        return $this->sarRun($this->pool, 'timeout', []);
    }
}
