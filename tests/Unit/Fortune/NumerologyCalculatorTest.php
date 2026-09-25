<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\NumerologyCalculator;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;

class NumerologyCalculatorTest extends TestCase
{
    public function test_verified_fixture_final_values(): void
    {
        $fx = VerifiedFortuneFixture::section('numerology');
        $result = (new NumerologyCalculator())->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        $this->assertSame($fx['name_used'], $data['name_used']);
        $this->assertSame($fx['life_path']['display'], $data['life_path']['display']);
        $this->assertSame($fx['life_path']['primary'], $data['life_path']['primary']);
        $this->assertSame($fx['life_path']['master'], $data['life_path']['master']);
        $this->assertSame($fx['destiny']['display'], $data['destiny']['display']);
        $this->assertSame($fx['soul']['display'], $data['soul']['display']);
        $this->assertSame($fx['personality']['display'], $data['personality']['display']);
        $this->assertSame($fx['birthday']['display'], $data['birthday']['display']);
        $this->assertSame($fx['maturity']['display'], $data['maturity']['display']);
        $this->assertSame($fx['maturity']['raw_sum'], $data['maturity']['raw_sum']);
    }

    public function test_life_path_intermediate_steps(): void
    {
        $fx = VerifiedFortuneFixture::section('numerology')['life_path']['breakdown'];
        $data = (new NumerologyCalculator())->calculate(VerifiedFortuneFixture::input())->data;

        // 2026 → 2+0+2+6=10 → 1; month 4; day 6; 1+4+6=11 (master)
        $this->assertSame($fx['year_reduced'], $data['life_path']['breakdown']['year']);
        $this->assertSame($fx['month'], $data['life_path']['breakdown']['month']);
        $this->assertSame($fx['day'], $data['life_path']['breakdown']['day']);
        $this->assertSame(
            $fx['sum_before_master'],
            $data['life_path']['breakdown']['year']
            + $data['life_path']['breakdown']['month']
            + $data['life_path']['breakdown']['day']
        );
        $this->assertSame(11, $data['life_path']['master']);
        $this->assertSame(2, $data['life_path']['primary']);
    }

    public function test_destiny_soul_personality_letter_math(): void
    {
        $fx = VerifiedFortuneFixture::section('numerology');
        $table = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9,
            'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'O' => 6, 'P' => 7, 'Q' => 8, 'R' => 9,
            'S' => 1, 'T' => 2, 'U' => 3, 'V' => 4, 'W' => 5, 'X' => 6, 'Y' => 7, 'Z' => 8,
        ];
        $vowels = ['A', 'I', 'U', 'E', 'O'];

        $sumLetters = function (string $word) use ($table): int {
            $s = 0;
            foreach (str_split($word) as $c) {
                $s += $table[$c];
            }

            return $s;
        };
        $sumVowels = function (string $word) use ($table, $vowels): int {
            $s = 0;
            foreach (str_split($word) as $c) {
                if (in_array($c, $vowels, true)) {
                    $s += $table[$c];
                }
            }

            return $s;
        };
        $sumConsonants = function (string $word) use ($table, $vowels): int {
            $s = 0;
            foreach (str_split($word) as $c) {
                if (! in_array($c, $vowels, true)) {
                    $s += $table[$c];
                }
            }

            return $s;
        };
        $reduce = function (int $n): int {
            while ($n > 9) {
                $n = array_sum(str_split((string) $n));
            }

            return $n;
        };

        $this->assertSame($fx['destiny']['letter_sums']['HANA'], $sumLetters('HANA'));
        $this->assertSame($fx['destiny']['letter_sums']['YAMADA'], $sumLetters('YAMADA'));
        $this->assertSame(
            $fx['destiny']['primary'],
            $reduce($reduce($sumLetters('HANA')) + $reduce($sumLetters('YAMADA')))
        );

        $this->assertSame(2, $sumVowels('HANA')); // A+A; Y=子音
        $this->assertSame(16, $sumVowels('YAMADA'));
        $this->assertSame($fx['soul']['primary'], $reduce($reduce(2) + $reduce(16)));

        $this->assertSame(7, $sumConsonants('HANA')); // Y
        $this->assertSame(11, $sumConsonants('YAMADA'));
        $this->assertSame($fx['personality']['primary'], $reduce($reduce(7) + $reduce(11)));

        // Maturity: master Life Path 11 + Destiny 9 = 20 → 2
        $this->assertSame(20, 11 + $fx['destiny']['primary']);
        $this->assertSame($fx['maturity']['raw_sum'], 20);
    }
}
