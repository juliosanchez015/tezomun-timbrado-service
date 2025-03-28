<?php

namespace Tezomun\TimbradoService\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PhpCfdi\XmlCancelacion\Capsules\Cancellation;
use PhpCfdi\XmlCancelacion\Credentials;
use PhpCfdi\XmlCancelacion\Models\CancelDocument;
use PhpCfdi\XmlCancelacion\Models\CancelDocuments;
use PhpCfdi\XmlCancelacion\Signers\DOMSigner;

class CFDICancellationService
{
    /**
     * @var string rfc del emisor
     */
    protected $issuerRfc;
    /**
     * @var array array para convertir en xml string
     */
    protected $invoice;

    /**
     * Allowed cancellation reasons.
     */
    private const ALLOWED_REASONS = ['01', '02', '03', '04'];

    /**
     * @param string $issuerRfc rfc del emisor
     * @param array $invoice array de los elementos que traer la factura para el proceso de cancelación como
     *      - uuid: de la factura a cancelar
     *      - reason: razón de la cancelación 01,02,03... vienen descritos en la tabla reasons_cancel_invoices
     *      - replacementUuid: el folio de sustitución de la factura para relacionarlo, si es con errores (razón 01)
     *      - total: total del monto de la factura
     *      - rfcReceptor: rfc del receptor, del que se genero la factura
     */
    public function __construct(string $issuerRfc, array $invoice)
    {
        $this->issuerRfc = $issuerRfc;
        $this->invoice = $this->validateInvoices($invoice);
    }

    /**
     * Validate invoices array.
     *
     * @param array $invoice
     * @return array
     * @throws InvalidArgumentException
     */
    private function validateInvoices(array $invoice): array
    {
        if (!isset($invoice['uuid']) || !isset($invoice['reason']) || !isset($invoice['rfcReceptor']) || !isset($invoice['total'])) {
            throw new InvalidArgumentException('Each invoice must contain a "uuid" and a "reason"and a "rfcReceptor"and a "total".');
        }
        if (!in_array($invoice['reason'], self::ALLOWED_REASONS, true)) {
            throw new InvalidArgumentException("Invalid cancellation reason: {$invoice['reason']}. Allowed values are '01' or '02'.");
        }
        if ($invoice['reason'] === '01' && empty($invoice['replacementUuid'])) {
            throw new InvalidArgumentException("Reason '01' requires a replacement UUID.");
        }
        return $invoice;
    }

    /**
     * Generate the CFDI cancellation XML and return it as a string.
     *
     * @return string XML content as a string.
     */
    public function generateCancellationXml(): string
    {
        $cer = config('timbrado.csd_cer_pem');
        $cerPath = Storage::path("csd/$cer");
        $key = config('timbrado.csd_key');
        $keyPath = Storage::path("csd/$key");
        $pass = config('timbrado.csd_pass');
        // certificado, llave privada y clave de llave
        $credentials = new Credentials($cerPath, $keyPath, $pass);
        if (!empty($this->invoice['replacementUuid'])) {
            $cancelDocument = CancelDocument::newWithErrorsUnrelated($this->invoice['uuid']);
        } else {
            $cancelDocument = CancelDocument::newWithErrorsRelated($this->invoice['uuid'], $this->invoice['replacementUuid']);
        }
        // datos de cancelación
        $data = new Cancellation(
            $this->issuerRfc,
            new CancelDocuments($cancelDocument),
            new \DateTimeImmutable()
        );

        // generación del xml
        $xml = (new DOMSigner())->signCapsule($data, $credentials);
        return $xml;
    }
}
