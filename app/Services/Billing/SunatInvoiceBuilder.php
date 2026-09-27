<?php

namespace App\Services\Billing;

use App\Enums\DocumentType;
use App\Enums\IdentityDocumentType;
use App\Enums\OrderItemStatus;
use App\Enums\TaxType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TenantBillingSetting;
use App\Support\NumberToWords;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;

/**
 * Translates a Restaurant order into a Greenter SUNAT invoice (catalogue 01).
 *
 * Product prices are stored gross (IGV included), so the taxable base is derived
 * per line. Exonerated/unaftected lines keep their gross value as the sale value.
 */
class SunatInvoiceBuilder
{
    /**
     * @param  array{doc_type?: ?string, doc_number?: ?string, name?: ?string, address?: ?string}  $customer
     */
    public function build(
        Order $order,
        TenantBillingSetting $settings,
        DocumentType $type,
        string $series,
        int $correlativo,
        array $customer = [],
    ): Invoice {
        $rate = $settings->igvRate();
        $order->loadMissing('items.modifiers');

        $details = [];
        $gravadas = 0.0;
        $exoneradas = 0.0;
        $inafectas = 0.0;
        $igvTotal = 0.0;

        foreach ($order->items as $item) {
            if ($item->status === OrderItemStatus::Void) {
                continue;
            }

            $detail = new SaleDetail;
            $detail->setCodProducto((string) ($item->product_id ?? $item->id));
            $detail->setUnidad('NIU');
            $detail->setDescripcion($this->description($item));
            $detail->setCantidad((float) $item->quantity);
            $detail->setTipAfeIgv($item->tax_type->sunatCode());

            $quantity = (float) $item->quantity;
            $lineGross = (float) $item->line_total;
            $unitGross = $quantity > 0 ? $lineGross / $quantity : $lineGross;

            if ($item->tax_type->isTaxed()) {
                $unitValue = round($unitGross / (1 + $rate), 6);
                $base = round($unitValue * $quantity, 2);
                $igv = round($base * $rate, 2);

                $detail->setMtoValorUnitario($unitValue);
                $detail->setMtoBaseIgv($base);
                $detail->setPorcentajeIgv(round($rate * 100, 2));
                $detail->setIgv($igv);
                $detail->setMtoValorVenta($base);
                $detail->setTotalImpuestos($igv);

                $gravadas += $base;
                $igvTotal += $igv;
            } else {
                $base = round($unitGross * $quantity, 2);

                $detail->setMtoValorUnitario(round($unitGross, 6));
                $detail->setMtoBaseIgv(0);
                $detail->setPorcentajeIgv(0);
                $detail->setIgv(0);
                $detail->setMtoValorVenta($base);
                $detail->setTotalImpuestos(0);

                if ($item->tax_type === TaxType::Exonerado) {
                    $exoneradas += $base;
                } else {
                    $inafectas += $base;
                }
            }

            $detail->setMtoPrecioUnitario(round($unitGross, 6));
            $details[] = $detail;
        }

        $invoice = new Invoice;
        $invoice->setUblVersion('2.1');
        $invoice->setTipoOperacion('0101');
        $invoice->setTipoDoc((string) $type->sunatCode());
        $invoice->setSerie($series);
        $invoice->setCorrelativo((string) $correlativo);
        $invoice->setFechaEmision($order->paid_at ?? now());
        $invoice->setTipoMoneda(config('restivo.currency'));
        $invoice->setCompany($this->company($settings));
        $invoice->setClient($this->client($type, $customer));
        $invoice->setMtoOperGravadas(round($gravadas, 2));
        $invoice->setMtoOperExoneradas(round($exoneradas, 2));
        $invoice->setMtoOperInafectas(round($inafectas, 2));
        $invoice->setMtoIGV(round($igvTotal, 2));
        $invoice->setTotalImpuestos(round($igvTotal, 2));
        $invoice->setMtoImpVenta(round((float) $order->total, 2));
        $invoice->setDetails($details);
        $invoice->setLegends([
            (new Legend)
                ->setCode('1000')
                ->setValue(NumberToWords::soles((float) $order->total)),
        ]);

        return $invoice;
    }

    private function company(TenantBillingSetting $settings): Company
    {
        $address = (new Address)
            ->setUbigueo($settings->ubigeo)
            ->setDireccion($settings->address);

        return (new Company)
            ->setRuc($settings->ruc)
            ->setRazonSocial((string) $settings->business_name)
            ->setNombreComercial($settings->trade_name ?: $settings->business_name)
            ->setAddress($address);
    }

    /**
     * @param  array{doc_type?: ?string, doc_number?: ?string, name?: ?string, address?: ?string}  $customer
     */
    private function client(DocumentType $type, array $customer): Client
    {
        $docType = $customer['doc_type'] ?? null;
        $identity = $docType !== null ? IdentityDocumentType::tryFrom($docType) : null;
        $number = $customer['doc_number'] ?? null;
        $name = $customer['name'] ?? null;

        if ($identity === null && is_string($number) && $number !== '') {
            $identity = match (strlen($number)) {
                11 => IdentityDocumentType::Ruc,
                8 => IdentityDocumentType::Dni,
                default => null,
            };
        }

        if ($identity === null && $type === DocumentType::Boleta) {
            $identity = IdentityDocumentType::Other;
            $number = $number ?: '00000000';
        }

        if ($type === DocumentType::Boleta) {
            $name = $name ?: 'CLIENTES VARIOS';
        }

        return (new Client)
            ->setTipoDoc($identity?->sunatCode())
            ->setNumDoc($number)
            ->setRznSocial($name)
            ->setAddress(
                ($customer['address'] ?? null) !== null
                    ? (new Address)->setDireccion($customer['address'])
                    : null,
            );
    }

    private function description(OrderItem $item): string
    {
        $modifiers = $item->modifiers
            ->pluck('modifier_name')
            ->filter()
            ->implode(', ');

        return $modifiers !== ''
            ? $item->product_name.' ('.$modifiers.')'
            : $item->product_name;
    }
}
