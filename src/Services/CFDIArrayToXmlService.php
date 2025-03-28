<?php

namespace Tezomun\TimbradoService\Services;

use CfdiUtils\CadenaOrigen\DOMBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use CfdiUtils\XmlResolver\XmlResolver;
use CfdiUtils\Cfdi;

class CFDIArrayToXmlService
{
    /**
     * @var array resultado de errores
     */
    public $error = [];
    /**
     * @var string mensaje cuando existe un error
     */
    public $error_string = "";

    /**
     * Convierte un array en un string XML compatible con CFDI 4.0 de manera dinámica.
     *
     * @param array $data Array con la información del CFDI.
     * @return string XML en formato CFDI 4.0.
     */
    public function convert(array $data): string
    {
        try {

            $data = $this->capitalizeKeys($data);
            $cer = config('timbrado.csd_cer');
            $cerPath = Storage::path("csd/$cer");
            $certificado = new \CfdiUtils\Certificado\Certificado($cerPath);
            $comprobanteAtributos = [
                'Serie' => $data['Comprobante']["Serie"],
                'Folio' => $data['Comprobante']["Folio"],
                'Fecha' => $data['Comprobante']["Fecha"],
                'TipoDeComprobante' => $data['Comprobante']["TipoDeComprobante"],
                'Moneda' => $data['Comprobante']["Moneda"],
                'TipoCambio' => $data['Comprobante']["TipoCambio"],
                'SubTotal' => $data['Comprobante']["SubTotal"],
                'Descuento' => $data['Comprobante']["Descuento"],
                'Total' => $data['Comprobante']["Total"],
                'MetodoPago' => $data['Comprobante']["MetodoPago"],
                'FormaPago' => $data['Comprobante']["FormaPago"],
                'LugarExpedicion' => $data['Comprobante']["LugarExpedicion"],
                'Exportacion' => $data['Comprobante']["Exportacion"],
                // y otros atributos más...
            ];

            $myLocalResourcePath = Storage::path("sat/");;
            $myResolver = new \CfdiUtils\XmlResolver\XmlResolver($myLocalResourcePath);

            $creator = new \CfdiUtils\CfdiCreator40($comprobanteAtributos, $certificado);
            $creator->setXmlResolver($myResolver);
            $comprobante = $creator->comprobante();

            // No agrego (aunque puedo) el Rfc y Nombre porque uso los que están establecidos en el certificado
            $comprobante->addEmisor([
                'RegimenFiscal' => $data['Comprobante']["Emisor"]["RegimenFiscal"], // General de Ley Personas Morales
            ]);

            if (isset($data['Comprobante']["CfdiRelacionados"])) {
                if (!empty($data['Comprobante']["CfdiRelacionados"]["UuiDs"][0])) {
                    // Agregar nodo de CfdiRelacionados con el UUID de la factura original
                    $comprobante->addCfdiRelacionados([
                        'TipoRelacion' => $data['Comprobante']["CfdiRelacionados"]["TipoRelacion"] // Sustitución de CFDI previos
                    ])->addCfdiRelacionado([
                        'UUID' => $data['Comprobante']["CfdiRelacionados"]["UuiDs"][0] // UUID del CFDI relacionado
                    ]);
                }
            }
            $comprobante->addReceptor($data['Comprobante']["Receptor"]);
            foreach ($data['Comprobante']["Conceptos"] as $concepto) {
                $conceptArray = [
                    'ClaveProdServ' => $concepto["ClaveProdServ"], // Clave del producto o servicio
                    'NoIdentificacion' => $concepto["NoIdentificacion"], // Identificador del producto
                    'Cantidad' => $concepto["Cantidad"], // Cantidad del producto
                    'ClaveUnidad' => $concepto["ClaveUnidad"], // Clave de la unidad de medida
                    'Unidad' => $concepto["Unidad"], // Unidad de medida
                    'Descripcion' => $concepto["Descripcion"], // Descripción del producto
                    'ValorUnitario' => $concepto["ValorUnitario"], // Precio unitario
                    'Importe' => $concepto["Importe"], // Importe total del concepto (cantidad * valor unitario)
                    'ObjetoImp' => $concepto["ObjetoImp"], // Objeto del impuesto (01 = Producto o servicio sujeto a impuestos)
                ];
                if(isset($concepto["Descuento"])){
                    $conceptArray["Descuento"]=$concepto["Descuento"];
                }
                $concept = $comprobante->addConcepto($conceptArray);
                if (isset($concepto["Impuestos"]) && count($concepto["Impuestos"]) > 0) {
                    foreach ($concepto["Impuestos"] as $impuesto) {
                        $concept->addTraslado([
                            'Base' => $impuesto["Base"], // Base del impuesto
                            'Impuesto' => $impuesto["Impuesto"], // Código del impuesto (002 = IVA)
                            'TipoFactor' => $impuesto["TipoFactor"], // Puede ser 'Tasa', 'Cuota' o 'Exento'
                            'TasaOCuota' => str_pad($impuesto["TasaOCuota"], 8, "0", STR_PAD_RIGHT), // 16% (Expresado como 0.16)
                            'Importe' => $impuesto["Importe"], // Cálculo del IVA
                        ]);
                        if (isset($concepto["Descuento"])) {
                            if ($concepto["Descuento"] > 0) {
                                $concept->addRetencion([
                                    'Base' => $concepto["Descuento"], // Base del impuesto
                                    'Impuesto' => $impuesto["Impuesto"], // Código del impuesto (002 = IVA)
                                    'TipoFactor' => $impuesto["TipoFactor"], // Puede ser 'Tasa', 'Cuota' o 'Exento'
                                    'TasaOCuota' => str_pad("0.00", 8, "0", STR_PAD_RIGHT), // 16% (Expresado como 0.16)
                                    'Importe' => 0.00, // Cálculo del IVA
                                ]);
                            }
                        }
                    }
                }

            }


            // método de ayuda para establecer las sumas del comprobante e impuestos
            // con base en la suma de los conceptos y la agrupación de sus impuestos
            $creator->addSumasConceptos(null, 2);

            // método de ayuda para generar el sello (obtener la cadena de origen y firmar con la llave privada)
            $csdKey = config("timbrado.csd_key_pem"); // Replace with actual passphrase
            $keyPath = Storage::get("csd/$csdKey");
            $passphrase = config("timbrado.csd_pass");
            $creator->addSello($keyPath, $passphrase);

            // método de ayuda para mover las declaraciones de espacios de nombre al nodo raíz
            $creator->moveSatDefinitionsToComprobante();

            // método de ayuda para validar usando las validaciones estándar de creación de la librería
            $asserts = $creator->validate();
            if ($asserts->hasErrors()) { // contiene si hay o no errores
                $this->error = collect($asserts->errors())->toArray();
                Log::error($this->error);
                $this->error_string = "CFDIArrayToXmlService::convert:La factura contiene erroores para poderse enviar ";
                foreach ($this->error as $error) {
                    Log::error("Código: {$error->getCode()} | Mensaje: {$error->getExplanation()}");
                    $this->error_string .="Código: {$error->getCode()} | Mensaje: {$error->getExplanation()}";
                }
                return false;
            }

            // método de ayuda para generar el xml y guardar los contenidos en un archivo
            //$creator->saveXml('... lugar para almacenar el cfdi ...');
            // método de ayuda para generar el xml y retornarlo como un string
            return $creator->asXml();
        } catch (\Exception $e) {
            Log::error("CFDIArrayToXmlService::convert: Error ->" . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->error_string = $e->getMessage();
            $this->error = $e->getTrace();
            return false;
        }
    }

    /**
     * @param array $array
     * @return array
     */
    public function capitalizeKeys(array $array): array
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $newKey = ucfirst($key); // Convertir la primera letra en mayúscula
            // Si el valor es un array, aplicar la función recursivamente
            if (is_array($value)) {
                $newArray[$newKey] = $this->capitalizeKeys($value);
            } else {
                $newArray[$newKey] = $value;
            }
        }
        return $newArray;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getErrorString(): string
    {
        return $this->error_string;
    }

}
