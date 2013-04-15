PHP Runtime
===========
Now, you can disable and override standard functions in real-time.

```php
<?php
$code = <<<CODE
<?php
echo str_replace( 0, 1, 100 );
?>
CODE;

// thrown exception, becouse str_replace disabled
Dm\Runtime::code($code)
    ->disableFunction('str_replace')
    ->execute();
```

```php
<?php
$code = <<<CODE
<?php
echo str_replace( 0, 1, 100 );
?>
CODE;

// output 111
echo str_replace( 0, 1, 100 );

// output 000
Dm\Runtime::code($code)
    ->overrideFunction('str_replace', function ($search, $replace, $subject) {
        echo str_replace($replace, $search, $subject);
    })
    ->execute();
```
