<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$dbError = '';
$errors = [];
$rows = [];
$form = ['nama' => '', 'nomor_lisensi' => '', 'cabang_olahraga' => ''];
$editing = false;
$searchName = trim((string) ($_GET['nama'] ?? ''));
$searchLicense = trim((string) ($_GET['lisensi'] ?? ''));
$notice = '';

function e(string $value): string
{   
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = connectDatabase();
    initializeDatabase($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'delete') {
            deleteInstructor($pdo, (int) ($_POST['id'] ?? 0));
            header('Location: index.php?status=deleted');
            exit;
        }

        if ($action === 'save') {
            $form['nama'] = trim((string) ($_POST['nama'] ?? ''));
            $form['nomor_lisensi'] = trim((string) ($_POST['nomor_lisensi'] ?? ''));
            $form['cabang_olahraga'] = trim((string) ($_POST['cabang_olahraga'] ?? ''));
            $id = (int) ($_POST['id'] ?? 0);
            $editing = $id > 0;

            if ($form['nama'] === '') {
                $errors[] = 'Nama instruktur wajib diisi.';
            }
            if ($form['nomor_lisensi'] === '') {
                $errors[] = 'Nomor lisensi wajib diisi.';
            }
            if ($form['cabang_olahraga'] === '') {
                $errors[] = 'Keahlian cabang olahraga wajib diisi.';
            }

            if ($errors === []) {
                try {
                    saveInstructor($pdo, $form, $id);
                    if ($editing) {
                        header('Location: index.php?status=updated');
                    } else {
                        header('Location: index.php?status=created');
                    }
                    exit;
                } catch (PDOException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                    $errors[] = 'Nomor lisensi sudah digunakan oleh instruktur lain.';
                }
            }
        }
    }

    if (isset($_GET['edit'])) {
        $record = findInstructor($pdo, (int) $_GET['edit']);
        if ($record) {
            $form = [
                'nama' => $record['nama'],
                'nomor_lisensi' => $record['nomor_lisensi'],
                'cabang_olahraga' => $record['cabang_olahraga'],
            ];
            $editing = true;
        }
    }

    $rows = findInstructors($pdo, $searchName, $searchLicense);

    $statusMessages = [
        'created' => 'Data instruktur berhasil ditambahkan.',
        'updated' => 'Data instruktur berhasil diperbarui.',
        'deleted' => 'Data instruktur berhasil dihapus.',
    ];
    $notice = $statusMessages[(string) ($_GET['status'] ?? '')] ?? '';
} catch (PDOException $exception) {
    $dbError = 'Database tidak dapat diakses';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Instruktur &amp; Coach</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="page">
        <header class="page-header">
            <p class="eyebrow">Mitra Venue</p>
            <h1>Data Instruktur &amp; Coach</h1>
        </header>

        <?php if ($dbError !== ''): ?>
            <p class="message error"><?= e($dbError) ?></p>
        <?php else: ?>
            <?php if ($notice !== ''): ?>
                <p class="message success"><?= e($notice) ?></p>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <p class="message error"><?= e($error) ?></p>
            <?php endforeach; ?>

            <section class="panel" aria-labelledby="form-title">
                <div class="section-heading">
                    <h2 id="form-title"><?= $editing ? 'Edit instruktur' : 'Tambah instruktur' ?></h2>
                    <?php if ($editing): ?>
                        <a class="text-link" href="index.php">Batal edit</a>
                    <?php endif; ?>
                </div>
                <form method="post" class="entry-form">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= isset($_GET['edit']) ? (int) $_GET['edit'] : (int) ($_POST['id'] ?? 0) ?>">
                    <label>
                        Nama instruktur
                        <input type="text" name="nama" maxlength="120" value="<?= e($form['nama']) ?>" required>
                    </label>
                    <label>
                        Nomor lisensi
                        <input type="text" name="nomor_lisensi" maxlength="50" value="<?= e($form['nomor_lisensi']) ?>" required>
                    </label>
                    <label>
                        Keahlian cabang olahraga
                        <input type="text" name="cabang_olahraga" maxlength="120" value="<?= e($form['cabang_olahraga']) ?>" required>
                    </label>
                    <button class="button primary" type="submit"><?= $editing ? 'Simpan perubahan' : 'Tambah data' ?></button>
                </form>
            </section>

            <section class="records" aria-labelledby="records-title">
                <div class="section-heading records-heading">
                    <div>
                        <p class="eyebrow">Direktori</p>
                        <h2 id="records-title">Daftar instruktur</h2>
                    </div>
                    <span class="record-count"><?= count($rows) ?> data</span>
                </div>
                <form method="get" class="search-form">
                    <label>
                        Cari nama
                        <input type="search" name="nama" value="<?= e($searchName) ?>" placeholder="Nama instruktur">
                    </label>
                    <label>
                        Nomor lisensi
                        <input type="search" name="lisensi" value="<?= e($searchLicense) ?>" placeholder="Nomor lisensi">
                    </label>
                    <button class="button secondary" type="submit">Cari</button>
                    <?php if ($searchName !== '' || $searchLicense !== ''): ?>
                        <a class="text-link" href="index.php">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Instruktur</th>
                                <th>Nomor Lisensi</th>
                                <th>Keahlian Cabang Olahraga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($rows === []): ?>
                                <tr><td class="empty-state" colspan="4">Belum ada data yang cocok.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e($row['nama']) ?></td>
                                        <td><?= e($row['nomor_lisensi']) ?></td>
                                        <td><?= e($row['cabang_olahraga']) ?></td>
                                        <td class="actions">
                                            <a class="button small secondary" href="?edit=<?= (int) $row['id'] ?>">Edit</a>
                                            <form method="post" onsubmit="return confirm('Hapus data instruktur ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <button class="button small danger" type="submit">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>