<?php
/**
 * ******************************************************************************
 * qq互联登录类
 * ******************************************************************************
 */

namespace dyoauth\oauth;


class qqOauth
{
    /**
     * @var string
     */
    private $_openId = '';

    /**
     * @var string
     */
    private $_unionId = '';

    /**
     * @var string
     */
    private $_getAuthCodeUrl = "https://graph.qq.com/oauth2.0/authorize";

    /**
     * @var string
     */
    private $_getAccessTokenUrl = "https://graph.qq.com/oauth2.0/token";

    /**
     * @var string
     */
    private $_getOpenIdUrl = "https://graph.qq.com/oauth2.0/me";

    /**
     * @var string
     */
    private $_apiHost = "https://graph.qq.com/user/";

    /**
     * @var string
     * */
    private $_appId = '';//QQ互联ID

    /**
     * @var string
     * */
    private $_appKey = '';//QQ互联


    /**
     * @var string
     * */
    private $_callbackUrl = '';//回调URL


    /**
     * @var string
     * */
    private $_scope = 'snsapi_login';//接口类型

    public function __construct($appId = '', $appKey = '', $callbackUrl = '')
    {
        $this->setAppId($appId);
        $this->setAppKey($appKey);
        $this->setCallbackUrl($callbackUrl);
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
        $params['client_id'] = $this->getAppId();
        $params['redirect_uri'] = $this->getCallbackUrl();
        $params['state'] = $state;
        $params['scope'] = $this->getScope();
        return $this->_getAuthCodeUrl . '?' . http_build_query($params);
    }

    /**
     * 获取qq用户信息
     * @param $code
     * @return array
     */
    public function getUserInfo($code)
    {
        $accessToken = $this->getAccessToken($code);
        if (empty($accessToken)) {
            return ['code' => 101, 'msg' => 'QQ认证失败'];
        }
        if (!empty($accessToken['error'])) {
            return ['code' => 101, 'msg' => $accessToken['error_description']];
        }
//        $params['unionid']=$this->getUnionId($accessToken['access_token']);
        $openRes = $this->getUnionId($accessToken['access_token']);
        if (empty($openRes)) {
            return ['code' => 101, 'msg' => 'QQ认证失败'];
        }
        if (!empty($openRes['error'])) {
            return ['code' => 101, 'msg' => $openRes['error_description']];
        }
        $apiUrl = $this->_apiHost . 'get_user_info';
        $this->_openId = $openRes['openid'];
        $this->_unionId = $openRes['unionid'];
        $params = array();
        $params['oauth_consumer_key'] = $this->getAppId();
        $params['access_token'] = $accessToken['access_token'];
        $params['openid'] = $openRes['openid'];
        $params['unionid'] = $openRes['unionid'];
        $params['format'] = 'json';
        $result = $this->getApiData($apiUrl, $params);
        return $result;
    }

    /**
     * 获取用户OpenId
     * @param $accessToken
     * @return mixed
     */
    private function getUnionId($accessToken)
    {
        //-------请求参数列表
        $params = array();
        $params["access_token"] = $accessToken;
        $params['unionid'] = 1;
        $response = file_get_contents($this->_getOpenIdUrl . '?' . http_build_query($params));
        $result = $this->JsonpDecode($response);
        return $result;
    }

    /**
     * @return string
     */
    public function openId()
    {
        return $this->_openId;
    }

    /**
     * 获取用户AccessToken
     * @param $code
     * @return array
     */
    private function getAccessToken($code)
    {
        $params = array();
        $params["grant_type"] = "authorization_code";
        $params["client_id"] = $this->getAppId();
        $params["redirect_uri"] = $this->getCallbackUrl();
        $params["client_secret"] = $this->getAppKey();
        $params["code"] = $code;
        $response = file_get_contents($this->_getAccessTokenUrl . '?' . http_build_query($params));
        $result = $this->JsonpDecode($response);
        return $result;
    }

    /**
     * 获取用户OpenId
     * @param $accessToken
     * @return mixed
     */
    private function getOpenId($accessToken)
    {
        //-------请求参数列表
        $params = array();
        $params["access_token"] = $accessToken;
        $response = file_get_contents($this->_getOpenIdUrl . '?' . http_build_query($params));
        $result = $this->JsonpDecode($response);
        return $result;
    }


    /**
     * 将字符串转换为可以进行json_decode的格式
     * 将转换后的参数值赋值给成员属性$this->client_id,$this->openid
     * @param $response
     * @return array
     */
    private function JsonpDecode($response)
    {
        $result = array();

        if (strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $json = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $result = json_decode($json, true);
        } else {
            parse_str($response, $result);
        }
        return $result;
    }

    /**
     * 获取接口数据
     * @param $apiUrl
     * @param $param
     * @return array
     */
    private function getApiData($apiUrl, $param = array())
    {
        $result = file_get_contents($apiUrl . '?' . http_build_query($param));
        return json_decode($result, true);
    }

    // 过滤掉emoji表情
    public function removeEmoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '□' : $match[0];
            },
            $str);

        return $str;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->_appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->_appId = $appId;
    }

    /**
     * @return string
     */
    public function getAppKey()
    {
        return $this->_appKey;
    }

    /**
     * @param string $appKey
     */
    public function setAppKey($appKey)
    {
        $this->_appKey = $appKey;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->_callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->_callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->_scope;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->_scope = $scope;
    }
}