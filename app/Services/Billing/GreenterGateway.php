<?php

namespace App\Services\Billing;

use App\Enums\BillingMode;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Sale\Invoice;
use Greenter\See;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Thin, mockable wrapper around Greenter's See. Sending to SUNAT requires the
 * SOAP extension; signing only needs OpenSSL, so XML can be produced offline.
 */
class GreenterGateway
{
    /**
     * Normalise a certificate (PEM content, or a .p12/.pfx bundle) to PEM.
     */
    public function buildPem(string $certificatePath, ?string $password = null): string
    {
        $content = File::get($certificatePath);

        if ($this->isPem($content)) {
            return $content;
        }

        $certificate = new X509Certificate($content, (string) $password);

        return (string) $certificate->export(X509ContentType::PEM);
    }

    public function signXml(Invoice $invoice, string $pemCertificate): string
    {
        $see = $this->makeSee($pemCertificate, null, null, null);

        return (string) $see->getXmlSigned($invoice);
    }

    /**
     * @return array{success: bool, code: ?string, description: ?string, xml: ?string, cdr: ?string}
     */
    public function send(
        Invoice $invoice,
        string $pemCertificate,
        string $solUser,
        string $solPassword,
        BillingMode $mode,
    ): array {
        $see = $this->makeSee($pemCertificate, $invoice->getCompany()?->getRuc(), $solUser, $solPassword, $mode);

        try {
            $result = $see->send($invoice);
        } catch (Throwable $exception) {
            return $this->failure($exception->getMessage());
        }

        if ($result === null) {
            return $this->failure('SUNAT no devolvió respuesta.');
        }

        if (! $result instanceof BillResult) {
            return $this->failure('SUNAT no devolvió un resultado de comprobante.');
        }

        $cdr = $result->getCdrResponse();
        $error = $result->getError();

        return [
            'success' => (bool) $result->isSuccess(),
            'code' => $cdr?->getCode() ?? $error?->getCode(),
            'description' => $cdr?->getDescription() ?? $error?->getMessage(),
            'xml' => $see->getFactory()->getLastXml(),
            'cdr' => $result->getCdrZip(),
        ];
    }

    private function makeSee(
        string $pemCertificate,
        ?string $ruc,
        ?string $solUser,
        ?string $solPassword,
        ?BillingMode $mode = null,
    ): See {
        $see = new See;
        $see->setCertificate($pemCertificate);
        $see->setBuilderOptions(['cache' => false]);

        if ($ruc !== null && $solUser !== null && $solPassword !== null) {
            $see->setClaveSOL($ruc, $solUser, $solPassword);
        }

        if ($mode !== null) {
            $see->setService(config('restivo.sunat.'.($mode->isProduction() ? 'production' : 'beta')));
        }

        return $see;
    }

    /**
     * @return array{success: bool, code: null, description: string, xml: null, cdr: null}
     */
    private function failure(string $message): array
    {
        return [
            'success' => false,
            'code' => null,
            'description' => $message,
            'xml' => null,
            'cdr' => null,
        ];
    }

    private function isPem(string $content): bool
    {
        return str_contains($content, 'BEGIN CERTIFICATE')
            || str_contains($content, 'BEGIN PRIVATE KEY')
            || str_contains($content, 'BEGIN ENCRYPTED PRIVATE KEY');
    }
}
