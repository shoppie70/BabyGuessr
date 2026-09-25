<?php

namespace Tests\Unit\Fortune;

use App\Services\Fortune\Calculators\ShukuyoCalculator;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Fortune\VerifiedFortuneFixture;

class ShukuyoCalculatorTest extends TestCase
{
    public function test_calculates_shukuyo_from_verified_fixture(): void
    {
        $fx = VerifiedFortuneFixture::section('shukuyo');
        $result = (new ShukuyoCalculator())->calculate(VerifiedFortuneFixture::input());

        $this->assertTrue($result->isCompleted());
        $data = $result->data;

        $this->assertSame($fx['lunar_date']['year'], $data['lunar_date']['year']);
        $this->assertSame($fx['lunar_date']['month'], $data['lunar_date']['month']);
        $this->assertSame($fx['lunar_date']['day'], $data['lunar_date']['day']);
        $this->assertSame($fx['lunar_date']['ganzhi_year'], $data['lunar_date']['ganzhi_year']);
        $this->assertSame($fx['lunar_date']['expected'], $data['lunar_date']['display']);

        $this->assertSame($fx['weekday']['expected'], $data['weekday']);
        $this->assertStringContainsString('太陰星', $data['yousei']);

        $this->assertSame($fx['honmei_shuku']['name'], $data['honmei_shuku']['name']);
        $this->assertSame($fx['honmei_shuku']['category'], $data['honmei_shuku']['category']);
        $this->assertSame($fx['honmei_shuku']['palace'], $data['honmei_shuku']['palace']);
        $this->assertSame($fx['honmei_shuku']['zodiac'], $data['honmei_shuku']['zodiac']);
    }

    public function test_lunar_date_via_independent_lunar_php_path(): void
    {
        $fx = VerifiedFortuneFixture::section('shukuyo');
        $solar = \com\nlf\calendar\Solar::fromYmd(2026, 4, 6);
        $lunar = $solar->getLunar();

        $this->assertSame($fx['lunar_date']['month'], $lunar->getMonth());
        $this->assertSame($fx['lunar_date']['day'], $lunar->getDay());
        $this->assertSame('心', $lunar->getXiu());
        $this->assertSame(1, $solar->getWeek()); // Monday
    }
}
