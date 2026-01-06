<?php
require '../libs/fpdf/fpdf.php';
include '../includes/db.php';

$company = [
    'name'    => 'My Rental Company',
    'address' => 'No. 123, Main Road, Phnom Penh, Cambodia',
    'phone'   => '+855 96 266 5240',
    'email'   => 'info@myrental.com'
];

$terms = [
    'Payment is due within 7 days from the invoice date.',
    'Late payments may be subject to additional charges.',
    'Utilities are billed based on actual usage.',
    'Please keep this invoice for your records.'
];

$bill_id = $_GET['id'];

function generateInvoiceNumber(PDO $pdo, $year) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM monthly_bill
        WHERE YEAR(bill_month) = ?
          AND invoice_number IS NOT NULL
    ");
    $stmt->execute([$year]);

    $count = $stmt->fetchColumn() + 1;

    return sprintf('RRS-%d-%04d', $year, $count);
}

$sql = "
SELECT 
    mb.bill_id,
    mb.invoice_number,
    mb.bill_month,
    mb.old_electric,
    mb.new_electric,
    mb.water_units,
    r.renter_name,
    r.mobile_number,
    rm.room_id,
    rt.room_type_name,
    rt.base_room_fee,
    ur.electric_rate,
    ur.water_rate
FROM monthly_bill mb
JOIN rental rl ON mb.rental_id = rl.rental_id
JOIN renter r ON rl.renter_id = r.renter_id
JOIN room rm ON rl.room_id = rm.room_id
JOIN room_type rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rate ur ON mb.rate_id = ur.rate_id
WHERE mb.bill_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$bill_id]);
$data = $stmt->fetch();
$year = date('Y', strtotime($data['bill_month']));

if (empty($data['invoice_number'])) {
    $invoiceNumber = generateInvoiceNumber($pdo, $year);

    $update = $pdo->prepare("
        UPDATE monthly_bill
        SET invoice_number = ?
        WHERE bill_id = ?
    ");
    $update->execute([$invoiceNumber, $bill_id]);

    $data['invoice_number'] = $invoiceNumber;
}


$electric_fee = ($data['new_electric'] - $data['old_electric']) * $data['electric_rate'];
$water_fee = $data['water_units'] * $data['water_rate'];
$total = $data['base_room_fee'] + $electric_fee + $water_fee;

$pdf = new FPDF();
$pdf->AddPage();

/* Logo */
$pdf->Image('./assets/logo.png', 10, 10, 25);

/* Company Info */
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,$company['name'],0,1,'R');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,$company['address'],0,1,'R');
$pdf->Cell(0,6,'Phone: '.$company['phone'],0,1,'R');
$pdf->Cell(0,6,'Email: '.$company['email'],0,1,'R');

$pdf->Ln(8);

/* Line */
$pdf->Line(10, 45, 200, 45);

/* Title */
$pdf->Ln(10);
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'RENT INVOICE',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',11);
$pdf->Cell(140,8,'Room: '.$data['room_id'],0,0);
$pdf->Cell(0,8,'Invoice No: '.$data['invoice_number'],0,1);

$pdf->Cell(0,8,'Renter: '.$data['renter_name'],0,1);

$pdf->Cell(0,8,'Contact: '.$data['mobile_number'],0,1);
$pdf->Cell(0,8,'Month: '.date('d/M/Y', strtotime($data['bill_month'])),0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(145,8,'Description',1);
$pdf->Cell(40,8,'Amount ($)',1,1,'R');

$pdf->SetFont('Arial','',11);
$pdf->Cell(145,8,'Room Fee ('.$data['room_type_name'].')',1);
$pdf->Cell(40,8,number_format($data['base_room_fee'],2),1,1,'R');

$pdf->Cell(145,8,'Electric Fee',1);
$pdf->Cell(40,8,number_format($electric_fee,2),1,1,'R');

$pdf->Cell(145,8,'Water Fee',1);
$pdf->Cell(40,8,number_format($water_fee,2),1,1,'R');

$pdf->SetFont('Arial','B',11);
$pdf->Cell(145,8,'TOTAL',1);
$pdf->Cell(40,8,number_format($total,2),1,1,'R');

// $pdf->Ln(5);

/* Footer line */
$pdf->Line(10, 250, 200, 250);

$pdf->SetY(220);
$pdf->SetFont('Arial','',9);

/* Terms title */
$pdf->Cell(0,6,'Terms & Conditions:',0,1);

/* Terms text */
foreach ($terms as $term) {
    $pdf->MultiCell(0,5,'- ' . $term);
}

$pdf->Ln(10);

/* Signature */
$pdf->Cell(145,6,'Authorized Signature:',0,0);
$pdf->Cell(0,6,'_________________________',0,1);

$pdf->Cell(160,6,' ',0,0);
$pdf->Cell(0,6,date('Y-m-d'),0,1);

$pdf->SetFont('Arial','',8);
$pdf->Cell(0,8,'Thank you for your payment.',0,1,'C');

$pdf->Output();
