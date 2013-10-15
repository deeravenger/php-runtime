<?php
/**
 * PHP Runtime
 *
 * @link      http://github.com/dmkuznetsov/php-runtime
 * @copyright Copyright (c) 2013 Dmitry Kuznetsov <kuznetsov2d@gmail.com>
 * @license   http://raw.github.com/dmkuznetsov/php-runtime/master/LICENSE.txt New BSD License
 */
namespace Dm\Runtime;

class Parser
{
    /** @var string */
    protected $_sourceCode;
    /** @var array */
    protected $_disableFunctions = array();
    /** @var array */
    protected $_overrideFunctions = array();

    /**
     * @param $sourceCode
     * @param array $disableFunctions
     * @param array $overrideFunctions
     */
    public function __construct($sourceCode, array $disableFunctions, array $overrideFunctions)
    {
        $this->_sourceCode = $sourceCode;
        $this->_disableFunctions = $disableFunctions;
        $this->_overrideFunctions = $overrideFunctions;
    }

    /**
     * Parse and transform your code
     * @return string
     */
    public function parse()
    {
        $blocks = $this->_parseBlocks($this->_sourceCode);
        $blocks = $this->_clearBlocks($blocks);
        $code = $this->_glueBlocks($blocks);
        $code = $this->_parseCode($code);
        return $code;
    }

    /**
     * Group blocks (php, text)
     * @param $sourceCode
     * @return array
     */
    protected function _parseBlocks($sourceCode)
    {
        $tokens = token_get_all(trim($sourceCode));
        $blocks = array();
        $i = 0;
        $next = false;
        $isPhp = false;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $value = $token;
            } else {
                list($id, $value) = $token;
                switch ($id) {
                    case T_OPEN_TAG:
                        $next = true;
                        $isPhp = true;
                        $value = '';
                        break;
                    case T_OPEN_TAG_WITH_ECHO:
                        break;
                    case T_CLOSE_TAG:
                        if ($isPhp) {
                            $next = true;
                            $isPhp = false;
                            $value = '';
                        }
                        break;
                }
            }
            if (!isset($blocks[$i])) {
                $blocks[$i] = array('code' => '', 'is_php' => $isPhp);
            }
            $blocks[$i]['code'] .= $value;
            if ($next) {
                $next = false;
                $blocks[++$i] = array('code' => '', 'is_php' => $isPhp);
            }
            $blocks[$i]['is_php'] = $isPhp;
        }
        return $blocks;
    }

    /**
     * Clear empty blocks
     * @param array $blocks
     * @return array
     */
    protected function _clearBlocks(array $blocks)
    {
        $result = array();
        $isPhp = false;
        $isText = false;
        foreach ($blocks as $block) {
            if (!empty($block['code'])) {
                if ($block['is_php'] && $isPhp) {
                    $result[count($result)]['code'] .= $block['code'];
                } else if (!$block['is_php'] && $isText) {
                    $result[count($result)]['code'] .= $block['code'];
                } else {
                    $result[] = $block;
                }
            }
        }
        return $result;
    }

    /**
     * Glue all blocks
     * @param array $blocks
     * @return string
     */
    protected function _glueBlocks(array $blocks)
    {
        $result = array();
        foreach ($blocks as $block) {
            if (!$block['is_php']) {
                $result[] = $this->_textToCode($block['code']);
            } else {
                $result[] = trim($block['code']);
            }
        }
        return implode("\n", $result);
    }

    /**
     * Convert text block to php block
     * @param $text
     * @return string
     */
    protected function _textToCode($text)
    {
        $tokens = token_get_all(trim($text));
        $result = '';
        $phpBlock = '';
        $isPhp = false;
        $startFromPhp = false;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $value = $token;
            } else {
                list($id, $value) = $token;
                switch ($id) {
                    case T_OPEN_TAG_WITH_ECHO:
                        if (empty($result)) {
                            $startFromPhp = true;
                        }
                        $value = "\nDM_RUNTIME_PARSER_BLOCK;\necho ";
                        $isPhp = true;
                        break;
                    case T_CLOSE_TAG:
                        $isPhp = false;
                        $value = '';
                        break;
                }
            }
            if ($isPhp) {
                $phpBlock .= $value;
                $value = '';
            } else {
                if (!empty($phpBlock)) {
                    $value = rtrim($phpBlock, ';') . ";\necho <<<DM_RUNTIME_PARSER_BLOCK\n";
                    $phpBlock = '';
                }
            }
            $result .= $value;
        }
        if (!$startFromPhp) {
            $result = sprintf("echo <<<DM_RUNTIME_PARSER_BLOCK\n%s", $result);
        } else {
            $result = sprintf("echo %s", $result);
        }
        $result = ltrim($result, "\nDM_RUNTIME_PARSER_BLOCK;\n");
        $result = rtrim($result, "\nDM_RUNTIME_PARSER_BLOCK") . "\nDM_RUNTIME_PARSER_BLOCK;";
        return $result;
    }

    /**
     * Something interesting here
     * @param $sourceCode
     * @return string
     */
    protected function _parseCode($sourceCode)
    {
        $result = '';
        $tokens = token_get_all("<?php\n" . trim($sourceCode) . "\n?>");
        $skip = false;
        $waitingSemicolon = false;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $value = $token;
            } else {
                list($id, $value) = $token;
                switch ($id) {
                    case T_OPEN_TAG:
                    case T_CLOSE_TAG:
                        $value = '';
                        break;
                    case T_NAMESPACE:
                    case T_USE;
                        $waitingSemicolon = true;
                        break;
                    // exit, echo, print - is not a function
                    case T_EXIT:
                    case T_ECHO:
                    case T_PRINT:
                    case T_EVAL:
                    case T_EMPTY:
                    case T_ISSET:
                    case T_UNSET:
                    case T_STRING:
                        $lowerValue = strtolower(trim($value));
                        if (!$skip && !$waitingSemicolon) {
                            if (in_array($lowerValue, $this->_disableFunctions)) {
                                $value = sprintf("Dm\\Runtime\\DisableFunction::func_%s", $lowerValue);
                            } elseif (in_array($lowerValue, $this->_overrideFunctions)) {
                                $value = sprintf("Dm\\Runtime\\OverrideFunction::func_%s", $lowerValue);
                            }
                        }
                        break;
                }
            }
            $result .= $value;

            $lowerValue = strtolower(trim($value));
            if ($waitingSemicolon && $lowerValue == ';') {
                $waitingSemicolon = false;
            }
            if (!empty($lowerValue)) {
                // skip value after
                if (in_array($lowerValue, array('::', '->', 'use', 'namespace', 'function', 'class', 'const'))) {
                    $skip = true;
                } else {
                    $skip = false;
                }
            }
        }
        $result = "\n" . trim($result) . "\n";
        return $result;
    }
}