<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $keyword = $_POST['penulis']; // Mengambil input dari form

    $payload = json_encode(array("keyword" => $keyword));

    // Inisialisasi cURL ke Flask
    $ch = curl_init('http://127.0.0.1:5000/api/scrape');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    // Sekarang variabel $data berisi info penulis dan daftar artikel
    $author = $data['author_info'];
    $articles = $data['articles'];
}
?>

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
        <div class="top-row">

            <div class="card_kriteria">
                <h3>Kriteria Pencarian</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span>Penulis:</span> <strong><?php echo $_POST['penulis'] ?? '-'; ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Keyword:</span> <strong><?php echo $_POST['keyword'] ?? '-'; ?></strong>
                    </div>
                    <div class="info-item">
                        <span>Metode:</span> <strong><?php echo $_POST['similarity'] ?? '-'; ?></strong>
                    </div>
                </div>
                <a href="index.php" class="btn-back">← Cari Lagi</a>
            </div>

            <div class="card_scholar">
                <div class="profile-header">
                    <img src="<?php echo $author['photo']; ?>" class="profile-img">
                    <div class="profile-details">
                        <h4><?php echo $author['nama']; ?></h4>
                        <p><?php echo $author['univ']; ?></p>
                        <p><?php echo $author['email']; ?></p>
                    </div>
                </div>
            </div>

        </div>

        <div class="card_table">
            <table>
                <?php foreach ($articles as $row): ?>
                    <tr>
                        <td><?php echo $row['Judul']; ?></td>
                        <td><?php echo $row['Penulis']; ?></td>
                        <td><?php echo $row['Tahun']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>

</html>