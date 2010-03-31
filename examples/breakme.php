<?php
set_include_path(get_include_path() . PATH_SEPARATOR .
                 dirname(__FILE__) . "/../src");

require_once "autoloader.php";

use looah\Looah;

$lua = new Looah();
$lua->setInterpreterPath("/usr/local/bin/lua-5.1");
echo $lua->execute(<<<EOB
while true do
    print("wasting cpu cycles")
end
EOB
);