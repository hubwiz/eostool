<?php
require('vendor/autoload.php')

use EosTool\Client\NodeClient;

$nc = new NodeClient();
$ret = $nc->chain->getInfo();
if($ret->hasError()) throw new Exception($ret->getError());
echo 'chain id =>' . $ret->getResult()->chain_id . PHP_EOL;
