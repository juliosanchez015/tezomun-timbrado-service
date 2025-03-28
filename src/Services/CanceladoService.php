<?php

namespace Tezomun\TimbradoService\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Tezomun\TimbradoService\Models\CancelRequest;
use Tezomun\TimbradoService\Models\TimbradoResponse;

class CanceladoService extends BaseService
{

    /**
     * @return string
     * @description obtener la url completa de la petición
     */
    protected function getTimbradoUrl(): string
    {
        // Verificar si el entorno es producción
        if (App::environment('production')) {
            // URL para producción
            return "{$this->baseUrlCancel}/{$this->path}";
        } else {
            // URL para pruebas
            return "{$this->baseUrlSandbox}/{$this->path}";
        }
    }
    /**
     * @param CancelRequest $cancelRequest
     * @return TimbradoResponse
     */
    public function timbrar(CancelRequest $cancelRequest): TimbradoResponse
    {
        $this->headers = [
            "Expect:",
            "Content-Type: text/plain",
            "usrws: {$this->svcUser}",
            "pwdws: {$this->svcPwd}",
        ];
        if (!isset($cancelRequest->xml_string)) {
            throw new \InvalidArgumentException("CanceladoService::timbrar:el xml string debe de existir y debe de venir lleno");
        }
        if (empty($cancelRequest->xml_string)) {
            throw new \InvalidArgumentException("CanceladoService::timbrar:el xml string debe de existir y debe de venir lleno");
        }
        $this->path = "cancela4/cancelarXml";
        $cancelRequest->url = $this->getTimbradoUrl();
        $cancelRequest->type = parent::CFDI;
        return $this->sendRequest($cancelRequest);
    }

    /**
     * @param CancelRequest $cancelRequest
     * @return TimbradoResponse
     */
    protected function sendRequest(CancelRequest $cancelRequest): TimbradoResponse
    {
        try {
            // Validación manual
            if (
                empty($cancelRequest->type) ||
                empty($cancelRequest->url) ||
                empty($cancelRequest->sender_email) ||
                empty($cancelRequest->recipient_rfc) ||
                empty($cancelRequest->total) ||
                empty($cancelRequest->type_cfdi) ||
                empty($cancelRequest->xml_string)
            ) {
                Log::debug("InvalidArgumentException------------>");
                Log::debug("CanceladoService::sendRequest:Los campos de CancelRequest
                type[$cancelRequest->type],url[$cancelRequest->url],sender_email[$cancelRequest->sender_email],
                recipient_rfc[$cancelRequest->recipient_rfc],total[$cancelRequest->total],
                type_cfdi[$cancelRequest->type_cfdi]
                data[] son requeridos.");
                throw new \InvalidArgumentException("CanceladoService::sendRequest:Los campos de CancelRequest
                type[$cancelRequest->type],url[$cancelRequest->url],sender_email[$cancelRequest->sender_email],
                recipient_rfc[$cancelRequest->recipient_rfc],total[$cancelRequest->total],
                type_cfdi[$cancelRequest->type_cfdi]
                data[] son requeridos.");
            }
            if(!in_array($cancelRequest->type_cfdi,CancelRequest::VOUCHER_TYPE)){
                Log::debug("InvalidArgumentException------------>");
                Log::debug("CanceladoService::sendRequest:el campotype_cfdi  no pertenece a los registrados.");
                throw new \InvalidArgumentException("CanceladoService::sendRequest:el campotype_cfdi  no pertenece a los registrados.");
            }
            $this->headers[] = "tipo: {$cancelRequest->type}";
            if (!empty($cancelRequest->sender_email)) {
                $this->headers[] = "emaile: {$cancelRequest->sender_email}";
            }
            if (!empty($cancelRequest->recipient_email)) {

                $this->headers[] = "emailr: {$cancelRequest->recipient_email}";
            }
            if (!empty($cancelRequest->recipient_rfc)) {
                $this->headers[] = "rfcr: {$cancelRequest->recipient_rfc}";
            }
            if (!empty($cancelRequest->type_cfdi)) {
                $this->headers[] = "tipocfdi: {$cancelRequest->type_cfdi}";
            }
            if (!empty($cancelRequest->total)) {
                $this->headers[] = "total: {$cancelRequest->total}";
            }
            if ($this->log) {
                Log::debug("headers---->");
                Log::debug($this->headers);
            }
            $ch = curl_init($cancelRequest->url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $cancelRequest->xml_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                $this->headers
            );
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            $response = curl_exec($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            if ($this->log) {
                Log::debug("response------------>");
                Log::debug($response);
            }
            list($response_body, $headers) = $this->parse_response($response, $headerSize);
            if ($this->log) {
                Log::debug("headers------------>");
                Log::debug($headers);
                Log::debug("$response_body------------>");
                Log::debug($response_body);
            }
            // Obtener headers y cuerpo de la respuesta
            $code = $headers["codigo"] ?? -1;
            $code_int = intval($code);

            $timbradoResponse = new TimbradoResponse();
            $timbradoResponse->code = $code_int;
            $timbradoResponse->url = $cancelRequest->url;

            $timbradoResponse->env = str_contains($cancelRequest->url, 'pruebas')
                ? 'pruebas'
                : 'produccion';
            if ($code_int !== 0) {
                $message = isset($headers['errmsg']) ? $headers['errmsg'] : 'Error desconocido';
                $total_code = $code;
                $timbradoResponse->message = $message;
                $timbradoResponse->body = "";
                $timbradoResponse->error = true;
                $timbradoResponse->total_code = $total_code;
            } else {
                $timbradoResponse->message = "";
                $timbradoResponse->body = $response_body;
                $timbradoResponse->error = false;
            }
            return $timbradoResponse;
        } catch (\Exception $exception) {
            if ($this->log) {
                Log::debug("Exception------------>");
                Log::debug($exception->getTraceAsString());
                Log::debug($exception->getMessage());
            }
            throw new \InvalidArgumentException("CanceladoService::sendRequest:Error al generar la cancelacion" . $exception->getMessage());
        }
    }
}
