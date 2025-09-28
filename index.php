<?php
session_start();

require_once 'db.php';
require_once 'parser.php';

// Ambil data referensi dari DB dan buat menjadi map/array asosiatif
$reference_map = $pdo->query("SELECT model_name, base_customer FROM model_references")->fetchAll(PDO::FETCH_KEY_PAIR);

if (!isset($_SESSION['comparison_results'])) {
    $_SESSION['comparison_results'] = [];
}

if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    $_SESSION['comparison_results'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$clipboard_content = "";
$message = "";

if (isset($_POST['action']) && $_POST['action'] === 'compare') {
    $base_text = $_POST['base_data'] ?? '';
    $comparison_text = $_POST['comparison_data'] ?? '';

    if (!empty($base_text) && !empty($comparison_text) && !empty($reference_map)) {
        $base_data = parsePastedData($base_text);
        $comparison_data = parsePastedData($comparison_text);
        
        // Buat map dari base data untuk pencarian cepat: 'ModelName_Customer' => data
        $base_map = [];
        foreach ($base_data as $row) {
            $key = $row['model_name'] . '_' . $row['customer'];
            $base_map[$key] = $row;
        }

        $new_updates_for_db = [];
        $current_run_results = [];

        // **LOGIKA BARU: Mulai dari data pembanding (OLE/OLP)**
        foreach ($comparison_data as $comp_row) {
            $model_name = $comp_row['model_name'];

            // 1. Cek apakah model ini ada di tabel referensi kita
            if (isset($reference_map[$model_name])) {
                $target_customer = $reference_map[$model_name]; // e.g., 'OLM'
                $lookup_key = $model_name . '_' . $target_customer;

                // 2. Cari data yang cocok (model & customer) di data base
                if (isset($base_map[$lookup_key])) {
                    $base_row = $base_map[$lookup_key];

                    // 3. Jika ditemukan, bandingkan versi AP
                    if ($base_row['ap_version'] > $comp_row['ap_version']) {
                        
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comparison_results WHERE model_name = ? AND new_ap_version = ?");
                        $stmt->execute([$model_name, $base_row['ap_version']]);
                        $is_already_saved = $stmt->fetchColumn() > 0;

                        $result_row = [
                            'model_name'      => $model_name,
                            'customer_base'   => $base_row['customer'], // Customer dari base
                            'new_ap'          => $base_row['ap_version'],
                            'old_ap'          => $comp_row['ap_version'],
                            'cp'              => $base_row['cp_version'],
                            'csc'             => $base_row['csc_version'],
                            'confirm_status'  => $base_row['confirm_status'],
                            'register_date'   => $base_row['register_date'],
                            'os_version'      => $base_row['os_version'],
                            'full_row_string' => $base_row['full_row_string'],
                            'is_new'          => !$is_already_saved 
                        ];
                        
                        $current_run_results[$model_name] = $result_row;
                    }
                }
            }
        }
        
        foreach($current_run_results as $model_name => $result_row) {
            $_SESSION['comparison_results'][$model_name] = $result_row;

            if ($result_row['is_new']) {
                $new_updates_for_db[] = [ /* ... data untuk DB ... */ ];
                $clipboard_content .= $result_row['full_row_string'] . "\n";
            }
        }
        
        if (!empty($new_updates_for_db)) {
           // Logika simpan DB sama seperti sebelumnya
        }

        if (empty($current_run_results)) {
            $message = "Tidak ada update baru yang ditemukan berdasarkan tabel referensi.";
        }

    } elseif (empty($reference_map)) {
        $message = "Tabel referensi kosong. Harap isi terlebih dahulu melalui halaman kelola referensi.";
    } else {
        $message = "Harap isi kedua area teks untuk perbandingan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Perbandingan Tabel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Alat Perbandingan Versi Software</h1>
            <a href="references.php" style="font-weight: bold;">Kelola Tabel Referensi →</a>
        </div>
        
        <form action="index.php" method="post">
            <div class="form-container">
                <div class="form-group">
                    <label for="base_data">Tabel BASE (Referensi: OXM, OLM, OXT)</label>
                    <textarea name="base_data" id="base_data" placeholder="Salin dan tempel data dari Excel di sini..."></textarea>
                </div>
                <div class="form-group">
                    <label for="comparison_data">Tabel PEMBANDING (Khusus: OLE, OLP)</label>
                    <textarea name="comparison_data" id="comparison_data" placeholder="Salin dan tempel data dari Excel di sini..."></textarea>
                </div>
            </div>
            <div class="button-container">
                <button type="submit" name="action" value="compare">Bandingkan</button>
                <button type="submit" name="action" value="reset" style="background-color: #dc3545;">Reset Tampilan</button>
            </div>
        </form>

        <?php if (!empty($_SESSION['comparison_results'])): ?>
        <div class="results">
            <h2>Hasil Perbandingan Kumulatif</h2>
            <?php if(!empty(trim($clipboard_content))): ?>
            <div class="button-container" style="text-align: right; margin-top: -50px;">
                <button type="button" id="copyBtn">Salin Hasil Baru ke Clipboard</button>
            </div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Model Name</th>
                        <th>Customer Base</th>
                        <th>AP Base (Baru)</th>
                        <th>AP Pembanding (Lama)</th>
                        <th>CP</th>
                        <th>CSC</th>
                        <th>Confirm Status</th>
                        <th>Register Date</th>
                        <th>OS Version</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['comparison_results'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['model_name']) ?></td>
                        <td><?= htmlspecialchars($row['customer_base']) ?></td>
                        <td><?= htmlspecialchars($row['new_ap']) ?></td>
                        <td><?= htmlspecialchars($row['old_ap']) ?></td>
                        <td><?= htmlspecialchars($row['cp']) ?></td>
                        <td><?= htmlspecialchars($row['csc']) ?></td>
                        <td><?= htmlspecialchars($row['confirm_status']) ?></td>
                        <td><?= htmlspecialchars($row['register_date']) ?></td>
                        <td><?= htmlspecialchars($row['os_version']) ?></td>
                        <td>
                            <span class="status-updated">Ada Update</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <textarea id="clipboard-source" style="display:none;"><?= htmlspecialchars(trim($clipboard_content)) ?></textarea>
        
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="results">
            <h2>Hasil Perbandingan</h2>
            <p><?= $message ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Script Javascript sama seperti sebelumnya
    </script>
</body>
</html>