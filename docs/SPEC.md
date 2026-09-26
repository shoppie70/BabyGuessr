# BabyGuessr v2 実装仕様書

- Status: Draft v1
- Date: 2026-09-25
- Purpose: 友人の子どもの名前を仲間内で当てる、1ゲーム限定・限定公開Webアプリ
- Test fixture: Subject A（詳細は tests/Fixtures/Fortune/）

---

## 1. プロダクト定義

BabyGuessr v2 は、友人に子どもが生まれた際、両親が正解の名前と出生情報を事前登録し、共有URLを受け取った友人・家族が赤ちゃんの下の名前を予想して遊ぶ限定公開Webアプリである。

一般向けサービス化、複数ユーザー向けSaaS化、複数ゲーム管理は行わない。1デプロイにつき1人の赤ちゃん、1ゲームのみを扱う。

ゲーム作成者には登録負担への付加価値として、出生情報と姓名から複数占術の鑑定レポートをAI APIで生成し提供する。

### 1.1 最優先事項

1. 名前当てゲームが簡単に遊べること
2. 運営者自身がDB・ログ・診断画面を確認しても正解名を偶然知りにくいこと
3. 共有レンタルサーバー上で常駐プロセスなしに運用できること
4. 鑑定の基礎計算とAIによる文章生成を分離すること
5. 占術計算の正しさをSubject A の Verified Golden Fixture（暦・天文照合済み）で回帰テストできること

---

## 2. スコープ

### 2.1 v1 に含める

- 1人分の赤ちゃん情報登録
- 一回限りの初期セットアップ
- 名前当てゲーム
- ニックネーム入力
- 挑戦回数管理
- 正解 / 不正解判定
- 回答履歴保存
- 両親向け管理画面
- 運営者向けネタバレ防止診断画面
- プロフィールの暗号化保存
- 回答内容の暗号化保存
- 判定用HMAC
- 7系統の占術計算・鑑定
- 総合鑑定
- 育成・開運ガイド
- AI生成状況管理・再試行
- noindex / nofollow

### 2.2 v1 に含めない

- 一般ユーザー登録
- 認証アカウント
- 誰でもゲームを作成できる機能
- 複数ゲーム / 複数赤ちゃん管理
- SNS公開を前提としたSEO
- 課金
- メール通知
- Queue Worker 常駐
- WebSocket
- 管理者による平文氏名閲覧UI
- 両親自身の出生情報を使った親子相性鑑定

親子相性鑑定は旧 Narrative 資料には存在するが、v1 では両親の出生情報を収集しないため対象外とする。

---

## 3. 利用者と権限

### 3.1 Parent / Owner

赤ちゃんの両親。初期情報を登録し、鑑定結果を閲覧し、参加URLを共有する。

Owner URL では以下を行える。

- 登録済みプロフィールの確認
- 鑑定結果閲覧
- 鑑定生成状況確認
- 鑑定再生成
- 回答履歴確認
- 正解者確認
- ゲーム終了
- 名前公開

### 3.2 Player

共有URLを受け取った友人・家族。

- ニックネーム入力
- 下の名前を回答
- 判定結果確認
- 再挑戦
- 正解時に正式な氏名・読みを閲覧

ログインは不要。

### 3.3 Operator

アプリを設置・保守する運営者。

Operator は動作確認を行えるが、通常運用では正解名や鑑定本文を閲覧しない。

専用診断画面では以下のみ確認可能とする。

- プロフィール登録済みか
- 各計算処理の成功 / 失敗
- 各AI生成の成功 / 失敗
- 実行日時
- AIモデル
- token 使用量
- エラーコード
- 回答件数
- 正解件数

平文の氏名、読み、回答文字列、AI鑑定本文は表示しない。

---

## 4. URL設計

URL secret は十分な長さの暗号学的ランダム値を使用する。

```text
/setup/{setup-token}
/play/{play-token}
/owner/{owner-token}
/ops/{ops-token}
```

### 4.1 `/`

原則 404 または最低限の Not Found 表示とする。

一般サービスのランディングページは作らない。

### 4.2 setup token

- 1回限り
- プロフィール登録完了後は無効
- DBに平文保存しない

### 4.3 play token

参加者に共有する。

### 4.4 owner token

両親だけが保持する。

### 4.5 ops token

運営者だけが保持する。

各tokenは用途ごとに分離し、相互利用不可とする。

---

## 5. 初期登録フォーム

### 5.1 必須項目

- 姓（漢字）
- 名（漢字）
- 姓の読み
- 名の読み
- 性別
- 生年月日
- 出生時刻
- 出生地

出生時刻不明にも対応する。ただし入力なしの場合は、時刻を必要とする一部鑑定を簡易版に切り替える。

### 5.2 任意項目

- 出生体重
- メッセージ

出生体重はゲーム判定・主要占術計算には利用せず、記念プロフィール用途とする。

### 5.3 ローマ字表記

数秘術で姓名を使用するため、読み仮名からローマ字候補を生成する。

- 姓ローマ字
- 名ローマ字

自動生成後、Parent が確認・修正できること。

ローマ字表記方式の差で数秘結果が変化し得るため、AI任せの自動確定は禁止する。

### 5.4 出生地

保存項目:

- 表示用出生地文字列
- 緯度
- 経度
- 位置精度種別

位置精度種別:

- exact
- facility
- municipality
- manual

位置座標は占星術計算に使用する。

---

## 6. セットアップフロー

```text
/setup/{secret}
        ↓
プロフィール入力
        ↓
入力確認
        ↓
暗号化保存
        ↓
占術基礎計算
        ↓
鑑定生成画面
        ↓
7占術を順次AI生成
        ↓
総合鑑定生成
        ↓
育成・開運ガイド生成
        ↓
完了
        ↓
Parent用URL / Player用URL表示
```

AI生成を1 HTTPリクエスト内で全件実行しない。

共有レンタルサーバーのタイムアウトを避けるため、ブラウザから `generate-next` を逐次呼び出し、1リクエストにつき原則1レポートだけ生成する。

画面離脱後も Owner URL から再開できる。

---

## 7. 名前当てゲーム

### 7.1 画面

スマートフォン最優先。

例:

```text
赤ちゃんの名前を当てよう！

姓：○○

あなたの名前 / ニックネーム
[              ]

赤ちゃんの名前
[              ]

[ 答える ]

現在 4 回目の挑戦
```

必要以上の出生情報は表示しない。

### 7.2 回答対象

下の名前のみ。

### 7.3 正規化

判定前に以下を正規化する。

- Unicode 正規化
- 前後空白除去
- 全角 / 半角整理
- 内部不要空白除去
- カタカナをひらがなへ統一

漢字は原則完全一致。

異体字・旧字体の自動同一視は v1 では行わない。

### 7.4 判定

v1 では以下の3状態を持つ。

- `correct`: 漢字名が一致
- `reading_match`: 読みのみ一致
- `wrong`: どちらも不一致

UI:

- correct: 「大正解」＋正式氏名・読みを公開
- reading_match: 「読み方は正解。漢字はまだ違います」
- wrong: 「不正解」

`reading_match` を不要と判断した場合、設定で `wrong` と同じ表示に変更できるようにする。

### 7.5 挑戦回数

ブラウザセッション単位でカウントする。

正解時には「N回目で正解」と表示する。

完全なユーザー同定はしない。

---

## 8. ゲーム状態

```text
open
revealed
closed
```

### open

回答可能。

### revealed

ゲーム終了。アクセス時に正式氏名・読みを表示する。

### closed

非公開。

---

## 9. ネタバレ防止設計

本アプリの重要要件。

### 9.1 原則

DB・phpMyAdmin・通常ログ・Operator診断画面を見ただけでは、運営者が正解名を知れない構造にする。

### 9.2 プロフィール保存

氏名を個別平文カラムで保持せず、プロフィールJSON全体を authenticated encryption で暗号化する。

例:

```json
{
  "family_name": "...",
  "given_name": "...",
  "family_name_kana": "...",
  "given_name_kana": "...",
  "family_name_roman": "...",
  "given_name_roman": "...",
  "sex": "...",
  "birth_date": "...",
  "birth_time": "...",
  "birth_place": "...",
  "latitude": 0,
  "longitude": 0,
  "birth_weight": null
}
```

DBには `profile_ciphertext` として保存する。

### 9.3 判定用 HMAC

ゲーム判定ではプロフィールを毎回答ごとに復号しない。

以下を別途保存する。

- `given_name_hmac`
- `given_name_kana_hmac`

計算:

```text
HMAC-SHA256(normalizedValue, APP_HMAC_KEY)
```

回答を同様にHMAC化して constant-time comparison する。

単純SHA-256は禁止する。日本の名前は候補空間が小さく辞書攻撃できるため、秘密鍵付きHMACとする。

### 9.4 回答履歴

回答文字列も平文保存しない。

- `guess_ciphertext`
- `guess_hmac`
- `result`
- `attempt_no`
- `session_hash`

を保存する。

正解回答が存在しても、DB閲覧だけではその文字列を確認できない。

### 9.5 AI鑑定

以下もすべて暗号化保存する。

- 各占術の基礎計算JSON
- 各占術AIレポートJSON
- 総合鑑定JSON
- 育成ガイドJSON

### 9.6 ログ

以下をログ出力禁止とする。

- 平文氏名
- 読み
- profile JSON
- 回答文字列
- AI prompt本文
- AI response本文

ログには次だけ残す。

- internal ID
- report type
- status
- duration
- HTTP status
- provider error code

### 9.7 限界

アプリケーション暗号鍵を管理する運営者が意図的に復号処理を実行すれば、技術的には平文を取得できる。

v1 の目的は「運営者から暗号学的に完全秘匿する」ことではなく、「保守作業中の偶発的ネタバレを防ぐ」ことである。

---

## 10. 占術アーキテクチャ

占術処理を以下の2段階に分離する。

```text
Profile
   ↓
Deterministic Calculator
   ↓
Calculation JSON
   ↓
AI Interpreter
   ↓
Fortune Report JSON
```

### 10.1 Calculator の責務

AIを使わず、可能な限り決定論的に基礎データを算定する。

### 10.2 AI Interpreter の責務

計算済みJSONを受け取り、Parent向けに読みやすい鑑定文章へ変換する。

AIに暦・画数・天体位置などの基礎計算を推測させない。

---

## 11. 対象占術

v1 では旧 Narrative 資料に合わせ、以下7系統を対象とする。

1. 四柱推命
2. 九星気学
3. 宿曜占星術
4. 数秘術
5. 算命学
6. 紫微斗数
7. 西洋占星術 / ホロスコープ

加えて以下を生成する。

8. 7大占術 総合鑑定
9. 育成・開運ガイド

---

## 12. 占術別の構造化データ

### 12.1 四柱推命

最低限:

- 年柱
- 月柱
- 日柱
- 時柱
- 天干
- 地支
- 蔵干
- 通変星
- 十二運星
- 五行バランス
- 特殊関係（冲・合等）

出生時刻不明の場合は時柱を欠損として扱う。

### 12.2 九星気学

最低限:

- 本命星
- 月命星
- 日命星
- 傾宮
- 同会
- 五行
- 節気区分
- 必要な時差補正情報

### 12.3 宿曜占星術

最低限:

- 旧暦生年月日
- 本命宿
- 宿分類
- 四宮
- 十二宮配当
- 曜星

### 12.4 数秘術

最低限:

- Life Path
- Destiny
- Soul
- Personality
- Birthday
- Maturity

姓名由来の数値は、Parentが確認したローマ字表記を入力値とする。

### 12.5 算命学

最低限:

- 陰占
- 陽占
- 日干
- 天中殺
- 十大主星
- 十二大従星
- 守護神関連基礎データ
- 五行バランス

### 12.6 紫微斗数

最低限:

- 旧暦生年月日
- 時辰
- 命主
- 身主
- 十二宮
- 主星
- 四化星
- 命宮
- 身宮
- 三方四正等の主要構造

### 12.7 西洋占星術

最低限:

- 太陽
- 月
- ASC
- 水星
- 金星
- 火星
- 木星
- 土星
- 天王星
- 海王星
- 冥王星
- サイン
- 度数
- ハウス
- エレメント
- クオリティ
- 主要アスペクト

天体位置・ASC・ハウスの計算はAI推測ではなく、天文計算ライブラリまたは決定論的な外部計算プロバイダを利用する。

---

## 13. AI鑑定レポート仕様

### 13.1 AI API

API provider は交換可能なinterfaceを持たせる。

初期実装は OpenAI API を想定する。

API key は `.env` に保存し、クライアントへ送らない。

### 13.2 個別鑑定の出力JSON

各占術は原則以下の共通構造を返す。

```json
{
  "title": "",
  "summary": "",
  "core_traits": [],
  "strengths": [],
  "relationships": "",
  "talents": "",
  "growth": "",
  "lucky_elements": [],
  "message_to_parents": "",
  "disclaimer": ""
}
```

占術固有セクションは `details` 以下に追加可。

### 13.3 文体

- 新生児の両親が楽しく読める
- 肯定的だが断定しすぎない
- 将来を確定事項として表現しない
- 医療・健康上の予言を行わない
- 重大な人生判断を促さない
- 「娯楽としての鑑定」であることを明示

### 13.4 AI生成失敗

- `pending`
- `processing`
- `completed`
- `failed`

を保持する。

失敗したレポートだけ再生成可能にする。

---

## 14. 総合鑑定

7占術の基礎計算と個別レポートを入力として生成する。

最低限以下を含む。

- キャッチコピー
- 7占術サマリー
- 複数占術で共通して現れたテーマ
- 性格・精神構造
- 強み・才能
- 対人傾向
- 成長テーマ
- 総合メッセージ

AIが「複数占術が一致している」と表現する場合、入力された複数の計算結果・レポートに実際に共通要素があることを前提とする。

---

## 15. 育成・開運ガイド

子ども本人の7占術結果のみを入力として生成する。

v1 では両親の占術情報は使用しない。

最低限:

- 幼少期の関わり方
- 思春期以降の成長テーマ
- 得意を伸ばす環境
- 苦手になりやすい環境
- ラッキーナンバー等の娯楽要素
- ラッキーカラー等の娯楽要素
- Parent向けメッセージ

教育・進路・医療等の重要判断を占いだけで決めるような表現は禁止する。

---

## 16. AI生成実行方式

共有サーバーでQueue Workerを使わない。

### 16.1 generate-next

Owner画面からクライアントが逐次以下を呼ぶ。

```text
POST /owner/{token}/fortune/generate-next
```

サーバーは次の `pending` 項目を1件だけ処理する。

処理順:

1. 四柱推命
2. 九星気学
3. 宿曜
4. 数秘
5. 算命学
6. 紫微斗数
7. ホロスコープ
8. 総合鑑定
9. 育成・開運ガイド

レスポンスには本文を返さず、以下だけ返す。

```json
{
  "completed": 4,
  "total": 9,
  "current": "numerology",
  "status": "completed"
}
```

### 16.2 冪等性

同一 `input_hash + calculator_version + prompt_version` の completed result が存在する場合は再実行しない。

明示的な「再生成」の場合のみ version を上げる。

---

## 17. DB設計

### 17.1 baby_profiles

```text
id
profile_ciphertext
profile_schema_version
given_name_hmac
given_name_kana_hmac
registered_at
created_at
updated_at
```

1レコードのみ。

### 17.2 game_states

```text
id
status
revealed_at
closed_at
created_at
updated_at
```

### 17.3 guesses

```text
id
guess_ciphertext
guess_hmac
session_hash
attempt_no
result
created_at
```

### 17.4 fortune_calculations

```text
id
calculator_key
calculator_version
input_hash
status
result_ciphertext
error_code
calculated_at
created_at
updated_at
```

### 17.5 fortune_reports

```text
id
report_key
prompt_version
provider
model
input_hash
status
result_ciphertext
input_tokens
output_tokens
error_code
generated_at
created_at
updated_at
```

### 17.6 operational_events

本文を持たない運用ログ。

```text
id
event_type
subject_type
subject_id
status
metadata_json
created_at
```

`metadata_json` に秘密情報を保存してはならない。

---

## 18. 診断画面

`/ops/{token}`

例:

```text
PROFILE
✓ registered
registered_at: 2026-09-25 13:21

CALCULATORS
✓ shichusuimei
✓ kyusei
✓ sukuyo
✓ numerology
✓ sanmeigaku
✓ ziwei
✓ western_astrology

AI REPORTS
✓ 7 / 7
✓ integrated
✓ parenting_guide

GAME
answers: 18
correct: 1

SECRET DATA
hidden
```

「表示」ボタン等で平文を復号する機能は設けない。

---

## 19. セキュリティ

- HTTPS 必須
- `.env` はdocument root外
- APP_KEY と HMAC key を分離
- token は最低128bit以上のランダム値
- URL tokenはDBへ平文保存しない
- CSRF対策
- rate limit
- AI API keyをブラウザへ渡さない
- debug mode無効
- request bodyのログ禁止
- `robots` noindex, nofollow
- `X-Robots-Tag: noindex, nofollow`
- 鑑定ページ・ゲームページをサイトマップに含めない

Player answer endpointには軽いrate limitを設定する。

---

## 20. 技術スタック

### 20.1 Backend

- PHP 8.2
- Laravel 12
- Blade
- MySQL / MariaDB

既存Laravel 10コードの段階的改修ではなく、新規Laravel 12アプリとして再構築し、必要なゲームロジックのみ移植する。

### 20.2 Frontend

- Blade
- Tailwind CSS
- Vanilla JavaScript / TypeScript
- Vite
- pnpm

React / Inertia は使用しない。

本番サーバー上でNode.jsを常駐させない。フロントアセットはローカルまたはCIでbuildして配布する。

### 20.3 Development

- Docker
- PHP 8.2
- MySQL / MariaDB
- Node.js
- pnpm

### 20.4 Production

Xserver / StarServer系共有レンタルサーバー。

Composer依存物とbuild済みassetsをデプロイする。

Laravelの公開対象は `public/` のみとする。

レンタルサーバー側のdocument root制約で直接 `public/` を設定できない場合、アプリ本体をweb公開ディレクトリ外へ配置し、public entrypointだけをdocument rootへ配置する。

---

## 21. Laravel採用理由

アプリ自体は小規模だが、v2では以下が必要になる。

- authenticated encryption
- validation
- CSRF
- rate limiting
- DB migrations
- model lifecycle
- HTTP client
- retry / timeout
- structured configuration
- feature test
- unit test

これらを軽量FW上に個別組み込みするよりLaravelを最小構成で使用する方が総コード量と保守負担が小さい。

React、Sanctum、Queue等の不要機能は導入しない。

---

## 22. テスト戦略

### 22.1 Unit Test

- 名前正規化
- HMAC生成
- HMAC比較
- Encryption / Decryption
- token検証
- 各占術Calculator
- report schema validation

### 22.2 Feature Test

- setup token以外では登録不可
- 登録後setup token無効
- play URLから回答可能
- owner URL以外から鑑定本文閲覧不可
- ops画面に秘密データが含まれない
- 正解回答がDB上で平文保存されない
- AI失敗時の再試行
- generate-nextの冪等性

### 22.3 Security Test

DB dumpを取得し、以下の文字列検索でヒットしないこと。

- 正解の漢字名
- 正解の読み
- 正解回答文字列
- AI鑑定本文の代表フレーズ

ログファイルでも同様に検査する。

---

## 23. Subject A Golden Fixture

既存の鑑定資料を、Calculator実装の回帰テスト用fixtureとして使用する。

### 23.1 入力

個人を特定できる姓名・住所は本ドキュメントに記載しない。正の入力値はリポジトリ内の Verified Fixture のみを参照する。

- Fixture: `tests/Fixtures/Fortune/verified_fixture.php`（実装・テスト用の合成データ）
- 検証メモ: `docs/phase-3-1-verification.md`

### 23.2 Verified Golden Fixture (Phase 3.1)

正の期待値は上記 Fixture。検証根拠は `docs/phase-3-1-verification.md`。

```text
（期待値の全文は verified_fixture.php を正とする。ここに実データは書かない。）
数秘例: HANA YAMADA / LP・Destiny 等は Fixture の numerology 節
```

### 23.3 Narrative Reference

旧 AI 鑑定の数値は**使わない**。公開リポジトリには転載しない。

### 23.4 Fixtureの扱い

1. 数値の正は Verified Fixture（合成入力）
2. Calculator と Verified が不一致なら Calculator か Fixture を更新する
3. 実在個人の出生情報を Fixture / ドキュメントに戻さない
4. Phase 4 AI 入力は Calculator 構造化データのみ（旧 Markdown の命式・星位置は投入禁止）

---

## 24. AIレポートのテスト

AI出力は文章の完全一致テストを行わない。

テスト対象:

- JSON schemaに一致する
- 必須セクションが存在する
- 入力Calculation JSONにない基礎数値を勝手に追加しない
- 名前が正しく表示される
- medical / deterministic future claimを含まない
- disclaimerがある

Golden Fixtureでは、旧 Narrative レポートを品質比較の参考資料とするが、文章一致を合格条件にしない。

---

## 25. 実装順序

### Phase 1: Game Core

- Laravel 12新規構築
- DB
- secret URL
- profile encryption
- HMAC
- setup
- play
- guesses
- owner
- ops diagnostics

完了条件:

Subject A を登録し、運営者がDBを見ても下の名前を確認できない状態で、正しい下の名前の回答だけが正解になる。

### Phase 2: Fortune Calculator

占術を1系統ずつ実装する。

推奨順:

1. 数秘術
2. 宿曜
3. 九星気学
4. 四柱推命
5. 算命学
6. 西洋占星術
7. 紫微斗数

各CalculatorはSubject A fixtureが通ってから次へ進む。

### Phase 3: AI Reports

- AI provider
- structured output
- individual reports
- encrypted storage
- retry
- progress UI

### Phase 4: Integrated Report

- 総合鑑定
- 育成・開運ガイド

### Phase 5: Production Deployment

- StarServer / Xserver配置
- HTTPS
- production key生成
- APP_DEBUG=false
- robots
- 実データ投入前のDB/log secret scan

---

## 26. Definition of Done

以下をすべて満たした時点でv1完成とする。

- Parentが限定URLから赤ちゃん情報を登録できる
- 登録後setup URLが無効になる
- Playerが限定URLから名前当てできる
- 正解判定がサーバー側で行われる
- 正解名がDBに平文で存在しない
- 回答内容もDBに平文で存在しない
- Operator診断画面からAI生成状態を確認できる
- Operator診断画面に正解名が出ない
- 7 Calculator がSubject A Golden Fixtureで検証済み
- 7個別鑑定を生成できる
- 総合鑑定を生成できる
- 育成・開運ガイドを生成できる
- 全鑑定本文が暗号化保存される
- AI失敗時に対象だけ再生成できる
- スマートフォンで問題なく利用できる
- noindex / nofollow が有効
- 本番サーバーでNode常駐やQueue Workerなしに動作する

---

## 27. 設計原則

このアプリでは将来の一般公開やSaaS化を想定した抽象化を行わない。

必要になるまで以下を追加しない。

- user_id
- tenant_id
- game_id
- roles / permissions
- repository layer
- event bus
- queue abstraction
- microservice

ただし、占術CalculatorとAI Providerは外部仕様・流派・API変更の影響が大きいためinterfaceで分離する。

最優先は「今回の1ゲームを安全に、楽しく、保守可能な最小構成で成立させること」とする。
