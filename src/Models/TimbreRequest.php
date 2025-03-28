<?php

namespace Tezomun\TimbradoService\Models;

class TimbreRequest
{
    /**
     * @var string $type
     * @description
     */
    public string $type;
    /**
     * @var string $url
     * @description dominio con path para generar la url de la petición
     */
    public string $url;
    /**
     * @var string $xml_string
     * @description xml de la petición viene en un string
     */
    public string $xml_string;
}