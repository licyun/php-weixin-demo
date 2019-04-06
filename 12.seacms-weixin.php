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
                // $result = $this->receiveText($postObj);
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
        // $keyword = $_GET["name"];
        //返回类型
        $returnType = 1;    

        //sql语句
        $sql = "SELECT v_id, v_name, v_pic, v_actor  FROM `sea_data` WHERE `v_name` LIKE CONCAT('%',?,'%')  LIMIT 1";
        //mysql连接信息
        $servername  = 'localhost';     // mysql服务器主机地址
        $username  = 'root';           // mysql用户名
        $password  = 'password';        // mysql用户名密码
        $dbname  = 'root';           // mysql 数据库名
        //结果数组
        $arrdata = array();
        $sqlnum = 1;

            // 创建连接
            $conn = new mysqli($servername, $username, $password, $dbname);
            // 检测连接
            if ($conn->connect_error) {
                die("连接失败: " . $conn->connect_error);
            }
            // 设置编码，防止中文乱码
            mysqli_query($conn , "set names utf8");
            //预处理
            $stmt = $conn->prepare($sql);
            if(!$stmt->prepare($sql))
            {
                print "Failed to prepare statement\n";
            }
            //绑定参数
            $stmt->bind_param('s', $keyword);
            //执行
            $stmt->execute();
            //获取结果
            $stmt->store_result();
            //结果条数
            $sqlnum = $stmt->num_rows;
            $itemCount = 0; 
            //解析参数值
            $stmt->bind_result($id, $name, $pic, $dis);
            if ($sqlnum == 0) {
                $returnType = 0;
            }else{
                while ($stmt->fetch()) 
                {
                    $arrdata = array($id, $name, $pic, $dis);
                }
            }
            //关闭处理连接
            $stmt->close();
            //关闭数据库连接
            $conn->close();
            if($returnType == 0){
                $result = $this->transmitText($object, "未找到您要的小说");
            }
        }
        return $this->compositionTpl($object, $arrdata, $sqlnum);
    }

    //根据数组拼接模板
    public function compositionTpl($object,$arr, $sqlnum){
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
        $baseUrl = "https://wx.seacms.net";
        //添加标题
        $Title = $arr[1];
        //添加描述
        $Description = $arr[3];
        //添加链接
        $Url = $baseUrl."/movie/id".$arr[0].".html";
        //添加图片
        $PicUrl ="https://seacms.com/".$arr[2];
        $item_str .= sprintf($itemTpl, $Title, $Description, $PicUrl, $Url);
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $sqlnum, $item_str);
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
                $content = "欢迎关注微信公众号";
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
