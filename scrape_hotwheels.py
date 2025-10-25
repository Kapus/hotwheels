"""Extensive Hot Wheels Wiki scraper with year lists and category coverage."""
from __future__ import annotations

import json
import re
import time
from dataclasses import dataclass, asdict
from pathlib import Path
from typing import Iterable, Optional

import requests
from bs4 import BeautifulSoup, Tag

BASE_URL = "https://hotwheels.fandom.com/wiki/List_of_{}_Hot_Wheels"
API_URL = "https://hotwheels.fandom.com/api.php"
OUTPUT_PATH = Path("C:/Users/Marcus/Documents/hotwheels_all_years.json")
START_YEAR = 1968
END_YEAR = 2025
REQUEST_TIMEOUT = 25
SLEEP_SECONDS = 1
HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/119.0 Safari/537.36"
    )
}
CATEGORY_TARGETS: tuple[tuple[str, dict[str, bool]], ...] = (
    ("Category:Hot_Wheels_cars", {}),
    ("Category:Hot_Wheels_Mainline", {}),
    ("Category:Treasure_Hunts", {"is_treasure_hunt": True}),
    ("Category:Super_Treasure_Hunts", {"is_treasure_hunt": True, "is_super_treasure": True}),
)
TREASURE_PATTERNS = (
    re.compile(r"treasure\s*hunt", re.IGNORECASE),
    re.compile(r"\bTH\b", re.IGNORECASE),
)
SUPER_PATTERNS = (
    re.compile(r"super\s*treasure", re.IGNORECASE),
    re.compile(r"\bSTH\b", re.IGNORECASE),
)


@dataclass
class CarRecord:
    name: str
    year: Optional[int]
    series: Optional[str]
    collector_number: Optional[str]
    is_treasure_hunt: bool
    is_super_treasure: bool
    image_url: str

    def serialize(self) -> dict:
        return asdict(self)


def contains_pattern(text: str, patterns: Iterable[re.Pattern]) -> bool:
    return any(pattern.search(text) for pattern in patterns)


def extract_text(cell: Optional[Tag]) -> str:
    return cell.get_text(strip=True) if cell else ""


def find_column_index(headers: list[str], candidates: Iterable[str]) -> Optional[int]:
    for idx, header in enumerate(headers):
        if any(candidate in header for candidate in candidates):
            return idx
    return None


def parse_table(table: Tag, year: Optional[int]) -> list[CarRecord]:
    rows = table.find_all("tr")
    if not rows:
        return []

    header_cells = [extract_text(cell).lower() for cell in rows[0].find_all(["th", "td"])]
    image_idx = find_column_index(header_cells, ("image", "photo", "picture"))
    name_idx = find_column_index(header_cells, ("name", "model", "car"))
    series_idx = find_column_index(header_cells, ("series", "subset", "segment"))
    number_idx = find_column_index(header_cells, ("number", "collector", "#", "no"))

    if image_idx is None:
        image_idx = 0
    if name_idx is None:
        name_idx = 1 if image_idx == 0 else 0

    cars: list[CarRecord] = []

    for row in rows[1:]:
        cells = row.find_all(["td", "th"])
        if not cells or name_idx >= len(cells):
            continue

        name = extract_text(cells[name_idx])
        if not name:
            continue

        series = extract_text(cells[series_idx]) if series_idx is not None and series_idx < len(cells) else ""
        collector_number = extract_text(cells[number_idx]) if number_idx is not None and number_idx < len(cells) else ""

        image_cell = cells[image_idx] if image_idx < len(cells) else None
        image_tag = image_cell.find("img") if image_cell else None
        image_url = image_tag["src"].strip() if image_tag and image_tag.has_attr("src") else ""

        label_source = f"{name} {series}".strip()
        is_super = contains_pattern(label_source, SUPER_PATTERNS)
        is_treasure = contains_pattern(label_source, TREASURE_PATTERNS) or is_super

        cars.append(
            CarRecord(
                name=name,
                year=year,
                series=series or None,
                collector_number=collector_number or None,
                is_treasure_hunt=is_treasure,
                is_super_treasure=is_super,
                image_url=image_url,
            )
        )

    return cars


def scrape_wiki_page(title: str, year: Optional[int], flags: Optional[dict[str, bool]] = None) -> list[CarRecord]:
    url = f"https://hotwheels.fandom.com/wiki/{title.replace(' ', '_')}"
    try:
        response = requests.get(url, headers=HEADERS, timeout=REQUEST_TIMEOUT)
        response.raise_for_status()
    except requests.RequestException as exc:
        print(f"[PAGE] Failed to fetch {title}: {exc}")
        return []

    soup = BeautifulSoup(response.text, "html.parser")
    tables = soup.find_all("table", class_="wikitable") or soup.find_all("table")
    if not tables:
        print(f"[PAGE] No tables found on {title}")
        return []

    cars: list[CarRecord] = []
    for table in tables:
        cars.extend(parse_table(table, year))

    if flags:
        for car in cars:
            if flags.get("is_super_treasure"):
                car.is_super_treasure = True
                car.is_treasure_hunt = True
            if flags.get("is_treasure_hunt"):
                car.is_treasure_hunt = True

    print(f"[PAGE] {title} yielded {len(cars)} cars.")
    return cars


def scrape_year_lists() -> list[CarRecord]:
    results: list[CarRecord] = []
    for year in range(START_YEAR, END_YEAR + 1):
        title = BASE_URL.format(year).split("/wiki/")[-1]
        year_cars = scrape_wiki_page(title, year)
        results.extend(year_cars)
        time.sleep(SLEEP_SECONDS)
    return results


def fetch_category_members(category: str) -> list[str]:
    members: list[str] = []
    cont: dict[str, str] = {}

    while True:
        params = {
            "action": "query",
            "list": "categorymembers",
            "cmtitle": category,
            "cmlimit": 500,
            "format": "json",
            "cmtype": "page",
            **cont,
        }
        try:
            response = requests.get(API_URL, params=params, headers=HEADERS, timeout=REQUEST_TIMEOUT)
            response.raise_for_status()
        except requests.RequestException as exc:
            print(f"[API] Failed to fetch category {category}: {exc}")
            break

        payload = response.json()
        batch = [item["title"] for item in payload.get("query", {}).get("categorymembers", [])]
        members.extend(batch)

        cont = payload.get("continue", {})
        if not cont:
            break

        time.sleep(0.25)

    print(f"[API] Category {category} produced {len(members)} pages.")
    return members


def scrape_categories() -> list[CarRecord]:
    aggregate: list[CarRecord] = []
    for category, flags in CATEGORY_TARGETS:
        titles = fetch_category_members(category)
        for title in titles:
            aggregate.extend(scrape_wiki_page(title, None, flags))
            time.sleep(SLEEP_SECONDS)
    return aggregate


def deduplicate(records: list[CarRecord]) -> list[CarRecord]:
    by_name: dict[tuple[str, Optional[int]], CarRecord] = {}
    by_number: dict[tuple[Optional[int], str], CarRecord] = {}

    def merge(into: CarRecord, other: CarRecord) -> None:
        into.series = into.series or other.series
        into.collector_number = into.collector_number or other.collector_number
        into.image_url = into.image_url or other.image_url
        into.is_treasure_hunt = into.is_treasure_hunt or other.is_treasure_hunt
        into.is_super_treasure = into.is_super_treasure or other.is_super_treasure

    for record in records:
        name_key = (record.name.strip().lower(), record.year)
        number_key: Optional[tuple[Optional[int], str]] = None

        if record.collector_number:
            number_key = (record.year, record.collector_number.strip().lower())
            if number_key in by_number:
                merge(by_number[number_key], record)
                continue

        if name_key in by_name:
            existing = by_name[name_key]
            merge(existing, record)
        else:
            by_name[name_key] = record
            if number_key:
                by_number[number_key] = record

    return list(by_name.values())


def main() -> None:
    print("Scraping annual lists...")
    year_records = scrape_year_lists()

    print("Scraping category collections...")
    category_records = scrape_categories()

    combined = deduplicate(year_records + category_records)

    total_treasure = sum(1 for car in combined if car.is_treasure_hunt)
    total_super = sum(1 for car in combined if car.is_super_treasure)

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    with OUTPUT_PATH.open("w", encoding="utf-8") as handle:
        json.dump([record.serialize() for record in combined], handle, ensure_ascii=False, indent=2)

    print(f"Combined cars scraped: {len(combined)}")
    print(f"Treasure Hunts flagged: {total_treasure}")
    print(f"Super Treasure Hunts flagged: {total_super}")
    print(f"JSON created at: {OUTPUT_PATH}")


if __name__ == "__main__":
    main()