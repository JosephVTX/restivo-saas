<?php

namespace App\Services\Orders;

use App\Enums\TaxType;

/**
 * Extracts the IGV amount from gross (IGV-included) prices.
 *
 * Products are stored with the final price the customer pays, so the taxable
 * base and the IGV are derived from it depending on the tax affectation.
 */
class TaxCalculator
{
    public function igvFromGross(float $gross, TaxType $taxType): float
    {
        if (! $taxType->isTaxed()) {
            return 0.0;
        }

        $rate = (float) config('restivo.igv_rate', 0.18);
        $base = $gross / (1 + $rate);

        return round($gross - $base, 2);
    }
}
