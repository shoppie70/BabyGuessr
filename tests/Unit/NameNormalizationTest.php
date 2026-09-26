<?php

namespace Tests\Unit;

use App\Services\NameNormalizationService;
use PHPUnit\Framework\TestCase;

class NameNormalizationTest extends TestCase
{
    protected NameNormalizationService $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new NameNormalizationService();
    }

    /**
     * カタカナとひらがなが同一になること
     */
    public function test_katakana_and_hiragana_are_normalized_to_the_same(): void
    {
        $hiragana = $this->normalizer->normalizeKana('はな');
        $katakana = $this->normalizer->normalizeKana('ハナ');

        $this->assertEquals('はな', $hiragana);
        $this->assertEquals('はな', $katakana);
        $this->assertEquals($hiragana, $katakana);
    }

    /**
     * 空白（半角・全角）が除去されること
     */
    public function test_spaces_are_trimmed_and_removed(): void
    {
        // 読みの空白除去
        $this->assertEquals('はな', $this->normalizer->normalizeKana(' は な '));
        $this->assertEquals('はな', $this->normalizer->normalizeKana('　は　な　'));
        $this->assertEquals('はな', $this->normalizer->normalizeKana(" \t ハ　ナ \n"));

        // 漢字の空白除去
        $this->assertEquals('花', $this->normalizer->normalizeKanji(' 花 '));
        $this->assertEquals('花', $this->normalizer->normalizeKanji('　花　'));
        $this->assertEquals('山田花', $this->normalizer->normalizeKanji(' 山田　花 '));
    }
}
