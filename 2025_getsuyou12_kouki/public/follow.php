<?php

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// セッションを開始（ログインユーザーの情報を取得するために必要）
session_start();


if (empty($_SESSION['login_user_id']) || empty($_POST['target_user_id'])) {
    // 400 Bad Request (不正なリクエスト) を返す
    header("HTTP/1.1 400 Bad Request");
    exit;
}

// 変数に代入して分かりやすくする
$follower_id = $_SESSION['login_user_id']; // フォローする人（自分）
$followee_id = $_POST['target_user_id'];   // フォローされる人（相手）

// 自分自身をフォローできないようにする制御
if ($follower_id == $followee_id) {
    // エラーメッセージをJSON形式で返却
    echo json_encode(['status' => 'error', 'message' => '自分はフォローできません']);
    exit;
}

// SQLの準備
$sql = "INSERT IGNORE INTO user_relationships (follower_user_id, followee_user_id) VALUES (:me, :target)";

$stmt = $dbh->prepare($sql);

// プレースホルダに値をバインドして実行（SQLインジェクション対策）
$stmt->execute([
    ':me' => $follower_id,
    ':target' => $followee_id
]);

// 処理成功をJSONでフロントエンドに通知
echo json_encode(['status' => 'success']);
?>