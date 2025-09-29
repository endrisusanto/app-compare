<?php
session_start();

require_once 'db.php';
require_once 'parser.php';

// Logika untuk menghapus satu baris dari hasil
if (isset($_POST['action']) && $_POST['action'] === 'delete_row') {
    $model_to_delete = $_POST['model_name'] ?? '';
    if (!empty($model_to_delete) && isset($_SESSION['comparison_results'][$model_to_delete])) {
        unset($_SESSION['comparison_results'][$model_to_delete]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Logika untuk reset
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    $_SESSION['comparison_results'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$reference_map = $pdo->query("SELECT model_name, base_customer FROM model_references")->fetchAll(PDO::FETCH_KEY_PAIR);
if (!isset($_SESSION['comparison_results'])) {
    $_SESSION['comparison_results'] = [];
}

$newly_added_clipboard_content = "";
$message = "";

if (isset($_POST['action']) && $_POST['action'] === 'compare') {
    $base_text = $_POST['base_data'] ?? '';
    $comparison_text = $_POST['comparison_data'] ?? '';

    if (!empty($base_text) && !empty($comparison_text) && !empty($reference_map)) {
        $base_data = parsePastedData($base_text);
        $comparison_data = parsePastedData($comparison_text);
        
        // Langkah 1: Buat map berisi versi TERBARU untuk setiap model di data BASE
        $base_map = [];
        foreach ($base_data as $row) {
            $key = $row['model_name'] . '_' . $row['customer'];
            if (!isset($base_map[$key]) || version_compare($row['ap_version'], $base_map[$key]['ap_version'], '>')) {
                $base_map[$key] = $row;
            }
        }

        // --- BLOK YANG DIPERBAIKI ---
        // Langkah 2: Buat map berisi versi TERBARU untuk setiap model di data PEMBANDING (OLE/OLP)
        $comparison_map = [];
        foreach ($comparison_data as $row) {
            $key = $row['model_name'];
            if (!isset($comparison_map[$key]) || version_compare($row['ap_version'], $comparison_map[$key]['ap_version'], '>')) {
                $comparison_map[$key] = $row;
            }
        }
        // --- AKHIR BLOK YANG DIPERBAIKI ---

        $current_run_results = [];

        // Langkah 3: Bandingkan versi TERBARU dari BASE dengan versi TERBARU dari PEMBANDING
        foreach ($comparison_map as $model_name => $comp_row) {
            if (isset($reference_map[$model_name])) {
                $target_customer = $reference_map[$model_name];
                $lookup_key = $model_name . '_' . $target_customer;

                if (isset($base_map[$lookup_key])) {
                    $base_row = $base_map[$lookup_key];

                    // Lakukan perbandingan antara versi terbaru vs terbaru
                    if (version_compare($base_row['ap_version'], $comp_row['ap_version'], '>')) {
                        $result_row = [
                            'model_name'      => $model_name,
                            'customer_base'   => $base_row['customer'],
                            'new_ap'          => $base_row['ap_version'],
                            'old_ap'          => $comp_row['ap_version'], // Ini sekarang adalah versi terbaru dari PEMBANDING
                            'cp'              => $base_row['cp_version'],
                            'csc'             => $base_row['csc_version'],
                            'full_row_string' => $base_row['full_row_string']
                        ];
                        $current_run_results[$model_name] = $result_row;
                    }
                }
            }
        }
        
        foreach($current_run_results as $model_name => $result_row) {
            if (!isset($_SESSION['comparison_results'][$model_name])) {
                 $newly_added_clipboard_content .= $result_row['full_row_string'] . "\n";
            }
            $_SESSION['comparison_results'][$model_name] = $result_row;
        }
        
        if (empty($current_run_results)) {
            $message = "Tidak ada update baru yang ditemukan.";
        }

    } elseif (empty($reference_map)) {
        $message = "Tabel referensi kosong. Harap isi terlebih dahulu.";
    } else {
        $message = "Harap isi kedua area teks untuk perbandingan.";
    }
}

// Menyiapkan data untuk tombol "Salin Semua"
$all_rows_for_clipboard = '';
if (!empty($_SESSION['comparison_results'])) {
    foreach ($_SESSION['comparison_results'] as $result) {
        $all_rows_for_clipboard .= $result['full_row_string'] . "\n";
    }
}

$page_title = "Alat Perbandingan Versi Software";
require 'header.php'; 
?>

<main class="container mx-auto p-4 md:p-8">
    <div class="w-full max-w-7xl mx-auto backdrop-blur-xl bg-white/30 dark:bg-slate-900/30 p-6 md:p-10 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-500/10 dark:shadow-black/50">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-violet-500">
                Alat Bantu Filter Daily Releases
            </h1>
            <a href="references.php" class="font-semibold text-sky-500 dark:text-sky-400 hover:text-sky-600 dark:hover:text-sky-300 transition-colors">
                Kelola Tabel Referensi →
            </a>
        </div>
        
        <form action="index.php" method="post" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="base_data" class="block font-semibold mb-2 text-slate-600 dark:text-slate-300">Tabel BASE (Referensi: OXM, OLM, OXT, OLO)</label>
                    <textarea name="base_data" id="base_data" rows="12" class="w-full p-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all text-xs font-mono" placeholder="Salin dan tempel data dari Excel di sini..."></textarea>
                </div>
                <div>
                    <label for="comparison_data" class="block font-semibold mb-2 text-slate-600 dark:text-slate-300">Tabel PEMBANDING (Khusus: OLE, OLP)</label>
                    <textarea name="comparison_data" id="comparison_data" rows="12" class="w-full p-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition-all text-xs font-mono" placeholder="Salin dan tempel data dari Excel di sini..."></textarea>
                </div>
            </div>
            <div class="flex justify-center items-center gap-4 pt-4">
                <button type="submit" name="action" value="compare" class="px-8 py-3 font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-full shadow-lg shadow-sky-500/30 transition-all transform hover:scale-105">Bandingkan</button>
                <button type="submit" name="action" value="reset" class="px-8 py-3 font-bold text-white bg-red-500 hover:bg-red-600 rounded-full shadow-lg shadow-red-500/30 transition-all transform hover:scale-105">Reset</button>
            </div>
        </form>

        <?php if (!empty($_SESSION['comparison_results'])): ?>
        <div class="mt-12">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h2 class="text-2xl font-bold border-l-4 border-sky-500 pl-4">List Releasan Baru</h2>
                <div class="flex items-center gap-2">
                    <?php if(!empty(trim($newly_added_clipboard_content))): ?>
                    <button type="button" id="copyNewBtn" class="px-5 py-2 text-sm font-medium text-white bg-green-500 hover:bg-green-600 rounded-full shadow-lg shadow-green-500/30 transition-all">Salin Hasil Baru</button>
                    <?php endif; ?>
                     <button type="button" id="copyAllBtn" class="px-5 py-2 text-sm font-medium text-sky-800 dark:text-sky-200 bg-sky-200/50 dark:bg-sky-800/50 hover:bg-sky-200 dark:hover:bg-sky-700 rounded-full transition-all">Salin Semua</button>
                </div>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-slate-200/50 dark:border-slate-800/50">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100/50 dark:bg-slate-800/50 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3">Model & Customer</th>
                            <th class="px-4 py-3">Versi AP (Base vs XID)</th>
                            <th class="px-4 py-3">Detail Lain</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/50">
                        <?php foreach (array_reverse($_SESSION['comparison_results'], true) as $model_name => $row): ?>
                        <tr class="hover:bg-slate-200/30 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($row['model_name']) ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($row['customer_base']) ?></div>
                            </td>
                            <td class="px-4 py-3 font-mono align-top">
                                <span class="font-bold text-green-500 dark:text-green-400"><?= htmlspecialchars($row['new_ap']) ?></span>
                                <span class="text-slate-400 mx-1">></span>
                                <span class="text-red-500 dark:text-red-400"><?= htmlspecialchars($row['old_ap']) ?></span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400 align-top">
                                <div>CP: <?= htmlspecialchars($row['cp']) ?></div>
                                <div>CSC: <?= htmlspecialchars($row['csc']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-center align-middle">
                                <div class="flex items-center justify-center gap-2">
                                    <button title="Salin baris ini" class="copy-row-btn p-2 rounded-full hover:bg-sky-100 dark:hover:bg-sky-900/50 text-sky-500 transition-colors" data-clipboard-text="<?= htmlspecialchars($row['full_row_string']) ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                    <form action="index.php" method="post" onsubmit="return confirm('Anda yakin ingin menghapus baris ini?');" class="inline-block">
                                        <input type="hidden" name="action" value="delete_row">
                                        <input type="hidden" name="model_name" value="<?= htmlspecialchars($model_name) ?>">
                                        <button type="submit" title="Hapus baris ini" class="p-2 rounded-full hover:bg-red-100 dark:hover:bg-red-900/50 text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <textarea id="clipboard-new" class="sr-only"><?= htmlspecialchars(trim($newly_added_clipboard_content)) ?></textarea>
        <textarea id="clipboard-all" class="sr-only"><?= htmlspecialchars(trim($all_rows_for_clipboard)) ?></textarea>
        
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="mt-12 text-center p-8 bg-slate-100/50 dark:bg-slate-800/50 rounded-lg">
            <h2 class="text-xl font-semibold mb-2">Hasil Perbandingan</h2>
            <p class="text-slate-600 dark:text-slate-400"><?= $message ?></p>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    const handleCopyClick = (button, textToCopy) => {
        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy).then(() => {
            const originalContent = button.innerHTML;
            const originalClasses = button.className;
            
            if (button.id === 'copyNewBtn' || button.id === 'copyAllBtn') {
                 button.textContent = 'Tersalin!';
                 button.classList.remove('bg-green-500', 'hover:bg-green-600', 'bg-sky-200/50', 'dark:bg-sky-800/50');
                 button.classList.add('bg-blue-500', 'text-white');
            } else {
                 button.innerHTML = `<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
            }

            setTimeout(() => {
                button.innerHTML = originalContent;
                button.className = originalClasses;
            }, 1500);
        }).catch(err => {
            console.error('Gagal menyalin teks: ', err);
            alert('Gagal menyalin teks.');
        });
    };

    const copyNewBtn = document.getElementById('copyNewBtn');
    if (copyNewBtn) {
        copyNewBtn.addEventListener('click', () => {
            const text = document.getElementById('clipboard-new').value;
            handleCopyClick(copyNewBtn, text);
        });
    }

    const copyAllBtn = document.getElementById('copyAllBtn');
    if(copyAllBtn) {
        copyAllBtn.addEventListener('click', () => {
             const text = document.getElementById('clipboard-all').value;
             handleCopyClick(copyAllBtn, text);
        });
    }

    document.querySelectorAll('.copy-row-btn').forEach(button => {
        button.addEventListener('click', () => {
            const text = button.dataset.clipboardText;
            handleCopyClick(button, text);
        });
    });
});
</script>

<?php require 'footer.php'; ?>