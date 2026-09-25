<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\FourPillarsCalculator;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;
use Tests\TestCase;

class FourPillarsCalculatorTest extends TestCase
{
    protected FourPillarsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FourPillarsCalculator();
    }

    public function test_calculates_four_pillars_from_verified_fixture(): void
    {
        $fx = VerifiedFortuneFixture::section('four_pillars');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        $this->assertSame($fx['year']['expected'], $data['year_pillar']['pillar']);
        $this->assertSame($fx['ten_god']['year'], $data['year_pillar']['ten_god_gan']);

        $this->assertSame($fx['month']['expected'], $data['month_pillar']['pillar']);
        $this->assertSame($fx['ten_god']['month'], $data['month_pillar']['ten_god_gan']);

        $this->assertSame($fx['day']['expected'], $data['day_pillar']['pillar']);
        $this->assertSame($fx['day_master']['expected'], $data['day_master']['gan']);
        $this->assertSame($fx['day_master']['element'], $data['day_master']['element']);

        $this->assertSame($fx['hour']['expected'], $data['time_pillar']['pillar']);
        $this->assertSame($fx['ten_god']['hour'], $data['time_pillar']['ten_god_gan']);

        $relationTypes = array_column($data['special_relations'], 'type');
        foreach ($fx['special_relations'] as $type) {
            $this->assertContains($type, $relationTypes);
        }

        // Narrative Reference（旧日柱/旧時柱）へ回帰しないこと
        $this->assertNotSame('旧日柱', $data['day_pillar']['pillar']);
        $this->assertNotSame('旧時柱', $data['time_pillar']['pillar']);
    }

    public function test_handles_missing_birth_time_as_partial(): void
    {
        $fx = VerifiedFortuneFixture::section('four_pillars');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::inputWithoutTime());

        $this->assertSame(FortuneCalculationResult::STATUS_PARTIAL, $result->status);
        $data = $result->data;
        $this->assertSame($fx['year']['expected'], $data['year_pillar']['pillar']);
        $this->assertSame($fx['month']['expected'], $data['month_pillar']['pillar']);
        $this->assertSame($fx['day']['expected'], $data['day_pillar']['pillar']);
        $this->assertNull($data['time_pillar']);
    }
}
