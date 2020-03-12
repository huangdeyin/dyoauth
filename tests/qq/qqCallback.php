<?php

namespace test;

include '..\..\src\oauth\qqOauth.php';

use dyoauth\qqOauth;

$qq = new qqOauth();
$code = $_GET['code'];
$qq->getUserInfo($code);;