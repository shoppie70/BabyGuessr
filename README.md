# GuessBabyName

友人・家族が赤ちゃんの「下の名前」を当てる限定公開 Web アプリです。登録した出生情報から 7 系統の占術を計算し、Gemini で鑑定文を生成できます。複数の赤ちゃんを同時に運用できます。

**スタック:** Laravel 12 · PHP 8.3 · MySQL 8.4 · Tailwind CSS v4 · Blade · Laravel Sail

---

## Features

- **名前当てゲーム** — 漢字・ひらがな・カタカナで判定。参加者には性別と生年月日だけを表示
- **みんなの回答一覧** — 試行結果（はずれ / 読み一致 / 正解）を共有画面に表示
- **ネタバレ防止** — 氏名・読み・出生地・回答は DB 上で暗号化。正誤は HMAC 比較（復号しない）
- **複数赤ちゃん** — setup から追加登録。各プロフィールに独立した game / manage / diagnostics トークン
- **ハブ** — `/hub/{HUB_TOKEN}` から苗字・性別・生年月日ラベル付きでショートカット
- **占術エンジン（Pure PHP）** — 数秘術・宿曜・九星・四柱・算命・西洋占星術・紫微斗数
- **AI 鑑定** — Gemini Batch / Interactions API。個人識別情報は送信しない
- **本番向け** — フロントはビルド済みアセットを配備

> [!IMPORTANT]
> `/` は意図的に非公開（404）です。本番ではトークン付き URL かハブだけを共有してください。鑑定画面の URL は manage トークンを含むため、扱う相手に注意してください。

---

## Getting started

### Prerequisites

- Docker Desktop（Laravel Sail）
- Node.js 20+（ホストで `npm run build` する場合）

### 1. Clone & env

```bash
cp .env.example .env
```

### 2. Install & Sail

```bash
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
```

名前判定用の HMAC 鍵を `.env` に設定します。

```bash
./vendor/bin/sail artisan tinker --execute="
\$secret = bin2hex(random_bytes(32));
\$env = file_get_contents('.env');
\$env = preg_replace('/^GAME_HMAC_SECRET=.*$/m', 'GAME_HMAC_SECRET=' . \$secret, \$env);
file_put_contents('.env', \$env);
echo 'GAME_HMAC_SECRET set' . PHP_EOL;
"
```

### 3. Migrate & assets

```bash
./vendor/bin/sail artisan migrate
npm run build
```

> [!NOTE]
> Sail コンテナ内の `npm run build` はホストと OS/CPU が違うと Rollup の optional deps で失敗することがあります。その場合はホスト側で `npm run build` してください。

### 4. Open locally

| 用途 | URL（開発デフォルトトークン） |
|------|------------------------------|
| ハブ | http://localhost/hub/demo-hub-token-2026 |
| 登録 | http://localhost/setup/demo-setup-token-2026 |
| トップ（local のみ近道表示） | http://localhost/ |

フィクスチャ（開発用サンプル赤ちゃん）でゲームをすぐ試す場合:

```bash
./vendor/bin/sail artisan db:seed --class=Database\\Seeders\\BabyProfileTestSeeder
```

ゲーム URL 例: http://localhost/g/demo-game-token-2026

---

## How it works

```text
両親 ──setup──► BabyProfile（暗号化）──► 占術計算 ──► Gemini 鑑定
                 │
                 ├─ game_token     → 友人の名前当て (/g/…)
                 ├─ manage_token   → 鑑定・共有リンク (/manage/…)
                 └─ diagnostics    → 稼働確認（氏名非表示）

ハブ (/hub/{HUB_TOKEN}) ──► 上記ショートカット一覧
```

### トークン（`.env`）

| 変数 | 用途 |
|------|------|
| `HUB_TOKEN` | ハブ入口。manage 等の URL が見えるので厳重に |
| `SETUP_TOKEN` | 赤ちゃん登録（複数回可） |
| `MANAGE_TOKEN` / `DIAGNOSTICS_TOKEN` | 未登録時のブートストラップ用。登録時はプロフィールごとにランダム発行 |
| `GAME_HMAC_SECRET` | 名前判定用。後から変えると既存判定が壊れる |
| `GEMINI_API_KEY` | 鑑定生成 |

### ゲーム画面の公開範囲

| タイミング | 表示 |
|------------|------|
| 正解前 | 性別・生年月日のみ（苗字・本名は出さない） |
| 正解後 | 正式氏名・読み |
| 常に非公開 | 出生地・出生時刻・体重 |

---

## Fortune & Gemini

登録時に占術計算と Gemini バッチ依頼が走ります。完了取り込みはスケジューラです。

```bash
# 占術のみ再計算
./vendor/bin/sail artisan fortune:calculate
./vendor/bin/sail artisan fortune:calculate --id=1 --force

# 鑑定を同期で実 API 呼び出し（省略時は全プロフィール）
./vendor/bin/sail artisan fortune:generate-report --live
./vendor/bin/sail artisan fortune:generate-report --live --id=2

# バッチ未送信の再送 + 完了取り込み
./vendor/bin/sail artisan fortune:sync-batch
```

`routes/console.php` では `fortune:sync-batch` が 5 分ごとに登録されています。スケジューラを動かす環境では `php artisan schedule:run` を定期実行してください。

---

## Testing

```bash
./vendor/bin/sail artisan test
```

主な範囲: 名前正規化・HMAC・暗号化・ゲーム判定・URL 保護・占術フィクスチャ・Gemini（Http::fake）・ハブ。

---

## Project layout

```text
app/
  Console/Commands/     fortune:* 系
  Http/Controllers/     setup / game / manage / hub / diagnostics
  Services/             GameJudge*, NameHmac*, Fortune/*
docs/                   SPEC・検証メモ
resources/views/        Blade（スマホファースト）
tests/                  Feature / Unit / Fixtures/Fortune
```

仕様の詳細は [`docs/SPEC.md`](docs/SPEC.md) を参照してください。占術検証メモは [`docs/phase-3-1-verification.md`](docs/phase-3-1-verification.md) です。
