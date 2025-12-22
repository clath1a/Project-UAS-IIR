<?php
require_once __DIR__ . '/vendor/autoload.php';

use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;

// Inisialisasi variabel agar tidak "Undefined variable" jika request gagal
$author = ['nama' => '-', 'univ' => '-', 'email' => '-', 'photo' => ''];
$articles = [];
$metode_pilihan = $_POST['similarity'] ?? 'cosine';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $penulis = $_POST['penulis'];
    $keyword_query = $_POST['keyword']; // Variabel query yang benar
    $jumlah_data = $_POST['jumlah'];

    // --- 1. Komunikasi dengan Flask (Python) ---
    $payload = json_encode(array(
        "penulis" => $penulis,
        "keyword" => $keyword_query,
        "jumlah" => $jumlah_data
    ));

    $ch = curl_init('http://127.0.0.1:5000/api/scrape');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if ($result && isset($result['status']) && $result['status'] == 'success') {
        $author = $result['author_info'];
        $articles = $result['articles'];
    }

    // Hanya jalankan similarity jika ada artikel yang ditemukan
    if (!empty($articles)) {
        // --- 2. Vektorisasi Teks (Judul Artikel + Keyword Query) ---
        $corpus = [];
        foreach ($articles as $row) {
            $corpus[] = strtolower($row['judul']);
        }
        $corpus[] = strtolower($keyword_query); // Gunakan keyword_query dari POST

        $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
        $vectorizer->fit($corpus);
        $vectorizer->transform($corpus);

        $transformer = new TfIdfTransformer($corpus);
        $transformer->transform($corpus);

        $sample_data = $corpus;
        $total = count($sample_data);
        $q_index = $total - 1;

        // --- 3. Looping Perhitungan Similarity ---
        foreach ($articles as $i => &$article) {
            $numerator = 0.0;
            $denom_wkq = 0.0;
            $denom_wkj = 0.0;
            $sum_min = 0.0;

            $current_doc = (array)$sample_data[$i];
            $query_doc = (array)$sample_data[$q_index];
            $feature_count = count($current_doc);

            for ($x = 0; $x < $feature_count; $x++) {
                $w_q = $query_doc[$x];
                $w_d = $current_doc[$x];

                $numerator += $w_q * $w_d;
                $denom_wkq += pow($w_q, 2);
                $denom_wkj += pow($w_d, 2);

                if ($w_q > 0 && $w_d > 0) {
                    $sum_min += min($w_q, $w_d);
                }
            }

            $result = 0;
            switch ($metode_pilihan) {
                case "cosine":
                    if (($denom_wkq * $denom_wkj) != 0) $result = $numerator / (sqrt($denom_wkq * $denom_wkj));
                    break;
                case "jaccard":
                    if (($denom_wkq + $denom_wkj - $numerator) != 0) $result = $numerator / ($denom_wkq + $denom_wkj - $numerator);
                    break;
                case "dice":
                    if ((0.5 * $denom_wkq + 0.5 * $denom_wkj) != 0) $result = $numerator / (0.5 * $denom_wkq + 0.5 * $denom_wkj);
                    break;
                case "overlap":
                    $min_denom = min($denom_wkq, $denom_wkj);
                    if ($min_denom != 0) $result = $numerator / $min_denom;
                    break;
                case "asymmetric":
                    $sum_q = array_sum($query_doc);
                    if ($sum_q != 0) $result = $sum_min / $sum_q;
                    break;
            }
            $article['similarity_score'] = round($result, 3);
        }
        unset($article);

        // Sorting: Terbesar ke terkecil agar tidak error usort
        usort($articles, function ($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });
    }
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
                    <div class="info-item"><span>Penulis:</span> <strong><?php echo htmlspecialchars($_POST['penulis'] ?? '-'); ?></strong></div>
                    <div class="info-item"><span>Keyword:</span> <strong><?php echo htmlspecialchars($_POST['keyword'] ?? '-'); ?></strong></div>
                    <div class="info-item"><span>Metode:</span> <strong><?php echo strtoupper($metode_pilihan); ?></strong></div>
                </div>
                <a href="index.php" class="btn-back">← Cari Lagi</a>
            </div>

            <div class="card_scholar">
                <div class="profile-header">
                    <img src="<?php echo $author['photo'] ?: 'https://via.placeholder.com/100'; ?>" class="profile-img">
                    <div class="profile-details">
                        <h4><?php echo $author['nama']; ?></h4>
                        <p><?php echo $author['univ']; ?></p>
                        <p><?php echo $author['email']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card_table">
            <h3>Hasil Analisis Similarity (Limit: <?php echo htmlspecialchars($_POST['jumlah'] ?? '0'); ?> Data)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Judul Artikel</th>
                        <th>Penulis</th>
                        <th>Tanggal Rilis</th>
                        <th>Nama Jurnal</th>
                        <th>Jumlah Sitasi</th>
                        <th>Link Jurnal</th>
                        <th>Nilai Similaritas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($articles)): ?>
                        <?php foreach ($articles as $row): ?>
                            <tr>
                                <td class="judul"><?php echo $row['judul']; ?></td>
                                <td><?php echo $row['penulis']; ?></td>
                                <td><span class="badge"><?php echo $row['tanggal']; ?></span></td>
                                <td><?php echo $row['jurnal']; ?></td>
                                <td><?php echo $row['sitasi'] ?? '0'; ?></td>
                                <td>
                                    <a href="<?php echo $row['link']; ?>" target="_blank" class="link-jurnal" style="font-size: 11px; word-break: break-all;">
                                        <?php echo $row['link']; ?>
                                    </a>
                                </td>
                                <td style="font-weight: bold; color: #2973B2;">
                                    <?php echo $row['similarity_score']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">Data tidak ditemukan atau Flask belum menyala.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>