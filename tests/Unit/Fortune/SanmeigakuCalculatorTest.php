<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\SanmeigakuCalculator;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;
use Tests\TestCase;

class SanmeigakuCalculatorTest extends TestCase
{
    protected SanmeigakuCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SanmeigakuCalculator();
    }

    public function test_calculates_sanmeigaku_from_verified_fixture(): void
    {
        $fx = VerifiedFortuneFixture::section('sanmeigaku');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        $this->assertSame($fx['day_stem']['expected'], $data['day_stem']);
        $this->assertSame($fx['tenchusatsu']['expected'], $data['tenchūsatsu']);

        $this->assertSame($fx['insen']['year'], $data['insen']['year']['pillar']);
        $this->assertSame($fx['insen']['month'], $data['insen']['month']['pillar']);
        $this->assertSame($fx['insen']['day'], $data['insen']['day']['pillar']);

        foreach ($fx['yousen'] as $key => $expected) {
            $this->assertSame($expected, $data['yousen'][$key], "yousen.{$key}");
        }

        $this->assertSame($fx['shugoshin']['primary'], $data['shugoshin']['primary']);
        $this->assertSame($fx['shugoshin']['secondary'], $data['shugoshin']['secondary']);

        // 旧「甲＝大樹」人体星図へ回帰しない
        $this->assertNotSame('甲', $data['day_stem']);
        $this->assertNotSame($fx['rejected_narrative']['yousen']['north'], $data['yousen']['north']);
    }
}
