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
        //获取消息内容
        // $keyword = trim($postObj->Content); 
        //获取当前时间戳
        $time = time(); 
        //获取返回类型
        $type = trim($postObj->MsgType);
        //当为event事件时触发
        if($type == "event"){
            $result = $this->receiveEvent($postObj);
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
        //但content为数组时调用方法组装图文消息
        if(is_array($content))
            $result = $this->transmitNews($object, $content);
        return $result;
    }
    
    
     //回复图文消息
    private function transmitNews($object, $newsArray)
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
