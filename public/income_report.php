<?php
require '../libs/fpdf/fpdf.php';
include '../includes/db.php';

/* Read filters */
$year  = $_GET['year']  ?? '';
$month = $_GET['month'] ?? '';

$where = [];
$params = [];

if ($year) {
    $where[] = "YEAR(mb.bill_month) = ?";
    $params[] = $year;
}

if ($month) {
    $where[] = "MONTH(mb.bill_month) = ?";
    $params[] = $month;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT 
    DATE_FORMAT(mb.bill_month, '%Y-%m') AS period,
    SUM(
        rt.base_room_fee +
        ((mb.new_electric - mb.old_electric) * ur.electric_rate) +
        (mb.water_units * ur.water_rate)
    ) AS income
FROM monthly_bill mb
JOIN rental rl ON mb.rental_id = rl.rental_id
JOIN room rm ON rl.room_id = rm.room_id
JOIN room_type rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rate ur ON mb.rate_id = ur.rate_id
$whereSql
GROUP BY period
ORDER BY period
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

/* Calculate total */
$totalIncome = 0;
foreach ($data as $row) {
    $totalIncome += $row['income'];
}

/* PDF */
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'INCOME REPORT',0,1,'C');

$pdf->Ln(4);
$pdf->SetFont('Arial','',11);

$subtitle = 'All Periods';
if ($year && $month) {
    $subtitle = date('F', mktime(0,0,0,$month)) . " $year";
} elseif ($year) {
    $subtitle = "Year $year";
}

$pdf->Cell(0,8,"Report Period: $subtitle",0,1,'C');
$pdf->Ln(5);

/* Table Header */
$pdf->SetFont('Arial','B',11);
$pdf->Cell(60,8,'Period',1);
$pdf->Cell(60,8,'Income ($)',1,1,'R');

$pdf->SetFont('Arial','',11);

/* Rows */
foreach ($data as $row) {
    $pdf->Cell(60,8,$row['period'],1);
    $pdf->Cell(60,8,number_format($row['income'],2),1,1,'R');
}

/* Total */
$pdf->SetFont('Arial','B',11);
$pdf->Cell(60,8,'TOTAL',1);
$pdf->Cell(60,8,number_format($totalIncome,2),1,1,'R');

$pdf->Ln(10);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,8,'Generated on: ' . date('Y-m-d H:i'),0,1,'C');

$pdf->Output();
