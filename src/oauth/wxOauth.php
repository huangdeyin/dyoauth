<?php
/**
 * ********************************************************************
 * 微信第三方登录类
 * ********************************************************************
 */
namespace oauth;
use yii;

class wxOauth
{
    /**
     * @var string
     */
    private $_openId = '';

    /**
     * @var string
     */
    private $_getAuthCodeUrl = "https://open.weixin.qq.com/connect/qrconnect";

    /**
     * @var string
     */
    private $_getAccessTokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";

    /**
     * @var string
     */
    private $_apiHost = "https://api.weixin.qq.com/sns/";

    /**
     * 获取用户授权base_url
     * */
    private $_getAuthUrl='https://open.weixin.qq.com/connect/oauth2/authorize';

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @return null|wxOauth
     */
    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取微信登陆接口地址
     * @return string
     */
    public function getAuthorizeURL()
    {
        $state = md5(uniqid(rand(), TRUE));
        $params = array();
        $params['response_type'] = "code";
        $params['appid'] = yii::$app->params['wx.appid'];
        $params['redirect_uri'] = yii::$app->params['wx.callback'];
        $params['state'] = $state;
        $params['scope'] = yii::$app->params['wx.scope'];
        return $this->_getAuthCodeUrl . '?' . http_build_query($params) . '#wechat_redirect';
    }

    /**
     * 获取微信登陆接口地址
     * @return string
     */
    public function getPhoneAuthorizeURL($scope)
    {
        $state = md5(uniqid(rand(), TRUE));
        $params = array();
        $params['response_type'] = "code";
        $params['appid'] = yii::$app->params['wxs.appid'];
        $params['redirect_uri'] = yii::$app->params['wxs.callback'];
        $params['state'] = $state;
        $params['scope'] = $scope;
        return $this->_getAuthUrl . '?' . http_build_query($params) . '#wechat_redirect';
    }

    /**
     * @param $unified_cp
     * @return string
     */
    public static function emoji_get_name($unified_cp)
    {
        $emoji_maps = '';
        return $emoji_maps[$unified_cp] ? $emoji_maps[$unified_cp] : '?';
    }


    /**
     * 表情符号转换为一个特殊符号,默认是"#"
     * @param string $text
     * @param string $replaceStr
     * @return string
     */

    public static function emoji_to_string($text, $replaceStr = "#")
    {
        $emoji_maps = [];
        $text = str_ireplace(array_keys($emoji_maps), self::coverString($replaceStr), self::coverString($text));
        $text = str_replace("\\x", "%", $text);
        return urldecode($text);
    }





    /**
     * 转换为16进制
     * @param string $text
     * @return string
     */

    public static function coverString($text)
    {

        $text = urlencode($text);
        $text = str_replace("%", "\\x", $text);
        return $text;
    }


    /**
     * 获取微信用户信息
     * @param $code
     * @return array
     */

    public function getUserInfo($code)
    {
        $accessToken = $this->getAccessToken($code);
        if (empty($accessToken)) {
            return array('error' => '微信认证失败');
        }
        if (!empty($accessToken['errcode'])) {
            return array('error' => $accessToken['errmsg']);
        }

        $apiUrl = $this->_apiHost . 'userinfo';
        $this->_openId = $accessToken['openid'];
        $params = array();
        $params['access_token'] = $accessToken['access_token'];
        $params['openid'] = $accessToken['openid'];
        $result = $this->getApiData($apiUrl, $params);
        $result['nickname'] = self::removeEmoji($result['nickname']);
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
     * @return array|mixed
     */

    private function getAccessToken($code)
    {
        $params = array();
        $params['appid'] = yii::$app->params['wx.appid'];
        $params['secret'] = yii::$app->params['wx.app_secret'];
        $params['code'] = $code;
        $params['grant_type'] = 'authorization_code';

        $response = file_get_contents($this->_getAccessTokenUrl.'?'.http_build_query($params));
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

        $response = file_get_contents($apiUrl.'?'.http_build_query($param));
        return json_decode($response, true);
    }

    // 过滤掉emoji表情
    public static function removeEmoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '□' : $match[0];
            },
            $str);

        return $str;
    }
}