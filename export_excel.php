<?php
require 'vendor/autoload.php';
include 'include/koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Nama Pelanggan');
$sheet->setCellValue('C1', 'Tanggal');
$sheet->setCellValue('D1', 'Total');

$sql = "SELECT * FROM transaksi WHERE DATE(tanggal) = '$tanggal'";
$result = $koneksi->query($sql);

$no = 1;
$rowIndex = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowIndex, $no++);
    $sheet->setCellValue('B' . $rowIndex, $row['nama_pelanggan']);
    $sheet->setCellValue('C' . $rowIndex, $row['tanggal']);
    $sheet->setCellValue('D' . $rowIndex, $row['total']);
    $rowIndex++;
}

$writer = new Xlsx($spreadsheet);
$filename = 'laporan_transaksi_' . $tanggal . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
