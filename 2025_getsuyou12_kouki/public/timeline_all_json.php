<?php
// すべての投稿タブ用php

// データベース接続
// Dockerのサービス名 'mysql' をホストとして指定
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

session_start();

// ログインチェック
// ログインしていないユーザーからのアクセスの場合、401エラーを返してデータを渡さない
if (empty($_SESSION['login_user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    header("Content-Type: application/json");
    print(json_encode(['entries' => []]));
    return;
}

// --- 無限スクロール用パラメータの取得 ---
// JavaScript（fetchのURLパラメータ）から送られてくる値を取得
// limit: 一回に取得する件数（5件）
// offset: 何件スキップして取得するか
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// --- SQLの構築 ---
// 1. bbs_entries（投稿）と users（投稿者情報）を結合してデータを取得
// 3. LIMITとOFFSETを使って、必要な範囲のデータだけを効率よく取得する
$sql = 'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename,'
    . ' (SELECT COUNT(*) FROM user_relationships WHERE follower_user_id = :login_user_id AND followee_user_id = bbs_entries.user_id) AS is_following'
    . ' FROM bbs_entries'
    . ' INNER JOIN users ON bbs_entries.user_id = users.id'
    . ' ORDER BY bbs_entries.created_at DESC' // 新しい順に並べる
    . ' LIMIT :limit OFFSET :offset'; // ★ここが無限スクロールの肝

// プリペアドステートメントの準備
$select_sth = $dbh->prepare($sql);

// 値をバインド（SQLインジェクション対策）
$select_sth->bindValue(':limit', $limit, PDO::PARAM_INT);
$select_sth->bindValue(':offset', $offset, PDO::PARAM_INT);
$select_sth->bindValue(':login_user_id', $_SESSION['login_user_id'], PDO::PARAM_INT);

// SQL実行
$select_sth->execute();

// 本文の表示用フィルタ関数（XSS対策）
function bodyFilter (string $body): string
{
    $body = htmlspecialchars($body); // HTMLタグを無効化（スクリプト埋め込み防止）
    $body = nl2br($body); // 改行コードを <br> タグに変換して、改行が見えるようにする
    return $body;
}

// フロントエンドに返すデータの整形
$result_entries = [];
foreach ($select_sth as $entry) {
    $result_entry = [
        'id' => $entry['id'],
        'user_id' => $entry['user_id'],
        'user_name' => $entry['user_name'],
        'body' => bodyFilter($entry['body']),
        // 画像ファイル名がある場合のみパスを作成、なければ空文字
        'image_file_url' => empty($entry['image_filename']) ? '' : ('/image/' . $entry['image_filename']),
        'created_at' => $entry['created_at'],
        'is_following' => $entry['is_following'], // 1ならフォロー中、0なら未フォロー
        'is_me' => ($entry['user_id'] == $_SESSION['login_user_id']), // 自分の投稿かどうか（削除ボタンなどの表示制御に使える）
    ];
    $result_entries[] = $result_entry;
}

// JSON形式でレスポンスを返す
// JavaScriptの fetch() はこのJSONを受け取って画面を描画する
header("HTTP/1.1 200 OK");
header("Content-Type: application/json");
print(json_encode(['entries' => $result_entries]));
?>