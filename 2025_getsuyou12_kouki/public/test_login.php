<?php
session_start();
// さっきDBに入れた「テスト太郎(id=1)」としてログインしたことにする
$_SESSION['login_user_id'] = 1;

echo "<h1>テストログイン成功！</h1>";
echo "<p><a href='/index.html'>ここをクリックしてタイムラインへ移動</a></p>";