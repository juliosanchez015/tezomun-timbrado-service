<?php

namespace Tezomun\TimbradoService\Services;

use Illuminate\Support\Facades\App;

class BaseService
{
    const XML ='XML';
    const CFDI ='cfdi';
    const ZIP ='ZIP';
    const TIMBRE ='TIMBRE';
    /**
     * @var string $svcUser
     * @description usuario de servicio en la cabecera
     */
    protected $svcUser;
    /**
     * @var string $svcPwd
     * @description password de servicio en la cabecera
     */
    protected $svcPwd;
    /**
     * @var bool activar
     * @description  log en laravel
     */
    public $log = false;
    /**
     * @var string $baseUrl
     * @description url para el ambiente de producción
     */
    protected $baseUrl;
    /**
     * @var string $baseUrlCancel
     * @description url para cancelación de producción, ya que cambia a diferencia de timbrado
     */
    protected $baseUrlCancel;
    /**
     * @var string $path
     * @description direccion para timbrar o para cancelar
     */
    protected $path;

    /**
     * @var $baseUrlSandbox
     * @description url para el ambiente de pruebas
     */
    protected $baseUrlSandbox;
    /**
     * @var $baseUrlSandbox
     * @description headers para petición general
     */
    protected $headers;

    public function __construct()
    {
        $this->svcUser = config('timbrado.user');
        $this->svcPwd = config('timbrado.password');
        $this->baseUrl = config('timbrado.url');
        $this->baseUrlCancel = config('timbrado.url_cancel');
        $this->baseUrlSandbox = config('timbrado.url_sandbox');
        $this->headers = [
            "Expect:",
            "Content-Type: text/xml",
            "usrws: {$this->svcUser}",
            "pwdws: {$this->svcPwd}",
        ];
    }

    /**
     * @return string
     * @description obtener la url completa de la petición
     */
    protected function getTimbradoUrl(): string
    {
        // Verificar si el entorno es producción
        if (App::environment('production')) {
            // URL para producción
            return "{$this->baseUrl}/{$this->path}";
        } else {
            // URL para pruebas
            return "{$this->baseUrlSandbox}/{$this->path}";
        }
    }

    /**
     * @description Función de apoyo para parsear la respuesta de HTTP regresada por CURL
     * @param string $response respuesta de petición curl para poder parsear
     * @param $headerSize
     * @return array
     *
     */
    function parse_response(string $response, $headerSize): array
    {
        $header_text = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $headers = [];
        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else {
                $rep = str_replace("ERROR:", "ERROR", $line);
                if (!empty($rep)) {
                    list ($key, $value) = explode(': ', $rep);
                    $headers[$key] = $value;
                }
            }
        return array($body, $headers);
    }
}