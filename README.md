# PHP 第三方登录授权 SDK

#### 用于登录QQ，微信，微博
暂时只维护了QQ模块，后续需要添加新的模块

安装：

安装方式一：

<code>
    composer require deyin/dyoauth
</code>

安装方式二：

在您的composer.json中加入配置：

    {
        "require": {
            "deyin/dyoauth": "~1.0"
        }
    }

示例：

    /**
     * 第三方登录入口
     * */
    public function actionThirdParty()
    {
        $type = $_GET['type'];
        if ($type == 'qq') {
            $appId = '';
            $appKey = '';
            $callbackUrl = '';
            $qq = new qqOauth($appId, $appKey, $callbackUrl);
            $url = $qq->getAuthorizeURL();
        }
        $this->redirect($url);
    }
