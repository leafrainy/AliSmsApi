<?php
/*
阿里云国际版账号国际SMS发送类  
@author：leafrainy
@time：2022年06月13日
@notice：参考于阿里云220613之时的官方API demo
@食用方式：

@单一手机号

$config  = array(
    'accessKeyId' => "xxxx", 
    'accessKeySecret' => "xxxxx", 
    'PhoneNumbers' => "区号手机号", 
    'Message' => "哈哈哈"
    );
$go = new Ali4IntSmsApi($config);

$go->send_sms();

*/


class Ali4IntSmsApi {


    //必填：是否启用https,false为不启用
    private $security = false;

    //阿里授权ak
    private $accessKeyId = "";
    //阿里授权aks
    private $accessKeySecret = "";
    //短信内容
    private $Message = "";
    //接受手机号
    private $PhoneNumbers = "";


    public function __construct($config =array()){
        $this->accessKeyId = $config['accessKeyId'];
        $this->accessKeySecret = $config['accessKeySecret'];
        $this->Message = json_encode($config["Message"], JSON_UNESCAPED_UNICODE);
        $this->PhoneNumbers = $config['PhoneNumbers'];
    }

    //发送短信
    public function send_sms(){
		
        $signData = $this->sign();
        $url = ($this->security ? 'https' : 'http')."://dysmsapi.ap-southeast-1.aliyuncs.com/";
        $content = $this->fetchContent($url, $signData['method'], "Signature=".$signData['signature'].$signData['sortedQueryStringTmp']);
        $res = json_decode($content,true);
		
        return $res;		

    }

    //生成签名
    private function sign($method='POST'){
        $params = array(
            "To" => $this->PhoneNumbers, 
            "Message"=> $this->Message,
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $this->accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
            "Action" => "SendMessageToGlobe",
            "Version" => "2018-05-01",
            
            );
        ksort($params);
        $sortedQueryStringTmp = "";
        foreach ($params as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }
        $stringToSign = "${method}&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $this->accessKeySecret . "&",true));

        $signature = $this->encode($sign);

        return array(
            "method" => "POST",
            "signature"=>$signature,
            "sortedQueryStringTmp"=>$sortedQueryStringTmp,
            );

    }

    //编码
	private function encode($str){
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    //发送请求
    private function fetchContent($url, $method, $body){
        $ch = curl_init();

        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            $url .= '?'.$body;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "x-sdk-client" => "php/2.0.0"
        ));

        if(substr($url, 0,5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $rtn = curl_exec($ch);

        if($rtn === false) {
            trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
        }

        curl_close($ch);

        return $rtn;
    }

}

?>