<?php
/**
 * PHP Runtime
 *
 * @link      http://github.com/dmkuznetsov/php-runtime
 * @copyright Copyright (c) 2013 Dmitry Kuznetsov <kuznetsov2d@gmail.com>
 * @license   http://raw.github.com/dmkuznetsov/php-runtime/master/LICENSE.txt New BSD License
 */
namespace Dm\Runtime;

class OverrideFunction
{
    /** @var array */
    protected static $_overrideFunctions = array();

    public static function set($name, \Closure $function)
    {
        $result = false;
        if (function_exists($name)) {
            self::$_overrideFunctions[$name] = $function;
            $result = true;
        }
        return $result;
    }

    public static function __callStatic($name, $arguments)
    {
        if (!isset(self::$_overrideFunctions[$name])) {
            //
        } else {
            call_user_func_array(self::$_overrideFunctions[$name], $arguments);
        }
    }
}