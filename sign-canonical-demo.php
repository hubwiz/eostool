<?php
require('vendor/autoload.php');

use EosTool\Signer\LocalSigner;

$prvKeys = ['5KQwrPbwdL6PhXujxW37FSSQZ1JiwsST4cqQzDeyXtP79zkvFD3'];
$signer = new LocalSigner($prvKeys);
$signatures = $signer->sign($tx,$pubKeys,$chainId);
echo $signatures[0]->toEos() . PHP_EOL;
