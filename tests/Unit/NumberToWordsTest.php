<?php

namespace Tests\Unit;

use App\Support\NumberToWords;
use PHPUnit\Framework\TestCase;

class NumberToWordsTest extends TestCase
{
    public function test_it_says_zero(): void
    {
        $this->assertSame('SON CERO CON 00/100 SOLES', NumberToWords::soles(0));
    }

    public function test_it_says_amounts_with_cents(): void
    {
        $this->assertSame('SON DOSCIENTOS TREINTA Y SEIS CON 50/100 SOLES', NumberToWords::soles(236.5));
    }

    public function test_it_says_one_thousand(): void
    {
        $this->assertSame('SON MIL CON 00/100 SOLES', NumberToWords::soles(1000));
    }

    public function test_it_says_millions(): void
    {
        $this->assertSame('SON UN MILLON QUINIENTOS MIL CON 00/100 SOLES', NumberToWords::soles(1500000));
    }
}
