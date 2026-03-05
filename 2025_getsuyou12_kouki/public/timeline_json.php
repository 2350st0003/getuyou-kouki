<?php
// フォロー中タブ用php

// データベース接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

session_start();

// ログインチェック
// 非同期通信（Fetch API）で呼ばれるため、エラー時はHTMLではなくJSONでエラーを返す
if (empty($_SESSION['login_user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    header("Content-Type: application/json");
    print(json_encode(['entries' => []]));
    return;
}

// --- 無限スクロール用パラメータ ---
// JavaScriptから送られてくる「何件目から何件ほしいか」を受け取る
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// --- SQLの構築（タイムラインロジックの核心） ---
$sql = 'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename,'
    // フォロー状態の判定
    . ' (SELECT COUNT(*) FROM user_relationships WHERE follower_user_id = :login_user_id AND followee_user_id = bbs_entries.user_id) AS is_following'
    . ' FROM bbs_entries'
    . ' INNER JOIN users ON bbs_entries.user_id = users.id'
    . ' WHERE'
    . '   bbs_entries.user_id IN'
    . '     (SELECT followee_user_id FROM user_relationships WHERE follower_user_id = :login_user_id)'
    . '   OR bbs_entries.user_id = :login_user_id'
    . ' ORDER BY bbs_entries.created_at DESC' // 新しい順
    . ' LIMIT :limit OFFSET :offset'; // ページネーション

// プリペアードステートメント
$select_sth = $dbh->prepare($sql);
$select_sth->bindValue(':limit', $limit, PDO::PARAM_INT);
$select_sth->bindValue(':offset', $offset, PDO::PARAM_INT);
$select_sth->bindValue(':login_user_id', $_SESSION['login_user_id'], PDO::PARAM_INT);
$select_sth->execute();

// 本文の表示用フィルタ関数（XSS対策）
function bodyFilter (string $body): string
{
    $body = htmlspecialchars($body);
    $body = nl2br($body);
    return $body;
}

// データ整形処理
$result_entries = [];
foreach ($select_sth as $entry) {
    $result_entry = [
        'id' => $entry['id'],
        'user_id' => $entry['user_id'],
        'user_name' => $entry['user_name'],
        'body' => bodyFilter($entry['body']),
        // 画像がある場合のみパスを作成
        'image_file_url' => empty($entry['image_filename']) ? '' : ('/image/' . $entry['image_filename']),
        'created_at' => $entry['created_at'],
        'is_following' => $entry['is_following'],
        'is_me' => ($entry['user_id'] == $_SESSION['login_user_id']),
    ];
    $result_entries[] = $result_entry;
}

// JSONとして出力
header("HTTP/1.1 200 OK");
header("Content-Type: application/json");
print(json_encode(['entries' => $result_entries]));
?>