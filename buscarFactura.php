<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

// Inicializar el cliente Guzzle con autenticación
$client = new Client([
    'base_uri' => 'https://apisandbox.facturama.mx/3/cfdis', // URL base del entorno de pruebas
    'auth' => ['FelipeRivera', 'Felipollo123'] // Autenticación básica (usuario y contraseña)
]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['op1'])) {
        if ($_POST['op1'] === 'RFC' && isset($_POST['rfc'])) {
            // Buscar por RFC
            $rfc = $_POST['rfc'];
            try {
                // Obtener clientes y filtrar por RFC
                $response = $client->get('client');
                $clientes = json_decode($response->getBody());

                $clienteId = null;
                foreach ($clientes as $cliente) {
                    if ($cliente->Rfc === $rfc) {
                        $clienteId = $cliente->Id;
                        break;
                    }
                }

                if ($clienteId) {
                    // Obtener todas las facturas del cliente
                    $response = $client->get('cfdi', [
                        'query' => [
                            'type' => 'issued',
                            'rfc' => $rfc
                        ]
                    ]);

                    $facturas = json_decode($response->getBody());

                    // Filtrar facturas por ID del cliente
                    $facturasCliente = array_filter($facturas, function ($factura) use ($clienteId) {
                        return $factura->Customer->Id === $clienteId;
                    });

                    if (!empty($facturasCliente)) {
                        foreach ($facturasCliente as $factura) {
                            echo "Factura encontrada: " . $factura->Id . "<br>";

                            // Validar si la propiedad 'Complement' existe y contiene 'TaxStamp'
                            if (isset($factura->Complement->TaxStamp->Uuid)) {
                                $uuid = $factura->Complement->TaxStamp->Uuid;
                                echo '<a href="descargarFactura.php?uuid=' . $uuid . '" target="_blank">Imprimir Factura (PDF)</a><br><br>';
                            } else {
                                echo "No se encontró el UUID para la factura con ID: " . $factura->Id . ".<br>";
                            }
                        }
                    } else {
                        echo "No se encontraron facturas para este RFC.";
                    }
                } else {
                    echo "No se encontró ningún cliente con este RFC.";
                }
            } catch (Exception $e) {
                echo "Error en la API: " . $e->getMessage();
                echo "<pre>";
                print_r($e); // Imprimir la excepción completa para depuración
                echo "</pre>";
            }
        } elseif ($_POST['op1'] === 'Folio Factura' && isset($_POST['folio'])) {
            // Buscar por Folio
            $folio = $_POST['folio'];

            try {
                // Solicitar facturas filtrando por folio
                $response = $client->get('cfdi', [
                    'query' => [
                        'type' => 'issued',
                        'folioStart' => $folio
                    ]
                ]);

                $facturas = json_decode($response->getBody());

                if (!empty($facturas)) {
                    foreach ($facturas as $factura) {
                        echo "Factura encontrada: " . $factura->Id . "<br>";

                        // Validar si la propiedad 'Complement' existe y contiene 'TaxStamp'
                        if (isset($factura->Complement->TaxStamp->Uuid)) {
                            $uuid = $factura->Complement->TaxStamp->Uuid;
                            echo '<a href="descargarFactura.php?uuid=' . $uuid . '" target="_blank">Imprimir Factura (PDF)</a><br><br>';
                        } else {
                            echo "No se encontró el UUID para la factura con ID: " . $factura->Id . ".<br>";
                        }
                    }
                } else {
                    echo "No se encontraron facturas con este folio.";
                }
            } catch (Exception $e) {
                echo "Error en la API: " . $e->getMessage();
                echo "<pre>";
                print_r($e); // Imprimir la excepción completa para depuración
                echo "</pre>";
            }
        }
    }
}
?>
