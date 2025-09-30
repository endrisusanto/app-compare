<?php
// parser.php - REFACTORED FOR FLEXIBILITY

function parsePastedData($pastedText) {
    $lines = explode("\n", trim($pastedText));
    if (count($lines) < 2) {
        return []; // Tidak ada data atau hanya header
    }

    $headerLine = array_shift($lines);
    $headers = str_getcsv($headerLine, "\t");
    
    // Memisahkan kolom yang wajib ada dan yang opsional
    $required_cols = ['Model Name', 'Customer', 'AP(Code) ver.'];
    $optional_cols = ['CP(BB) ver.', 'CSC ver.'];
    
    $col_indices = [];

    // Langkah 1: Validasi kolom yang wajib ada.
    foreach($required_cols as $col) {
        $index = array_search($col, $headers);
        if ($index === false) {
            // Hentikan proses jika kolom esensial tidak ditemukan.
            die("Error Fatal: Kolom esensial '$col' tidak ditemukan di header. Pastikan data yang ditempel memiliki kolom ini.");
        }
        $col_indices[$col] = $index;
    }

    // Langkah 2: Cari indeks untuk kolom opsional tanpa menghentikan proses.
    foreach($optional_cols as $col) {
        $index = array_search($col, $headers);
        if ($index !== false) {
            $col_indices[$col] = $index;
        }
    }

    $data = [];
    foreach ($lines as $line) {
        if (trim($line) === '') continue; // Lewati baris kosong
        
        $row = str_getcsv($line, "\t");
        
        // Pastikan kolom esensial bisa diakses
        if (isset($row[$col_indices['Model Name']]) && isset($row[$col_indices['Customer']]) && isset($row[$col_indices['AP(Code) ver.']])) {
            
            // Ambil data dari kolom opsional hanya jika kolom tersebut ada di header
            $cp_version = isset($col_indices['CP(BB) ver.']) && isset($row[$col_indices['CP(BB) ver.']]) ? trim($row[$col_indices['CP(BB) ver.']]) : '';
            $csc_version = isset($col_indices['CSC ver.']) && isset($row[$col_indices['CSC ver.']]) ? trim($row[$col_indices['CSC ver.']]) : '';

            $rowData = [
                'model_name'      => trim($row[$col_indices['Model Name']]),
                'customer'        => trim($row[$col_indices['Customer']]),
                'ap_version'      => trim($row[$col_indices['AP(Code) ver.']]),
                'cp_version'      => $cp_version,
                'csc_version'     => $csc_version,
                'full_row_string' => $line // Simpan baris original
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