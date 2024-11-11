<?php
if (isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];

    try {
        // Descargar el PDF de la factura
        $response = $client->get('cfdi/'.$uuid.'/pdf', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $pdfContent = $response->getBody()->getContents();

        // Configurar el encabezado para la descarga del PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="factura_' . $uuid . '.pdf"');
        echo $pdfContent;
        exit;
    } catch (ClientException $e) {
        echo "Error al descargar el PDF: " . $e->getMessage();
    }
} else {
    echo "No se proporcionó un UUID de factura válido.";
}
?>