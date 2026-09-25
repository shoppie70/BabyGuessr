<?php

namespace App\Services;

use RuntimeException;

class NameHmacService
{
    protected string $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?? config('game.hmac_secret', '');

        if (empty($this->secret)) {
            throw new RuntimeException('HMAC secret key is not configured.');
        }
    }

    /**
     * 正規化された名前からHMAC-SHA256を生成
     */
    public function generateHmac(string $normalized): string
    {
        return hash_hmac('sha256', $normalized, $this->secret);
    }

    /**
     * 入力されたHMACと保存済みHMACを安全に比較 (constant-time)
     */
    public function matches(string $candidateHmac, string $storedHmac): bool
    {
        return hash_equals($storedHmac, $candidateHmac);
    }
}
