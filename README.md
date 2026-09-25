# GuessBabyName v2

友人に子どもが生まれた際、正解の名前と出生情報を事前登録し、共有URLを受け取った友人・家族が赤ちゃんの下の名前を予想して遊ぶ1ゲーム限定・限定公開Webアプリケーションです。

---

## 🌟 特徴

- **ネタバレ防止アーキテクチャ**:
  - 赤ちゃんの氏名・読み・出生地・回答履歴はすべてデータベース上に平文で保存されず、暗号化（AES-256-CBC）されて保存されます。
  - 正誤判定は復号を行わず、サーバー側の秘密鍵を用いたHMAC-SHA256ハッシュの一定時間比較（constant-time comparison）で判定します。
  - 運用者が保守中に偶発的に名前を知ってしまうのを防ぐため、診断画面（`/diagnostics/{token}`）では名前を表示せず、登録ステータスと集計件数のみを確認可能です。
- **スマートフォンファーストUI**:
  - Tailwind CSSによるシンプルで直感的なレスポンシブデザイン。
  - 漢字だけでなく、ひらがな・カタカナ（読み仮名）の一致判定もサポート。
  - ページ再読み込みのない非同期回答体験。
- **Laravel 12 & Laravel Sail**:
  - Laravel 12 標準構成へ刷新。
  - ローカル開発環境は Docker Desktop 上の Laravel Sail（PHP 8.3 / MySQL 8.4）に統一。
- **本番配備の容易性**:
  - 本番サーバー上では Node.js を使用せず運用できるよう、フロントエンド成果物（CSS/JS）はビルド済みファイルとしてそのまま配備可能な構成です。

---

## 🛠 技術スタック

- **Backend**: Laravel 12.x / PHP 8.2+ 互換（PHP 8.3にて動作検証済み）
- **Database**: MySQL 8.4 (Laravel Sail)
- **Frontend**: Blade, Tailwind CSS v4, Vanilla JavaScript (fetch API)
- **Dev Environment**: Laravel Sail (Docker Compose)
- **Testing**: PHPUnit 11

---

## 🚀 ローカル起動手順

リポジトリをcloneした状態からローカル画面を確認するまでの手順です。

### 前提条件
- Docker Desktop が起動していること

### 1. 環境変数の準備

```bash
cp .env.example .env
```

### 2. 依存関係のインストール & Sail起動

Composerがホスト端末にある場合:
```bash
composer install
./vendor/bin/sail up -d
```

ホストにPHP/Composerがない場合は、Dockerコンテナ経由でインストール可能です:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

./vendor/bin/sail up -d
```

### 3. アプリケーションキーとHMAC秘密鍵の生成

```bash
./vendor/bin/sail artisan key:generate
```

続いて、名前判定用のHMAC鍵（`GAME_HMAC_SECRET`）を `.env` に設定します:
```bash
./vendor/bin/sail artisan tinker --execute="
\$secret = bin2hex(random_bytes(32));
\$env = file_get_contents('.env');
\$env = preg_replace('/^GAME_HMAC_SECRET=.*$/m', 'GAME_HMAC_SECRET=' . \$secret, \$env);
file_put_contents('.env', \$env);
echo 'Generated GAME_HMAC_SECRET' . PHP_EOL;
"
```

### 4. データベースマイグレーション

```bash
./vendor/bin/sail artisan migrate
```

### 5. フロントエンドのビルド

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### 6. 動作確認方法の選択

本アプリは、以下のいずれかの方法で動作確認を行えます。

#### 【方法A】実際の登録フローで確認する（推奨）

Seederを使わず、ご両親が実際に赤ちゃん情報を登録するE2Eフローを確認できます：

1. ブラウザで **[http://localhost/setup/demo-setup-token-2026](http://localhost/setup/demo-setup-token-2026)** を開く
2. 赤ちゃんの情報（姓・名・読み・性別・生年月日等）を入力して「確認へ進む」をクリック
3. 確認画面で内容をチェックし、「登録する」をクリック
4. 完了画面に表示される **ゲーム参加URL（`/g/{game-token}`）** をコピーしてゲームを開始

#### 【方法B】テストフィクスチャ（山田花）で簡易確認する

手動登録を省略してすぐにゲームや診断画面を試したい場合は、テスト用Seederを実行してください：

```bash
./vendor/bin/sail artisan db:seed --class=Database\\Seeders\\BabyProfileTestSeeder
```
※このSeederは開発・自動テスト専用であり、本番の `DatabaseSeeder` では自動投入されません。

---

## 🌐 画面URL一覧

> [!WARNING]
> **アクセス時の注意点**:  
> 本アプリは一般公開トップページを持たない1ゲーム限定のサービスです。  
> `http://localhost` 直下（`/`）を開くと仕様により非公開案内（404）が表示されます。  
> ブラウザからは必ず以下の **トークン付きURL**（例: `/setup/demo-setup-token-2026`）へ直接アクセスしてください。（ローカル環境の `/` にも開発用ショートカットリンクが表示されます）

| 画面 | デフォルトURL (開発用) | 説明 |
| :--- | :--- | :--- |
| **初期セットアップ** (ご両親用) | [http://localhost/setup/demo-setup-token-2026](http://localhost/setup/demo-setup-token-2026) | 1回限りの赤ちゃん情報登録画面（入力→確認→完了） |
| **名前当てゲーム** (参加者用) | `http://localhost/g/{game-token}`<br>※Seeder時は [http://localhost/g/demo-game-token-2026](http://localhost/g/demo-game-token-2026) | 友人が回答するゲーム画面 |
| **管理者画面** (両親・運営用) | [http://localhost/manage/demo-manage-token-2026](http://localhost/manage/demo-manage-token-2026) | 登録情報確認・ステータス変更・回答ログ一覧 |
| **診断画面** (運用保守用) | [http://localhost/diagnostics/demo-diag-token-2026](http://localhost/diagnostics/demo-diag-token-2026) | ネタバレ防止（平文氏名非表示）の稼働・回答統計確認 |
| **トップページ** | [http://localhost/](http://localhost/) | 非公開案内ページ（404） |

全公開ページに `<meta name="robots" content="noindex, nofollow">` が設定されています。

### ゲーム画面の公開情報仕様

ゲーム参加者に余計な個人情報が漏れないよう、表示項目は厳格にコントロールされています：

- **正解前**: 姓（苗字）、性別（女の子/男の子）、生年月日のみ表示
- **正解後**: 正式氏名（漢字）、読み（ひらがな）のみ公開
- **非公開**: 出生地、出生時刻、出生体重は鑑定内部用データとし、ゲーム参加者には一切表示されません。

### 動作確認例（山田花データ投入時）

ゲーム画面にて:
- `楓` と入力 → **不正解**
- `はな` または `ハナ` と入力 → **読み一致**（「読み方は大正解！漢字はまだ違います」）
- `花` と入力 → **大正解**（正式な氏名「山田 花」、読み「やまだ はな」がオープン）

---

## 🧪 自動テストの実行

```bash
./vendor/bin/sail artisan test
```

### テスト範囲
- **名前正規化 (Unit)**: カタカナ/ひらがな統一、全角/半角スペース除去
- **HMAC (Unit)**: ハッシュ生成の一貫性、不一致検証、constant-time比較
- **暗号化 (Feature)**: データベース上に平文氏名が残らないこと、モデル復号の正常性、回答履歴の暗号化
- **名前判定 (Feature)**: 山田花データに対する「花」(正解)、「はな/ハナ」(読み一致)、「楓」(不正解) の判定およびAPIフロー
- **URL保護 (Feature)**: 無効なトークンやルートURLの404保護、noindex設定の検証、HTMLソースへの正解名非露出
