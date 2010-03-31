Looah: Sandboxed Lua for PHP 
<http://github.com/sk89q/looah>
 
Copyright (c) 2008 Fran Rogers
Copyright (c) 2010 sk89q <http://www.sk89q.com>
 
Introduction
------------

Looah allows for execution of Lua code in a sandbox. It is largely based
off of code by Fran Rogers for a MediaWiki extension, but it has been
reformatted to fit your televi--an easy to use library. Looah can use
either an external Lua interpreter binary or use the Lua extension for PHP.

Only *nix systems are supported if using the external Lua binary. Using the
Lua extension means that a max execution time will not be enforced.

Using Looah is as simple as:

    $lua = new Looah();
    $result = $lua->execute("print(1234 + 5)");

The max. lines and calls constraints are enforced using Lua code, but the
maximum execution time is enforced using PHP. The process will be killed
if the execution time limit is exceeded. Sandboxing is done using Lua code
written by Fran Rogers. For more information about Lua sandboxes, please see
http://lua-users.org/wiki/SandBoxes

Looah is written for PHP 5.3. A few changed lines will fix that for
previous PHP versions, however.