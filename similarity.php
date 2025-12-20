<?php
require_once __DIR__.'/vendor/autoload.php';

use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Math\Distance\Euclidean;
use Phpml\Math\Distance\Manhattan;
use Phpml\Math\Distance\Chebyshev;
use Phpml\Math\Distance\Minkowski;
use Phpml\Math\Distance\Canberra;


// D1-D4 (urut) di index 0 - 3
$sample_data = [
    "dolar naik harga naik hasil turun", 
    "harga naik harus gaji naik",
    "premium tidak pengaruh dolar",
    "harga laptop naik",
    "naik harga"
];

// TF raw
$tf = new TokenCountVectorizer(new WhitespaceTokenizer());
$tf->fit($sample_data);
$tf->transform($sample_data);
//print_r($sample_data);

// TF-IDF
$tfidf = new TfIdfTransformer($sample_data);
$tfidf->transform($sample_data);

//echo "<br><br>";
//print_r($sample_data);

// Hitung jarak
$total = count($sample_data);

//abaikan errornya
echo "<br><br><b><u>SIMILARITY BASED ON DISTANCE</u></b><br>";

echo "<b><u>Euclidean</u></b><br>";
$euclidean = new Euclidean();
for ($i = 0; $i < $total - 1; $i++) {
    $result = $euclidean->distance($sample_data[$total-1], $sample_data[$i]);
    echo "D".($i+1)." dan Q = ".round($result, 2)."<br>";
}

echo "<br><b><u>Manhattan</u></b><br>";
$manhattan = new Manhattan();
for ($i = 0; $i < $total - 1; $i++) {
    $result = $manhattan->distance($sample_data[$i], $sample_data[$total-1]);
    echo "D".($i+1)." dan Q = ".round($result, 2)."<br>";
}

echo "<br><b><u>Chebyshev</u></b><br>";
$chebyshev = new Chebyshev();
for ($i = 0; $i < $total - 1; $i++) {
    $result = $chebyshev->distance($sample_data[$i], $sample_data[$total-1]);
    echo "D".($i+1)." dan Q = ".round($result, 2)."<br>";
}

echo "<br><b><u>Minkowski</u></b><br>";
$minkowski = new Minkowski();
for ($i = 0; $i < $total - 1; $i++) {
    $result = $minkowski->distance($sample_data[$i], $sample_data[$total-1]);
    echo "D".($i+1)." dan Q = ".round($result, 2)."<br>";
}

echo "<br><b><u>Canberra</u></b><br>";
$canberra = new Canberra();
for ($i = 0; $i < $total - 1; $i++) {
    $result = $canberra->distance($sample_data[$i], $sample_data[$total-1]);
    echo "D".($i+1)." dan Q = ".round($result, 2)."<br>";
}
?>
