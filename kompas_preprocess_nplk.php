<?php
require_once __DIR__ . '/vendor/autoload.php';
include_once("simple_html_dom.php");

//buat ngurus proxy ubaya (pakai komp lab)
//$proxy = "proxy3.ubaya.ac.id:8080"; 

// Fungsi untuk menjalankan script Python

function get_nltk_processing($text)
{
    // 1. Membersihkan teks dari karakter yang bermasalah untuk shell
    $clean_text = preg_replace('/\s+/', ' ', $text);

    // 2. Membangun perintah dengan PENGALIHAN ERROR (2>&1)
    // Ini mengalihkan STDERR (error) ke STDOUT (output utama), sehingga shell_exec menangkapnya.
    // Jika 'python3' tidak bekerja, ganti dengan path lengkap atau 'python'.
    $command = 'python nltk_preprocess.py ' . escapeshellarg($clean_text) . ' 2>&1';

    // 3. Menjalankan perintah dan menangkap output (berisi JSON ATAU error)
    $output = shell_exec($command);

    // 4. Decode JSON
    $result = json_decode($output, true);

    // 5. Pemeriksaan output (Logic Perbaikan)
    if (json_last_error() !== JSON_ERROR_NONE || (is_array($result) && (isset($result['error']) || isset($result['error_python'])))) {
        // Jika gagal decode JSON (karena ada error/peringatan Python di output)
        // ATAU jika JSON berhasil didecode tetapi mengandung kunci error (dari Python)
        return [
            'error' => 'Failed to decode JSON or Python script returned error.',
            'raw_output' => $output, // Output mentah (untuk debugging)
            'command_ran' => $command // Perintah yang dijalankan
        ];
    }

    // Jika berhasil didecode dan tidak ada kunci error eksplisit
    return $result;
}

//kalau pake komp sendiri
$proxy = "";
$extract = extract_html("https://www.kompas.com/", $proxy);
$i = 0;
if ($extract['code'] == '200') {
    $html = new simple_html_dom();
    $html->load($extract['message']);
    $i = 0;

    //Sastrawi stemmer untuk bandingan
    $streammerFactory = new \Sastrawi\Stemmer\StemmerFactory();
    $stemmer = $streammerFactory->createStemmer();
    //stopword sastrawi
    $stopwordFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
    $stopword = $stopwordFactory->createStopWordRemover();

    //loop scraping
    foreach ($html->find('div[class="wSpec-item"]') as $news) {
        if ($i > 10) {
            break;
        } else {
            $newsDate = $news->find('span', 0)->innertext;
            $newsTitle = $news->find('h4[class="wSpec-title"]', 0)->innertext;
            $newsLink = $news->find('a', 0)->href;

            //Pemrosesan Sastrawi 
            $stemTittle = $stemmer->stem($newsTitle);
            $stopTitle = $stopword->remove($stemTittle);

            // Pemrosesan NLTK
            $title_for_nltk = strtolower($newsTitle);
            $nltk_results = get_nltk_processing($title_for_nltk);

            echo "News Date: " . $newsDate . "<br>";
            echo "News Title: " . $newsTitle . "<br>";

            // Output Sastrawi
            echo "News Title (Sastrawi Stem): " . $stemTittle . "<br>";
            echo "News Title (Sastrawi Stop): " . $stopTitle . "<br>";
            echo"";
            
            // --- OUTPUT NLTK (Logika Display yang Diperbaiki) ---
            if (isset($nltk_results['error'])) {
                // KASUS ERROR: Mencetak informasi debugging
                echo "<span style='color: red;'>NLTK Error: " . $nltk_results['error'] . "</span><br>";
                echo "Command: " . htmlspecialchars($nltk_results['command_ran']) . "<br>";
                echo "DEBUG: Raw Output from Python: <pre style='background: #fee; border: 1px solid red; padding: 5px;'>" . htmlspecialchars($nltk_results['raw_output']) . "</pre>";
            } else {
                // KASUS SUKSES: Mencetak hasil dari NLTK
                // Sesuaikan nama key sesuai dengan yang Anda definisikan di nltk_preprocess.py
                echo "News Title (NLTK Porter Stem): " . ($nltk_results['porter_stemming'] ?? 'N/A') . "<br>";
                echo "News Title (NLTK WordNet Lemma): " . ($nltk_results['wordnet_lemmatization'] ?? 'N/A') . "<br>";
            }

            // Selalu tampilkan link setelah semua proses teks selesai
            echo "News Link: " . $newsLink . "<br><br>";
        }
        $i++;
    }
}



function extract_html($url, $proxy)
{

    $response = array();

    $response['code'] = '';

    $response['message'] = '';

    $response['status'] = false;

    $agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1';

    // Some websites require referrer

    $host = parse_url($url, PHP_URL_HOST);

    $scheme = parse_url($url, PHP_URL_SCHEME);

    $referrer = $scheme . '://' . $host;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HEADER, false);

    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_URL, $url);

    curl_setopt($curl, CURLOPT_PROXY, $proxy);

    curl_setopt($curl, CURLOPT_USERAGENT, $agent);

    curl_setopt($curl, CURLOPT_REFERER, $referrer);

    curl_setopt($curl, CURLOPT_COOKIESESSION, 0);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

    // allow to crawl https webpages

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

    // the download speed must be at least 1 byte per second

    curl_setopt($curl, CURLOPT_LOW_SPEED_LIMIT, 1);

    // if the download speed is below 1 byte per second for more than 30 seconds curl will give up

    curl_setopt($curl, CURLOPT_LOW_SPEED_TIME, 30);

    $content = curl_exec($curl);

    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $response['code'] = $code;

    if ($content === false) {

        $response['status'] = false;

        $response['message'] = curl_error($curl);
    } else {

        $response['status'] = true;

        $response['message'] = $content;
    }

    curl_close($curl);

    return $response;
}
