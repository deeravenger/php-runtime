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

class OverrideFunction
{
    /** @var array */
    protected static $_overrideFunctions = array();

    /**
     * Register override function
     * @param $name
     * @param callable $function
     * @return bool
     */
    public static function register($name, \Closure $function)
    {
        self::$_overrideFunctions[$name] = $function;
        return true;
    }

    public static function __callStatic($name, $arguments)
    {
        if (substr($name, 0, 5) == 'func_') {
            $name = substr($name, 5);
        }
        if (!isset(self::$_overrideFunctions[$name])) {
            throw new Exception(sprintf("Function %s() not exists", $name));
        } else {
            call_user_func_array(self::$_overrideFunctions[$name], $arguments);
        }
    }
}