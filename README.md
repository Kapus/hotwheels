# Hot Wheels Collector Dashboard

En komplett PHP-applikation för att katalogisera Hot Wheels-bilar från 1968 till idag. Projektet använder MySQL, Bootstrap 5 och Chart.js för att leverera en responsiv upplevelse med inloggning, samlingshantering och erbjudanden mellan användare.

## Funktioner
- Registrering, inloggning och sessioner.
- Katalogvy med sök, filter, sortering och visning av samlar-nummer.
- Hantering av användarens samling inklusive antal, skick, anteckningar och egna customs.
- Profilsida med statistik, TH-ratio samt seriefördelning via Chart.js.
- Skicka köp- och bytesförslag mellan användare.
- Importskript för JSON/CSV-data inklusive fältet `collector_number`.

## Kom igång
1. Skapa databasen:
   ```sql
   SOURCE database/schema.sql;
   ```
2. Uppdatera `config/config.php` med dina MySQL-uppgifter.
3. Kör eventuellt importskriptet:
   ```bash
   php scripts/import_cars.php data/hotwheels.json
   ```
4. Surfa till `http://localhost/hotwheels/public/login.php` och skapa ett konto.

## JSON-format
```json
{
  "name": "Custom Camaro",
  "year": 1968,
  "series": "Original 16",
   "collector_number": "01/16",
  "is_treasure_hunt": false,
  "is_super_treasure": false,
  "image_url": "https://example.com/camaro.jpg"
}
```

## Licens
Projektet är avsett som exempel och kan fritt byggas vidare på.
