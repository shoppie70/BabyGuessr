# Phase 3.1 検証メモ（合成フィクスチャ）

Adopted fixture: `tests/Fixtures/Fortune/verified_fixture.php`  
対象: Subject A（**完全合成**） `2000-01-15 12:00 Asia/Tokyo` / 検証県検証市中央区（固定座標）

本メモは実装の回帰テスト方針のみを残す。実在個人の出生情報は含めない。

## 方針

1. 正の期待値は Calculator 出力を Fixture に固定したもの
2. 外部暦・JPL との照合は合成データでは行わない（西洋占星の `horizons_longitude` は Calculator 値のミラー）
3. Narrative / 旧 AI 鑑定の数値は参照しない

## チェックリスト

- [x] 合成入力（姓名・日時・座標）に差し替え
- [x] 7 系統 Calculator の出力を Fixture に再固定
- [x] Unit Test が Fixture と一致
