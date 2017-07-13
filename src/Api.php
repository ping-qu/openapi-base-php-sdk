<?php
namespace Pingqu\OpenApi;



/**
 * Class Client
 * @package Pingqu\MultimediaTranscoder\V1
 *
 * Pingqu MultimediaTranscoder\V1(PQVT) 的客户端类，封装了用户通过VT API对PQVT服务的各种操作，
 * 用户通过Client实例可以进行Bucket，Object，MultipartUpload, ACL等操作，具体
 * 的接口规则可以参考官方PQVT API文档
 */
class Api
{

    private  $params = array();
    private  $header = array();
    /**
     * 构造函数
     *必须传$accessKeyId, $accessKeySecret, $endpoint
     *
     */
    public function __construct($accessKeyId, $accessKeySecret, $api)
    {
        $accessKeyId = trim($accessKeyId);
        $accessKeySecret = trim($accessKeySecret);
        $api = trim($api);

        if (empty($accessKeyId)) {
            throw new Exception("access key id is empty");
        }
        if (empty($accessKeySecret)) {
            throw new Exception("access key secret is empty");
        }
        if (empty($api)) {
            throw new Exception("api is empty");
        }
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->api = $api;
        self::checkEnv();
    }

    /*
     * 设置请求参数
     *
     */
    public function setParams($params = array()){
        $this->params = $params;
    }

    /*
     * 设置请求头
     */
    public function setHeader($header = array()){
        $this->header = $header;
    }

    /*
     * 携带签名信息发送http请求
     */
    public function sendRequest($method = null){
        \Pingqu\OpenApi\Auth\Sign::setConfig([
            'accessKeyId'=>$this->accessKeyId,
            'accessKey'=>$this->accessKeySecret
        ]);
        $method = empty($method)?'GET':$method;
        $para = empty($this->params)?[]:$this->params;
        $httpCore = new \Pingqu\OpenApi\Http\RequestCore($this->api);
        $httpCore->set_body(\DdvPhp\DdvUrl::buildQuery($para));
        $httpCore->set_method($method);
        foreach ($this->header as $key=>$item) {
            $httpCore->add_header($key,$item);
        }
        $httpCore->add_header('Authorization', \Pingqu\OpenApi\Auth\Sign::getAuth($httpCore));
        $httpResponse = $httpCore->send_request(true);
        return $httpResponse;
    }

    /**
     * 用来检查sdk所以来的扩展是否打开
     *
     * @throws Exception
     */
    public static function checkEnv()
    {
        if (function_exists('get_loaded_extensions')) {
            //检测curl扩展
            $enabled_extension = array("curl");
            $extensions = get_loaded_extensions();
            if ($extensions) {
                foreach ($enabled_extension as $item) {
                    if (!in_array($item, $extensions)) {
                        throw new Exception("Extension {" . $item . "} is not installed or not enabled, please check your php env.");
                    }
                }
            } else {
                throw new Exception("function get_loaded_extensions not found.");
            }
        } else {
            throw new Exception('Function get_loaded_extensions has been disabled, please check php config.');
        }
    }

}
