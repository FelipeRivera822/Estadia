<?php
// Iniciar el buffer de salida
ob_start();

// Cargar autoload de Composer
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Credenciales para la API de Facturama
$username = 'FelipeRivera';
$password = 'Felipollo123';

// Datos del receptor y de la factura
$rfc = $_POST['Receiver']['Rfc'] ?? null;
$nombre = $_POST['Receiver']['Name'] ?? null;
$email = $_POST['Receiver']['Email'] ?? null;
$usoCFDI = $_POST['Receiver']['CfdiUse'] ?? null;
$regimenFiscal = $_POST['Receiver']['FiscalRegime'] ?? null;
$codigoPostal = $_POST['Receiver']['TaxZipCode'] ?? null;
$currency = 'MXN';
$paymentForm = $_POST['PaymentForm'] ?? null;
$paymentMethod = $_POST['PaymentMethod'] ?? null;
$folio = $_POST['Folio'] ?? null;
$expeditionZipCode = '57000';

// Validar el RFC del receptor
if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[0-9A-Z]{3}$/', $rfc)) {
    die('El RFC no es válido.');
}

// Crear cliente HTTP para Facturama
$client = new Client([
    'base_uri' => 'https://apisandbox.facturama.mx/',
    'auth' => [$username, $password]
]);

$items = [];
$totalGeneral = 0;
$ivaRate = 0.16;
$iva = 0;

// Procesar los items de la factura
foreach ($_POST['Items'] as $item) {
    if (isset($item['Quantity'], $item['UnitPrice'], $item['ProductCode'], $item['Description'])) {
        $quantity = (int)$item['Quantity'];
        $unitPrice = (float)$item['UnitPrice'];
        $total = $quantity * $unitPrice;
        $totalGeneral += $total;
        $iva += $total * $ivaRate;

        $items[] = [
            'Total' => $total,
            'Subtotal' => $total,
            'UnitPrice' => $unitPrice,
            'Quantity' => $quantity,
            'ProductCode' => $item['ProductCode'],
            'UnitCode' => 'E48',
            'Description' => $item['Description'],
            'TaxObject' => '01',
        ];
    }
}

// Obtener la fecha actual
$fecha = date('Y-m-d');

// Datos de la factura
$invoiceData = [
    'CfdiType' => 'I',
    'Folio' => $folio,
    'Issuer' => [
        'Rfc' => 'MAGL400819MY3',
        'Name' => 'LUIS MARTINEZ GARCIA',
        'FiscalRegime' => '626'
    ],
    'Receiver' => [
        'Rfc' => $rfc,
        'Name' => $nombre,
        'Email' => $email,
        'CfdiUse' => $usoCFDI,
        'FiscalRegime' => $regimenFiscal,
        'TaxZipCode' => $codigoPostal,
    ],
    'Items' => $items,
    'Currency' => $currency,
    'PaymentForm' => $paymentForm,
    'PaymentMethod' => $paymentMethod,
    'ExpeditionPlace' => $expeditionZipCode,
];

try {
    // Enviar la solicitud a la API de Facturama
    $response = $client->request('POST', '3/cfdis', [
        'json' => $invoiceData,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    $data = json_decode($response->getBody(), true);

    if (isset($data['Complement']['TaxStamp']['Uuid'])) {
        $uuid = $data['Complement']['TaxStamp']['Uuid'];

        // Crear contenido para el código QR con el UUID y la fecha
        $contenidoQR = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=$uuid&re=EMISOR_RFC&rr=$rfc&tt=" . number_format($totalGeneral + $iva, 2, '.', '') . "&fecha=$fecha";

        // Generar el código QR
        $qrCode = new QrCode($contenidoQR);
        $qrCode->setSize(150); // Ajusta el tamaño del código QR
        $qrCode->setMargin(10); // Ajusta el margen del QR
        $writer = new PngWriter();
        $qrImage = $writer->write($qrCode);

        // Guardar el archivo QR temporalmente
        $nombreArchivoQR = 'factura_qr.png';
        $qrImage->saveToFile($nombreArchivoQR);

        // Generar el PDF con TCPDF
        require_once 'C:\wamp64\www\Estadia\entregable\vendor\tecnickcom\tcpdf\tcpdf.php';
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Factura Generada');
        $pdf->SetTitle('Factura');
        $pdf->SetSubject('Factura de Compra');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // Agregar el logo
        $pdf->Image('C:\wamp64\www\Estadia\entregable\imagenes\logo.png', 10, 10, 50, '', 'PNG');
        $pdf->Ln(20);

        // Título de la factura
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 51, 102);
        $pdf->Cell(0, 10, 'FACTURA DE COMPRA', 0, 1, 'C');

        // Datos fiscales del receptor
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(10);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->Cell(0, 10, 'Datos Fiscales', 0, 1, 'L', true);
        $pdf->Cell(0, 10, "RFC: $rfc", 0, 1, 'L');
        $pdf->Cell(0, 10, "Nombre/Razón Social: $nombre", 0, 1, 'L');
        $pdf->Cell(0, 10, "Correo Electrónico: $email", 0, 1, 'L');
        $pdf->Cell(0, 10, "Uso CFDI: $usoCFDI", 0, 1, 'L');
        $pdf->Cell(0, 10, "Régimen Fiscal: $regimenFiscal", 0, 1, 'L');
        $pdf->Cell(0, 10, "Código Postal: $codigoPostal", 0, 1, 'L');

        // Información de ticket y folio
        $pdf->Ln(5);
        $pdf->SetFillColor(192, 192, 192);
        $pdf->Cell(0, 10, 'Información de Ticket', 0, 1, 'L', true);
        $pdf->Cell(0, 10, "Folio: $folio", 0, 1, 'L');
        $pdf->Cell(0, 10, "Fecha: $fecha", 0, 1, 'L');  // Fecha de la factura

        // Detalles de productos
        $pdf->Ln(5);
        $pdf->SetFillColor(169, 169, 169);
        $pdf->Cell(0, 10, 'Detalles de Productos', 0, 1, 'L', true);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(211, 211, 211);
        $pdf->Cell(60, 10, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Precio Unitario', 1, 0, 'C', true);
        $pdf->Cell(50, 10, 'Total', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 12);
        foreach ($items as $item) {
            $pdf->Cell(60, 10, $item['Description'], 1);
            $pdf->Cell(30, 10, $item['Quantity'], 1, 0, 'C');
            $pdf->Cell(50, 10, number_format($item['UnitPrice'], 2), 1, 0, 'C');
            $pdf->Cell(50, 10, number_format($item['Total'], 2), 1, 1, 'C');
        }

        // Totales
        $pdf->Ln(5);
        $pdf->Cell(140, 10, 'Subtotal:', 1);
        $pdf->Cell(50, 10, number_format($totalGeneral, 2), 1, 1, 'C');
        $pdf->Cell(140, 10, 'IVA:', 1);
        $pdf->Cell(50, 10, number_format($iva, 2), 1, 1, 'C');
        $pdf->Cell(140, 10, 'Total General:', 1);
        $pdf->Cell(50, 10, number_format($totalGeneral + $iva, 2), 1, 1, 'C');

        // Añadir sellos digitales
        $selloDigitalCFDI = "AZhPsR9IYXNLpwrYaiPHNHsY003OIY8S5XvIUI/JU0wWHpHUGqNwTVH29jADqNCK1jCNza1LPIb0NSItKDChBalZILAUULlLjqF2tPy3y9M9O71/l4oet9KGDUnrPvJG8k6OHZdSEztt2yx+JBQHRe5IvgD823hlHYfM47fdSi/2Rtuhh8ZdKSgNFWNBCnV5Wy6F6gATpxoQRnkWtH/4k/o2oS7u75wcJcvC7GZssljepsSBXqvRRIr1F41OnAsAYGOQIEYsM7qklcfWQ0XRxXp/e+499cGGJTJ7K8vFK73gRKJ8hpcIR6RW2CLvSSp6hkk5W/qPiZ2uzLlrYlONTQ==";
        $selloDigitalSAT = "hw4oUQFExWiaFGnHz6H6AHbRFsZMpEvOXPBLuJyH2v1iTeFEow5TcbMdBfO5T3UAigFbsIcjASOi+Y6hxgiv1exoAPMtGdBsFoNzewAibLNm6388tnTj7tR8TYoiyXk/v6ZTRW04HbWCohMyygx2w+oYzTwFXWKk56hu0co21CxvUNkvS4fNlxN5CFwcoDNb1SPlUsjf/yGjk8IpfyifvdfYW3dAoT+eg/6xKEZjC7hql2vBClsfzXRYVIPnvz2ANbX7I4cOf/TzD6A5wXVfa0CX952INbY3kBushT3kSMNJjHFiITkeJRMn3QFFJC9ZjPLrx+H6xDPwN0SV0EPhAA==";

        $pdf->Ln(10);
        $pdf->MultiCell(0, 10, "Sello Digital del CFDI:\n$selloDigitalCFDI", 0, 'L', false);
        $pdf->Ln(5);
        $pdf->MultiCell(0, 10, "Sello Digital del SAT:\n$selloDigitalSAT", 0, 'L', false);

        // Ajustar QR al final del documento
        $pdf->Ln(10);
        $pdf->Image($nombreArchivoQR, 10, $pdf->GetY(), 50, 50, 'PNG');  // Posicionar QR al final

        // Generar y mostrar el PDF
        $pdf->Output();
    }
} catch (ClientException $e) {
    echo 'Error en la API: ' . $e->getMessage();
}
?>
