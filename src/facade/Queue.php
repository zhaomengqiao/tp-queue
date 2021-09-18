<?php

namespace think\facade;

use think\Facade;

/**
 * Class Queue
 * @package think\facade
 * @mixin \think\Queue
 * @method static void setConnection($connection)
 */
class Queue extends Facade
{
    protected static function getFacadeClass()
    {
        return 'queue';
    }
}
