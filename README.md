__EosTool__的目的是消除使用PHP开发EOS区块链应用的痛苦，例如：

- 通过Nodeos和Keosd的RPC接口调用其功能
- 离线生成EOS格式的私钥和公钥
- 使用本地私钥生成符合EOS要求的交易签名
- 将交易对象序列化为Nodeos要求的packed_trx格式

<!--more-->

可以认为EosTool是PHP版本的__eosjs__，利用它可以完整地实现EOS官方客户端Cleos的功能，
也可以很方便地在PHP应用中增加对EOS区块链的支持能力，极大地提高开发效率。

> 原文链接：[http://sc.hubwiz.com/codebag/eos-php-sdk/](http://sc.hubwiz.com/codebag/eos-php-sdk/?affid=github7878)

EosTool运行在__Php 7.1+__环境下，当前版本1.0.0，主要代码文件清单如下：

<table class="table table-striped">
  <thead>
    <tr>
      <th>代码文件</th><th>说明</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>eostool/src/client/NodeClient.php</td><td>节点软件nodeos的rpc接口封装类</td></tr>
    <tr><td>eostool/src/client/WalletClient.php</td><td>钱包软件keosd的rpc接口封装类</td></tr>
    <tr><td>eostool/src/client/RpcOutput.php</td><td>RPC返回结果封装类</td></tr>
    <tr><td>eostool/src/Crypto/PrivateKey.php</td><td>EOS私钥类</td></tr>
    <tr><td>eostool/src/Crypto/PublicKey.php</td><td>EOS公钥类</td></tr>
    <tr><td>eostool/src/Crypto/Signature.php</td><td>EOS签名类</td></tr>
    <tr><td>eostool/src/Serializer/AbiType.php</td><td>EOS的ABI类型封装类</td></tr>
    <tr><td>eostool/src/Serializer/AbiTypeFactory.php</td><td>ABI类型工厂类</td></tr>
    <tr><td>eostool/src/Serializer/SerialBuffer.php</td><td>序列化缓冲区实现类</td></tr>
    <tr><td>eostool/src/Serializer/Serializer.php</td><td>序列化器实现类</td></tr>
    <tr><td>eostool/src/Signer/Signer.php</td><td>签名器接口</td></tr>
    <tr><td>eostool/src/Signer/KeosdSigner.php</td><td>Keosd签名器实现类</td></tr>
    <tr><td>eostool/src/Signer/LocalSigner.php</td><td>本地离线签名器实现接口</td></tr>
    <tr><td>eostool/src/Contract.php</td><td>合约类</td></tr>
    <tr><td>eostool/src/EosTool.php</td><td>开发包入口类</td></tr>
    <tr><td>eostool/tests</td><td>单元测试用例目录</td></tr>
    <tr><td>eostool/phpunit.xml</td><td>单元测试配置文件</td></tr>
    <tr><td>eostool/vendor</td><td>第三方依赖包</td></tr>
    <tr><td>eostool/composer.json</td><td>composer配置文件</td></tr>
  </tbody>
</table>



### 2. 访问节点服务器

使用__NodeClient__类访问nodeos的rpc接口。例如，下面的代码访问本机运行的
Nodeos节点的`chain`插件的`get_info`接口：

```
use EosTool\Client\NodeClient;

$nc = new NodeClient();
$ret = $nc->chain->getInfo();
if($ret->hasError()) throw new Exception($ret->getError());
$info = $ret->getResult();
```

__2.1 RPC调用分组__

Nodeos采用了插件化架构，不同的插件的API也归入不同的分组，EosTool采用了保持一致的
命名方法，根据api即可推断出NodeClient的调用方法：API分组对应于NodeClient的一个同名
属性，API则对应与No的Client的分组同名属性下的一个经过__camelCase__转化的方法。例如：

<table class="table table-striped">
  <thead>
    <tr><th>插件</th><th>API分组</th><th>RPC API</th><th>NodeClient方法</th></tr>
  </thead>
  <tbody>
    <tr><td>chain_api_plugin</td><td>chain</td><td>get_info</td><td>$nc->chain->getInfo()</td></tr>
    <tr><td>history_api_plugin</td><td>history</td><td>get_transaction</td><td>$nc->history->getTransaction()</td></tr>
    <tr><td>net_api_plugin</td><td>net</td><td>status</td><td>$nc->net->status()</td></tr>
    <tr><td>producer_api_plugin</td><td>producer</td><td>get_runtime_options</td><td>$nc->producer->getRunTimeOptions()</td></tr>
    <tr><td>dbsize_api_plugin</td><td>dbsize</td><td>get</td><td>$nc->dbsize->get()</td></tr>
  </tbody>
</table>

RPC API的官方文档：https://developers.eos.io/eosio-nodeos/reference

__2.2 RPC调用参数__

对于Nodeos而言，有些调用需要传入额外的参数，例如chain插件的get_block接口，
使用EosTool进行调用时，将参数组织为一个关联数组即可，示例代码如下：

```
$payload = [  'block_num_or_id' => 1 ];
$ret = $nc->chain->getBlock($payload);
```



__2.3 RPC调用返回值__

所有RPC调用的返回结果都是一个__RpcOutput__实例，调用其`hasError()`方法可以
判断是否调用出错，进一步可以利用`getError()`方法获取错误信息。

RPC调用的响应则可以通过`getResult()`方法获取，它是一个由原始的JSON结果
转化出的StdClass对象，因此可以方便的提取属性信息，例如：

```
echo 'chain id' . $info->chain_id . PHP_EOL;
```

__2.4 访问主网/测试网节点__

在创建NodeClient实例时，可以传入额外的参数执行来制定要访问的EOS主网或测试网节点。
例如，使用下面的代码访问某个主网节点：

```
$nc = new NodeClient(['base_uri' => 'https://api.eosnewyork.io:443/v1/']);
```

或者访问jungle测试网的某个节点：

```
$nc = new NodeClient(['base_uri' => 'https://jungle.eosio.cr:443/v1/']);
```


### 3、访问钱包服务器

新版的Keosd已经不提供RPC API文档，这可能意味着它在EOS软件栈中已经开始滑向边缘地位。
不过可以在这个地址访问老版的文档：https://developers.eos.io/eosio-nodeos/v1.1.0/reference

使用__WalletClient__类访问Keosd的rpc接口。例如，下面的代码访问本机运行的
Keosd的`list_wallets`接口：

```
use EosTool\Client\WalletClient;

$wc = new WalletClient();
$ret = $wc->listWallets();
if($ret->hasError()) throw new Exception($ret->getError());
$wallets = $ret->getResult();
```

由于Keosd的API不再分组，因此RPC对应的方法直接挂在WalletClient对象上，这是一个不同之处。
与NodeClient一样的是，WalletClient的调用返回结果也是一个RpcOutput对象。

1.4版的Keosd默认使用UNIX套接字而不是HTTP提供RPC接口，这可能是考虑到绝大多数情况下
Keosd都运行在本机，使用IPC会更安全一些。因此这也是WalletClient的默认实例化选项，
在绝大多数情况下，不需要传入额外的参数来实例化WalletClient。

### 4. 私钥与公钥

EOS的密钥算法类似于比特币，但做了一些调整，定义了自己的格式。

使用__PrivateKey__类的静态方法`new()`生成随机私钥。例如：

```
use EosTool\Crypto\PrivateKey;

$prv = PrivateKey::new();
echo $prv->toEos() . PHP_EOL; //类似：5Hu6nxM6s6UQ3nYkr1s1GKA17zPqpceUuWxH3JBwK8ZorMSRqGi
```

`toEos()`方法用来将私钥对象转换为EOS的自定义格式。

__4.1 公钥推导__

从私钥可以推导出公钥，例如：

```
$pub = $prv->getPublicKey();
echo $pub->toEos() . PHP_EOL; //类似：EOS6wQ6t3n148GfzLzgxq7cC8ARDKxeaB3hQXdXn7oZYdwEyAXiSv
```

同样，使用`toEos()`方法将公钥转换为EOS的自定义格式。

__4.2 导入EOS私钥__

可以将一个EOS格式的私钥转化为EosTool的PrivateKey对象，例如，下面的
代码将指定的EOS私钥导入，并显示其对应的EOS公钥：

```
$prv = PrivateKey::fromEos('5Hu6nxM6s6UQ3nYkr1s1GKA17zPqpceUuWxH3JBwK8ZorMSRqGi');
echo $prv->getPublicKey()->toEos() . PHP_EOL;
```

__4.3 权威签名__

PrivateKey的`sign()`方法支持普通签名和EOS节点要求的权威签名。例如下面的代码返回一个
普通签名：

```
$hex = '1234567890abcdef...';
$signature = $prv->sign($hex);
```

传入额外的参数来获得指定数据的权威签名：

```
$hex = '1234567890abcdef...';
$signature = $prv->sign($hex,true);
```

### 5. 序列化

EOS要求交易在提交节点`push_transaction`之前先进行序列化，这也是在PHP中操作EOS交易
绕不过去的一个环节。

在EosTool中，使用__Serializer__类进行序列化操作。例如，下面的代码将一个EOS转账交易
序列化为可以提交给EOS节点旳16进制码流格式：

```
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
```

Serializer的静态方法`fromAbi()`用来根据一个指定的abi构造序列化器实例，然后
利用实例的`serialize()`方法对指定类型的数据进行序列化操作，得到16进制码流。

### 6. 签名

EosTool提供了两种进行交易签名的方法：利用Keosd进行签名，或者使用本地私钥进行签名。

使用__KeosdSigner__类来利用钱包服务器完成签名。例如：

```
use EosTool\Signer\KeosdSigner;

$signer = new KeosdSigner();
$signatures = $signer->sign($tx,$pubKeys,$chainId);
```

利用__LocalSigner__类，则可以避免使用keosd，直接利用离线私钥签名。例如：

```
use EosTool\Signer\LocalSigner;

$prvKeys = ['5KQwrPbwdL6PhXujxW37FSSQZ1JiwsST4cqQzDeyXtP79zkvFD3'];
$signer = new LocalSigner($prvKeys);
$signatures = $signer->sign($tx,$pubKeys,$chainId);
```

### 7. 交易提交

一个交易数据，需要经过规范化、序列化、签名、打包一系列操作，才可以提交给
Nodeos节点广播出去。__EosTool__类提供了`transact()`方法来隔离这些繁琐的操作。

例如，下面的代码使用NodeClient和LocalSigner创建一个EosTool实例，然后提交
一个交易：

```
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
```

可以很方便地将签名器改为KeosdSigner，例如：

```
$nc = new NodeClient();
$signer = new KeosdSigner();
$tool = new EosTool($nc,$signer);
```

### 8. 调用单个合约动作

使用EosTool的`pushAction()`方法调用单个合约动作。例如，下面的代码调用tommy
账户托管合约的`hi()`方法：

```
$tool = new EosTool(new NodeClient(),new KeosdSigner());
$ret = $tool->pushAction('tommy','hi',['user'=>'tommy']);
```

### 9. 部署合约

使用EosTool的`setContract()`方法部署合约，例如：

```
$tool = new EosTool(new NodeClient(),new KeosdSigner());

$account = 'tommy';
$abi = file_get_contents('hello.abi');
$wasm = file_get_contents('hello.wasm');
$ret = $tool->setContract($account,$abi,$wasm);
```

