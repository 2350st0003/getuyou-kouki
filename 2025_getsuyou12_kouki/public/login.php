<?php
// セッションを開始（ログイン状態の管理に必須）
session_start();

// 既にログイン済みかどうかをチェック
// $_SESSION['login_user_id'] に値が入っていればログイン中とみなす
if (!empty($_SESSION['login_user_id'])) {
    // ログイン済みならメイン画面へリダイレクト
    header("HTTP/1.1 302 Found");
    header("Location: ./index.html");
    return;
}

$message = ""; // 画面に表示するエラーメッセージ用変数

// POSTメソッドでアクセスされた場合（ログインボタンが押された時）の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームからの入力を受け取る（未入力の場合は空文字）
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // データベース接続
    $dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
    
    // メールアドレスでユーザーを検索
    // プレースホルダ (:email) を使用してSQLインジェクション対策
    $select_sth = $dbh->prepare("SELECT * FROM users WHERE email = :email");
    $select_sth->execute([':email' => $email]);
    $user = $select_sth->fetch(); // 検索結果を1行取得

    // ユーザーが見つからなかった場合
    if (empty($user)) {
        $message = "メールアドレスまたはパスワードが間違っています。";
    } else {
        // ユーザーが見つかった場合、パスワードの検証を行う
        // password_verify: 入力された平文パスワードと、DB内のハッシュ化されたパスワードを比較
        if (password_verify($password, $user['password'])) {
            // 認証成功時
            // セッションにユーザーIDを保存（これがログイン状態の証になる）
            $_SESSION['login_user_id'] = $user['id'];
            
            // メイン画面へリダイレクト
            header("HTTP/1.1 302 Found");
            header("Location: ./index.html");
            return;
        } else {
            // パスワード不一致の場合
            $message = "メールアドレスまたはパスワードが間違っています。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechFocus - ログイン</title>
    <style>
        /* 全要素のリセットCSS */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* 画面中央にフォームを配置するためのFlexboxレイアウト */
        body {
            background-color: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #1c1e21;
        }
        
        /* ログインフォームのコンテナ（白いカード部分） */
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        h1 { margin-bottom: 30px; font-size: 1.8rem; color: #1d9bf0; }
        
        /* 入力フォームのスタイル */
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #cfd9de;
            border-radius: 6px;
            font-size: 1rem;
            outline: none;
            transition: 0.2s;
        }
        
        /* フォーカス時に青い枠線を表示 */
        input:focus { border-color: #1d9bf0; box-shadow: 0 0 0 3px rgba(29,155,240,0.1); }
        
        /* ログインボタンのスタイル（Twitterっぽいボタン） */
        button {
            width: 100%;
            padding: 12px;
            background-color: #1d9bf0;
            color: #fff;
            border: none;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
        }
        
        button:hover { background-color: #1a8cd8; }
        
        /* エラーメッセージ表示エリア */
        .error { color: #f4212e; font-size: 0.9rem; margin-bottom: 15px; text-align: left; }
        
        /* 会員登録ページへのリンク */
        .link { margin-top: 20px; font-size: 0.9rem; }
        .link a { color: #1d9bf0; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>TechFocus</h1>
        
        <?php if (!empty($message)): ?>
            <div class="error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="メールアドレス" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit">ログイン</button>
        </form>

        <div class="link">
            アカウントをお持ちでないですか？<br>
            <a href="/register.php">会員登録はこちら</a>
        </div>
    </div>
</body>
</html>