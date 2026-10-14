<?php
declare(strict_types=1);

function connectDatabase(): PDO
{
    $host = '127.0.0.1';
    $database = 'instruktur';
    $username = 'root';
    $password = '';

    $server = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $server->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS instruktur (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(120) NOT NULL,
            nomor_lisensi VARCHAR(50) NOT NULL UNIQUE,
            cabang_olahraga VARCHAR(120) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if ((int) $pdo->query('SELECT COUNT(*) FROM instruktur')->fetchColumn() === 0) {
        $seed = $pdo->prepare('INSERT INTO instruktur (nama, nomor_lisensi, cabang_olahraga) VALUES (?, ?, ?)');
        foreach ([
            ['Fuad Dua', '1', 'Sepak Bola'],
            ['Dzaki Pra', '2', 'Renang'],
            ['Rahman Put', '3', 'Bulu Tangkis'],
            ['Dadan Sura', '4', 'Tenis'],
            ['Udin Hit', '5', 'Catur'],
        ] as $instructor) {
            $seed->execute($instructor);
        }
    }
}

function deleteInstructor(PDO $pdo, int $id): void
{
    $statement = $pdo->prepare('DELETE FROM instruktur WHERE id = ?');
    $statement->execute([$id]);
}

function saveInstructor(PDO $pdo, array $form, int $id): void
{
    if ($id > 0) {
        $statement = $pdo->prepare(
            'UPDATE instruktur SET nama = ?, nomor_lisensi = ?, cabang_olahraga = ? WHERE id = ?'
        );
        $statement->execute([$form['nama'], $form['nomor_lisensi'], $form['cabang_olahraga'], $id]);
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO instruktur (nama, nomor_lisensi, cabang_olahraga) VALUES (?, ?, ?)'
    );
    $statement->execute([$form['nama'], $form['nomor_lisensi'], $form['cabang_olahraga']]);
}

function findInstructor(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare('SELECT * FROM instruktur WHERE id = ?');
    $statement->execute([$id]);
    $record = $statement->fetch();

    return $record === false ? null : $record;
}

function findInstructors(PDO $pdo, string $name, string $license): array
{
    $statement = $pdo->prepare(
        'SELECT * FROM instruktur WHERE nama LIKE :nama AND nomor_lisensi LIKE :lisensi ORDER BY id DESC'
    );
    $statement->execute([
        'nama' => '%' . $name . '%',
        'lisensi' => '%' . $license . '%',
    ]);

    return $statement->fetchAll();
}