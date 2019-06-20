## 系统要求
 + swoole2+ 推荐swoole4.3+
## 使用方法：
 目前只集成了钉钉消息发送，其他自行扩展
   
 curl方式：
  ~~~
  $demo = [
      'class' => 'DingMsg',
      'method' => 'run',
      'type' => 'SN', //SN:异步，SW:同步
      'param' => [
          'access_token' => 'token', //钉钉机器人token
          'message' => '123',
          'atMobiles'=>['18262213157']
      ]
  ];
  
  $curl = curl_init();
  //设置抓取的url
  curl_setopt($curl, CURLOPT_URL, 'http://127.0.0.1:9601/'); //实际服务地址
  //设置头文件的信息作为数据流输出
  curl_setopt($curl, CURLOPT_HEADER, 1);
  //设置获取的信息以文件流的形式返回，而不是直接输出。
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  //设置post方式提交
  curl_setopt($curl, CURLOPT_POST, 1);
  //设置post数据
  curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($demo));
  //执行命令
  $data = curl_exec($curl);
  //关闭URL请求
  curl_close($curl);
  //显示获得的数据
  print_r($data);
  
  ~~~
