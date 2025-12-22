from flask import Flask, request, jsonify
from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
import random

app = Flask(__name__)


def run_scraper(penulis_input, limit_data):
    # --- SETUP CHROME OPTIONS ---
    chrome_options = webdriver.ChromeOptions()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")

    # User-Agent asli agar tidak diblokir Google
    user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    chrome_options.add_argument(f"user-agent={user_agent}")

    driver = webdriver.Chrome(
        service=ChromeService(ChromeDriverManager().install()), options=chrome_options
    )

    # Inisialisasi data yang akan dikirim balik
    data_final = {
        "author": {"nama": "-", "univ": "-", "email": "-", "photo": ""},
        "articles": [],
    }

    try:
        # 1. Cari Profil Penulis
        search_url = f"https://scholar.google.com/scholar?hl=en&q={penulis_input.replace(' ', '+')}"
        driver.get(search_url)
        time.sleep(random.uniform(2, 4))

        # Ambil link profil dari hasil pencarian pertama
        profile_elm = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "h4.gs_rt2 a"))
        )
        full_profile_url = profile_elm.get_attribute("href")
        if "scholar.google.com" not in full_profile_url:
            full_profile_url = "https://scholar.google.com" + full_profile_url

        # 2. Masuk ke Halaman Profil & Ambil Biodata
        driver.get(full_profile_url)
        time.sleep(random.uniform(3, 5))

        data_final["author"] = {
            "nama": driver.find_element(By.ID, "gsc_prf_in").text,
            "univ": driver.find_element(By.CLASS_NAME, "gsc_prf_il").text,
            "photo": driver.find_element(By.ID, "gsc_prf_pup-img").get_attribute("src"),
            "email": driver.find_element(By.ID, "gsc_prf_ivh").text,
        }

        # 3. Ambil Artikel dengan Batasan (Limit)
        rows = driver.find_elements(By.CSS_SELECTOR, "tr.gsc_a_tr")
        for row in rows[:limit_data]:  # Memotong jumlah baris sesuai input user
            try:
                title_elm = row.find_element(By.CSS_SELECTOR, "a.gsc_a_at")
                gray_elms = row.find_elements(By.CSS_SELECTOR, "div.gs_gray")
                raw_jurnal = gray_elms[1].text if len(gray_elms) > 1 else "-"
                step1 = raw_jurnal.split(',')[0].strip()
                if ' ' in step1:
                    nama_jurnal_bersih = step1.rsplit(' ', 1)[0]
                else:
                    nama_jurnal_bersih = step1

                data_final["articles"].append(
                    {
                        "judul": title_elm.text,
                        "penulis": gray_elms[0].text if len(gray_elms) > 0 else "-",
                        "jurnal": nama_jurnal_bersih,
                        "tahun": row.find_element(By.CSS_SELECTOR, "td.gsc_a_y").text,
                        "sitasi": row.find_element(
                            By.CSS_SELECTOR, "a.gsc_a_ac"
                        ).text,  # Pastikan baris ini ada
                        "link": title_elm.get_attribute("href"),
                    }
                )
            except:
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
    # Ambil jumlah data dari PHP, default ke 5 jika tidak ada
    jumlah = int(data.get("jumlah", 5))

    if not penulis:
        return jsonify({"status": "error", "message": "Nama penulis kosong"}), 400

    print(f"Mencari {jumlah} data untuk: {penulis}")

    try:
        # Jalankan scraping dinamis
        hasil = run_scraper(penulis, jumlah)

        return jsonify(
            {
                "status": "success",
                "author_info": hasil["author"],
                "articles": hasil["articles"],
            }
        )
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
