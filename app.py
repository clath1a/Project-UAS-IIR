from flask import Flask, request, jsonify
from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
import random
import re
import nltk
from nltk.tokenize import word_tokenize
from nltk.stem import PorterStemmer
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from googletrans import Translator

# Inisialisasi
translator = Translator()
english_stemmer = PorterStemmer()
indonesian_stemmer = StemmerFactory().create_stemmer()

# Download resource NLTK
nltk.download("punkt")

# Inisialisasi Stemmer
english_stemmer = PorterStemmer()
factory = StemmerFactory()
indonesian_stemmer = factory.create_stemmer()

app = Flask(__name__)


def preprocess_hybrid(text):
    # pembersihan sederhana, kapital ke huruf kecil dan cleaning simbol
    text = text.lower()
    text = re.sub(r"[^a-z\s]", "", text)

    # mendeteksi bahasa dengan googletrans
    try:
        lang = translator.detect(text).lang
    except:
        lang = "en"  # Default english jika gagal deteksi

    tokens = word_tokenize(text)
    stemmed_tokens = []

    # logika if untuk memilih Stemmer berdasarkan bahasa yang terdeteksi
    for token in tokens:
        if lang == "id":
            # Jika terdeteksi Indonesia = Sastrawi
            stemmed_tokens.append(indonesian_stemmer.stem(token))
        else:
            # Jika terdeteksi Inggris = Porter (NLTK)
            stemmed_tokens.append(english_stemmer.stem(token))

    return " ".join(stemmed_tokens)

    """
    Fungsi untuk membersihkan teks, tokenisasi, dan stemming 
    untuk kedua bahasa (Hybrid).
    """
    # 1. Case Folding & Remove Special Characters
    text = text.lower()
    text = re.sub(r"[^a-z\s]", "", text)

    # 2. Tokenization
    tokens = word_tokenize(text)

    # 3. Stemming (Hybrid approach)
    # Karena kita tidak tahu bahasa per kata, kita jalankan keduanya
    # atau prioritaskan salah satu.
    stemmed_tokens = []
    for token in tokens:
        # Jalankan Indonesian Stemmer
        stemmed = indonesian_stemmer.stem(token)
        # Jalankan English Stemmer pada hasil sebelumnya
        stemmed = english_stemmer.stem(stemmed)
        stemmed_tokens.append(stemmed)

    return " ".join(stemmed_tokens)


def run_scraper(penulis_input, limit_data):
    # SETUP CHROME OPTIONS 
    chrome_options = webdriver.ChromeOptions()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")

    # menggunakan useragent asli agar tidak diblokir google
    user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    chrome_options.add_argument(f"user-agent={user_agent}")

    driver = webdriver.Chrome(
        service=ChromeService(ChromeDriverManager().install()), options=chrome_options
    )

    # inisialisasi data
    data_final = {
        "author": {"nama": "-", "univ": "-", "email": "-", "photo": ""},
        "articles": [],
    }

    try:
        # utk cari profile penulis
        search_url = f"https://scholar.google.com/scholar?hl=en&q={penulis_input.replace(' ', '+')}"
        driver.get(search_url)
        time.sleep(random.uniform(2, 4))

        # get link profile dari hasil search pertama
        profile_elm = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "h4.gs_rt2 a"))
        )
        full_profile_url = profile_elm.get_attribute("href")
        if "scholar.google.com" not in full_profile_url:
            full_profile_url = "https://scholar.google.com" + full_profile_url

        # mengabmil biodata dengan cara masuk ke link tsb
        driver.get(full_profile_url)
        time.sleep(random.uniform(3, 5))

        data_final["author"] = {
            "nama": driver.find_element(By.ID, "gsc_prf_in").text,
            "univ": driver.find_element(By.CLASS_NAME, "gsc_prf_il").text,
            "photo": driver.find_element(By.ID, "gsc_prf_pup-img").get_attribute("src"),
            "email": driver.find_element(By.ID, "gsc_prf_ivh").text,
        }

        # mengambil artikel dengan limit
        rows = driver.find_elements(By.CSS_SELECTOR, "tr.gsc_a_tr")
        for row in rows[:limit_data]:  # memotong jumlah baris sesuai input user
            try:
                title_elm = row.find_element(By.CSS_SELECTOR, "a.gsc_a_at")
                judul_asli = title_elm.text

                # PRE-PROCESSING
                judul_processed = preprocess_hybrid(judul_asli)
                detail_url = title_elm.get_attribute("href")
                main_window = driver.current_window_handle

                # membuka link detail di tab baru, agar tidak kehilangan daftar utama
                driver.execute_script(f"window.open('{detail_url}', '_blank');")
                driver.switch_to.window(driver.window_handles[1])
                time.sleep(random.uniform(1, 2))  # harus menunggu loading selesai

                # crawl tanggal rilis
                try:
                    # cari teks "date"
                    fields = driver.find_elements(By.CLASS_NAME, "gsc_oci_field")
                    values = driver.find_elements(By.CLASS_NAME, "gsc_oci_value")
                    tanggal_lengkap = "-"

                    for idx, field in enumerate(fields):
                        if "date" in field.text.lower():
                            tanggal_lengkap = values[idx].text  
                            break
                except:
                    tanggal_lengkap = row.find_element(
                        By.CSS_SELECTOR, "td.gsc_a_y"
                    ).text

                # close tab detail dan kembali
                driver.close()
                driver.switch_to.window(main_window)

                # pembersihan nama journal
                gray_elms = row.find_elements(By.CSS_SELECTOR, "div.gs_gray")
                raw_jurnal = gray_elms[1].text if len(gray_elms) > 1 else "-"
                jurnal_clean = (
                    raw_jurnal.split(",")[0].rsplit(" ", 1)[0]
                    if " " in raw_jurnal
                    else raw_jurnal
                )

                data_final["articles"].append(
                    {
                        "judul": judul_asli,  
                        "judul_cleaned": judul_processed,
                        "penulis": gray_elms[0].text,
                        "tanggal": tanggal_lengkap, 
                        "jurnal": jurnal_clean,
                        "sitasi": row.find_element(By.CSS_SELECTOR, "a.gsc_a_ac").text,
                        "link": detail_url,
                    }
                )
            except Exception as e:
                # Jika error, pastikan balik ke tab utama
                if len(driver.window_handles) > 1:
                    driver.close()
                    driver.switch_to.window(driver.window_handles[0])
                continue

    except Exception as e:
        print(f"Error Scraper: {e}")
    finally:
        driver.quit()

    return data_final


@app.route("/api/scrape", methods=["POST"])
def scrape_api():
    data = request.json
    penulis = data.get("penulis", "")
    keyword_input = data.get("keyword", "")
    # get jumlah data dari PHP, default = 5 jika user tidak input
    jumlah = int(data.get("jumlah", 5))

    if not penulis:
        return jsonify({"status": "error", "message": "Nama penulis kosong"}), 400

    print(f"Mencari {jumlah} data untuk: {penulis}")

    try:
        hasil = run_scraper(penulis, jumlah)
        keyword_cleaned = preprocess_hybrid(keyword_input) if keyword_input else ""
        return jsonify(
            {
                "status": "success",
                "author_info": hasil["author"],
                "articles": hasil["articles"],
                "keyword_cleaned": keyword_cleaned
            }
        )
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
