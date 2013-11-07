<?php
namespace Dm\Runtime;

/**
 * Class DisableFunction
 * @package Dm\Runtime
 * @link https://github.com/dmkuznetsov/php-runtime
 * @author Dmitry Kuznetsov <kuznetsov2d@gmail.com>
 * @license The MIT License (MIT)
 */
class DisableFunction
{
    public static function __callStatic($name, $arguments)
    {
        $msg = sprintf("%s(%s) has been disabled for security reasons", $name, implode(', ', $arguments));
        throw new \Dm\Runtime\Exception($msg);
    }
}