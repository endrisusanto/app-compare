<?php
// parser.php

function parsePastedData($pastedText) {
    $lines = explode("\n", trim($pastedText));
    if (count($lines) < 2) {
        return []; // Tidak ada data atau hanya header
    }

    $headerLine = array_shift($lines);
    $headers = str_getcsv($headerLine, "\t");
    
    // Cari index kolom yang kita butuhkan
    $required_cols = [
        'Model Name', 'Customer', 'AP(Code) ver.', 'CP(BB) ver.', 'CSC ver.', 
        'Confirm Status', 'Reg. Date', 'OS Version'
    ];
    
    $col_indices = [];
    foreach($required_cols as $col) {
        $index = array_search($col, $headers);
        if ($index === false) {
            // Jika kolom penting tidak ditemukan, hentikan proses
            die("Error: Kolom '$col' tidak ditemukan di header. Pastikan Anda menyalin header dengan benar.");
        }
        $col_indices[$col] = $index;
    }

    $data = [];
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        
        $row = str_getcsv($line, "\t");
        
        // Pastikan jumlah kolom di baris ini cukup
        if (count($row) > max($col_indices)) {
            $rowData = [
                'model_name' => trim($row[$col_indices['Model Name']]),
                'customer' => trim($row[$col_indices['Customer']]),
                'ap_version' => trim($row[$col_indices['AP(Code) ver.']]),
                'cp_version' => isset($row[$col_indices['CP(BB) ver.']]) ? trim($row[$col_indices['CP(BB) ver.']]) : '',
                'csc_version' => isset($row[$col_indices['CSC ver.']]) ? trim($row[$col_indices['CSC ver.']]) : '',
                'confirm_status' => trim($row[$col_indices['Confirm Status']]),
                'register_date' => trim($row[$col_indices['Reg. Date']]),
                'os_version' => trim($row[$col_indices['OS Version']]),
                'full_row_string' => $line // Simpan baris original untuk clipboard
            ];
            // Hanya tambahkan jika model_name dan ap_version tidak kosong
            if (!empty($rowData['model_name']) && !empty($rowData['ap_version'])) {
                $data[] = $rowData;
            }
        }
    }

    return $data;
}
?>