Looah: Sandboxed Lua for PHP 
fork with PHP control of the execution environment
<http://github.com/sk89q/looah>
 
Copyright (c) 2008 Fran Rogers
Copyright (c) 2010 sk89q <http://www.sk89q.com>
Copyright (c) 2013 Kyle J. Temkin <ktemkin@binghamton.edu>

Note
------------

This modified version of Looah is provided with a slightly 
modified directory structure, so it can be more easily submoduled.

Introduction
------------

Looah allows for execution of Lua code in a sandbox. It is largely based
off of code by Fran Rogers for a MediaWiki extension, but it has been
reformatted to fit your televi--an easy to use library. Looah can use
either an external Lua interpreter binary or use the Lua extension for PHP.

Only \*nix systems are supported if using the external Lua binary, for now. 
Using the Lua extension means that a max execution time will not be enforced.

Using Looah is as simple as:
```php
    $lua = new Looah();
    $result = $lua->execute("print(1234 + 5)"); // => "1239"
```

This fork allows a PHP array to be easily used as the "environment" for 
evaluated Lua code, like so:
```php
    $lua = new Looah();
    $state = array('a' => 1, 'b' => 2) 
    $result = $lua->execute('c = a + b; print(c);', $state); // =>  "3"
    print_r($state); // => Array ([a] => 1, [b] => 2, [c] => 3)
```
Sandbox-safe built-ins will automatically be added to the environment
when the sandbox is created; and will automatically be removed before the
state is returned.

You can use this functionality to easily pass supported types in and out of
sandboxed Lua programs, maintaing type, as above. You can also use this to
emulate executing multiple piece of code in the same sandbox:
```php
    $lua = new Looah();
    $state = array();
    $lua->execute('function x(y) return y + 3 end', $state);
    $result = $lua->execute('print(x(3))', $state); // => "6"
```

Functions without upvalues are portable, which means:
 * You can pass them from sandbox to sandbox using the $state array, as
   above; and
 * You can serialize states containing these functions (e.g. for storage
   in a database) and use them later.

The max. lines and calls constraints are enforced using Lua code, but the
maximum execution time is enforced using PHP. The process will be killed
if the execution time limit is exceeded. Sandboxing is done using Lua code
written by Fran Rogers. For more information about Lua sandboxes, please see
http://lua-users.org/wiki/SandBoxes

Looah is written for PHP 5.3. A few changed lines will fix that for
previous PHP versions, however.
