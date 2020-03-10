<?php
/**
 * ********************************************************************
 * 微博第三方登录类
 * ********************************************************************
 */
namespace oauth;

class wbOauth
{
    /**
     * @var string
     */
    private $_openId = '';

    /**
     * @var string
     */
    private $_getAuthCodeUrl = "https://api.weibo.com/oauth2/authorize";

    /**
     * @var string
     */
    private $_getAccessTokenUrl = "https://api.weibo.com/oauth2/access_token";


    /**
     * @var string
     */
    private $_apiHost = "https://api.weibo.com/2/";

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @return null|wbOauth
     */
    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取登录地址
     * @return string
     */
    public function getAuthorizeURL()
    {
        $state = md5(uniqid(rand(), TRUE));
        $params = array();
        $params['response_type'] = "code";
        $params['client_id'] = globalConfig('wb.akey');
        $params['redirect_uri'] = str_replace('http://',\ooopic\input::getScheme(),globalConfig("wb.callback"));
        $params['state'] = $state;
        $params['display'] = globalConfig('wb.display');
        return $this->_getAuthCodeUrl . '?' . http_build_query($params);
    }

    /**
     * 获取微博用户信息
     * @param $code
     * @return array
     */
    public function getUserInfo($code)
    {
        $accessToken = $this->getAccessToken($code);
        if (empty($accessToken)) {
            return array('error' => '微博认证失败');
        }
        if (!empty($accessToken['error'])) {
            return array('error' => $accessToken['error']);
        }

        $apiUrl = $this->_apiHost . "users/show.json";
        $this->_openId = $accessToken['uid'];
        $params = array();
        $params["access_token"] = $accessToken['access_token'];
        $params["uid"] = $accessToken['uid'];
        $result = $this->getApiData($apiUrl, $params);
        return $result;
    }


    /**
     * @return string
     */
    public function openId(){
        return $this->_openId;
    }

    /**
     * 获取用户AccessToken
     * @param $code
     * @return array|mixed
     */
    private function getAccessToken($code)
    {
        $params = array();
        $params['client_id'] = globalConfig('wb.akey');
        $params['client_secret'] = globalConfig('wb.skey');
        $params['grant_type'] = 'authorization_code';
        $params['code'] = $code;
        $params['redirect_uri'] = globalConfig("wb.callback");

        $response = \ooopic\http::post($this->_getAccessTokenUrl, $params);
        $result = json_decode($response, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * 获取接口数据
     * @param $apiUrl
     * @param $param
     * @return array
     */
    private function getApiData($apiUrl, $param = array())
    {
        $response = \ooopic\http::get($apiUrl, $param);
        $result = json_decode($response, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            foreach ($result as $key => $val) {
                $result[$key] = iconv('UTF-8', 'GBK', $val);
            }
            return $result;
        } else {
            return array();
        }
    }

}