<?php
namespace Pingqu\OpenApi\Auth;
use \DdvPhp\DdvUrl;

class Sign
{
    const TIMESTAMP = 'timestamp';
    private static $authVersion = 'pingqu-auth-v1';
    private static $config;
    public static function setConfig($config){
        self::$config = $config;
    }
    public static function getAuth(\Pingqu\OpenApi\Http\RequestCore $requestCore, $options = array(), $config=null)
    {
        $config = empty($config) ? self::$config : $config;

        if(empty($config['accessKeyId'])){
            throw Exception('sss' ,'DFDSF');
        }
        $accessKeyId = $config['accessKeyId'];
        $expiredTimeOffset = empty($config['expiredTimeOffset'])?1800:$config['expiredTimeOffset'];
        $signTime = time();
        //设定时间戳，注意：如果自行指定时间戳需要为UTC时间
        if (!isset($options[Sign::TIMESTAMP])) {
            //默认值当前时间
            $timestamp = new \DateTime();
        } else {
            $timestamp = $options[Sign::TIMESTAMP];
        }
        $timestamp->setTimezone(new \DateTimeZone("UTC"));
        $signTimeString = $timestamp->format("Y-m-d\TH:i:s\Z");

        // 授权字符串
        $authString = self::$authVersion."/{$accessKeyId}/{$signTimeString}/{$expiredTimeOffset}";
        //生成加密key
        $signingKey = hash_hmac('sha256', $authString, $config['accessKey']);


        $method = empty($requestCore->method)?'GET':strtoupper($requestCore->method);
        $canonicalUri = $requestCore->request_url;
        //去除//
        $canonicalUri = substr($canonicalUri, 0, 2)==='//' ? substr($canonicalUri, 1):$canonicalUri;
        $canonicalUris = \DdvPhp\DdvUrl::parse($canonicalUri);
        $canonicalPath = isset($canonicalUris['path'])?$canonicalUris['path']:'';
        $canonicalPath = self::formatath($canonicalPath);
        //取得query
        $canonicalQuery = isset($canonicalUris['query'])?$canonicalUris['query']:'';
        // get请求
        if ($method === 'GET') {
            // 如果有请求体
            if (!empty($requestCore->request_body)) {
                    // 拼接到query中
                $canonicalQuery .= (empty($canonicalQuery) ? '' : '&').$requestCore->request_body;
            }
        }
        $canonicalUris['query'] = $canonicalQuery;
        $requestCore->request_url = \DdvPhp\DdvUrl::build($canonicalUris) ;
        // 重新排序编码
        $canonicalQuery = self::canonicalQuerySort($canonicalQuery);
        $signHeaders = $requestCore->request_headers;
        $authHeadersStr = implode(';', array_keys($signHeaders));
        // 获取签名头
        $canonicalHeaders = self::getCanonicalHeaders($signHeaders);

        //生成需要签名的信息体
        $canonicalRequest = "{$method}\n{$canonicalPath}\n{$canonicalQuery}\n{$canonicalHeaders}";

        //服务端模拟客户端算出的签名信息
        $sessionSign = hash_hmac('sha256', $canonicalRequest, $signingKey);
        // 组成最终签名串
        $authString .= "/{$authHeadersStr}/{$sessionSign}";

        return $authString;

    }
    private static function getCanonicalHeaders($signHeaders = array())
    {
        //重新编码
        $canonicalHeader = array();
        foreach ($signHeaders as $key => $value) {
            $canonicalHeader[] = strtolower(DdvUrl::urlEncode(trim($key))).':'.DdvUrl::urlEncode(trim($value));
        }
        sort($canonicalHeader);
        //服务器模拟客户端生成的头
        $canonicalHeader = implode("\n", $canonicalHeader) ;
        return $canonicalHeader;
    }
    private static function canonicalQuerySort($canonicalQuery = '')
    {
        //拆分get请求的参数
        $canonicalQuery = empty($canonicalQuery) ? array() : explode('&',$canonicalQuery);
        $tempNew = array();
        $temp = '';
        $tempI = '';
        $tempKey = '';
        $tempValue = '';
        foreach ($canonicalQuery as $key => $temp) {
            $temp = DdvUrl::urlDecode($temp);
            $tempI = strpos($temp,'=');
            if (strpos($temp,'=')===false) {
                continue;
            }
            $tempKey = substr($temp, 0,$tempI);
            $tempValue = substr($temp, $tempI+1);

            $tempNew[] = DdvUrl::urlEncode($tempKey).'='.DdvUrl::urlEncode($tempValue);
        }
        sort($tempNew);
        $canonicalQuery = implode('&', $tempNew) ;
        unset($temp,$tempI,$tempKey,$tempValue,$tempNew);
        return $canonicalQuery;
    }
    public static function formatath($path){
        $path = '/'.implode('/', array_filter(explode('/', $path)));
        // 强制/开头
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        return $path;
    }
}