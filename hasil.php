<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian Artikel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="result-container">
        <div class="card_biodata">
            <h3>Kriteria Pencarian</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span>Nama Penulis:</span>
                    <strong><?php echo $_POST['penulis'] ?? '-'; ?></strong>
                </div>
                <div class="info-item">
                    <span>Keyword:</span>
                    <strong><?php echo $_POST['keyword'] ?? '-'; ?></strong>
                </div>
                <div class="info-item">
                    <span>Jumlah Data:</span>
                    <strong><?php echo $_POST['jumlah'] ?? '0'; ?></strong>
                </div>
                <div class="info-item">
                    <span>Metode:</span>
                    <strong><?php echo $_POST['similarity'] ?? '-'; ?></strong>
                </div>
            </div>
            <a href="index.php" class="btn-back">← Cari Lagi</a>
        </div>

        <div class="card_table">
            <table>
                <thead>
                    <tr>
                        <th>Judul Artikel</th>
                        <th>Penulis</th>
                        <th>Tanggal Rilis</th>
                        <th>Nama Jurnal</th>
                        <th>Link</th>
                        <th>Nilai Sim</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="judul">Penerapan Cosine Similarity pada...</td>
                        <td>Ahmad Fauzi</td>
                        <td>2023-10-12</td>
                        <td>Jurnal Informatika</td>
                        <td><a href="#" class="link-jurnal">Buka</a></td>
                        <td><span class="badge">0.982</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>