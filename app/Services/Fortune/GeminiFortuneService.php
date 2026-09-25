<?php

namespace App\Services\Fortune;

use App\Models\BabyProfile;
use App\Models\FortuneCalculation;
use App\Models\FortuneReport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GeminiFortuneService
{
    public const CALCULATOR_KEYS = [
        'numerology',
        'shukuyo',
        'nine_star_ki',
        'four_pillars',
        'sanmeigaku',
        'western_astrology',
        'zi_wei_dou_shu',
    ];

    public function generate(BabyProfile $profile): FortuneReport
    {
        $report = FortuneReport::firstOrNew(['baby_profile_id' => $profile->id]);
        $previousCiphertext = $report->exists ? $report->report_ciphertext : null;
        $hadCompletedBody = is_array($previousCiphertext) && $previousCiphertext !== [];

        $report->status = FortuneReport::STATUS_GENERATING;
        $report->error_code = null;
        $report->save();

        try {
            $bundle = $this->compactBundleForPrompt(
                $this->sanitizeBundleForGemini($this->buildCalculatorBundle($profile))
            );
            $payload = $this->buildRequestPayload($bundle);
            $responseBody = $this->callInteractionsApi($payload);
            $parsed = $this->parseAndValidateResponse($responseBody);

            $report->fill([
                'status' => FortuneReport::STATUS_COMPLETED,
                'model' => config('services.gemini.model'),
                'report_ciphertext' => $parsed['report'],
                'input_tokens' => $parsed['input_tokens'],
                'output_tokens' => $parsed['output_tokens'],
                'error_code' => null,
                'generated_at' => now(),
            ]);
            $report->save();

            return $report->fresh();
        } catch (GeminiFortuneException $e) {
            $this->markFailure($report, $e->errorCode, $previousCiphertext, $hadCompletedBody);
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Gemini fortune generation failed', [
                'error_code' => 'unexpected',
                'exception' => $e::class,
            ]);
            $this->markFailure($report, 'unexpected', $previousCiphertext, $hadCompletedBody);
            throw new GeminiFortuneException('鑑定の生成に失敗しました', 'unexpected', 0, $e);
        }
    }

    /**
     * 登録直後に呼ぶ。APIが失敗しても登録は残し、cron が queued を再送する。
     */
    public function queueBatch(BabyProfile $profile): void
    {
        $report = FortuneReport::firstOrNew(['baby_profile_id' => $profile->id]);
        if ($report->isCompleted() && $report->hasReportBody()) {
            return;
        }
        if ($report->status === FortuneReport::STATUS_GENERATING && filled($report->batch_name)) {
            return;
        }

        $report->status = FortuneReport::STATUS_QUEUED;
        $report->error_code = null;
        $report->save();

        if (empty(config('services.gemini.api_key'))) {
            $report->error_code = 'api_key_missing';
            $report->save();

            return;
        }

        try {
            $bundle = $this->compactBundleForPrompt(
                $this->sanitizeBundleForGemini($this->buildCalculatorBundle($profile))
            );
            $response = $this->postBatch($this->buildBatchBody($profile, $bundle));
            $name = $response['name'] ?? null;
            if (!is_string($name) || $name === '') {
                throw new GeminiFortuneException('バッチの受付番号がありません', 'batch_name_missing');
            }

            $report->fill([
                'status' => FortuneReport::STATUS_GENERATING,
                'batch_name' => $name,
                'model' => config('services.gemini.model'),
                'error_code' => null,
            ]);
            $report->save();
        } catch (GeminiFortuneException $e) {
            $report->error_code = $e->errorCode;
            $report->status = in_array($e->errorCode, ['calculations_missing', 'api_key_invalid'], true)
                ? FortuneReport::STATUS_FAILED
                : FortuneReport::STATUS_QUEUED;
            $report->save();
            Log::warning('Gemini batch submit failed', ['error_code' => $e->errorCode]);
        } catch (\Throwable $e) {
            $report->error_code = 'unexpected';
            $report->status = FortuneReport::STATUS_QUEUED;
            $report->save();
            Log::warning('Gemini batch submit failed', ['exception' => $e::class]);
        }
    }

    public function pullBatch(FortuneReport $report): void
    {
        if ($report->status !== FortuneReport::STATUS_GENERATING || blank($report->batch_name)) {
            return;
        }

        try {
            $job = $this->getBatch($report->batch_name);
        } catch (GeminiFortuneException $e) {
            $report->error_code = $e->errorCode;
            $report->save();

            return;
        }

        $state = $job['metadata']['state'] ?? $job['state'] ?? '';
        if (is_array($state)) {
            $state = $state['name'] ?? '';
        }

        if (in_array($state, ['JOB_STATE_FAILED', 'JOB_STATE_CANCELLED', 'JOB_STATE_EXPIRED'], true)) {
            $report->status = FortuneReport::STATUS_FAILED;
            $report->error_code = strtolower($state);
            $report->save();

            return;
        }

        if ($state !== 'JOB_STATE_SUCCEEDED') {
            return;
        }

        try {
            $text = $this->extractInlineBatchText($job);
            $usage = $this->extractBatchUsage($job);
            $parsed = $this->parseAndValidateResponse([
                'output_text' => $text,
                'usage' => [
                    'total_input_tokens' => $usage['input'] ?? null,
                    'total_output_tokens' => $usage['output'] ?? null,
                ],
            ]);
            $report->fill([
                'status' => FortuneReport::STATUS_COMPLETED,
                'report_ciphertext' => $parsed['report'],
                'input_tokens' => $parsed['input_tokens'],
                'output_tokens' => $parsed['output_tokens'],
                'error_code' => null,
                'generated_at' => now(),
            ]);
            $report->save();
        } catch (GeminiFortuneException $e) {
            $report->status = FortuneReport::STATUS_FAILED;
            $report->error_code = $e->errorCode;
            $report->save();
        }
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public function buildBatchBody(BabyProfile $profile, array $bundle): array
    {
        $payload = $this->buildRequestPayload($bundle);

        return [
            'batch' => [
                'display_name' => 'fortune-'.$profile->id,
                'input_config' => [
                    'requests' => [
                        'requests' => [[
                            'request' => [
                                'contents' => [[
                                    'role' => 'user',
                                    'parts' => [['text' => $payload['input']]],
                                ]],
                                'systemInstruction' => [
                                    'parts' => [['text' => $payload['system_instruction']]],
                                ],
                                'generationConfig' => [
                                    'responseMimeType' => 'application/json',
                                    'responseSchema' => $payload['response_format']['schema'],
                                ],
                            ],
                            'metadata' => ['key' => 'fortune'],
                        ]],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function postBatch(array $body): array
    {
        $model = (string) config('services.gemini.model');
        $url = rtrim((string) config('services.gemini.batch_base'), '/')
            .'/models/'.$model.':batchGenerateContent';

        return $this->batchRequest('post', $url, $body);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getBatch(string $batchName): array
    {
        $url = rtrim((string) config('services.gemini.batch_base'), '/').'/'.ltrim($batchName, '/');

        return $this->batchRequest('get', $url);
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    protected function batchRequest(string $method, string $url, ?array $body = null): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new GeminiFortuneException('Gemini API Keyが未設定です', 'api_key_missing');
        }

        try {
            $pending = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20);
            $response = $method === 'get' ? $pending->get($url) : $pending->post($url, $body ?? []);
        } catch (ConnectionException $e) {
            throw new GeminiFortuneException('Gemini APIへの接続に失敗しました', 'timeout', 0, $e);
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new GeminiFortuneException('Gemini API Keyが無効です', 'api_key_invalid', $response->status());
        }

        if ($response->failed()) {
            throw new GeminiFortuneException('Geminiバッチの依頼に失敗しました', 'http_error', $response->status());
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new GeminiFortuneException('Geminiバッチの応答が不正です', 'invalid_json');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $job
     */
    protected function extractInlineBatchText(array $job): string
    {
        $lists = [
            $job['response']['inlinedResponses']['inlinedResponses'] ?? null,
            $job['response']['inlinedResponses'] ?? null,
            $job['dest']['inlinedResponses'] ?? null,
        ];

        foreach ($lists as $list) {
            if (!is_array($list) || !array_is_list($list) || !isset($list[0]) || !is_array($list[0])) {
                continue;
            }
            $first = $list[0];
            if (isset($first['error'])) {
                throw new GeminiFortuneException('バッチ鑑定が失敗しました', 'batch_item_failed');
            }
            $resp = is_array($first['response'] ?? null) ? $first['response'] : $first;
            if (isset($resp['text']) && is_string($resp['text']) && $resp['text'] !== '') {
                return $resp['text'];
            }
            $parts = $resp['candidates'][0]['content']['parts'] ?? [];
            $chunks = [];
            if (is_array($parts)) {
                foreach ($parts as $part) {
                    if (isset($part['text']) && is_string($part['text'])) {
                        $chunks[] = $part['text'];
                    }
                }
            }
            if ($chunks !== []) {
                return implode('', $chunks);
            }
        }

        throw new GeminiFortuneException('バッチ結果から本文を取得できませんでした', 'empty_output');
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array{input: ?int, output: ?int}
     */
    protected function extractBatchUsage(array $job): array
    {
        $lists = [
            $job['response']['inlinedResponses']['inlinedResponses'] ?? null,
            $job['dest']['inlinedResponses'] ?? null,
        ];
        foreach ($lists as $list) {
            if (!is_array($list) || !isset($list[0]['response']['usageMetadata'])) {
                continue;
            }
            $usage = $list[0]['response']['usageMetadata'];

            return [
                'input' => isset($usage['promptTokenCount']) ? (int) $usage['promptTokenCount'] : null,
                'output' => isset($usage['candidatesTokenCount']) ? (int) $usage['candidatesTokenCount'] : null,
            ];
        }

        return ['input' => null, 'output' => null];
    }

    /**
     * Calculator結果のみ（PIIなし）
     *
     * @return array<string, array{status: string, calculator_version: string, data: array}>
     */
    public function buildCalculatorBundle(BabyProfile $profile): array
    {
        $calcs = FortuneCalculation::query()
            ->where('baby_profile_id', $profile->id)
            ->get()
            ->keyBy('calculator_key');

        $bundle = [];
        foreach (self::CALCULATOR_KEYS as $key) {
            $calc = $calcs->get($key);
            if (!$calc) {
                throw new GeminiFortuneException("占術計算が未完了です: {$key}", 'calculations_missing');
            }
            $bundle[$key] = [
                'status' => $calc->status,
                'calculator_version' => $calc->calculator_version,
                'data' => $calc->result_ciphertext ?? [],
            ];
        }

        return $bundle;
    }

    /**
     * 氏名・読み・出生地など識別情報を除去してから Gemini に送る
     *
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public function sanitizeBundleForGemini(array $bundle): array
    {
        $deniedKeys = [
            'name_used',
            'given_name',
            'family_name',
            'given_name_roman',
            'family_name_roman',
            'given_name_kana',
            'family_name_kana',
            'full_name',
            'full_name_roman',
            'full_name_roman_western',
            'full_name_roman_japanese',
            'birth_place',
            'birth_date',
            'birth_time',
            'birth_weight',
        ];

        return $this->stripDeniedKeys($bundle, $deniedKeys);
    }

    /**
     * 鑑定に必要な要点だけ残して payload を縮小（無料枠の 503 回避）
     *
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public function compactBundleForPrompt(array $bundle): array
    {
        $compact = [];

        foreach ($bundle as $key => $item) {
            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $compact[$key] = [
                'status' => $item['status'] ?? null,
                'calculator_version' => $item['calculator_version'] ?? null,
                'data' => match ($key) {
                    'numerology' => [
                        'life_path' => $data['life_path'] ?? null,
                        'destiny' => $data['destiny'] ?? null,
                        'soul' => $data['soul'] ?? null,
                        'personality' => $data['personality'] ?? null,
                        'birthday' => $data['birthday'] ?? null,
                        'maturity' => $data['maturity'] ?? null,
                    ],
                    'shukuyo' => [
                        'honmei_shuku' => $data['honmei_shuku'] ?? null,
                        'yousei' => $data['yousei'] ?? null,
                        'weekday' => $data['weekday'] ?? null,
                        'lunar_date' => [
                            'ganzhi_year' => $data['lunar_date']['ganzhi_year'] ?? null,
                            'display' => $data['lunar_date']['display'] ?? null,
                        ],
                    ],
                    'nine_star_ki' => [
                        'honmei_star' => $data['honmei_star'] ?? null,
                        'getsumei_star' => $data['getsumei_star'] ?? null,
                        'nichimei_star' => $data['nichimei_star'] ?? null,
                        'keikyu' => $data['keikyu'] ?? null,
                        'dokai' => $data['dokai'] ?? null,
                        'time_nine_star' => $data['solar_time_correction']['time_nine_star'] ?? null,
                    ],
                    'four_pillars' => [
                        'day_master' => $data['day_master'] ?? null,
                        'year_pillar' => $data['year_pillar']['pillar'] ?? null,
                        'month_pillar' => $data['month_pillar']['pillar'] ?? null,
                        'day_pillar' => $data['day_pillar']['pillar'] ?? null,
                        'time_pillar' => $data['time_pillar']['pillar'] ?? null,
                        'ten_gods' => [
                            'year' => $data['year_pillar']['ten_god_gan'] ?? null,
                            'month' => $data['month_pillar']['ten_god_gan'] ?? null,
                            'day' => $data['day_pillar']['ten_god_gan'] ?? null,
                            'hour' => $data['time_pillar']['ten_god_gan'] ?? null,
                        ],
                        'element_balance' => $data['element_balance'] ?? null,
                        'special_relations' => array_values(array_unique(array_column($data['special_relations'] ?? [], 'type'))),
                    ],
                    'sanmeigaku' => [
                        'day_stem' => $data['day_stem'] ?? null,
                        'tenchusatsu' => $data['tenchūsatsu'] ?? $data['tenchusatsu'] ?? null,
                        'insen' => [
                            'year' => $data['insen']['year']['pillar'] ?? null,
                            'month' => $data['insen']['month']['pillar'] ?? null,
                            'day' => $data['insen']['day']['pillar'] ?? null,
                        ],
                        'yousen' => $data['yousen'] ?? null,
                        'shugoshin' => $data['shugoshin'] ?? null,
                    ],
                    'western_astrology' => [
                        'sun' => $data['planets']['Sun']['formatted'] ?? null,
                        'moon' => $data['planets']['Moon']['formatted'] ?? null,
                        'ascendant' => $data['angles']['ascendant']['formatted'] ?? null,
                        'midheaven' => $data['angles']['midheaven']['formatted'] ?? null,
                        'mercury' => $data['planets']['Mercury']['formatted'] ?? null,
                        'venus' => $data['planets']['Venus']['formatted'] ?? null,
                        'mars' => $data['planets']['Mars']['formatted'] ?? null,
                        'jupiter' => $data['planets']['Jupiter']['formatted'] ?? null,
                        'saturn' => $data['planets']['Saturn']['formatted'] ?? null,
                        'element_balance' => $data['element_balance'] ?? null,
                        'modality_balance' => $data['modality_balance'] ?? null,
                        'major_aspects' => array_slice(array_map(
                            fn ($a) => ($a['body1_ja'] ?? $a['body1'] ?? '').' '.($a['aspect_name'] ?? '').' '.($a['body2_ja'] ?? $a['body2'] ?? ''),
                            $data['aspects'] ?? []
                        ), 0, 8),
                    ],
                    'zi_wei_dou_shu' => [
                        'ming_zhu' => $data['ming_zhu'] ?? null,
                        'shen_zhu' => $data['shen_zhu'] ?? null,
                        'ming_gong' => $data['ming_gong'] ?? null,
                        'shen_gong' => $data['shen_gong'] ?? null,
                        'main_stars' => $data['main_stars'] ?? null,
                        'si_hua' => $data['si_hua'] ?? null,
                        'pattern' => $data['pattern'] ?? null,
                    ],
                    default => $data,
                },
            ];
        }

        return $compact;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $deniedKeys
     * @return array<string, mixed>
     */
    protected function stripDeniedKeys(array $data, array $deniedKeys): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (in_array((string) $key, $deniedKeys, true)) {
                continue;
            }
            $clean[$key] = is_array($value)
                ? $this->stripDeniedKeys($value, $deniedKeys)
                : $value;
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    public function buildRequestPayload(array $bundle): array
    {
        $userInput = json_encode([
            'subject_label' => 'お子さま',
            'instruction' => '下記のCalculatorデータを事実として使い、鑑定レポートJSONを生成してください。',
            'calculators' => $bundle,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return [
            'model' => config('services.gemini.model'),
            'store' => false,
            'system_instruction' => $this->systemInstruction(),
            'input' => $userInput,
            'response_format' => [
                'type' => 'text',
                'mime_type' => 'application/json',
                'schema' => $this->responseSchema(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function callInteractionsApi(array $payload): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new GeminiFortuneException('Gemini API Keyが未設定です', 'api_key_missing');
        }

        $endpoint = config('services.gemini.endpoint');
        $timeout = (int) config('services.gemini.timeout', 120);

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout($timeout)
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new GeminiFortuneException('Gemini APIへの接続がタイムアウトしました', 'timeout', 0, $e);
        }

        // Free tier の一時的な混雑・レート制限に短くリトライ（テストではスキップ）
        $attempts = 0;
        while (
            !app()->environment('testing')
            && $attempts < 2
            && ($response->status() === 503 || $response->status() === 429)
        ) {
            $attempts++;
            sleep($response->status() === 429 ? 40 : 15);
            try {
                $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                    ->timeout($timeout)
                    ->post($endpoint, $payload);
            } catch (ConnectionException $e) {
                throw new GeminiFortuneException('Gemini APIへの接続がタイムアウトしました', 'timeout', 0, $e);
            }
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new GeminiFortuneException('Gemini API Keyが無効です', 'api_key_invalid', $response->status());
        }

        if ($response->status() === 429) {
            throw new GeminiFortuneException('Gemini APIの利用上限に達しました', 'rate_limit', 429);
        }

        if ($response->status() === 503) {
            $hint = (string) ($response->json('error.message') ?? 'service unavailable');
            throw new GeminiFortuneException(
                'Gemini APIが混雑しています。モデルを変えるか、しばらくして再試行してください。',
                'service_unavailable',
                503
            );
        }

        if ($response->serverError()) {
            throw new GeminiFortuneException('Gemini APIサーバーエラー', 'server_error', $response->status());
        }

        if ($response->failed()) {
            $code = $response->json('error.code') ?? 'http_error';
            throw new GeminiFortuneException(
                'Gemini APIリクエストに失敗しました',
                is_string($code) ? $code : 'http_error',
                $response->status()
            );
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new GeminiFortuneException('Gemini APIレスポンスが不正です', 'invalid_json');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $responseBody
     * @return array{report: array, input_tokens: ?int, output_tokens: ?int}
     */
    protected function parseAndValidateResponse(array $responseBody): array
    {
        $text = $this->extractOutputText($responseBody);
        if ($text === null || $text === '') {
            throw new GeminiFortuneException('Gemini APIから本文を取得できませんでした', 'empty_output');
        }

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new GeminiFortuneException('Gemini出力のJSON解析に失敗しました', 'invalid_json', 0, $e);
        }

        if (!is_array($decoded)) {
            throw new GeminiFortuneException('Gemini出力がオブジェクトではありません', 'schema_mismatch');
        }

        $validator = Validator::make($decoded, $this->laravelValidationRules());
        if ($validator->fails()) {
            throw new GeminiFortuneException('Gemini出力がスキーマに一致しません', 'schema_mismatch');
        }

        $usage = $responseBody['usage'] ?? [];

        return [
            'report' => $decoded,
            'input_tokens' => isset($usage['total_input_tokens']) ? (int) $usage['total_input_tokens'] : null,
            'output_tokens' => isset($usage['total_output_tokens']) ? (int) $usage['total_output_tokens'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $responseBody
     */
    protected function extractOutputText(array $responseBody): ?string
    {
        if (!empty($responseBody['output_text']) && is_string($responseBody['output_text'])) {
            return $responseBody['output_text'];
        }

        $steps = $responseBody['steps'] ?? [];
        if (!is_array($steps)) {
            return null;
        }

        $chunks = [];
        foreach ($steps as $step) {
            if (($step['type'] ?? null) !== 'model_output') {
                continue;
            }
            foreach ($step['content'] ?? [] as $part) {
                if (($part['type'] ?? null) === 'text' && isset($part['text']) && is_string($part['text'])) {
                    $chunks[] = $part['text'];
                }
            }
        }

        return $chunks === [] ? null : implode('', $chunks);
    }

    protected function markFailure(
        FortuneReport $report,
        string $errorCode,
        mixed $previousCiphertext,
        bool $hadCompletedBody
    ): void {
        $report->error_code = $errorCode;

        if ($hadCompletedBody) {
            $report->status = FortuneReport::STATUS_COMPLETED;
            $report->report_ciphertext = $previousCiphertext;
        } else {
            $report->status = FortuneReport::STATUS_FAILED;
        }

        $report->save();
    }

    protected function systemInstruction(): string
    {
        return <<<'TXT'
あなたは新生児の両親向けに、娯楽としての占い鑑定文を書く日本語ライターです。

必須ルール:
1. 入力されたCalculatorデータを事実としてのみ使用する。干支・星位置・数値を再計算・書き換え・推測しない。
2. Calculatorと矛盾する占術データを生成しない。特に四柱推命・算命学・九星気学・西洋占星術の値を独自判断で変更しない。
3. 対象者は「この子」「お子さま」と呼ぶ。氏名・読み・住所・出生体重など個人を特定する情報は書かない（入力にも含まれない）。
4. 病気・寿命・事故・不幸の断定、職業の断定、「絶対成功する」等の過剰断言は禁止。「〜という傾向があります」「〜を伸ばしやすいでしょう」程度にする。
5. 文体は温かく前向きで読みやすく、少し特別感がある。断定しすぎない。
6. 各占術レポートはおおよそ500〜1000文字程度。総合鑑定と育成ガイドは少し厚めに。スマートフォンで読める量にする。
7. 占いはエンターテインメントとして表現する。
8. 指定のJSONスキーマ以外のキーを返さない。
TXT;
    }

    /**
     * @return array<string, mixed>
     */
    public function responseSchema(): array
    {
        $report = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'personality' => ['type' => 'string'],
                'strengths' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'relationships' => ['type' => 'string'],
                'future_tendencies' => ['type' => 'string'],
                'message' => ['type' => 'string'],
            ],
            'required' => [
                'title',
                'summary',
                'personality',
                'strengths',
                'relationships',
                'future_tendencies',
                'message',
            ],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'summary' => [
                    'type' => 'object',
                    'properties' => [
                        'catchphrase' => ['type' => 'string'],
                        'overview' => ['type' => 'string'],
                    ],
                    'required' => ['catchphrase', 'overview'],
                    'additionalProperties' => false,
                ],
                'reports' => [
                    'type' => 'object',
                    'properties' => [
                        'numerology' => $report,
                        'shukuyo' => $report,
                        'nine_star_ki' => $report,
                        'four_pillars' => $report,
                        'sanmeigaku' => $report,
                        'western_astrology' => $report,
                        'zi_wei_dou_shu' => $report,
                    ],
                    'required' => self::CALCULATOR_KEYS,
                    'additionalProperties' => false,
                ],
                'integrated_report' => [
                    'type' => 'object',
                    'properties' => [
                        'core_traits' => ['type' => 'string'],
                        'strengths' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'growth' => ['type' => 'string'],
                        'relationships' => ['type' => 'string'],
                        'future' => ['type' => 'string'],
                    ],
                    'required' => ['core_traits', 'strengths', 'growth', 'relationships', 'future'],
                    'additionalProperties' => false,
                ],
                'parenting_guide' => [
                    'type' => 'object',
                    'properties' => [
                        'early_childhood' => ['type' => 'string'],
                        'school_age' => ['type' => 'string'],
                        'teenage' => ['type' => 'string'],
                        'advice' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'lucky_elements' => [
                            'type' => 'object',
                            'properties' => [
                                'numbers' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'colors' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                                'activities' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                            'required' => ['numbers', 'colors', 'activities'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'required' => ['early_childhood', 'school_age', 'teenage', 'advice', 'lucky_elements'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['summary', 'reports', 'integrated_report', 'parenting_guide'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function laravelValidationRules(): array
    {
        $reportRules = [
            'title' => ['required', 'string'],
            'summary' => ['required', 'string'],
            'personality' => ['required', 'string'],
            'strengths' => ['required', 'array', 'min:1'],
            'strengths.*' => ['string'],
            'relationships' => ['required', 'string'],
            'future_tendencies' => ['required', 'string'],
            'message' => ['required', 'string'],
        ];

        $rules = [
            'summary.catchphrase' => ['required', 'string'],
            'summary.overview' => ['required', 'string'],
            'integrated_report.core_traits' => ['required', 'string'],
            'integrated_report.strengths' => ['required', 'array', 'min:1'],
            'integrated_report.strengths.*' => ['string'],
            'integrated_report.growth' => ['required', 'string'],
            'integrated_report.relationships' => ['required', 'string'],
            'integrated_report.future' => ['required', 'string'],
            'parenting_guide.early_childhood' => ['required', 'string'],
            'parenting_guide.school_age' => ['required', 'string'],
            'parenting_guide.teenage' => ['required', 'string'],
            'parenting_guide.advice' => ['required', 'array', 'min:1'],
            'parenting_guide.advice.*' => ['string'],
            'parenting_guide.lucky_elements.numbers' => ['required', 'array'],
            'parenting_guide.lucky_elements.colors' => ['required', 'array'],
            'parenting_guide.lucky_elements.activities' => ['required', 'array'],
        ];

        foreach (self::CALCULATOR_KEYS as $key) {
            foreach ($reportRules as $field => $rule) {
                $rules["reports.{$key}.{$field}"] = $rule;
            }
        }

        return $rules;
    }
}
