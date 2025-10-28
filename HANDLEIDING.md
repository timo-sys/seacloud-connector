# Seafile API Client - Handleiding

Een eenvoudige PHP client voor communicatie met de Seafile API. Werkt met API tokens van de Seafile Business/Professional Edition.

## Inhoudsopgave

1. [Installatie](#installatie)
2. [Snelstart](#snelstart)
3. [API Token verkrijgen](#api-token-verkrijgen)
4. [Basis gebruik](#basis-gebruik)
5. [SQL-achtige queries](#sql-achtige-queries)
6. [Complete API referentie](#complete-api-referentie)
7. [Foutafhandeling](#foutafhandeling)
8. [Voorbeelden](#voorbeelden)

## Installatie

De client bestaat uit slechts **2 bestanden** die je nodig hebt:

1. `SeafileClient.php` - De hoofdclient
2. `examples.php` - Voorbeeldcode (optioneel)

### Vereisten

- PHP 7.0 of hoger
- cURL extensie ingeschakeld
- Toegang tot een Seafile server (Business/Professional Edition)

### Setup

1. Download de bestanden naar je project:
   ```bash
   curl -O https://jouw-repo/SeafileClient.php
   ```

2. Include de client in je PHP script:
   ```php
   <?php
   require_once 'SeafileClient.php';
   ```

Dat is alles! Geen Composer, geen dependencies, gewoon simpel.

## Snelstart

```php
<?php
require_once 'SeafileClient.php';

// Configuratie
$serverUrl = 'https://jouw-seafile-server.com';
$username = 'jouw@email.com';
$password = 'jouw-wachtwoord';

// Stap 1: Verkrijg een API token
$token = SeafileClient::getToken($serverUrl, $username, $password);

// Stap 2: Maak een client aan
$client = new SeafileClient($serverUrl, $token);

// Stap 3: Gebruik de API!
$libraries = $client->getLibraries();
foreach ($libraries as $lib) {
    echo "Bibliotheek: {$lib['name']}\n";
}
```

## API Token verkrijgen

Je hebt een API token nodig om te authenticeren. Er zijn twee manieren:

### Methode 1: Via username en password (aanbevolen voor eerste keer)

```php
$token = SeafileClient::getToken(
    'https://jouw-seafile-server.com',
    'jouw@email.com',
    'jouw-wachtwoord'
);

if ($token) {
    echo "Je token is: $token\n";
    // Bewaar dit token veilig! Je kunt het hergebruiken.
} else {
    echo "Kon geen token verkrijgen\n";
}
```

### Methode 2: Gebruik een bestaand token

Als je al een token hebt, gebruik het direct:

```php
$client = new SeafileClient(
    'https://jouw-seafile-server.com',
    'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0'
);
```

**💡 Tip:** Bewaar je token in een configuratiebestand of omgevingsvariabele, niet hardcoded in je code!

```php
// .env bestand of config.php
define('SEAFILE_TOKEN', 'jouw-token-hier');

// In je script
$client = new SeafileClient($serverUrl, SEAFILE_TOKEN);
```

## Basis gebruik

### Connectie testen

```php
if ($client->ping()) {
    echo "Verbinding OK!\n";
} else {
    echo "Geen verbinding: " . $client->getLastError() . "\n";
}
```

### Account informatie ophalen

```php
$info = $client->getAccountInfo();
echo "Gebruiker: {$info['email']}\n";
echo "Ruimte gebruikt: {$info['usage']} bytes\n";
```

### Bibliotheken (repositories) ophalen

```php
$libraries = $client->getLibraries();
foreach ($libraries as $lib) {
    echo "ID: {$lib['id']}\n";
    echo "Naam: {$lib['name']}\n";
    echo "Grootte: {$lib['size']} bytes\n";
}
```

### Directory inhoud bekijken

```php
$libraryId = 'je-library-id-hier';
$items = $client->listDirectory($libraryId, '/');

foreach ($items as $item) {
    if ($item['type'] === 'dir') {
        echo "[MAP]    {$item['name']}\n";
    } else {
        echo "[BESTAND] {$item['name']} ({$item['size']} bytes)\n";
    }
}
```

### Bestanden uploaden

```php
$result = $client->uploadFile(
    $libraryId,           // Library ID
    '/pad/naar/lokaal-bestand.pdf',  // Lokaal bestand
    '/Documents/',        // Remote map
    'nieuw-bestand.pdf'   // Nieuwe naam (optioneel)
);

if ($result) {
    echo "Upload geslaagd! ID: {$result['id']}\n";
}
```

### Bestanden downloaden

```php
$downloadUrl = $client->getDownloadLink($libraryId, '/document.pdf');

if ($downloadUrl) {
    // Download het bestand
    $fileContent = file_get_contents($downloadUrl);
    file_put_contents('/lokaal/pad/document.pdf', $fileContent);
}
```

## SQL-achtige queries

De Seafile API ondersteunt geen échte SQL, maar de client biedt SQL-achtige functionaliteit via zoeken en filters.

### Basis zoeken (LIKE operator)

```php
// SELECT * FROM files WHERE name LIKE '%rapport%'
$results = $client->search('rapport');

foreach ($results['results'] as $file) {
    echo "{$file['name']} - {$file['fullpath']}\n";
}
```

### Geavanceerd zoeken met filters (WHERE clausules)

```php
// SELECT * FROM files
// WHERE type = 'file'
// AND size > 1048576
// AND modified_time > '2024-01-01'

$results = $client->advancedSearch([
    'query' => '*',                    // Zoekterm (* = alles)
    'obj_type' => 'file',             // WHERE type = 'file'
    'size_from' => 1048576,           // WHERE size > 1MB
    'time_from' => strtotime('2024-01-01')  // WHERE modified > datum
]);
```

### Beschikbare filters (WHERE clausules)

| Filter | SQL equivalent | Beschrijving |
|--------|---------------|--------------|
| `query` | `LIKE '%term%'` | Zoekterm (verplicht) |
| `repo_id` | `library_id = 'xxx'` | Specifieke bibliotheek |
| `path` | `path LIKE '/Documents%'` | Specifiek pad |
| `obj_type` | `type = 'file'/'dir'` | Bestanden of mappen |
| `size_from` | `size >= bytes` | Minimum bestandsgrootte |
| `size_to` | `size <= bytes` | Maximum bestandsgrootte |
| `time_from` | `modified >= timestamp` | Gewijzigd na datum |
| `time_to` | `modified <= timestamp` | Gewijzigd voor datum |

### Praktische query voorbeelden

```php
// Alle PDF bestanden groter dan 5MB
$pdfs = $client->advancedSearch([
    'query' => 'pdf',
    'obj_type' => 'file',
    'size_from' => 5242880  // 5MB
]);

// Bestanden in een specifieke map
$docs = $client->advancedSearch([
    'query' => '*',
    'path' => '/Documents/2024'
]);

// Recent gewijzigde bestanden (laatste 7 dagen)
$recent = $client->advancedSearch([
    'query' => '*',
    'time_from' => strtotime('-7 days')
]);

// Bestanden in een specifieke bibliotheek tussen 1MB en 10MB
$filtered = $client->advancedSearch([
    'query' => '*',
    'repo_id' => 'abc-123-def-456',
    'size_from' => 1048576,   // 1MB
    'size_to' => 10485760     // 10MB
]);
```

### Query resultaten verwerken

```php
$results = $client->advancedSearch(['query' => 'rapport']);

if ($results) {
    $total = $results['total'];
    $items = $results['results'];

    echo "Totaal gevonden: $total\n";

    foreach ($items as $item) {
        echo "Naam: {$item['name']}\n";
        echo "Pad: {$item['fullpath']}\n";
        echo "Bibliotheek: {$item['repo_name']}\n";
        echo "Grootte: {$item['size']} bytes\n";
        echo "---\n";
    }
} else {
    echo "Fout: " . $client->getLastError() . "\n";
}
```

## Complete API referentie

### Authenticatie

#### `SeafileClient::getToken($baseUrl, $username, $password)`
Verkrijg een API token met username en password.

**Parameters:**
- `$baseUrl` (string): Server URL
- `$username` (string): Gebruikersnaam
- `$password` (string): Wachtwoord

**Returns:** `string|false` - Token of false bij fout

**Voorbeeld:**
```php
$token = SeafileClient::getToken(
    'https://cloud.example.com',
    'user@example.com',
    'password123'
);
```

### Client initialisatie

#### `new SeafileClient($baseUrl, $token)`
Maak een nieuwe client instantie.

**Parameters:**
- `$baseUrl` (string): Server URL
- `$token` (string): API token

**Voorbeeld:**
```php
$client = new SeafileClient('https://cloud.example.com', $token);
```

### Account & Server

#### `ping()`
Test de verbinding met de server.

**Returns:** `bool` - True als verbinding OK

```php
if ($client->ping()) {
    echo "Server is bereikbaar\n";
}
```

#### `getAccountInfo()`
Haal account informatie op.

**Returns:** `array|false`

```php
$info = $client->getAccountInfo();
// [
//   'email' => 'user@example.com',
//   'name' => 'Gebruiker Naam',
//   'usage' => 1234567,
//   'total' => 10737418240
// ]
```

### Bibliotheken (Libraries/Repositories)

#### `getLibraries()`
Haal alle bibliotheken op.

**Returns:** `array|false` - Array van bibliotheken

```php
$libraries = $client->getLibraries();
```

#### `getLibrary($repoId)`
Haal een specifieke bibliotheek op.

**Parameters:**
- `$repoId` (string): Bibliotheek ID

**Returns:** `array|false`

```php
$lib = $client->getLibrary('abc-123-def');
```

### Bestanden & Mappen

#### `listDirectory($repoId, $path = '/')`
Haal directory inhoud op.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Pad (standaard '/')

**Returns:** `array|false` - Array van bestanden/mappen

```php
$items = $client->listDirectory('abc-123', '/Documents/');
```

#### `getFileDetail($repoId, $path)`
Haal gedetailleerde file informatie op.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Bestandspad

**Returns:** `array|false`

```php
$details = $client->getFileDetail('abc-123', '/document.pdf');
// [
//   'id' => 'file-id',
//   'name' => 'document.pdf',
//   'size' => 12345,
//   'mtime' => 1234567890
// ]
```

#### `getDownloadLink($repoId, $path)`
Verkrijg download URL voor een bestand.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Bestandspad

**Returns:** `string|false` - Download URL

```php
$url = $client->getDownloadLink('abc-123', '/file.pdf');
$content = file_get_contents($url);
```

#### `uploadFile($repoId, $localPath, $remotePath = '/', $filename = null)`
Upload een bestand.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$localPath` (string): Lokaal bestandspad
- `$remotePath` (string): Remote map (standaard '/')
- `$filename` (string): Naam op server (optioneel)

**Returns:** `array|false`

```php
$result = $client->uploadFile(
    'abc-123',
    '/tmp/document.pdf',
    '/Documents/',
    'rapport-2024.pdf'
);
```

#### `createDirectory($repoId, $path)`
Maak een nieuwe map aan.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Pad naar nieuwe map

**Returns:** `array|false`

```php
$client->createDirectory('abc-123', '/NieuweMapa');
```

#### `delete($repoId, $path)`
Verwijder een bestand of map.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Pad naar item

**Returns:** `bool`

```php
$client->delete('abc-123', '/oude-map/');
```

#### `rename($repoId, $path, $newName)`
Hernoem een bestand of map.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Huidige pad
- `$newName` (string): Nieuwe naam

**Returns:** `bool`

```php
$client->rename('abc-123', '/oud.txt', 'nieuw.txt');
```

#### `copy($srcRepoId, $srcPath, $dstRepoId, $dstPath)`
Kopieer een bestand of map.

**Parameters:**
- `$srcRepoId` (string): Bron bibliotheek ID
- `$srcPath` (string): Bron pad
- `$dstRepoId` (string): Doel bibliotheek ID
- `$dstPath` (string): Doel pad

**Returns:** `bool`

```php
$client->copy('abc-123', '/file.txt', 'def-456', '/backup/');
```

#### `move($srcRepoId, $srcPath, $dstRepoId, $dstPath)`
Verplaats een bestand of map.

**Parameters:**
- `$srcRepoId` (string): Bron bibliotheek ID
- `$srcPath` (string): Bron pad
- `$dstRepoId` (string): Doel bibliotheek ID
- `$dstPath` (string): Doel pad

**Returns:** `bool`

```php
$client->move('abc-123', '/file.txt', 'abc-123', '/archief/');
```

### Zoeken

#### `search($query, $perPage = 25)`
Basis zoekfunctie.

**Parameters:**
- `$query` (string): Zoekterm
- `$perPage` (int): Resultaten per pagina (standaard 25)

**Returns:** `array|false`

```php
$results = $client->search('rapport', 50);
```

#### `advancedSearch($filters)`
Geavanceerd zoeken met filters.

**Parameters:**
- `$filters` (array): Associatieve array met filters
  - `query` (string, verplicht): Zoekterm
  - `repo_id` (string): Specifieke bibliotheek
  - `path` (string): Specifiek pad
  - `obj_type` (string): 'file' of 'dir'
  - `time_from` (int): Unix timestamp
  - `time_to` (int): Unix timestamp
  - `size_from` (int): Bytes
  - `size_to` (int): Bytes

**Returns:** `array|false`

```php
$results = $client->advancedSearch([
    'query' => 'pdf',
    'obj_type' => 'file',
    'size_from' => 1048576,
    'time_from' => strtotime('-30 days')
]);
```

### Gedeelde links

#### `getSharedLinks()`
Haal alle gedeelde links op.

**Returns:** `array|false`

```php
$links = $client->getSharedLinks();
```

#### `createShareLink($repoId, $path, $password = null, $expireDays = null)`
Maak een gedeelde link.

**Parameters:**
- `$repoId` (string): Bibliotheek ID
- `$path` (string): Bestandspad
- `$password` (string): Optioneel wachtwoord
- `$expireDays` (int): Aantal dagen geldig

**Returns:** `array|false`

```php
$link = $client->createShareLink(
    'abc-123',
    '/document.pdf',
    'geheim123',  // Wachtwoord
    7             // 7 dagen geldig
);
// [
//   'link' => 'https://server.com/f/abc123/',
//   'token' => 'abc123',
//   'expire_date' => '2024-12-31'
// ]
```

#### `deleteShareLink($token)`
Verwijder een gedeelde link.

**Parameters:**
- `$token` (string): Link token

**Returns:** `bool`

```php
$client->deleteShareLink('abc123');
```

## Foutafhandeling

De client retourneert `false` bij fouten. Gebruik `getLastError()` voor details:

```php
$result = $client->getLibraries();

if ($result === false) {
    echo "Fout opgetreden: " . $client->getLastError() . "\n";
} else {
    // Verwerk resultaat
}
```

### Voorbeelden van foutafhandeling

```php
// Probeer een bestand te uploaden
$result = $client->uploadFile($libId, '/pad/naar/bestand.pdf');

if ($result === false) {
    $error = $client->getLastError();

    if (strpos($error, 'HTTP Error 404') !== false) {
        echo "Bibliotheek niet gevonden\n";
    } elseif (strpos($error, 'HTTP Error 403') !== false) {
        echo "Geen toegang\n";
    } elseif (strpos($error, 'Bestand niet gevonden') !== false) {
        echo "Lokaal bestand bestaat niet\n";
    } else {
        echo "Onbekende fout: $error\n";
    }
} else {
    echo "Upload geslaagd!\n";
}
```

### Veelvoorkomende fouten

| Error | Betekenis | Oplossing |
|-------|-----------|-----------|
| `HTTP Error 401` | Ongeldige authenticatie | Controleer je API token |
| `HTTP Error 403` | Geen toegang | Controleer permissies |
| `HTTP Error 404` | Niet gevonden | Controleer bibliotheek/bestand ID |
| `HTTP Error 500` | Server fout | Controleer server logs |
| `CURL Error` | Netwerk probleem | Controleer verbinding/firewall |

## Voorbeelden

Zie het bestand `examples.php` voor uitgebreide voorbeelden van:

- Token verkrijgen
- Connectie testen
- Account informatie ophalen
- Bibliotheken browsen
- Bestanden zoeken
- Bestanden uploaden/downloaden
- Mappen aanmaken
- Bestanden delen
- SQL-achtige queries uitvoeren

### Simpel voorbeeld script

```php
<?php
require_once 'SeafileClient.php';

// Configuratie
$config = [
    'server' => 'https://jouw-server.com',
    'username' => 'jouw@email.com',
    'password' => 'wachtwoord'
];

// Verkrijg token
$token = SeafileClient::getToken(
    $config['server'],
    $config['username'],
    $config['password']
);

if (!$token) {
    die("Kon geen token verkrijgen\n");
}

// Maak client
$client = new SeafileClient($config['server'], $token);

// Test connectie
if (!$client->ping()) {
    die("Geen verbinding met server\n");
}

// Haal bibliotheken op
$libraries = $client->getLibraries();

echo "Je hebt " . count($libraries) . " bibliotheken:\n\n";

foreach ($libraries as $lib) {
    echo "📚 {$lib['name']}\n";
    echo "   ID: {$lib['id']}\n";
    echo "   Grootte: " . formatBytes($lib['size']) . "\n\n";

    // Toon eerste 5 bestanden
    $items = $client->listDirectory($lib['id'], '/');

    if ($items) {
        $count = 0;
        foreach ($items as $item) {
            if ($count++ >= 5) break;

            $icon = $item['type'] === 'dir' ? '📁' : '📄';
            echo "   $icon {$item['name']}\n";
        }
    }
    echo "\n";
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
}
```

## Tips & Best Practices

### 1. Token opslag

❌ **Niet doen:**
```php
$client = new SeafileClient($url, 'hardcoded-token-hier');
```

✅ **Wel doen:**
```php
// Gebruik omgevingsvariabelen
$client = new SeafileClient($url, getenv('SEAFILE_TOKEN'));

// Of een config bestand (buiten webroot!)
$config = require '/var/config/seafile.php';
$client = new SeafileClient($url, $config['token']);
```

### 2. Foutafhandeling

❌ **Niet doen:**
```php
$result = $client->getLibraries();
foreach ($result as $lib) { ... }  // Crash als $result false is!
```

✅ **Wel doen:**
```php
$result = $client->getLibraries();
if ($result === false) {
    error_log("Seafile fout: " . $client->getLastError());
    return;
}
foreach ($result as $lib) { ... }
```

### 3. Grote uploads

Voor grote bestanden, overweeg chunked uploads:

```php
function uploadLargeFile($client, $repoId, $filePath, $remotePath) {
    $maxSize = 100 * 1024 * 1024; // 100MB chunks
    $fileSize = filesize($filePath);

    if ($fileSize > $maxSize) {
        echo "Waarschuwing: groot bestand ($fileSize bytes)\n";
        echo "Dit kan even duren...\n";
    }

    return $client->uploadFile($repoId, $filePath, $remotePath);
}
```

### 4. Rate limiting

Voeg delays toe bij bulk operaties:

```php
$files = glob('/pad/naar/bestanden/*');

foreach ($files as $file) {
    $client->uploadFile($repoId, $file, '/backup/');
    usleep(500000); // 0.5 seconde pauze
}
```

### 5. Batch operaties

Groepeer operaties voor betere performance:

```php
// Haal alle library info in één keer op
$libraries = $client->getLibraries();

// Cache library IDs voor later gebruik
$libCache = [];
foreach ($libraries as $lib) {
    $libCache[$lib['name']] = $lib['id'];
}

// Gebruik cache
$docLibId = $libCache['Documenten'] ?? null;
if ($docLibId) {
    $client->uploadFile($docLibId, '/pad/naar/file.pdf');
}
```

## Veelgestelde vragen (FAQ)

### Hoe krijg ik een API token?

Gebruik de `getToken()` methode met je username en password. Bewaar het token daarna veilig.

### Kan ik SQL queries uitvoeren op Seafile?

Nee, Seafile heeft geen directe SQL interface. Gebruik de `advancedSearch()` methode voor SQL-achtige filtering.

### Werkt dit met Seafile Community Edition?

Ja! Deze client werkt met zowel Community als Business/Professional Edition.

### Hoe upload ik meerdere bestanden tegelijk?

Loop door je bestanden en roep `uploadFile()` aan voor elk bestand:

```php
$files = ['/file1.pdf', '/file2.pdf', '/file3.pdf'];

foreach ($files as $file) {
    $result = $client->uploadFile($libId, $file);
    if ($result) {
        echo "✓ $file geupload\n";
    } else {
        echo "✗ $file mislukt: " . $client->getLastError() . "\n";
    }
}
```

### Hoe download ik een hele map?

Haal eerst de directory listing op, loop door de items:

```php
function downloadDirectory($client, $repoId, $remotePath, $localPath) {
    $items = $client->listDirectory($repoId, $remotePath);

    foreach ($items as $item) {
        if ($item['type'] === 'file') {
            $downloadUrl = $client->getDownloadLink(
                $repoId,
                $remotePath . '/' . $item['name']
            );

            if ($downloadUrl) {
                $content = file_get_contents($downloadUrl);
                file_put_contents($localPath . '/' . $item['name'], $content);
            }
        } elseif ($item['type'] === 'dir') {
            // Recursief voor submappen
            mkdir($localPath . '/' . $item['name']);
            downloadDirectory(
                $client,
                $repoId,
                $remotePath . '/' . $item['name'],
                $localPath . '/' . $item['name']
            );
        }
    }
}
```

### Kan ik verwijderde bestanden herstellen?

Deze client ondersteunt geen directe trash/restore functionaliteit. Gebruik de Seafile web interface hiervoor.

### Hoe kan ik de voortgang van een upload bijhouden?

Voor upload voortgang kun je de cURL PROGRESSFUNCTION callback gebruiken:

```php
// Dit vereist custom implementatie in de request() methode
// Zie PHP cURL documentatie voor CURLOPT_PROGRESSFUNCTION
```

## Troubleshooting

### "CURL Error: SSL certificate problem"

```php
// LET OP: alleen voor development!
// Voeg toe aan de request() methode voor testing:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```

Voor productie: installeer correcte SSL certificaten.

### "HTTP Error 401: Unauthorized"

Je API token is verlopen of ongeldig. Verkrijg een nieuw token:

```php
$token = SeafileClient::getToken($url, $username, $password);
```

### "HTTP Error 500: Internal Server Error"

Server-side probleem. Controleer:
- Seafile server logs
- Disk ruimte op de server
- Database connectie

### Upload mislukt zonder fout

Controleer:
- PHP `upload_max_filesize` en `post_max_size` instellingen
- Seafile server upload limieten
- Bestandspermissies

## Licentie

Deze client is open-source en mag vrijelijk gebruikt worden voor zowel commerciële als niet-commerciële doeleinden.

## Support

Voor vragen of problemen:
- Bekijk de officële Seafile API documentatie: https://seafile-api.readme.io/
- Check de `examples.php` voor praktische voorbeelden
- Raadpleeg deze handleiding voor API referentie

## Changelog

### Versie 1.0
- Initiële release
- Ondersteuning voor basis API operaties
- Zoek en filter functionaliteit
- Bestand upload/download
- Gedeelde links beheer
