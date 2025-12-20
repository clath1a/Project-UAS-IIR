from scrapy import Selector
import requests

resp = requests.get('https://www.detik.com')
resp.raise_for_status()
sel = Selector(body=resp.content, type="html")

i = 0
for item in sel.css('article.ph_newsfeed_d'):
  if i == 10: break
  else:
    title = item.css('a.media__link::attr(dtr-ttl)').get()
    date = item.css('div.media__date').get().strip()
    link = item.css('a.media__link::attr(href)').get()

    print("News Date :",date, "<br>")
    print("News Title :",title, "<br>")
    print("News Link :",link, "<br>")
    print("<br>")

    i += 1
