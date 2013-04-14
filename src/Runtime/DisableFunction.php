<?php
/**
 * PHP Runtime
 *
 * @link      http://github.com/dmkuznetsov/php-runtime
 * @copyright Copyright (c) 2013 Dmitry Kuznetsov <kuznetsov2d@gmail.com>
 * @license   http://raw.github.com/dmkuznetsov/php-runtime/master/LICENSE.txt New BSD License
 */
namespace Dm\Runtime;
use Dm\Runtime\Exception as Exception;

class DisableFunction
{
    public static function __callStatic($name, $arguments)
    {
        $msg = sprintf("%s(%s) has been disabled for security reasons", $name, implode(', ', $arguments));
        throw new Exception($msg);
//        trigger_error($msg, E_USER_WARNING);
    }
}