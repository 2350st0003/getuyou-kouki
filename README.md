## 概要

月曜1,2限Webシステムの授業で作成した、アプリケーションです。

---

## 主な機能

-   **ユーザー認証**: 会員登録、ログイン、ログアウト機能。
-   **タイムライン**: 「フォロー中」と「すべての投稿」をタブで切り替え可能
-   **画像投稿**: 複数枚の画像投稿に対応し、ブラウザ側で自動リサイズを行ってから送信
-   **無限スクロール**: 画面下部に到達すると、過去の投稿を自動的に読み込みます。


---

## ディレクトリ構成

```
2025_webSystem/
├── docker-compose.yml
├── .gitignore
└── public/
    ├── index.html      # メイン画面 (タイムライン)
    ├── login.php       # ログイン画面
    ├── register.php    # 会員登録画面
    ├── post.php        # 投稿処理API
    └── image/          # 画像保存ディレクトリ
```
---

## 構築方法

AWS EC2などのサーバーにSSH接続します。

#### Docker環境の構築
本アプリケーションを実行するには、Docker および Docker Compose が必要です。
以下のコマンドをサーバー内で順番に実行してください。
```bash
# パッケージの更新とDockerのインストール
sudo yum update -y
sudo yum install -y docker git

# Dockerサービスの起動と自動起動設定
sudo systemctl start docker
sudo systemctl enable docker

# 現在のユーザー(ec2-user)をdockerグループに追加
sudo usermod -aG docker ec2-user

# グループ設定を反映させる（一度ログアウトするか、以下のコマンドを実行）
newgrp docker
```
#### 最新版のDockerComposeのインストール
以下のコマンドをサーバー内で順番に実行してください。
```bash
# ディレクトリ作成
sudo mkdir -p /usr/local/lib/docker/cli-plugins/

# 最新版のダウンロード
sudo curl -SL https://github.com/docker/compose/releases/latest/download/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose

# 実行権限の付与
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
```
最後にバージョンを確認し、バージョン情報が表示されればインストール完了です。
```bash
docker compose version
# 出力例: Docker Compose version v2.xx.x
```

#### コードの複製
以下のコマンドを実行して、環境をセットアップし起動します。

```bash
# リポジトリの取得
git clone https://github.com/2350ST0009/2025_webSystem.git
cd 2025_webSystem

# 画像保存用フォルダの作成
mkdir -p public/image
chmod 777 public/image

# アプリケーション起動
docker compose up -d --build
```

#### SQLの構築
アプリケーションを動作させるには、MySQLコンテナに接続し (`docker compose exec mysql mysql -u root -p`)、以下のSQLを実行してテーブルを作成する必要があります。

```sql
USE example_db;

-- 1. ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    icon_filename TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. 投稿テーブル
CREATE TABLE IF NOT EXISTS bbs_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    body TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. 画像管理テーブル
CREATE TABLE IF NOT EXISTS entry_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    image_filename TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (entry_id)
);

-- 4. フォロー関係テーブル
CREATE TABLE IF NOT EXISTS user_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_user_id INT NOT NULL,
    followee_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_user_id, followee_user_id)
);
```

## 接続手順

上記のdockerやsqlを構築したら、webブラウザで接続してください。
会員登録から始める場合はこちらで接続してください。

```
http://[IPアドレス]/register.php
```

会員登録が済んでいる場合は上記のベージからログイン画面に進むか、以下のURLで接続してください。
```
http://[IPアドレス]/login.php
```
