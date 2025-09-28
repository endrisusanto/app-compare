<?php
require_once 'db.php';

$error = null;
$success_message = null;

// Logika untuk menambah satu referensi
if (isset($_POST['add_reference'])) {
    $model_name = trim($_POST['model_name']);
    $base_customer = trim($_POST['base_customer']);

    if (!empty($model_name) && !empty($base_customer)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO model_references (model_name, base_customer) VALUES (?, ?)");
            $stmt->execute([$model_name, $base_customer]);
            $success_message = "Referensi berhasil ditambahkan.";
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Kode error untuk duplikat entry
                $error = "Gagal menambahkan: Model Name '$model_name' sudah ada.";
            } else {
                $error = "Gagal menambahkan: Terjadi kesalahan database.";
            }
        }
    }
}

// Logika untuk bulk add (tambah massal)
if (isset($_POST['bulk_add'])) {
    $bulk_data = trim($_POST['bulk_data_text']);
    if (!empty($bulk_data)) {
        $lines = explode("\n", $bulk_data);
        $inserted_count = 0;
        $skipped_count = 0;

        $pdo->beginTransaction();
        try {
            // Menggunakan INSERT IGNORE agar baris duplikat diabaikan tanpa error
            $stmt = $pdo->prepare("INSERT IGNORE INTO model_references (model_name, base_customer) VALUES (?, ?)");
            foreach ($lines as $line) {
                if (empty(trim($line))) continue; // Lewati baris kosong
                
                // Memisahkan berdasarkan tab atau spasi
                $parts = preg_split('/\s+/', trim($line), 2);
                if (count($parts) === 2) {
                    $model_name = trim($parts[0]);
                    $base_customer = trim($parts[1]);
                    if (!empty($model_name) && !empty($base_customer)) {
                        $stmt->execute([$model_name, $base_customer]);
                        if ($stmt->rowCount() > 0) {
                            $inserted_count++;
                        } else {
                            $skipped_count++;
                        }
                    }
                }
            }
            $pdo->commit();
            $success_message = "Proses selesai. Berhasil menambahkan: $inserted_count, Dilewati (duplikat): $skipped_count.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan saat proses bulk add: " . $e->getMessage();
        }
    } else {
        $error = "Area teks untuk bulk add tidak boleh kosong.";
    }
}


// Logika untuk menghapus referensi
if (isset($_POST['delete_reference'])) {
    $id_to_delete = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM model_references WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    $success_message = "Referensi berhasil dihapus.";
    // Redirect untuk mencegah re-submit form saat refresh
    header("Location: references.php?message=" . urlencode($success_message));
    exit();
}

// Ambil pesan dari URL jika ada
if (isset($_GET['message'])) {
    $success_message = htmlspecialchars($_GET['message']);
}

// Ambil semua data referensi untuk ditampilkan
$references = $pdo->query("SELECT * FROM model_references ORDER BY model_name ASC")->fetchAll();


$page_title = "Kelola Tabel Referensi";
require 'header.php';
?>

<main class="container mx-auto p-4 md:p-8">
    <div class="w-full max-w-4xl mx-auto backdrop-blur-xl bg-white/30 dark:bg-slate-900/30 p-6 md:p-10 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-500/10 dark:shadow-black/50">

        <h1 class="text-3xl md:text-4xl font-extrabold text-center mb-2 text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-violet-500">
            Kelola Tabel Referensi
        </h1>
        <p class="text-center mb-8">
            <a href="index.php" class="font-semibold text-sky-500 dark:text-sky-400 hover:text-sky-600 dark:hover:text-sky-300 transition-colors">
                ← Kembali ke Halaman Perbandingan
            </a>
        </p>
        
        <div class="mb-10">
            <div class="border-b border-slate-300 dark:border-slate-700 mb-4">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button id="tab-single" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-sky-600 dark:text-sky-400 border-sky-500">
                        Tambah Satu per Satu
                    </button>
                    <button id="tab-bulk" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-slate-500 dark:text-slate-400 border-transparent hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-400 dark:hover:border-slate-500">
                        Tambah Massal (Bulk)
                    </button>
                </nav>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900/50 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                 <div class="bg-green-100 dark:bg-green-900/50 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg relative mb-4" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <div id="content-single" class="tab-content">
                <form action="references.php" method="post" class="flex flex-col md:flex-row items-end gap-4">
                    <div class="flex-grow w-full">
                        <label for="model_name" class="block font-semibold mb-2 text-slate-600 dark:text-slate-300">Model Reference</label>
                        <input type="text" name="model_name" id="model_name" required class="w-full p-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 transition-all">
                    </div>
                    <div class="flex-grow w-full">
                        <label for="base_customer" class="block font-semibold mb-2 text-slate-600 dark:text-slate-300">Customer Base</label>
                        <input type="text" name="base_customer" id="base_customer" required class="w-full p-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 transition-all">
                    </div>
                    <div class="w-full md:w-auto">
                        <button type="submit" name="add_reference" value="1" class="w-full px-8 py-3 font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-full shadow-lg shadow-sky-500/30 transition-all transform hover:scale-105">Tambah</button>
                    </div>
                </form>
            </div>

            <div id="content-bulk" class="tab-content hidden">
                <form action="references.php" method="post" class="space-y-4">
                    <div>
                        <label for="bulk_data_text" class="block font-semibold mb-2 text-slate-600 dark:text-slate-300">Data Referensi Massal</label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Tempel data dari Excel. Format: <strong>ModelName</strong> [spasi/tab] <strong>CustomerBase</strong> per baris.</p>
                        <textarea name="bulk_data_text" id="bulk_data_text" rows="10" class="w-full p-3 bg-white/50 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-sky-500 transition-all font-mono text-xs" placeholder="SM-A155F OLM&#10;SM-A155F OXM&#10;..."></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="bulk_add" value="1" class="px-8 py-3 font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-full shadow-lg shadow-sky-500/30 transition-all transform hover:scale-105">Proses & Tambah</button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <h2 class="text-xl font-bold border-l-4 border-sky-500 pl-4 mb-4">Daftar Referensi Saat Ini</h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200/50 dark:border-slate-800/50">
                <table class="w-full text-sm text-left">
                     <thead class="bg-slate-100/50 dark:bg-slate-800/50 text-xs uppercase">
                        <tr>
                            <th class="px-6 py-3">Model Reference</th>
                            <th class="px-6 py-3">Customer Base</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/50">
                        <?php if (empty($references)): ?>
                            <tr>
                                <td colspan="3" class="text-center px-6 py-10 text-slate-500 dark:text-slate-400">Belum ada data referensi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($references as $ref): ?>
                            <tr class="hover:bg-slate-200/30 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($ref['model_name']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($ref['base_customer']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <form action="references.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus item ini?');">
                                        <input type="hidden" name="id" value="<?= $ref['id'] ?>">
                                        <button type="submit" name="delete_reference" value="1" class="px-4 py-1 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-full shadow-md shadow-red-500/30 transition-all transform hover:scale-105">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab-button');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Nonaktifkan semua tab
            tabs.forEach(item => {
                item.classList.remove('text-sky-600', 'dark:text-sky-400', 'border-sky-500');
                item.classList.add('text-slate-500', 'dark:text-slate-400', 'border-transparent', 'hover:text-slate-700', 'dark:hover:text-slate-300', 'hover:border-slate-400', 'dark:hover:border-slate-500');
            });
            
            // Sembunyikan semua konten
            contents.forEach(content => {
                content.classList.add('hidden');
            });

            // Aktifkan tab yang diklik
            tab.classList.add('text-sky-600', 'dark:text-sky-400', 'border-sky-500');
            tab.classList.remove('text-slate-500', 'dark:text-slate-400', 'border-transparent', 'hover:text-slate-700', 'dark:hover:text-slate-300', 'hover:border-slate-400', 'dark:hover:border-slate-500');
            
            // Tampilkan konten yang sesuai
            const targetContentId = 'content-' + tab.id.split('-')[1];
            document.getElementById(targetContentId).classList.remove('hidden');
        });
    });
});
</script>

<?php require 'footer.php'; ?>