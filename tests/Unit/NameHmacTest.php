<?php

namespace Tests\Unit;

use App\Services\NameHmacService;
use PHPUnit\Framework\TestCase;

class NameHmacTest extends TestCase
{
    protected string $secret = 'test-secret-key-12345678901234567890';
    protected NameHmacService $hmacService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hmacService = new NameHmacService($this->secret);
    }

    /**
     * 同じ名前から同じHMACが生成されること
     */
    public function test_same_name_produces_same_hmac(): void
    {
        $hmac1 = $this->hmacService->generateHmac('花');
        $hmac2 = $this->hmacService->generateHmac('花');

        $this->assertNotEmpty($hmac1);
        $this->assertEquals($hmac1, $hmac2);
        $this->assertTrue($this->hmacService->matches($hmac1, $hmac2));
    }

    /**
     * 異なる名前では一致しないこと
     */
    public function test_different_names_produce_different_hmac(): void
    {
        $hmacAya = $this->hmacService->generateHmac('花');
        $hmacKaede = $this->hmacService->generateHmac('楓');

        $this->assertNotEquals($hmacAya, $hmacKaede);
        $this->assertFalse($this->hmacService->matches($hmacKaede, $hmacAya));
    }

    /**
     * 秘密鍵が異なれば同じ名前でもHMACが異なること
     */
    public function test_different_secret_produces_different_hmac(): void
    {
        $otherService = new NameHmacService('different-secret-key');

        $hmac1 = $this->hmacService->generateHmac('花');
        $hmac2 = $otherService->generateHmac('花');

        $this->assertNotEquals($hmac1, $hmac2);
    }
}
