<?php

namespace test;

include '..\..\src\oauth\qqOauth.php';

use dyoauth\qqOauth;

$appId = '';
$appKey = '';
$callbackUrl = '';
$qq = new qqOauth($appId, $appKey, $callbackUrl);
header('Location:' . $qq->getAuthorizeURL());
