<?php
namespace Pingqu\OpenApi\Auth;
use \DdvPhp\DdvAuth\AuthSha256;

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
        $method = empty($requestCore->method)?'GET':strtoupper($requestCore->method);
        $auth = new AuthSha256();

        $auth->setAuthVersion(self::$authVersion)->setMethod($method)->setAccessKeyId($config['accessKeyId'])->setAccessKey($config['accessKey']);
        //设定时间戳，注意：如果自行指定时间戳需要为UTC时间
        if (!isset($options[Sign::TIMESTAMP])) {
            //默认值当前时间
            $timestamp = new \DateTime();
        } else {
            $timestamp = $options[Sign::TIMESTAMP];
        }
        // 签名过期时间
        $auth->setSignTimeString($timestamp)->setExpiredTimeOffset(empty($config['expiredTimeOffset'])?1800:$config['expiredTimeOffset']);

        $canonicalUri = $requestCore->request_url;
        //去除//
        $canonicalUri = substr($canonicalUri, 0, 2)==='//' ? substr($canonicalUri, 1):$canonicalUri;
        $canonicalUris = \DdvPhp\DdvUrl::parse($canonicalUri);
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
        return $auth->setUri($requestCore->request_url)->getAuthString();

    }
}