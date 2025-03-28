<?php

namespace Tezomun\TimbradoService\Models;

class TimbradoResponse
{
    /**
     * @var int $code
     * @description código de respuesta de la cabecera
     */
    public int $code;
    /**
     * @var int $total_code
     * @description total de código de respuestas si existen mas de 1
     */
    public int $total_code;
    /**
     * @var string $message
     * @description mensaje de error si existe cuando un código de respuesta sea mayor a cero
     */
    public string $message;
    /**
     * @var string $body
     * @description contenido de la respuesta, debe ser un string y contener el xml
     */
    public string $body;
    /**
     * @var string $error
     * @description mensaje de error cuando un código de respuesta sea mayor a cero
     */
    public string $error;
    /**
     * @var string $uuid
     * @description cuando el código es cero regresa el uuid se le asigna a esta variable
     */
    public string $uuid;
    /**
     * @var string url
     * @description url de la petición para saber si es produccion o pruebas
     */
    public string $url;
    /**
     * @var string $env
     * @description saber si es producción o pruebas
     */
    public string $env;

    /**
     * @return bool
     * @description si fue correcta la petición o no
     */
    public function isSuccess(): bool
    {
        return $this->code === 0;
    }
}