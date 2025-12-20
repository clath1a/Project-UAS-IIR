from flask import Flask, request, jsonify
from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import pandas as pd
import time
import random

app = Flask(__name__)

def run_scraper(keyword_input):
    chrome_options = webdriver.ChromeOptions()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--window-size=1920,1080")

    # Anti-detect (Penting agar tidak kena Captcha di Localhost)
    user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    chrome_options.add_argument(f'user-agent={user_agent}')

    print("Memulai Browser...")
    driver = webdriver.Chrome(
        service=ChromeService(ChromeDriverManager().install()), options=chrome_options
    )

    data_hasil = []

    try:
        base_url = "https://scholar.google.com/scholar?hl=en&as_sdt=0%2C5&q="
        query_formatted = keyword_input.replace(" ", "+")
        search_url = f"{base_url}{query_formatted}&btnG="

        driver.get(search_url)
        time.sleep(random.uniform(2, 4))

        # Cari Profile
        try:
            profile_element = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "h4.gs_rt2 a"))
            )
            full_profile_url = profile_element.get_attribute("href")
            if "scholar.google.com" not in full_profile_url:
                full_profile_url = "https://scholar.google.com" + full_profile_url
        except Exception:
            return [] # Kembalikan list kosong jika tidak ada profile

        # Masuk ke Profile
        driver.get(full_profile_url)
        time.sleep(random.uniform(3, 5))

        rows = driver.find_elements(By.CSS_SELECTOR, "tr.gsc_a_tr")
        for row in rows[:15]: # Kita batasi 15 data agar loading tidak terlalu lama
            try:
                title_elm = row.find_element(By.CSS_SELECTOR, "a.gsc_a_at")
                gray_elms = row.find_elements(By.CSS_SELECTOR, "div.gs_gray")
                
                data_hasil.append({
                    "judul": title_elm.text,
                    "penulis": gray_elms[0].text if len(gray_elms) > 0 else "-",
                    "jurnal": gray_elms[1].text if len(gray_elms) > 1 else "-",
                    "tahun": row.find_element(By.CSS_SELECTOR, "td.gsc_a_y").text,
                    "sitasi": row.find_element(By.CSS_SELECTOR, "a.gsc_a_ac").text,
                    "link": title_elm.get_attribute("href"),
                })
            except:
                continue

    finally:
        driver.quit()

    return data_hasil # MENGEMBALIKAN LIST, BUKAN DATAFRAME

@app.route("/api/scrape", methods=["POST"])
def scrape_api():
    data = request.json
    keyword = data.get("keyword", "")

    if not keyword:
        return jsonify({"error": "Keyword kosong"}), 400

    try:
        # Jalankan fungsi scraper
        hasil_list = run_scraper(keyword)
        
        response_data = {
            "status": "success",
            "author_info": {
                "nama": "Joko Siswantoro",
                "univ": "University of Surabaya",
                "email": "Verified email at staff.ubaya.ac.id",
                "photo": "https://scholar.googleusercontent.com/citations?view_op=medium_photo&user=9ab9f3bf88999bf79c04b8812d510bca",
            },
            "articles": hasil_list,
        }
        return jsonify(response_data)
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)