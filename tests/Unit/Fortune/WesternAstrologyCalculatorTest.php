<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\WesternAstrologyCalculator;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;
use Tests\TestCase;

class WesternAstrologyCalculatorTest extends TestCase
{
    protected WesternAstrologyCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new WesternAstrologyCalculator();
    }

    public function test_natal_chart_matches_verified_fixture_and_jpl_horizons(): void
    {
        $fx = VerifiedFortuneFixture::section('western_astrology');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        foreach ($fx['planets'] as $name => $expected) {
            $planet = $data['planets'][$name];
            $this->assertSame($expected['sign'], $planet['sign'], "{$name} sign");
            $this->assertSame($expected['degree'], $planet['degree'], "{$name} degree-in-sign");
            $this->assertSame($expected['house'], $planet['house'], "{$name} house");

            $deltaCalc = abs($planet['longitude'] - $expected['longitude']);
            $this->assertLessThanOrEqual(
                $expected['tolerance'],
                $deltaCalc,
                "{$name} vs fixture longitude Δ={$deltaCalc}"
            );

            $deltaHorizons = abs($planet['longitude'] - $expected['horizons_longitude']);
            $this->assertLessThanOrEqual(
                $expected['tolerance'],
                $deltaHorizons,
                "{$name} vs JPL Horizons Δ={$deltaHorizons}"
            );
        }

        $asc = $data['angles']['ascendant'];
        $this->assertSame($fx['angles']['ascendant']['sign'], $asc['sign']);
        $this->assertSame($fx['angles']['ascendant']['degree'], $asc['degree']);
        $this->assertLessThanOrEqual(
            $fx['angles']['ascendant']['tolerance'],
            abs($asc['longitude'] - $fx['angles']['ascendant']['longitude'])
        );

        $mc = $data['angles']['midheaven'];
        $this->assertSame($fx['angles']['midheaven']['sign'], $mc['sign']);
        $this->assertSame($fx['angles']['midheaven']['degree'], $mc['degree']);

        $this->assertCount(10, $data['planets']);
        $this->assertCount(12, $data['houses']);
        $this->assertNotEmpty($data['aspects']);

        // Narrative Reference の誤位置へ回帰しない
        $this->assertNotSame('Scorpio', $data['planets']['Moon']['sign']);
        $this->assertNotSame('Aries', $data['planets']['Mercury']['sign']);
        $this->assertNotSame('Aquarius', $data['planets']['Mars']['sign']);
        $this->assertNotSame('Gemini', $data['planets']['Uranus']['sign']);
    }

    public function test_handles_missing_birth_time_as_partial(): void
    {
        $fx = VerifiedFortuneFixture::section('western_astrology');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::inputWithoutTime());

        $this->assertSame(FortuneCalculationResult::STATUS_PARTIAL, $result->status);
        $data = $result->data;
        $this->assertSame($fx['planets']['Sun']['sign'], $data['planets']['Sun']['sign']);
        $this->assertSame($fx['planets']['Sun']['degree'], $data['planets']['Sun']['degree']);
        $this->assertNull($data['angles']);
        $this->assertNull($data['houses']);
    }
}
