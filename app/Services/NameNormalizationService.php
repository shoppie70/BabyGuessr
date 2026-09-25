<?php

namespace App\Services;

class NameNormalizationService
{
    /**
     * 漢字表記の正規化
     * - 前後空白除去
     * - 内部の不要スペース（全角・半角）除去
     * - Unicode正規化 (NFKC または NFC)
     */
    public function normalizeKanji(string $name): string
    {
        // Unicode正規化
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($name, \Normalizer::FORM_C) ?: $name;
        } else {
            $normalized = $name;
        }

        // 前後および内部の全角・半角スペースを除去
        return preg_replace('/[\s\x{3000}]+/u', '', $normalized) ?? '';
    }

    /**
     * 読み仮名の正規化
     * - 前後空白除去
     * - 内部の不要スペース除去
     * - カタカナをひらがなに統一
     * - 全角ひらがなへ変換
     */
    public function normalizeKana(string $kana): string
    {
        // Unicode正規化
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($kana, \Normalizer::FORM_C) ?: $kana;
        } else {
            $normalized = $kana;
        }

        // 空白除去
        $trimmed = preg_replace('/[\s\x{3000}]+/u', '', $normalized) ?? '';

        // カタカナをひらがなに統一 ('c': 全角カタカナを全角ひらがなに変換, 'H': 半角カタカナを全角カタカナに変換)
        // 先に半角カタカナを全角カタカナにし、その後ひらがなに統一
        $hiragana = mb_convert_kana($trimmed, 'cHV', 'UTF-8');

        return $hiragana;
    }
}
