<?php
session_start();

// セッション変数を全て解除する
$_SESSION = array();

// セッションを切断する
if (isset($_COOKIE["PHPSESSID"])) {
    setcookie("PHPSESSID", '', time() - 1800, '/');
}
session_destroy();

// ログイン画面へ移動
header("Location: /login.php");
exit;