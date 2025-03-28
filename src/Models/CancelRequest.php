<?php

namespace Tezomun\TimbradoService\Models;

class CancelRequest
{

    /**
     * @var string $type_cfdi
     * @description Debe ser "reten" o "cfdi"
     */
    public string $type;
    /**
     * @var string $url
     * @description url petición
     */
    public string $url;
    /**
     * @var string $total
     * @description Es el Total en “Comprobante”
     */
    public string $total;
    /**
     * @var string $type_cfdi
     * @description Es el TipoDeComprobante
     */
    public string $type_cfdi;
    /**
     * @var string $xml_string
     * @description xml en string
     */
    public string $xml_string;
    /**
     * @var string  $sender_email
     * @description opcional email del emisor
     */
    public string $sender_email;
    /**
     * @var string $recipient_email
     * @description opcional email del receptor (Opcional)
     */
    public string $recipient_email;
    /**
     * @var string $recipient_rfc
     * @description RFC del receptor
     */
    public string $recipient_rfc;
    /**
     * @var string
     */
    public string $cancellation_type;

    /**
     * @description tipos de comprobante:
     * • I – Ingreso
     * • E -Egreso
     * • N – Nomina
     * • P – Pago
     * • T – Traslado
     */
    const VOUCHER_TYPE = ["I","E","N","P","T"];

}