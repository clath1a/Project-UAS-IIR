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
    # --- ISI SCRIPT SCRAPING ANDA DI SINI ---
    # (Copy seluruh isi fungsi scrape_scholar_anti_detect Anda ke sini)
    # Pastikan mengembalikan list of dictionary (data_hasil), bukan DataFrame
    # ... (kode scraping Anda) ...
    return data_hasil 

@app.route('/api/scrape', methods=['POST'])
def scrape_api():
    data = request.json
    keyword = data.get('keyword', '')
    
    if not keyword:
        return jsonify({"error": "Keyword tidak boleh kosong"}), 400
    
    try:
        hasil = run_scraper(keyword)
        # Tambahkan data biodata statis/dinamis untuk simulasi (sesuai permintaan Anda sebelumnya)
        response_data = {
            "status": "success",
            "author_info": {
                "nama": "Joko Siswantoro",
                "univ": "University of Surabaya",
                "email": "Verified email at staff.ubaya.ac.id",
                "photo": "https://scholar.googleusercontent.com/citations?view_op=medium_photo&user=9ab9f3bf88999bf79c04b8812d510bca"
            },
            "articles": hasil
        }
        return jsonify(response_data)
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == '__main__':
    # Jalankan di port 5000
    app.run(host='0.0.0.0', port=5000)