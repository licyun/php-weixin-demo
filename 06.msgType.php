<?php

$wechatObj = new wechat();
$wechatObj -> responseMsg();


class wechat {
    
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
            case "image":
                $result = $this->receiveImage($postObj);
                break;
            case "location":
                $result = $this->receiveLocation($postObj);
                break;
            case "voice":
                $result = $this->receiveVoice($postObj);
                break;
            case "video":
                $result = $this->receiveVideo($postObj);
                break;
            case "link":
                $result = $this->receiveLink($postObj);
                break;
            default:
                $result = "unknown msg type: ".$RX_TYPE;
                break;
        }
        
        echo $result;
        
    }
    
   //接收事件消息
    public function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎关注李呈云的个人测试服务号 ";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "CLICK":
                switch ($object->EventKey)
                {
                    //和按钮中的key相对应
                    case "github":
                        $content = array();
                        $content[] = array("Title"=>"李呈云的github", 
                            "Description"=>"返回一个图文连接消息", 
                            "PicUrl"=>"https://raw.githubusercontent.com/mzkwy/php-weixin-demo/master/weixin_droptable.jpg", 
                            "Url" =>"https://github.com/mzkwy/");
                        break;
                    default:
                        $content = "点击菜单：".$object->EventKey;
                        break;
                }
                break;
            case "VIEW":
                $content = "跳转链接 ".$object->EventKey;
                break;
            case "SCAN":
                $content = "扫描场景 ".$object->EventKey;
                break;
            case "LOCATION":
                $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
                break;
            default:
                $content = "receive a new event: ".$object->Event;
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
    
    //接收文本消息
    public function receiveText($object)
    {
        $keyword = trim($object->Content);
        $result = $this->transmitText($object, $keyword);
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
    
}
?>
