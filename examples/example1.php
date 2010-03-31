<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 dirname(__FILE__) . "/../src");

require_once "autoloader.php";

use looah\Looah;

$lua = new Looah();
$lua->setInterpreterPath("/usr/local/bin/lua-5.1");
echo $lua->execute(<<<EOB
function f(a, b)
    return a * 2 + b
end

print(f(3, 10))
EOB
);