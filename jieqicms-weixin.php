<?php
header("Content-type: text/html; charset=utf-8"); 
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapiTest();
$wechatObj -> responseMsg();
//$wechatObj->valid();
class wechatCallbackapiTest {
    
    //微信公众平台开发者认证，已经认证过了就不用了
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }
    
    //---------- 接 收 数 据 ---------- //
    public function responseMsg() {
        //获取POST数据
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; 
        //用SimpleXML解析POST过来的XML数据
        $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
        //获取发送方帐号（OpenID）
        $fromUsername = $postObj->FromUserName; 
        //获取接收方账号
        $toUsername = $postObj->ToUserName; 
        //获取当前时间戳
        $time = time(); 
        //获取返回类型
        $type = trim($postObj->MsgType);
        //消息类型分离
        switch ($type)
        {
            case "event":
                $result = $this->receiveEvent($postObj);
                break;
            case "text":
                $result = $this->receiveText($postObj);
                break;
            default:
                $result = $this->transmitText($postObj, "不支持该种类型");
                //$result = $this->receiveText($postObj);
                break;
        }
        echo $result;
    }
    
    
    //接受文本消息
    public function receiveText($object)
    {
        //获取文本消息
        $keyword = trim($object->Content);  
        //测试
        // $word = $_GET["name"];
        // if( !empty($word) ){
        //     $keyword = $word;
        // }
        $name = iconv("UTF-8", "GBK//IGNORE",$keyword);
        //返回类型
        $returnType = 1;
        //图文项目
        $item_str = "";
        $itemTpl = "        
        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>";
        //回复模板
        $result = "";
        $xmlTpl = "
        <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>%s</Articles>
        </xml>";
        //sql语句
        $sql = "SELECT articleid, articlename, intro  FROM `jieqi_article_article` WHERE `articlename` = ?  LIMIT 0 ,5";
        //mysql连接信息
        $servername  = '127.0.0.1';     // mysql服务器主机地址
        $username  = 'root';           // mysql用户名
        $password  = 'password';        // mysql用户名密码
        $dbname  = 'root';           // mysql 数据库名
        // 创建连接
        $conn = new mysqli($servername, $username, $password, $dbname);
        // 检测连接
        if ($conn->connect_error) {
            die("连接失败: " . $conn->connect_error);
        }
        // 设置编码，防止中文乱码
        mysqli_query($conn , "set names gbk");
        //预处理
        $stmt = $conn->prepare($sql);
        if(!$stmt->prepare($sql))
        {
            print "Failed to prepare statement\n";
        }
        //绑定参数
        $stmt->bind_param('s', $name);
        //执行
        $stmt->execute();
        //获取结果
        $stmt->store_result();
        //结果条数
        $sqlnum = $stmt->num_rows;
        $itemCount = 0; 
        //
        $stmt->bind_result($id, $articlename, $intro);
        if ($sqlnum == 0) {
            $returnType = 0;
        }else{
            while ($stmt->fetch()) 
            {
                //添加标题
                $Title = iconv('GB2312', 'UTF-8', $articlename);
                //添加描述
                $Description = iconv('GB2312', 'UTF-8', $intro);
                //添加链接
                $Url = "https://m.jieqicms.com/read/".$id."/";
                //添加图片
                $picId = intval($id/1000);
                $PicUrl ="https://img.jieqicms.com/files/article/image/".$picId."/".$id."/".$id."s.jpg";
                // 添加更多链接                                   
                if ($itemCount == 4) {                               
                     $Title='更多请点击>>';
                     $Url="https://m.jieqicms.com/s.php?s=".$keyword."";          
                }
                $item_str .= sprintf($itemTpl, $Title, $Description, $PicUrl, $Url);
                ++$itemCount; 
            }
            $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $sqlnum, $item_str);
        }
        //关闭处理连接
        $stmt->close();
        //关闭数据库连接
        $conn->close();
        if($keyword == "历史记录")
        {
            $result = $this->transmitText($object, "<a href='https://m.jieqicms.com/history.php'>历史记录</a>");
        }elseif($returnType == 0){
            $result = $this->transmitText($object, "未找到您要的小说");
        }
        return $result;
    }

    //回复文本消息
    public function transmitText($object, $content)
    {
        $xmlTpl = "
        <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
        </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    //回复图文消息
    public function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "        
        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>";
        
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "
        <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>$item_str</Articles>
        </xml>";
        
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }
    
   //接收事件消息
    public function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎关注杰奇CMS公众号,回复小说名称可搜索小说,回复'历史记录'可查看阅读历史";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "VIEW":
                $content = "跳转链接 ".$object->EventKey;
                break;
        }
        //判断调用图文还是消息类型返回
        if(is_array($content)){
            $result = $this->transmitNews($object, $content);
        }else{
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    //核对是不是从腾讯的微信服务器发过来的请求
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}
?>
