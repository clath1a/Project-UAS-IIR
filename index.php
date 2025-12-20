<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Artikel Ilmiah</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <h1>Pencarian Data Artikel Ilmiah</h1>
        <p class="subtitle">Aplikasi untuk mencari jurnal artikel ilmiah </p>
        
        <div class="box">
            <form action="hasil.php" method="POST">
                <div class="input-group">
                    <label for="penulis">Nama Penulis</label>
                    <input type="text" name="penulis" id="penulis" placeholder="Contoh: Joko Siswantoro">
                </div>

                <div class="input-group">
                    <label for="keyword">Keyword Artikel</label>
                    <input type="text" name="keyword" id="keyword" placeholder="Masukkan kata kunci penelitian...">
                </div>

                <div class="input-group">
                    <label for="jumlah">Jumlah Data</label>
                    <input type="number" name="jumlah" id="jumlah" placeholder="Contoh: 10">
                </div>

                <div class="input-group">
                    <label>Metode Similarity</label>
                    <div class="radio-container">
                        <label class="radio-item">
                            <input type="radio" name="similarity" value="cosine"> Cosine Similarity
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="similarity" value="jaccard"> Jaccard Index
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-search">Cari Artikel</button>
            </form>
        </div>
    </div>

</body>
</html>