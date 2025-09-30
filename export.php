<?php
// export.php - UPDATED with Custom Sorting

session_start(); 

require_once 'db.php';

// **MODIFIKASI: Query diubah untuk sorting kustom**
// Mengurutkan berdasarkan string SETELAH 'SM-'
$results = $pdo->query("
    SELECT full_row_string 
    FROM comparison_result 
    ORDER BY SUBSTRING(model_name, 4) ASC
")->fetchAll(PDO::FETCH_COLUMN);

if (empty($results)) {
    echo "<script>alert('Tidak ada data untuk diekspor.'); window.history.back();</script>";
    exit();
}

$filename = "comparison_results_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

if (isset($_SESSION['export_header']) && !empty($_SESSION['export_header'])) {
    $header_columns = str_getcsv($_SESSION['export_header'], "\t");
    fputcsv($output, $header_columns, "\t");
} else {
    fputcsv($output, ["Peringatan: Header kolom tidak ditemukan. Data dimulai dari baris ini."], "\t");
}

foreach ($results as $row_string) {
    if(trim($row_string) !== '') {
        $row_data = str_getcsv(trim($row_string), "\t");
        fputcsv($output, $row_data, "\t");
    }
}

fclose($output);
exit();
?>