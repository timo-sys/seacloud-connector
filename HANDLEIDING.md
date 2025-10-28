# SeaTable API Client - Handleiding

Een eenvoudige PHP client voor communicatie met de SeaTable API. **Ondersteunt volledige SQL queries** (SELECT, INSERT, UPDATE, DELETE). Werkt met API tokens van SeaTable Business/Enterprise Edition.

## Inhoudsopgave

1. [Wat is SeaTable?](#wat-is-seatable)
2. [Installatie](#installatie)
3. [Snelstart](#snelstart)
4. [API Token verkrijgen](#api-token-verkrijgen)
5. [Authenticatie](#authenticatie)
6. [SQL Queries](#sql-queries)
   - [SELECT](#select-queries)
   - [INSERT](#insert-queries)
   - [UPDATE](#update-queries)
   - [DELETE](#delete-queries)
7. [REST API Methoden](#rest-api-methoden)
8. [Complete API Referentie](#complete-api-referentie)
9. [Foutafhandeling](#foutafhandeling)
10. [Voorbeelden](#voorbeelden)

## Wat is SeaTable?

SeaTable is een no-code database platform (vergelijkbaar met Airtable) waarmee je databases kunt maken en beheren via een webinterface. De SeaTable API geeft je programmatische toegang tot je data met:

- **Volledige SQL ondersteuning** - Gebruik echte SQL queries (SELECT, INSERT, UPDATE, DELETE)
- **REST API** - Voor CRUD operaties op individuele rijen
- **No dependencies** - Deze client heeft alleen PHP met cURL nodig
- **Eenvoudig** - Slechts 1 bestand (`SeaTableClient.php`)

### SeaTable Hiërarchie

```
Base (vergelijkbaar met een database)
  └── Table (vergelijkbaar met een tabel)
       └── Rows (records/rijen)
            └── Columns (velden/kolommen)
```

## Installatie

De client bestaat uit slechts **2 bestanden**:

1. `SeaTableClient.php` - De hoofdclient
2. `examples.php` - Voorbeeldcode (optioneel)

### Vereisten

- PHP 7.0 of hoger
- cURL extensie ingeschakeld
- Toegang tot een SeaTable server (Cloud of Self-hosted)
- Een SeaTable API token

### Setup

1. Download `SeaTableClient.php` naar je project

2. Include de client in je PHP script:
   ```php
   <?php
   require_once 'SeaTableClient.php';
   ```

Dat is alles! Geen Composer, geen dependencies, gewoon simpel.

## Snelstart

```php
<?php
require_once 'SeaTableClient.php';

// 1. Maak client aan
$client = new SeaTableClient(
    'https://cloud.seatable.io',  // Server URL
    'jouw-api-token-hier'         // API token
);

// 2. Test authenticatie
if ($client->ping()) {
    echo "Verbonden!\n";
}

// 3. Voer SQL query uit
$result = $client->query("SELECT * FROM Employees WHERE Department = 'Sales' LIMIT 10");

// 4. Verwerk resultaten
foreach ($result['results'] as $row) {
    echo $row['Name'] . "\n";
}
```

## API Token verkrijgen

Je hebt een API token nodig om te authenticeren met de SeaTable API.

### Stappen om een API token te maken:

1. Log in op je SeaTable account
2. Open de base waar je toegang toe wilt
3. Klik op het **menu** icoon (drie puntjes) rechtsboven
4. Selecteer **Advanced** → **API Token**
5. Klik op **Generate API Token**
6. Kies **Read-Write** permissies (of Read-only als je alleen data wilt lezen)
7. Kopieer het gegenereerde token

**💡 Belangrijk:**
- Een API token is **permanent geldig** (vervalt niet)
- Elk API token is gekoppeld aan **één specifieke base**
- Bewaar je token veilig! Voeg het niet toe aan Git

### Token opslaan

**Aanbevolen:** Gebruik omgevingsvariabelen of een config bestand:

```php
// .env of config.php (buiten webroot!)
define('SEATABLE_URL', 'https://cloud.seatable.io');
define('SEATABLE_API_TOKEN', 'jouw-token-hier');

// In je script
$client = new SeaTableClient(SEATABLE_URL, SEATABLE_API_TOKEN);
```

## Authenticatie

SeaTable gebruikt een **twee-staps authenticatie**:

1. **API Token** (permanent) - Jij maakt deze aan in de SeaTable interface
2. **Base Token** (3 dagen geldig) - Wordt automatisch gegenereerd door de client

De SeaTableClient handelt dit automatisch af! Je hoeft alleen je API token te configureren.

```php
$client = new SeaTableClient($serverUrl, $apiToken);

// De client genereert automatisch een base token bij de eerste request
// Base tokens worden automatisch vernieuwd als ze verlopen
```

### Handmatige authenticatie test

```php
if ($client->ping()) {
    echo "Authenticatie succesvol!\n";
    echo "Base UUID: " . $client->getBaseUuid() . "\n";
} else {
    echo "Fout: " . $client->getLastError() . "\n";
}
```

## SQL Queries

**Dit is de krachtigste feature van SeaTable!** Je kunt echte SQL queries gebruiken.

### SELECT Queries

#### Basis SELECT

```php
// Alle rijen
$result = $client->query("SELECT * FROM Employees");

// Specifieke kolommen
$result = $client->query("SELECT Name, Email, Salary FROM Employees");

// Met LIMIT
$result = $client->query("SELECT * FROM Employees LIMIT 100");
```

#### WHERE Clausules

```php
// Vergelijkingen
$result = $client->query("SELECT * FROM Employees WHERE Salary > 50000");

// LIKE operator
$result = $client->query("SELECT * FROM Employees WHERE Name LIKE '%John%'");

// Meerdere voorwaarden
$result = $client->query("
    SELECT * FROM Employees
    WHERE Department = 'Sales'
    AND Salary > 40000
    AND Active = TRUE
");

// IN operator
$result = $client->query("
    SELECT * FROM Employees
    WHERE Department IN ('Sales', 'Marketing', 'IT')
");

// Datum filtering
$result = $client->query("
    SELECT * FROM Employees
    WHERE _ctime >= '2024-01-01 00:00:00'
    AND _ctime < '2025-01-01 00:00:00'
");

// IS NULL / IS NOT NULL
$result = $client->query("SELECT * FROM Employees WHERE Email IS NOT NULL");
```

#### ORDER BY

```php
// Oplopend
$result = $client->query("SELECT * FROM Employees ORDER BY Name ASC");

// Aflopend
$result = $client->query("SELECT * FROM Employees ORDER BY Salary DESC");

// Meerdere kolommen
$result = $client->query("
    SELECT * FROM Employees
    ORDER BY Department ASC, Salary DESC
");
```

#### LIMIT en OFFSET

```php
// Eerste 10 rijen
$result = $client->query("SELECT * FROM Employees LIMIT 10");

// Met offset (paginering)
$result = $client->query("SELECT * FROM Employees LIMIT 10 OFFSET 20");

// Let op: Maximum 10,000 rijen per query!
```

#### Aggregatie Functies

```php
// COUNT
$result = $client->query("SELECT COUNT(*) as total FROM Employees");
$total = $result['results'][0]['total'];

// SUM
$result = $client->query("SELECT SUM(Salary) as total_salary FROM Employees");

// AVG
$result = $client->query("SELECT AVG(Salary) as avg_salary FROM Employees");

// MIN en MAX
$result = $client->query("
    SELECT MIN(Salary) as min_sal, MAX(Salary) as max_sal
    FROM Employees
");
```

#### GROUP BY

```php
// Groeperen
$result = $client->query("
    SELECT Department, COUNT(*) as count
    FROM Employees
    GROUP BY Department
");

// Met meerdere kolommen
$result = $client->query("
    SELECT Department, Active, COUNT(*) as count
    FROM Employees
    GROUP BY Department, Active
");

// Met HAVING
$result = $client->query("
    SELECT Department, AVG(Salary) as avg_salary
    FROM Employees
    GROUP BY Department
    HAVING AVG(Salary) > 50000
");
```

#### JOIN Queries

SeaTable ondersteunt INNER JOIN (sinds versie 4.3):

```php
$result = $client->query("
    SELECT e.Name, e.Email, d.DepartmentName
    FROM Employees e
    INNER JOIN Departments d ON e.DepartmentID = d._id
    WHERE d.Active = TRUE
");
```

#### DISTINCT

```php
// Unieke waarden
$result = $client->query("SELECT DISTINCT Department FROM Employees");

// Met meerdere kolommen
$result = $client->query("SELECT DISTINCT Department, Location FROM Employees");
```

#### Helper Methode voor SELECT

Als je geen complexe SQL nodig hebt, gebruik de helper methode:

```php
$result = $client->select(
    'Employees',              // Tabel naam
    ['Name', 'Email'],        // Kolommen (leeg = alle)
    "Department = 'Sales'",   // WHERE clausule
    'Salary DESC',            // ORDER BY
    10                        // LIMIT
);
```

### INSERT Queries

**Let op:** INSERT werkt alleen voor **gearchiveerde bases** (big data storage)!

#### Enkele rij invoegen

```php
$result = $client->query("
    INSERT INTO Employees (Name, Email, Department, Salary)
    VALUES ('John Doe', 'john@example.com', 'Sales', 55000)
");
```

#### Helper Methode voor INSERT

```php
$result = $client->insert('Employees', [
    'Name' => 'John Doe',
    'Email' => 'john@example.com',
    'Department' => 'Sales',
    'Salary' => 55000
]);
```

**Voor normale (niet-gearchiveerde) bases:** Gebruik de REST API methode `appendRow()`:

```php
$result = $client->appendRow('Employees', [
    'Name' => 'John Doe',
    'Email' => 'john@example.com',
    'Department' => 'Sales',
    'Salary' => 55000
]);
```

### UPDATE Queries

```php
// Update met WHERE
$result = $client->query("
    UPDATE Employees
    SET Salary = 60000, Department = 'Management'
    WHERE Name = 'John Doe'
");

// Meerdere rijen updaten
$result = $client->query("
    UPDATE Employees
    SET Salary = Salary * 1.1
    WHERE Department = 'Sales' AND Active = TRUE
");
```

#### Helper Methode voor UPDATE

```php
$result = $client->update(
    'Employees',                    // Tabel
    ['Salary' => 60000],           // Data
    "Name = 'John Doe'"            // WHERE (verplicht!)
);
```

**⚠️ Belangrijk:** De WHERE clausule is verplicht voor veiligheid!

### DELETE Queries

```php
// Delete met WHERE
$result = $client->query("
    DELETE FROM Employees
    WHERE Active = FALSE
    AND _ctime < '2020-01-01'
");

// Specifieke rij
$result = $client->query("DELETE FROM Employees WHERE _id = 'abc123'");
```

#### Helper Methode voor DELETE

```php
$result = $client->delete(
    'Employees',
    "Active = FALSE"  // WHERE (verplicht!)
);
```

**⚠️ Belangrijk:** De WHERE clausule is verplicht voor veiligheid!

### SQL Query Limieten

- **Maximum 10,000 rijen** per query
- Zonder LIMIT worden standaard **100 rijen** geretourneerd
- JOIN wordt ondersteund (alleen INNER JOIN)
- Subqueries hebben beperkte ondersteuning

### Resultaten Verwerken

```php
$result = $client->query("SELECT * FROM Employees LIMIT 10");

if ($result === false) {
    echo "Fout: " . $client->getLastError() . "\n";
} else {
    // Haal rijen op
    $rows = $result['results'] ?? $result['rows'] ?? [];

    echo "Gevonden: " . count($rows) . " rijen\n";

    foreach ($rows as $row) {
        echo "Naam: {$row['Name']}, Email: {$row['Email']}\n";
    }

    // Metadata (indien beschikbaar)
    if (isset($result['metadata'])) {
        echo "Query duurde: {$result['metadata']['duration']}ms\n";
    }
}
```

## REST API Methoden

Naast SQL queries biedt SeaTable ook REST API methoden voor CRUD operaties.

### Rijen Ophalen

```php
// Haal alle rijen op uit een tabel
$rows = $client->listRows('Employees');

// Met view filter
$rows = $client->listRows('Employees', 'Active Employees');

// Met limit
$rows = $client->listRows('Employees', null, 50);
```

### Rij Toevoegen

```php
// Enkele rij
$result = $client->appendRow('Employees', [
    'Name' => 'Jane Smith',
    'Email' => 'jane@example.com',
    'Department' => 'IT'
]);

// De rij ID is beschikbaar in het resultaat
$rowId = $result['_id'];
```

### Meerdere Rijen Toevoegen

```php
$rows = [
    ['Name' => 'Alice', 'Email' => 'alice@example.com'],
    ['Name' => 'Bob', 'Email' => 'bob@example.com'],
    ['Name' => 'Charlie', 'Email' => 'charlie@example.com']
];

$result = $client->appendRows('Employees', $rows);
```

### Rij Updaten

```php
// Je hebt de rij ID nodig
$rowId = 'abc123...';

$result = $client->updateRow('Employees', $rowId, [
    'Salary' => 65000,
    'Department' => 'Management'
]);
```

### Meerdere Rijen Updaten

```php
$updates = [
    ['row_id' => 'abc123', 'row' => ['Salary' => 60000]],
    ['row_id' => 'def456', 'row' => ['Salary' => 62000]],
    ['row_id' => 'ghi789', 'row' => ['Salary' => 64000]]
];

$result = $client->updateRows('Employees', $updates);
```

### Rij Verwijderen

```php
// Enkele rij
$result = $client->deleteRow('Employees', 'abc123');

// Meerdere rijen
$result = $client->deleteRows('Employees', ['abc123', 'def456', 'ghi789']);
```

## Complete API Referentie

### Authenticatie

#### `new SeaTableClient($serverUrl, $apiToken)`
Maak een nieuwe client instantie.

**Parameters:**
- `$serverUrl` (string): Server URL (bijv. `https://cloud.seatable.io`)
- `$apiToken` (string): Je API token

**Voorbeeld:**
```php
$client = new SeaTableClient('https://cloud.seatable.io', 'token-hier');
```

#### `ping()`
Test de verbinding en authenticatie.

**Returns:** `bool` - True als authenticatie succesvol

```php
if ($client->ping()) {
    echo "Verbinding OK!\n";
}
```

#### `getBaseUuid()`
Verkrijg het UUID van de huidige base (beschikbaar na authenticatie).

**Returns:** `string|null` - Base UUID

```php
$uuid = $client->getBaseUuid();
```

### SQL Query Methoden

#### `query($sql, $params = [])`
Voer een SQL query uit.

**Parameters:**
- `$sql` (string): SQL query
- `$params` (array): Parameters voor prepared statements (optioneel)

**Returns:** `array|false` - Query resultaten of false bij fout

**Voorbeeld:**
```php
$result = $client->query("SELECT * FROM Employees WHERE Department = 'Sales'");
```

#### `select($table, $columns = [], $where = '', $orderBy = '', $limit = null)`
Helper methode voor SELECT queries.

**Parameters:**
- `$table` (string): Tabel naam
- `$columns` (array): Kolommen (leeg = alle)
- `$where` (string): WHERE clausule
- `$orderBy` (string): ORDER BY clausule
- `$limit` (int): LIMIT

**Returns:** `array|false`

**Voorbeeld:**
```php
$result = $client->select('Employees', ['Name', 'Email'], "Active = TRUE", 'Name ASC', 10);
```

#### `insert($table, $data)`
Helper methode voor INSERT (alleen voor gearchiveerde bases).

**Parameters:**
- `$table` (string): Tabel naam
- `$data` (array): Associatieve array met kolom => waarde

**Returns:** `array|false`

**Voorbeeld:**
```php
$result = $client->insert('Employees', [
    'Name' => 'John Doe',
    'Email' => 'john@example.com'
]);
```

#### `update($table, $data, $where)`
Helper methode voor UPDATE.

**Parameters:**
- `$table` (string): Tabel naam
- `$data` (array): Data om te updaten
- `$where` (string): WHERE clausule (verplicht!)

**Returns:** `array|false`

**Voorbeeld:**
```php
$result = $client->update('Employees', ['Salary' => 60000], "Name = 'John Doe'");
```

#### `delete($table, $where)`
Helper methode voor DELETE.

**Parameters:**
- `$table` (string): Tabel naam
- `$where` (string): WHERE clausule (verplicht!)

**Returns:** `array|false`

**Voorbeeld:**
```php
$result = $client->delete('Employees', "Active = FALSE");
```

### REST API Methoden

#### `listRows($tableName, $view = null, $limit = 1000)`
Haal rijen op uit een tabel.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$view` (string): View naam (optioneel)
- `$limit` (int): Maximum aantal rijen

**Returns:** `array|false`

```php
$rows = $client->listRows('Employees', 'Active Employees', 50);
```

#### `appendRow($tableName, $row)`
Voeg een rij toe.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$row` (array): Associatieve array met kolom => waarde

**Returns:** `array|false` - Bevat `_id` van de nieuwe rij

```php
$result = $client->appendRow('Employees', ['Name' => 'John', 'Email' => 'john@example.com']);
$rowId = $result['_id'];
```

#### `appendRows($tableName, $rows)`
Voeg meerdere rijen toe.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$rows` (array): Array van rijen

**Returns:** `array|false`

```php
$result = $client->appendRows('Employees', [
    ['Name' => 'Alice', 'Email' => 'alice@example.com'],
    ['Name' => 'Bob', 'Email' => 'bob@example.com']
]);
```

#### `updateRow($tableName, $rowId, $row)`
Update een rij.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$rowId` (string): Rij ID
- `$row` (array): Data om te updaten

**Returns:** `array|false`

```php
$result = $client->updateRow('Employees', 'abc123', ['Salary' => 65000]);
```

#### `updateRows($tableName, $updates)`
Update meerdere rijen.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$updates` (array): Array van updates `[['row_id' => 'xxx', 'row' => [data]], ...]`

**Returns:** `array|false`

```php
$result = $client->updateRows('Employees', [
    ['row_id' => 'abc123', 'row' => ['Salary' => 60000]],
    ['row_id' => 'def456', 'row' => ['Salary' => 62000]]
]);
```

#### `deleteRow($tableName, $rowId)`
Verwijder een rij.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$rowId` (string): Rij ID

**Returns:** `array|false`

```php
$result = $client->deleteRow('Employees', 'abc123');
```

#### `deleteRows($tableName, $rowIds)`
Verwijder meerdere rijen.

**Parameters:**
- `$tableName` (string): Tabel naam
- `$rowIds` (array): Array van rij IDs

**Returns:** `array|false`

```php
$result = $client->deleteRows('Employees', ['abc123', 'def456', 'ghi789']);
```

### Metadata Methoden

#### `getMetadata()`
Haal base metadata op (tabellen, kolommen, etc.).

**Returns:** `array|false`

```php
$metadata = $client->getMetadata();
```

#### `getTables()`
Haal lijst van alle tabellen in de base op.

**Returns:** `array|false`

```php
$tables = $client->getTables();
foreach ($tables as $table) {
    echo $table['name'] . "\n";
}
```

#### `getTable($tableName)`
Haal informatie op van een specifieke tabel.

**Parameters:**
- `$tableName` (string): Tabel naam

**Returns:** `array|false`

```php
$table = $client->getTable('Employees');
print_r($table['columns']);
```

#### `getColumns($tableName)`
Haal alle kolommen van een tabel op.

**Parameters:**
- `$tableName` (string): Tabel naam

**Returns:** `array|false`

```php
$columns = $client->getColumns('Employees');
foreach ($columns as $col) {
    echo "{$col['name']} ({$col['type']})\n";
}
```

#### `getStructure()`
Krijg een overzicht van de volledige base structuur.

**Returns:** `array|false`

```php
$structure = $client->getStructure();
foreach ($structure as $tableName => $tableInfo) {
    echo "Tabel: $tableName\n";
    foreach ($tableInfo['columns'] as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }
}
```

### Foutafhandeling

#### `getLastError()`
Haal de laatste foutmelding op.

**Returns:** `string|null`

```php
$result = $client->query("INVALID SQL");
if ($result === false) {
    echo "Fout: " . $client->getLastError() . "\n";
}
```

## Foutafhandeling

Alle methoden retourneren `false` bij fouten. Gebruik `getLastError()` voor details:

```php
$result = $client->query("SELECT * FROM NonExistentTable");

if ($result === false) {
    $error = $client->getLastError();
    echo "Query mislukt: $error\n";

    // Check specifieke fouten
    if (strpos($error, 'HTTP Error 401') !== false) {
        echo "Authenticatie probleem - check je API token\n";
    } elseif (strpos($error, 'HTTP Error 404') !== false) {
        echo "Tabel of base niet gevonden\n";
    } elseif (strpos($error, 'HTTP Error 500') !== false) {
        echo "Server fout - probeer later opnieuw\n";
    }
}
```

### Veelvoorkomende Fouten

| Error Code | Betekenis | Oplossing |
|------------|-----------|-----------|
| HTTP 401 | Ongeldige authenticatie | Check je API token |
| HTTP 403 | Geen toegang | Check token permissies (read/write) |
| HTTP 404 | Niet gevonden | Check base UUID, tabel naam, of rij ID |
| HTTP 500 | Server fout | Check SeaTable server status |
| CURL Error | Netwerk probleem | Check internet connectie |

### Best Practices voor Foutafhandeling

```php
function safeQuery($client, $sql) {
    $maxRetries = 3;
    $retryDelay = 1; // seconden

    for ($i = 0; $i < $maxRetries; $i++) {
        $result = $client->query($sql);

        if ($result !== false) {
            return $result;
        }

        $error = $client->getLastError();

        // Retry alleen bij tijdelijke fouten
        if (strpos($error, 'HTTP Error 500') !== false ||
            strpos($error, 'CURL Error') !== false) {
            echo "Tijdelijke fout, retry $i/$maxRetries...\n";
            sleep($retryDelay);
            $retryDelay *= 2; // Exponential backoff
            continue;
        }

        // Permanente fout, stop direct
        return false;
    }

    return false;
}
```

## Voorbeelden

Zie het bestand `examples.php` voor 18 uitgebreide voorbeelden van:

1. Authenticatie
2. Base structuur ophalen
3. SQL SELECT - Alle rijen
4. SQL SELECT met WHERE
5. SQL SELECT met ORDER BY en LIMIT
6. SQL aggregatie (COUNT, SUM, AVG)
7. SQL GROUP BY
8. Helper methode SELECT
9. Rij toevoegen (REST API)
10. Meerdere rijen toevoegen
11. Rij updaten
12. SQL UPDATE
13. Complexe SQL queries
14. Rijen ophalen (REST API)
15. Rijen verwijderen
16. Geavanceerde query voorbeelden
17. Foutafhandeling
18. Praktisch voorbeeld - Data analyse

### Simpel Voorbeeld Script

```php
<?php
require_once 'SeaTableClient.php';

// Configuratie
$client = new SeaTableClient(
    'https://cloud.seatable.io',
    'jouw-api-token-hier'
);

// Test connectie
if (!$client->ping()) {
    die("Geen verbinding: " . $client->getLastError() . "\n");
}

// Haal statistieken op
$result = $client->query("
    SELECT
        Department,
        COUNT(*) as employees,
        AVG(Salary) as avg_salary,
        MAX(Salary) as max_salary
    FROM Employees
    GROUP BY Department
    ORDER BY avg_salary DESC
");

if ($result) {
    echo "📊 Departement Statistieken:\n\n";

    foreach ($result['results'] as $row) {
        echo "Departement: {$row['Department']}\n";
        echo "  Medewerkers: {$row['employees']}\n";
        echo "  Gem. salaris: €" . number_format($row['avg_salary'], 2) . "\n";
        echo "  Max salaris: €" . number_format($row['max_salary'], 2) . "\n\n";
    }
} else {
    echo "Fout: " . $client->getLastError() . "\n";
}
```

### Praktisch Voorbeeld: Data Import

```php
<?php
require_once 'SeaTableClient.php';

$client = new SeaTableClient($serverUrl, $apiToken);

// Lees CSV bestand
$csvFile = 'employees.csv';
$rows = [];

if (($handle = fopen($csvFile, 'r')) !== false) {
    $headers = fgetcsv($handle); // Eerste rij = headers

    while (($data = fgetcsv($handle)) !== false) {
        $row = array_combine($headers, $data);
        $rows[] = $row;
    }

    fclose($handle);
}

// Import in batches van 100
$batches = array_chunk($rows, 100);
$imported = 0;

foreach ($batches as $i => $batch) {
    echo "Importing batch " . ($i + 1) . "/" . count($batches) . "...\n";

    $result = $client->appendRows('Employees', $batch);

    if ($result) {
        $imported += count($batch);
    } else {
        echo "Fout bij batch $i: " . $client->getLastError() . "\n";
    }
}

echo "✓ Import compleet: $imported rijen geïmporteerd\n";
```

### Praktisch Voorbeeld: Data Export

```php
<?php
require_once 'SeaTableClient.php';

$client = new SeaTableClient($serverUrl, $apiToken);

// Export data naar CSV
$result = $client->query("SELECT * FROM Employees ORDER BY Name");

if ($result) {
    $rows = $result['results'];

    $csvFile = 'export_' . date('Y-m-d') . '.csv';
    $handle = fopen($csvFile, 'w');

    // Headers
    if (count($rows) > 0) {
        fputcsv($handle, array_keys($rows[0]));

        // Data
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
    }

    fclose($handle);

    echo "✓ Export compleet: " . count($rows) . " rijen naar $csvFile\n";
}
```

## Tips & Best Practices

### 1. Token Beveiliging

❌ **Niet doen:**
```php
$client = new SeaTableClient($url, 'hardcoded-token-123456');
```

✅ **Wel doen:**
```php
// Gebruik omgevingsvariabelen
$client = new SeaTableClient($url, getenv('SEATABLE_API_TOKEN'));

// Of een config bestand (buiten webroot!)
$config = require '/var/config/seatable.php';
$client = new SeaTableClient($url, $config['token']);
```

### 2. Query Optimalisatie

**Gebruik LIMIT:**
```php
// Goed: LIMIT voorkomt te veel data
$result = $client->query("SELECT * FROM BigTable LIMIT 100");

// Slecht: Kan 10,000+ rijen retourneren
$result = $client->query("SELECT * FROM BigTable");
```

**Selecteer alleen nodige kolommen:**
```php
// Goed: Alleen wat je nodig hebt
$result = $client->query("SELECT Name, Email FROM Employees");

// Slecht: Onnodige data ophalen
$result = $client->query("SELECT * FROM Employees");
```

### 3. Batch Operaties

Voor bulk imports, gebruik batches:

```php
$data = [...]; // 1000 rijen
$batches = array_chunk($data, 100); // Splits in batches van 100

foreach ($batches as $batch) {
    $client->appendRows('Table', $batch);
    usleep(100000); // 0.1 seconde pauze tussen batches
}
```

### 4. Error Handling

Altijd errors afhandelen:

```php
$result = $client->query($sql);

if ($result === false) {
    error_log("SeaTable query failed: " . $client->getLastError());
    // Fallback actie
    return;
}

// Verwerk resultaat
```

### 5. Caching

Cache metadata voor betere performance:

```php
// Cache de structuur
$cacheFile = '/tmp/seatable_structure.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
    $structure = json_decode(file_get_contents($cacheFile), true);
} else {
    $structure = $client->getStructure();
    file_put_contents($cacheFile, json_encode($structure));
}
```

## SeaTable Systeem Kolommen

Elke tabel heeft automatische systeem kolommen:

| Kolom | Beschrijving |
|-------|--------------|
| `_id` | Unieke rij ID (gebruikt voor updates/deletes) |
| `_ctime` | Aanmaak tijd (ISO 8601 formaat) |
| `_mtime` | Laatste wijziging tijd |
| `_creator` | Email van de maker |
| `_last_modifier` | Email van laatste wijziger |

Deze kunnen gebruikt worden in queries:

```php
// Recent toegevoegde rijen
$result = $client->query("
    SELECT * FROM Employees
    WHERE _ctime >= '2024-01-01'
    ORDER BY _ctime DESC
");

// Recent gewijzigde rijen
$result = $client->query("
    SELECT * FROM Employees
    ORDER BY _mtime DESC
    LIMIT 10
");

// Rijen van specifieke gebruiker
$result = $client->query("
    SELECT * FROM Employees
    WHERE _creator = 'user@example.com'
");
```

## Veelgestelde Vragen (FAQ)

### Hoe maak ik een API token?

Zie [API Token verkrijgen](#api-token-verkrijgen) voor gedetailleerde instructies.

### Wat is het verschil tussen SQL en REST API methoden?

- **SQL** (`query()`) - Voor complexe queries, filtering, aggregatie, JOIN
- **REST API** (`appendRow()`, etc.) - Voor simpele CRUD operaties op individuele rijen

Gebruik SQL voor complexe operaties, REST API voor simpele toevoeg/update acties.

### Werkt INSERT via SQL?

INSERT via SQL werkt **alleen voor gearchiveerde bases** (big data storage).

Voor normale bases, gebruik de REST API methode `appendRow()` of `appendRows()`.

### Hoeveel rijen kan ik per query ophalen?

Maximum **10,000 rijen** per query. Zonder LIMIT worden standaard 100 rijen geretourneerd.

Voor meer data, gebruik paginering met LIMIT en OFFSET:

```php
$pageSize = 1000;
$page = 0;

do {
    $offset = $page * $pageSize;
    $result = $client->query("SELECT * FROM Table LIMIT $pageSize OFFSET $offset");
    $rows = $result['results'] ?? [];

    // Verwerk $rows...

    $page++;
} while (count($rows) === $pageSize);
```

### Hoe lang is een base token geldig?

Base tokens zijn **3 dagen geldig**. De client vernieuwt deze automatisch, je hoeft hier niets voor te doen.

### Kan ik meerdere bases tegelijk benaderen?

Nee, elk API token is gekoppeld aan één base. Voor meerdere bases heb je meerdere API tokens nodig:

```php
$client1 = new SeaTableClient($url, $token1); // Base 1
$client2 = new SeaTableClient($url, $token2); // Base 2
```

### Ondersteunen queries JOINs?

Ja! SeaTable ondersteunt **INNER JOIN** sinds versie 4.3:

```php
$result = $client->query("
    SELECT e.Name, d.DepartmentName
    FROM Employees e
    INNER JOIN Departments d ON e.DeptID = d._id
");
```

LEFT JOIN, RIGHT JOIN en OUTER JOIN worden nog niet ondersteund.

### Hoe werk ik met datums?

SeaTable gebruikt ISO 8601 formaat (`YYYY-MM-DD HH:MM:SS`):

```php
// Datum filtering
$result = $client->query("
    SELECT * FROM Events
    WHERE EventDate >= '2024-01-01 00:00:00'
    AND EventDate < '2025-01-01 00:00:00'
");

// Datum in PHP formaat converteren
$date = strtotime($row['EventDate']);
echo date('d-m-Y', $date);
```

## Troubleshooting

### "HTTP Error 401: Unauthorized"

**Oorzaak:** Ongeldig API token

**Oplossing:**
- Check of je API token correct is
- Genereer een nieuw token in SeaTable
- Zorg dat het token read/write permissies heeft

### "HTTP Error 404: Not Found"

**Oorzaak:** Base, tabel of rij niet gevonden

**Oplossing:**
- Check of de tabel naam correct is (hoofdlettergevoelig!)
- Check of je base UUID klopt
- Verifieer dat de rij ID bestaat

### "SQL syntax error"

**Oorzaak:** Ongeldige SQL syntax

**Oplossing:**
- Check je SQL syntax
- Gebruik backticks rond tabel/kolom namen: `` `Table Name` ``
- Check of de kolom bestaat in je tabel

### Query retourneert geen resultaten

**Oorzaak:** Query heeft geen matches of tabel is leeg

**Oplossing:**
```php
$result = $client->query("SELECT COUNT(*) as total FROM YourTable");
$total = $result['results'][0]['total'] ?? 0;

if ($total === 0) {
    echo "Tabel is leeg\n";
} else {
    echo "Tabel heeft $total rijen, check je WHERE clausule\n";
}
```

### "CURL Error: SSL certificate problem"

**Oorzaak:** SSL certificaat verificatie problemen

**Oplossing:**
```php
// Voor development/testing (NIET voor productie!)
// Voeg toe aan SeaTableClient.php in de request() methode:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```

Voor productie: zorg voor correcte SSL certificaten op je server.

## Licentie

Deze client is open-source en mag vrijelijk gebruikt worden voor zowel commerciële als niet-commerciële doeleinden.

## Links

- [SeaTable Website](https://seatable.io)
- [SeaTable API Documentatie](https://api.seatable.io)
- [SeaTable SQL Referentie](https://developer.seatable.io/scripts/sql/reference/)
- [SeaTable Forum](https://forum.seatable.io)

## Support

Voor vragen over SeaTable zelf:
- [SeaTable Forum](https://forum.seatable.io)
- [SeaTable Documentatie](https://docs.seatable.io)

Voor vragen over deze PHP client:
- Bekijk de `examples.php` voor praktische voorbeelden
- Raadpleeg deze handleiding voor API referentie
- Check de broncode in `SeaTableClient.php`

---

**Veel succes met je SeaTable project!** 🚀
