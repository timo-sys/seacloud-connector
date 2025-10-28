# Seafile API Client voor PHP

Een eenvoudige, standalone PHP client voor communicatie met de Seafile API. Geen dependencies, geen Composer, gewoon 1 PHP bestand.

## Features

✅ **Eenvoudig** - Slechts 1 bestand nodig (`SeafileClient.php`)
✅ **Geen dependencies** - Alleen PHP met cURL
✅ **SQL-achtige queries** - Geavanceerd zoeken met filters
✅ **Volledig gedocumenteerd** - Nederlandse handleiding inclusief
✅ **Business Edition ready** - Werkt met API keys
✅ **Veel voorbeelden** - Complete examples.php met 13+ voorbeelden

## Snelstart

```php
<?php
require_once 'SeafileClient.php';

// 1. Verkrijg API token
$token = SeafileClient::getToken(
    'https://jouw-seafile-server.com',
    'gebruiker@email.com',
    'wachtwoord'
);

// 2. Maak client
$client = new SeafileClient('https://jouw-seafile-server.com', $token);

// 3. Gebruik de API!
$libraries = $client->getLibraries();
foreach ($libraries as $lib) {
    echo $lib['name'] . "\n";
}
```

## Installatie

Download het bestand:

```bash
wget https://jouw-repo/SeafileClient.php
```

Of kopieer gewoon `SeafileClient.php` naar je project. Dat is alles!

## Wat kun je ermee doen?

### Account & Server
- ✅ Connectie testen (`ping()`)
- ✅ Account info ophalen (`getAccountInfo()`)

### Bibliotheken
- ✅ Alle libraries ophalen (`getLibraries()`)
- ✅ Specifieke library ophalen (`getLibrary()`)

### Bestanden & Mappen
- ✅ Directory inhoud tonen (`listDirectory()`)
- ✅ Bestand details (`getFileDetail()`)
- ✅ Bestanden downloaden (`getDownloadLink()`)
- ✅ Bestanden uploaden (`uploadFile()`)
- ✅ Mappen aanmaken (`createDirectory()`)
- ✅ Verwijderen (`delete()`)
- ✅ Hernoemen (`rename()`)
- ✅ Kopiëren (`copy()`)
- ✅ Verplaatsen (`move()`)

### Zoeken (SQL-achtig)
- ✅ Basis zoeken (`search()`)
- ✅ Geavanceerd zoeken met filters (`advancedSearch()`)
  - Filter op type (file/dir)
  - Filter op grootte (size_from/size_to)
  - Filter op datum (time_from/time_to)
  - Filter op locatie (repo_id/path)

### Delen
- ✅ Gedeelde links ophalen (`getSharedLinks()`)
- ✅ Nieuwe link maken (`createShareLink()`)
- ✅ Link verwijderen (`deleteShareLink()`)

## SQL-achtige queries

```php
// Zoek PDF bestanden > 5MB uit laatste maand
$results = $client->advancedSearch([
    'query' => 'pdf',
    'obj_type' => 'file',
    'size_from' => 5242880,           // 5MB
    'time_from' => strtotime('-30 days')
]);

// Zoek in specifieke map
$results = $client->advancedSearch([
    'query' => 'rapport',
    'path' => '/Documents/2024/'
]);

// Bestanden tussen 1-10MB
$results = $client->advancedSearch([
    'query' => '*',
    'size_from' => 1048576,   // 1MB
    'size_to' => 10485760     // 10MB
]);
```

## Voorbeelden

Zie `examples.php` voor 13 complete voorbeelden:

```bash
php examples.php
```

Voorbeelden omvatten:
1. Token verkrijgen
2. Connectie testen
3. Account informatie
4. Bibliotheken ophalen
5. Directory inhoud tonen
6. Zoeken in bestanden
7. Geavanceerd zoeken met filters
8. Bestand details
9. Download links
10. Bestanden uploaden
11. Mappen aanmaken
12. Gedeelde links
13. Complexe SQL-achtige queries

## Documentatie

Lees de volledige **[Nederlandse handleiding](HANDLEIDING.md)** voor:
- Gedetailleerde installatie instructies
- Complete API referentie
- Foutafhandeling
- Best practices
- FAQ
- Troubleshooting

## Vereisten

- PHP 7.0 of hoger
- cURL extensie
- Toegang tot Seafile server (Community/Business/Professional Edition)

## Bestanden

- **`SeafileClient.php`** - De hoofdclient (dit is het enige bestand dat je nodig hebt!)
- **`examples.php`** - 13 praktische voorbeelden
- **`HANDLEIDING.md`** - Uitgebreide Nederlandse documentatie
- **`README.md`** - Dit bestand

## Licentie

Open-source, vrij te gebruiken voor commerciële en niet-commerciële doeleinden.

## Links

- [Officiële Seafile API Documentatie](https://seafile-api.readme.io/)
- [Seafile Website](https://www.seafile.com/)

---

**Gemaakt voor eenvoudige Seafile API integratie** 🚀
