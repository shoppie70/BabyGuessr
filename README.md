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

## 🔮 占術基礎計算エンジン (Phase 3)

AIによる文章生成（解釈）の前段として、出生・姓名データから決定論的（再現可能）に基礎データを算出・暗号化保存する純粋PHPエンジンを搭載しています。

- **100% Pure PHP (Zero FFI / No C-extension)**: 共有レンタルサーバー環境（Xserver, StarServer等）でも外部依存やC拡張なしに動作可能。
- **対応占術 (7系統)**:
  1. **数秘術 (`numerology`)**: ピタゴラス数秘術（Life Path, Destiny, Soul, Personality, Birthday, Maturity）。母音・子音の厳密な判定（Yの特別ルール対応）。
  2. **宿曜占星術 (`shukuyo`)**: 旧暦・太陰太陽暦による27宿、宿分類、四宮、十二宮配当、曜星の完全判定。
  3. **九星気学 (`nine_star_ki`)**: 本命星、月命星、日命星（日本の気学に基づく陽遁・陰遁計算）、傾宮（月命盤回座）、同会（本命盤定位）、真太陽時・時差補正。
  4. **四柱推命 (`four_pillars`)**: 年柱・月柱・日柱・時柱の天干・地支・蔵干（余気・中気・本気）、通変星、十二運星、五行バランス、特殊関係（冲・合）。
  5. **算命学 (`sanmeigaku`)**: 陰占、陽占（人体星図の十大主星・十二大従星）、日干、天中殺（旬空）、守護神判定。
  6. **西洋占星術 (`western_astrology`)**: VSOP87/ELP2000準拠の高精度天文計算（0.1秒角精度）。10天体（太陽・月・水星・金星・火星・木星・土星・天王星・海王星・冥王星）のサイン・度数、Placidusハウス計算、ASC/MC、主要アスペクト、エレメント・クオリティ分布。
  7. **紫微斗数 (`zi_wei_dou_shu`)**: 旧暦生年月日時・時辰から命主、身主、命宮、身宮、十二宮配置、主星配置（紫微七殺巳宮格）、生年四化星、三方四正。
- **暗号化保存 (`fortune_calculations`)**: 計算結果データはすべて `encrypted:array` でDB保存され、平文の流出を防ぎます。
- **時刻欠損時のグレースフルハンドリング**: 出生時刻がない場合はエラーにせず、時柱やASC等を除外した `partial`（紫微斗数は `unavailable`）として記録。

### 計算コマンド (CLI)

```bash
# 全赤ちゃんプロファイルの全占術を一括計算
./vendor/bin/sail artisan fortune:calculate

# 特定プロファイルの強制再計算
./vendor/bin/sail artisan fortune:calculate --id=1 --force

# 特定占術のみ計算
./vendor/bin/sail artisan fortune:calculate --calculator=four_pillars
```

---

## ✨ AI鑑定レポート (Phase 4)

Gemini Interactions API（REST・`store=false`）で、7占術の計算結果を**1回のAPI呼び出し**で鑑定文に変換します。

- Provider: Google AI Studio (`GEMINI_API_KEY` / `GEMINI_MODEL`)
- 氏名・読み・住所など個人識別情報は Gemini に送りません
- 鑑定本文は `fortune_reports.report_ciphertext` に暗号化保存
- 友人の登録保存をきっかけに Gemini Batch API へ渡す。画面では待たない
- 完了の取り込みは `fortune:sync-batch`（5分ごと）。エックスサーバーの cron で `schedule:run` を回す
- できた鑑定は `/manage/{token}` から見て、友人に送る

```bash
# .env に GEMINI_API_KEY を設定後、実APIを1回だけ確認
./vendor/bin/sail artisan fortune:generate-report --live
```

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
- **占術基礎計算 (Unit & Feature)**:
  - Verified Fixture (`tests/Fixtures/Fortune/verified_fixture.php`, Phase 3.1): 暦・JPL照合済み
  - 数秘術 (HANA YAMADA / LP 11/2 等・途中計算テスト)
  - 宿曜 (旧暦 2/19, 心宿) / 九星 (八白日命・五黄時命) / 四柱 (庚戌日・丙戌時)
  - 算命 (日干庚) / 西洋 (JPL Horizons ±0.2°) / 紫微 (命宮巳)
  - 診断画面保護 (7系統の計算ステータス表示、本文完全秘匿)
- **AI鑑定 (Feature, Http::fake)**: 正常系・429・invalid JSON・暗号化・再生成失敗時の旧レポート維持・payloadのPII除外・Diagnostics本文非表示
