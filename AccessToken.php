<?php 

    //封装curl获取参数
    function getdata($url){
        // 初始化 
        $curl = curl_init(); 
        // 要访问的网址 
        curl_setopt($curl, CURLOPT_URL, $url); 
        // 不直接输入内容 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        // 降结果保存在$result中 
        $result = curl_exec($curl); 
        // 关闭 
        curl_close($curl); 
        //返回
        return $result;
    }
    
    // //改成自己的APPID
    define('APP_ID', 'wxf95d86030e82a5e1');
    // //改成自己的APPSECRET
    define('APP_SECRET', 'c91875c47f80886a19544f15d0dd6e2b');
    //微信获取token的地址
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".APP_ID."&secret=".APP_SECRET;
    //得到返回的json类型的数据
    $jsondata = getdata($url);
    //解析json数组
    $data = json_decode($jsondata, true);
    //输出token
    echo $data['access_token'];

?>
