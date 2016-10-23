<?php

$wechatObj = new wechat();
$wechatObj -> responseMsg();


class wechat {
    
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
        //定制返回图文消息的数组信息
        $content = array();
        $content[] = array("Title"=>"李呈云的github", "Description"=>"返回一个图文连接消息", "PicUrl"=>"https://raw.githubusercontent.com/mzkwy/php-weixin-demo/master/weixin_droptable.jpg", "Url" =>"https://github.com/mzkwy/");
        $result = $this->transmitNews($postObj, $content);

        echo $result;
        
    }

     //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        //图文消息转换为微信指定格式的xml
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
        //返回类型的xml
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
