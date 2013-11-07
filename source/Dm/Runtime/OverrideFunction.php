<?php
namespace Dm\Runtime;

/**
 * Class OverrideFunction
 * @package Dm\Runtime
 * @link https://github.com/dmkuznetsov/php-runtime
 * @author Dmitry Kuznetsov <kuznetsov2d@gmail.com>
 * @license The MIT License (MIT)
 */
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
            throw new \Dm\Runtime\Exception(sprintf("Function %s() not exists", $name));
        } else {
            call_user_func_array(self::$_overrideFunctions[$name], $arguments);
        }
    }
}