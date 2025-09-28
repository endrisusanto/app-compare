<?php
require_once 'db.php';

// Logika untuk menambah referensi baru
if (isset($_POST['add_reference'])) {
    $model_name = trim($_POST['model_name']);
    $base_customer = trim($_POST['base_customer']);

    if (!empty($model_name) && !empty($base_customer)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO model_references (model_name, base_customer) VALUES (?, ?)");
            $stmt->execute([$model_name, $base_customer]);
        } catch (PDOException $e) {
            $error = "Gagal menambahkan: Model Name mungkin sudah ada.";
        }
    }
}

// Logika untuk menghapus referensi
if (isset($_POST['delete_reference'])) {
    $id_to_delete = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM model_references WHERE id = ?");
    $stmt->execute([$id_to_delete]);
}

// Ambil semua data referensi untuk ditampilkan
$references = $pdo->query("SELECT * FROM model_references ORDER BY model_name ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tabel Referensi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Kelola Tabel Referensi</h1>
        <p><a href="index.php">← Kembali ke Halaman Perbandingan</a></p>

        <div class="results" style="margin-bottom: 30px;">
            <h2>Tambah Referensi Baru</h2>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form action="references.php" method="post" style="display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex: 2;">
                    <label for="model_name">Model Reference (Model Name)</label>
                    <input type="text" name="model_name" id="model_name" required style="width: 100%; padding: 8px;">
                </div>
                <div style="flex: 1;">
                    <label for="base_customer">Customer Base (e.g., OLM, OXM)</label>
                    <input type="text" name="base_customer" id="base_customer" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <button type="submit" name="add_reference" value="1">Tambah</button>
                </div>
            </form>
        </div>

        <div class="results">
            <h2>Daftar Referensi Saat Ini</h2>
            <table>
                <thead>
                    <tr>
                        <th>Model Reference (Model Name)</th>
                        <th>Customer Base</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($references)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">Belum ada data referensi.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($references as $ref): ?>
                        <tr>
                            <td><?= htmlspecialchars($ref['model_name']) ?></td>
                            <td><?= htmlspecialchars($ref['base_customer']) ?></td>
                            <td>
                                <form action="references.php" method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus item ini?');">
                                    <input type="hidden" name="id" value="<?= $ref['id'] ?>">
                                    <button type="submit" name="delete_reference" value="1" style="background-color: #dc3545; font-size: 14px; padding: 5px 10px;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>