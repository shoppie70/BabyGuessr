<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\ZiWeiDouShuCalculator;
use App\Services\Fortune\DTOs\FortuneCalculationResult;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;
use Tests\TestCase;

class ZiWeiDouShuCalculatorTest extends TestCase
{
    protected ZiWeiDouShuCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ZiWeiDouShuCalculator();
    }

    public function test_calculates_zi_wei_from_verified_fixture(): void
    {
        $fx = VerifiedFortuneFixture::section('zi_wei_dou_shu');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        $this->assertSame($fx['lunar_date']['ganzhi_year'], $data['lunar_date']['ganzhi_year']);
        $this->assertSame($fx['lunar_date']['month'], $data['lunar_date']['month']);
        $this->assertSame($fx['lunar_date']['day'], $data['lunar_date']['day']);
        $this->assertSame($fx['lunar_date']['time_zhi'], $data['lunar_date']['time_zhi']);

        $this->assertSame($fx['ming_zhu'], $data['ming_zhu']);
        $this->assertSame($fx['shen_zhu'], $data['shen_zhu']);
        $this->assertSame($fx['ming_gong'], $data['ming_gong']);
        $this->assertSame($fx['shen_gong'], $data['shen_gong']);

        $this->assertSame($fx['main_stars']['ming_gong'], $data['main_stars']['ming_gong']);
        $this->assertSame($fx['main_stars']['guan_lu_gong'], $data['main_stars']['guan_lu_gong']);

        foreach ($fx['palaces'] as $name => $zhi) {
            $this->assertSame($zhi, $data['palaces'][$name], $name);
        }

        foreach ($fx['si_hua'] as $key => $star) {
            $this->assertSame($star, $data['si_hua'][$key], $key);
        }
    }

    public function test_handles_missing_birth_time_as_unavailable(): void
    {
        $result = $this->calculator->calculate(VerifiedFortuneFixture::inputWithoutTime());

        $this->assertSame(FortuneCalculationResult::STATUS_UNAVAILABLE, $result->status);
        $this->assertSame('BIRTH_TIME_REQUIRED', $result->errorCode);
    }
}
