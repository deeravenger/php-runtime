<?php
require_once 'autoload.php';

class RuntimeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param $code
     * @param $expected
     * @dataProvider testGetSimpleCodeProvider
     */
    public function testGetSimpleCode($code, $expected)
    {
        $reflection = new ReflectionClass('Dm\\Runtime');
        $property = $reflection->getProperty('_code');
        $property->setAccessible(true);
        $method = $reflection->getMethod('_parse');
        $method->setAccessible(true);

        $runtime = Dm\Runtime::code($code);
        $method->invoke( $runtime );
        $actual = $property->getValue($runtime);
        $this->assertEquals($expected, $actual);
    }

    public function testGetSimpleCodeProvider()
    {
        $data = array();
        $data[] = array(
            'code' => <<<CODE
<?php
echo 'hello, world';
?>
how are you,<?=ucfirst('tom')?>?
<?php
?>
<?php
?>
<?php
echo 'hello, world 2';
?>
CODE
        , 'expected' => <<<CODE

echo 'hello, world';
echo <<<DM_RUNTIME_PARSER_BLOCK
how are you,
DM_RUNTIME_PARSER_BLOCK;
echo ucfirst('tom');
echo <<<DM_RUNTIME_PARSER_BLOCK
?
DM_RUNTIME_PARSER_BLOCK;
echo 'hello, world 2';

CODE
        );
        $data[] = array(
            'code' => <<<CODE
how are you,<?=ucfirst('tom')?>?
CODE
        , 'expected' => <<<CODE

echo <<<DM_RUNTIME_PARSER_BLOCK
how are you,
DM_RUNTIME_PARSER_BLOCK;
echo ucfirst('tom');
echo <<<DM_RUNTIME_PARSER_BLOCK
?
DM_RUNTIME_PARSER_BLOCK;

CODE
        );
        $data[] = array(
            'code' => <<<CODE
<?php
class Numbers {
}
?>
CODE
        , 'expected' => <<<CODE

class Numbers {
}

CODE
        );

        return $data;
    }

    /**
     * @param $code
     * @param $expected
     * @param $disableFunction
     * @dataProvider testGetClassCodeProvider
     */
    public function testGetClassCode($code, $expected, $disableFunction)
    {
        $reflection = new ReflectionClass('Dm\\Runtime');
        $property = $reflection->getProperty('_code');
        $property->setAccessible(true);
        $method = $reflection->getMethod('_parse');
        $method->setAccessible(true);

        $runtime = Dm\Runtime::code($code);
        $runtime->disableFunction( $disableFunction );
        $method->invoke( $runtime );
        $actual = $property->getValue($runtime);
        $this->assertEquals($expected, $actual);
    }

    public function testGetClassCodeProvider()
    {
        $data = array();
        $data[] = array(
            'code' => <<<'CODE'
<?php
$tmp = str_replace( '_', '-', 'test-test-test' );
class str_replace {
    const STR_REPLACE = 1;
    const str_replace = 1;
}
?>
CODE
        , 'expected' => <<<'CODE'

$tmp = Dm\Runtime\DisableFunction::str_replace( '_', '-', 'test-test-test' );
class str_replace {
    const STR_REPLACE = 1;
    const str_replace = 1;
}

CODE
        , 'disableFunction' => array('str_replace')
        );
        $data[] = array(
            'code' => <<<'CODE'
<?php
class Test {
    const str_replace = 1;
    public function __construct() {
        str_replace( '_', '-', 'test-test-test' );
    }
    public function str_replace() {
    }
}
?>
CODE
        , 'expected' => <<<'CODE'

class Test {
    const str_replace = 1;
    public function __construct() {
        Dm\Runtime\DisableFunction::str_replace( '_', '-', 'test-test-test' );
    }
    public function str_replace() {
    }
}

CODE
        , 'disableFunction' => array('str_replace')
        );
        $data[] = array(
            'code' => <<<'CODE'
<?php
class Test {
    const str_replace = 1;
    public function __construct() {
        str_replace( '_', '-', 'test-test-test' );
    }
    public static function str_replace() {
    }
}
Test::str_replace();
?>
CODE
        , 'expected' => <<<'CODE'

class Test {
    const str_replace = 1;
    public function __construct() {
        Dm\Runtime\DisableFunction::str_replace( '_', '-', 'test-test-test' );
    }
    public static function str_replace() {
    }
}
Test::str_replace();

CODE
        , 'disableFunction' => array('str_replace')
        );
        $data[] = array(
            'code' => <<<'CODE'
<?php
class Test {
    public static function str_replace() {
    }
}
Test  ::  str_replace();
str_replace(0,1,100);
?>
CODE
        , 'expected' => <<<'CODE'

class Test {
    public static function str_replace() {
    }
}
Test  ::  str_replace();
Dm\Runtime\DisableFunction::str_replace(0,1,100);

CODE
        , 'disableFunction' => array('str_replace')
        );

        return $data;
    }
}