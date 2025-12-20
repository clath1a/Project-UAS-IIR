import requests as rq  # library untuk mengirim request HTTP ke website
from bs4 import BeautifulSoup as bs  # library untuk parsing HTML

# Mengirim request GET ke halaman utama kompas.com
page = rq.get('https://www.detik.com/')

# Menampilkan status code dari request
# 200 = berhasil, 404 = tidak ditemukan, 500 = error server
print(page.status_code)

# Parsing isi halaman web ke dalam objek BeautifulSoup agar mudah diolah, 'html.parser' itu untuk menerjemahkan
soup = bs(page.content, 'html.parser')

# Mencari semua elemen <div> dengan class "wSpec-item" (setiap item beritanya), kalau mau 10 aja [:10]: tambahkan itu
for news in soup.find_all("article", {"class": "ph_newsfeed_d"}):

    # Mengambil teks judul berita dari elemen <h4> dengan class "wSpec-title"
    title = news.find("h3", {"class": "media__title"}).text.strip() # untuk yang ph_newsfeed_d

    # Mengambil teks tanggal/waktu dari elemen <span>
    img = news.get("i-img")

    # Mengambil link berita dari elemen <a> (atribut href)
    link = news.find("a", {"class":"media__link"}).get('href')

    # Mengambil category
    date = news.find("div", {"class":"media__date"}).text.strip()

    # Menampilkan hasil ekstraksi berita

    print("<tr>")
    print(f"<td><img src='{img}'></td>")
    print(f"<td>{date}</td>")
    print(f"<td>{title}</td>")
    print(f"<td>{link}</td>")
    print("</tr>")