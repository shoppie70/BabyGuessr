<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\NineStarKiCalculator;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;
use Tests\TestCase;

class NineStarKiCalculatorTest extends TestCase
{
    protected NineStarKiCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new NineStarKiCalculator();
    }

    public function test_calculates_nine_star_ki_from_verified_fixture(): void
    {
        $fx = VerifiedFortuneFixture::section('nine_star_ki');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        $this->assertSame($fx['year']['expected'], $data['honmei_star']['name']);
        $this->assertSame($fx['month']['expected'], $data['getsumei_star']['name']);
        $this->assertSame($fx['day']['expected'], $data['nichimei_star']['name']);
        $this->assertSame($fx['keikyu']['expected'], $data['keikyu']['name']);
        $this->assertSame($fx['dokai']['expected'], $data['dokai']['name']);

        $this->assertNotNull($data['solar_time_correction']);
        $this->assertSame($fx['hour']['expected'], $data['solar_time_correction']['time_nine_star']);

        // 日家≠時家（旧資料は日命星を五黄としていた）
        $this->assertNotSame($fx['hour']['expected'], $data['nichimei_star']['name']);
        $this->assertSame($fx['hour']['expected'], $data['solar_time_correction']['time_nine_star']);
    }

    public function test_handles_missing_birth_time_gracefully(): void
    {
        $fx = VerifiedFortuneFixture::section('nine_star_ki');
        $result = $this->calculator->calculate(VerifiedFortuneFixture::inputWithoutTime());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;
        $this->assertSame($fx['year']['expected'], $data['honmei_star']['name']);
        $this->assertSame($fx['month']['expected'], $data['getsumei_star']['name']);
        $this->assertSame($fx['keikyu']['expected'], $data['keikyu']['name']);
        $this->assertNull($data['solar_time_correction']);
    }
}
