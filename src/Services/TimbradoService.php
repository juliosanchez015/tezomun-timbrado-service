<?php

namespace Tezomun\TimbradoService\Services;

use Illuminate\Support\Facades\Log;
use Tezomun\TimbradoService\Models\TimbradoResponse;
use Tezomun\TimbradoService\Models\TimbreRequest;

class TimbradoService extends BaseService
{

    /**
     * @param TimbreRequest $timbreRequest
     * @return TimbradoResponse
     */
    public function timbrar(TimbreRequest $timbreRequest): TimbradoResponse
    {
        if(!isset($timbreRequest->xml_string)){
            throw new \InvalidArgumentException("el xml string debe de existir y debe de venir lleno");
        }
        if(empty($timbreRequest->xml_string)){
            throw new \InvalidArgumentException("el xml string debe de existir y debe de venir lleno");
        }
        $this->path = "timbre4/timbrarv5";
        $timbreRequest->url =$this->getTimbradoUrl();
        $timbreRequest->type = parent::XML;
        return $this->sendRequest($timbreRequest);
    }

    /**
     * @param TimbreRequest $timbreRequest
     * @return TimbradoResponse
     */
    protected function sendRequest(TimbreRequest $timbreRequest): TimbradoResponse
    {
        // Validación manual
        if (
            empty($timbreRequest->type) ||
            empty($timbreRequest->url) ||
            empty($timbreRequest->xml_string)
        ) {
            throw new \InvalidArgumentException("Los campos de CancelRequest type[$timbreRequest->type],url[$timbreRequest->url]data[] son requeridos.");
        }

        $this->headers[] = "tipo: $timbreRequest->type";

        $ch = curl_init($timbreRequest->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $timbreRequest->xml_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $this->headers
        );
        if ($this->log) {
            Log::debug("headers---->");
            Log::debug($this->headers);
        }
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
        // Obtener headers y cuerpo de la respuesta
        if ($this->log) {
            Log::debug("headers------------>");
            Log::debug($headers);
            Log::debug("$response_body------------>");
            Log::debug($response_body);
        }
        $code = $headers['codigo'][0] ?? -1;
        $code_int = intval($code);

        $timbradoResponse = new TimbradoResponse();
        $timbradoResponse->code = $code_int;
        $timbradoResponse->url = $timbreRequest->url;

        $uuid = $headers['uuid'] ?? "";
        $timbradoResponse->uuid = $uuid;
        $timbradoResponse->env = str_contains($timbreRequest->url, 'pruebas')
            ? 'pruebas'
            : 'produccion';

        if ($code_int !== 0 && empty($uuid)) {
            if(is_array($headers['errmsg'])){
                $message = count($headers['errmsg']) ? implode(",", $headers['errmsg']) : 'Error desconocido';
            }else{
                $message = $headers['errmsg'];
            }
            if(is_array($headers['codigo'])){
            $total_code = count($headers['codigo']) ? implode(",", $headers['codigo']) : "";
            }else{
                $total_code = $headers['codigo'];
            }
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
    }
}