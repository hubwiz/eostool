<?php
require('vendor/autoload.php');

use EosTool\Serializer\Serializer;

$abi = json_decode(file_get_contents('transaction.abi'),true);
$serializer = Serializer::fromAbi($abi);  

$tx = [
  'expiration'=>'2018-12-04T17:00:00',
  'ref_block_num' => 2878,
  'ref_block_prefix' => 29012031,
  'max_net_usage_words' => 0,
  'max_cpu_usage_ms' => 0,
  'delay_sec' => 0,
  'context_free_actions' => [],
  'actions' => [[
    'account' => 'eosio.token',
    'name' => 'transfer',
    'authorization' => [[
      'actor' => 'eosio',
      'permission' => 'active'
    ]],
    'data' => '1122334455667788990011223344556677.....889900'
  ]],
  'transaction_extensions' => []
];
$hex = $serializer->serialize('transaction',$tx);
echo  'serialized tx => ' .  $hex . PHP_EOL;
