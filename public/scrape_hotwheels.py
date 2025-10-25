import requests
from bs4 import BeautifulSoup
import json
import time

BASE_URL = "https://hotwheels.fandom.com/wiki/List_of_{}_Hot_Wheels"
HEADERS = {"User-Agent": "Mozilla/5.0"}
all_cars = []

for year in range(1968, 2026):
    print(f"🔄 Hämtar {year}...")
    url = BASE_URL.format(year)
    res = requests.get(url, headers=HEADERS)
    if res.status_code != 200:
        print(f"❌ Kunde inte hämta {year}")
        continue

    soup = BeautifulSoup(res.text, "html.parser")
    table = soup.find("table")
    if not table:
        print(f"⚠️ Ingen tabell hittad för {year}")
        continue

    for row in table.find_all("tr")[1:]:
        cols = row.find_all("td")
        if len(cols) >= 2:
            name = cols[1].text.strip()
            image = cols[0].find("img")
            image_url = image["src"] if image else ""
            all_cars.append({
                "name": name,
                "year": year,
                "series": None,
                "collector_number": None,
                "is_treasure_hunt": False,
                "is_super_treasure": False,
                "image_url": image_url
            })

    time.sleep(1)

with open("C:/Users/Marcus/Documents/hotwheels_all_years.json", "w", encoding="utf-8") as f:
    json.dump(all_cars, f, indent=2, ensure_ascii=False)

print(f"✅ Klart! Totalt {len(all_cars)} bilar sparade.")
