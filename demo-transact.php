<?php
require('vendor/autoload.php');

use EosTool\Client\NodeClient;
use EosTool\Signer\LocalSigner;
use EosTool\EosTool;

$nc = new NodeClient();
$signer = new LocalSigner(['5KQwrPbwdL6PhXujxW37FSSQZ1JiwsST4cqQzDeyXtP79zkvFD3']);
$tool = new EosTool($nc,$signer);

$tx = [
  'actions' => [[
    'account' => 'eosio.token',
    'name' => 'transfer',
    'authorization' => [[
      'actor' => 'eosio',
      'permission' => 'active'
    ]],
    'data' => [
      'from' => 'eosio',
      'to' => 'tommy',
      'quantity' => '200.0000 EOS',
      'memo' => 'take care'
    ]
  ]]
];
$ret = $tool->transact($tx);
echo $ret->getResult()->transaction_id . PHP_EOL;
