<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
session_start();

// ログインチェック
if (empty($_SESSION['login_user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['body'])) {
    $image_filename = null;

    // 画像が送られてきた場合の処理
    if (!empty($_POST['image_base64'])) {
        // Base64形式の画像データをデコード
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $_POST['image_base64']);
        $image_data = base64_decode($base64);
        
        $image_filename = bin2hex(random_bytes(16)) . '.jpg';
        // compose.ymlのボリューム設定に合わせたパス
        file_put_contents('/var/www/upload/image/' . $image_filename, $image_data);
    }

    // DB保存
    $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (user_id, body, image_filename) VALUES (:u, :b, :i)");
    $insert_sth->execute([
        ':u' => $_SESSION['login_user_id'],
        ':b' => $_POST['body'],
        ':i' => $image_filename
    ]);

    echo json_encode(['status' => 'success']);
}