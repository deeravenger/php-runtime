<?php
namespace Dm\Runtime;

/**
 * Class Api
 * @package Dm\Runtime
 * @link https://github.com/dmkuznetsov/php-runtime
 * @author Dmitry Kuznetsov <kuznetsov2d@gmail.com>
 * @license The MIT License (MIT)
 */
class Api
{
    /** @var string */
    protected $_sourceCode;
    /** @var string */
    protected $_code;
    /** @var array */
    protected $_overrideFunctions = array();
    /** @var array */
    protected $_disableFunctions = array();

    protected function __construct($sourceCode)
    {
        $this->_sourceCode = strval($sourceCode);
    }

    /**
     * @param $filePath
     * @return self
     */
    public static function file($filePath)
    {
        $sourceCode = file_get_contents($filePath);
        return new self($sourceCode);
    }

    /**
     * @param $sourceCode
     * @return self
     */
    public static function code($sourceCode)
    {
        return new self($sourceCode);
    }

    /**
     * Execute prepared code
     * @return mixed
     */
    public function execute()
    {
        $this->_parse();
        eval($this->_code);
    }

    /**
     * Override standard function
     * @param string $name
     * @param callable|\Closure $function
     * @return self
     */
    public function overrideFunction($name, \Closure $function)
    {
        $name = strtolower(trim(strval($name)));
        if (\Dm\Runtime\OverrideFunction::register($name, $function)) {
            $this->_overrideFunctions[] = $name;
        }
        return $this;
    }

    /**
     * Allow all, except next
     * @return self
     */
    public function disableFunction()
    {
        $list = array();
        foreach (func_get_args() as $functionName) {
            if (!is_array($functionName)) {
                $list[] = strval($functionName);
            } else {
                array_map('strval', $functionName);
                $list = array_merge($list, $functionName);
            }
        }
        $this->_disableFunctions = array_merge($this->_disableFunctions, $list);
        return $this;
    }

    protected function _parse()
    {
        $parser = new \Dm\Runtime\Parser($this->_sourceCode, $this->_disableFunctions, $this->_overrideFunctions);
        $this->_code = $parser->parse();
    }
}