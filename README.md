# SeaTable API Client voor PHP

Een eenvoudige, standalone PHP client voor communicatie met de SeaTable API. **Ondersteunt volledige SQL queries!**

Geen dependencies, geen Composer, gewoon 1 PHP bestand.

## Features

✅ **Volledige SQL ondersteuning** - SELECT, INSERT, UPDATE, DELETE met echte SQL syntax
✅ **Eenvoudig** - Slechts 1 bestand nodig (`SeaTableClient.php`)
✅ **Geen dependencies** - Alleen PHP met cURL
✅ **REST API** - Volledige CRUD operaties
✅ **Volledig gedocumenteerd** - Nederlandse handleiding inclusief
✅ **Business Edition ready** - Werkt met API keys
✅ **18 voorbeelden** - Complete examples.php met praktische use cases

## Wat is SeaTable?

SeaTable is een no-code database platform (zoals Airtable) waarmee je relationele databases kunt maken en beheren. Deze client geeft je **programmatische toegang met échte SQL queries!**

## Snelstart

```php
<?php
require_once 'SeaTableClient.php';

// 1. Maak client
$client = new SeaTableClient(
    'https://cloud.seatable.io',  // Server URL
    'jouw-api-token-hier'         // API token
);

// 2. Test connectie
if ($client->ping()) {
    echo "Verbonden!\n";
}

// 3. Voer SQL queries uit!
$result = $client->query("
    SELECT Name, Email, Department
    FROM Employees
    WHERE Salary > 50000
    ORDER BY Name
    LIMIT 10
");

// 4. Verwerk resultaten
foreach ($result['results'] as $row) {
    echo "{$row['Name']} - {$row['Department']}\n";
}
```

## Installatie

Download het bestand:

```bash
wget https://raw.githubusercontent.com/jouw-repo/seacloud-connector/main/SeaTableClient.php
```

Of kopieer gewoon `SeaTableClient.php` naar je project. Dat is alles!

## Wat kun je ermee doen?

### 🔍 Volledige SQL Queries

```php
// SELECT met WHERE, ORDER BY, LIMIT
$result = $client->query("
    SELECT * FROM Employees
    WHERE Department = 'Sales' AND Salary > 40000
    ORDER BY Salary DESC
    LIMIT 10
");

// Aggregatie functies
$result = $client->query("
    SELECT Department, COUNT(*) as total, AVG(Salary) as avg_salary
    FROM Employees
    GROUP BY Department
    HAVING AVG(Salary) > 50000
");

// JOIN queries
$result = $client->query("
    SELECT e.Name, e.Email, d.DepartmentName
    FROM Employees e
    INNER JOIN Departments d ON e.DepartmentID = d._id
");

// UPDATE
$result = $client->query("
    UPDATE Employees
    SET Salary = Salary * 1.1
    WHERE Performance = 'Excellent'
");

// DELETE
$result = $client->query("
    DELETE FROM Employees
    WHERE Status = 'Inactive' AND _ctime < '2020-01-01'
");
```

### 📊 REST API Operaties

```php
// Rijen ophalen
$rows = $client->listRows('Employees', 'Active View', 100);

// Rij toevoegen
$result = $client->appendRow('Employees', [
    'Name' => 'John Doe',
    'Email' => 'john@example.com',
    'Department' => 'Sales'
]);

// Meerdere rijen toevoegen
$result = $client->appendRows('Employees', [
    ['Name' => 'Alice', 'Email' => 'alice@example.com'],
    ['Name' => 'Bob', 'Email' => 'bob@example.com']
]);

// Rij updaten
$result = $client->updateRow('Employees', $rowId, [
    'Salary' => 65000,
    'Department' => 'Management'
]);

// Rij verwijderen
$result = $client->deleteRow('Employees', $rowId);
```

### 🔧 Metadata & Structuur

```php
// Haal base structuur op
$structure = $client->getStructure();

// Lijst van tabellen
$tables = $client->getTables();

// Kolommen van een tabel
$columns = $client->getColumns('Employees');

// Tabel informatie
$table = $client->getTable('Employees');
```

## SQL Capabilities

### Ondersteunde SQL Statements

- ✅ **SELECT** - Met WHERE, ORDER BY, LIMIT, OFFSET, GROUP BY, HAVING, DISTINCT
- ✅ **INSERT** - Voor gearchiveerde bases (big data storage)
- ✅ **UPDATE** - Met WHERE clausule
- ✅ **DELETE** - Met WHERE clausule
- ✅ **JOIN** - INNER JOIN (sinds versie 4.3)
- ✅ **Aggregatie** - COUNT, SUM, AVG, MIN, MAX

### SQL Voorbeelden

```php
// Complexe WHERE clausules
$result = $client->query("
    SELECT * FROM Products
    WHERE (Category = 'Electronics' OR Category = 'Computers')
    AND Price > 100
    AND Stock > 0
    AND Name LIKE '%Pro%'
");

// GROUP BY met HAVING
$result = $client->query("
    SELECT Category, COUNT(*) as total, AVG(Price) as avg_price
    FROM Products
    GROUP BY Category
    HAVING COUNT(*) > 10
    ORDER BY avg_price DESC
");

// DISTINCT
$result = $client->query("
    SELECT DISTINCT Category FROM Products
    ORDER BY Category
");

// Datum filtering
$result = $client->query("
    SELECT * FROM Orders
    WHERE OrderDate >= '2024-01-01'
    AND OrderDate < '2025-01-01'
    ORDER BY OrderDate DESC
");
```

### Helper Methoden

Voor eenvoudige queries kun je ook helper methoden gebruiken:

```php
// SELECT
$result = $client->select(
    'Employees',              // Tabel
    ['Name', 'Email'],        // Kolommen
    "Department = 'Sales'",   // WHERE
    'Salary DESC',            // ORDER BY
    10                        // LIMIT
);

// UPDATE
$result = $client->update(
    'Employees',
    ['Salary' => 60000],
    "Name = 'John Doe'"
);

// DELETE
$result = $client->delete(
    'Employees',
    "Status = 'Inactive'"
);
```

## Voorbeelden

Zie `examples.php` voor 18 complete voorbeelden:

```bash
php examples.php
```

### Voorbeeld Scripts

1. ✅ Authenticatie en connectie testen
2. ✅ Base structuur ophalen
3. ✅ SQL SELECT - Alle rijen
4. ✅ SQL SELECT met WHERE
5. ✅ SQL SELECT met ORDER BY
6. ✅ SQL aggregatie (COUNT, SUM, AVG)
7. ✅ SQL GROUP BY
8. ✅ Helper methode SELECT
9. ✅ Rij toevoegen
10. ✅ Meerdere rijen toevoegen
11. ✅ Rij updaten
12. ✅ SQL UPDATE
13. ✅ Complexe SQL queries
14. ✅ Rijen ophalen (REST API)
15. ✅ Rijen verwijderen
16. ✅ Geavanceerde query voorbeelden
17. ✅ Foutafhandeling
18. ✅ Praktisch voorbeeld - Data analyse

## API Token Verkrijgen

1. Log in op SeaTable
2. Open je base
3. Klik op menu (⋮) → **Advanced** → **API Token**
4. Klik **Generate API Token**
5. Kies **Read-Write** permissies
6. Kopieer het token

**💡 Tip:** Bewaar je token veilig in een config bestand of omgevingsvariabele!

```php
// .env of config.php
define('SEATABLE_TOKEN', 'jouw-token-hier');

// In je script
$client = new SeaTableClient($url, SEATABLE_TOKEN);
```

## Documentatie

Lees de volledige **[Nederlandse handleiding](HANDLEIDING.md)** voor:

- 📖 Gedetailleerde installatie instructies
- 📖 Volledige SQL syntax documentatie
- 📖 Complete API referentie
- 📖 Foutafhandeling & troubleshooting
- 📖 Best practices & tips
- 📖 FAQ
- 📖 Praktische voorbeelden

## Vereisten

- PHP 7.0 of hoger
- cURL extensie
- Toegang tot SeaTable server (Cloud of Self-hosted)
- Een SeaTable API token

## Bestanden

- **`SeaTableClient.php`** - De hoofdclient (dit is het enige bestand dat je nodig hebt!)
- **`examples.php`** - 18 praktische voorbeelden met SQL queries
- **`HANDLEIDING.md`** - Uitgebreide Nederlandse documentatie (100+ pagina's)
- **`README.md`** - Dit bestand

## Features Overzicht

### SQL Features
- ✅ SELECT met complexe WHERE clausules
- ✅ JOIN queries (INNER JOIN)
- ✅ GROUP BY met HAVING
- ✅ Aggregatie functies (COUNT, SUM, AVG, MIN, MAX)
- ✅ ORDER BY (ASC/DESC)
- ✅ LIMIT en OFFSET (paginering)
- ✅ DISTINCT
- ✅ UPDATE queries
- ✅ DELETE queries
- ✅ INSERT queries (voor gearchiveerde bases)
- ✅ IN, LIKE, IS NULL operators
- ✅ Datum en numerieke vergelijkingen

### REST API Features
- ✅ List rows (met view filtering)
- ✅ Append row (enkele rij toevoegen)
- ✅ Append rows (bulk toevoegen)
- ✅ Update row
- ✅ Update rows (bulk updaten)
- ✅ Delete row
- ✅ Delete rows (bulk verwijderen)

### Metadata Features
- ✅ Get base metadata
- ✅ Get tables
- ✅ Get table info
- ✅ Get columns
- ✅ Get structure (overzicht van hele base)

### Client Features
- ✅ Automatische authenticatie
- ✅ Base token caching (3 dagen)
- ✅ Automatische token vernieuwing
- ✅ Error handling
- ✅ Connection testing (ping)

## Performance Tips

### 1. Gebruik LIMIT
```php
// Goed
$result = $client->query("SELECT * FROM BigTable LIMIT 100");

// Slecht (kan 10,000 rijen retourneren!)
$result = $client->query("SELECT * FROM BigTable");
```

### 2. Selecteer alleen nodige kolommen
```php
// Goed
$result = $client->query("SELECT Name, Email FROM Users");

// Slecht
$result = $client->query("SELECT * FROM Users");
```

### 3. Gebruik batches voor bulk operaties
```php
$batches = array_chunk($data, 100);
foreach ($batches as $batch) {
    $client->appendRows('Table', $batch);
}
```

### 4. Cache metadata
```php
// Cache de structuur
$structure = $client->getStructure();
// Gebruik cached data...
```

## Limieten

- **Maximum 10,000 rijen** per SQL query
- Zonder LIMIT: standaard **100 rijen**
- Base tokens zijn **3 dagen geldig** (automatisch vernieuwd)
- API tokens zijn **permanent geldig**
- Elk API token is gekoppeld aan **één base**

## Foutafhandeling

Alle methoden retourneren `false` bij fouten:

```php
$result = $client->query("SELECT * FROM Table");

if ($result === false) {
    echo "Fout: " . $client->getLastError() . "\n";
} else {
    // Verwerk resultaten
}
```

## Praktische Use Cases

### Data Import van CSV

```php
$csv = array_map('str_getcsv', file('data.csv'));
$headers = array_shift($csv);

foreach (array_chunk($csv, 100) as $batch) {
    $rows = array_map(fn($row) => array_combine($headers, $row), $batch);
    $client->appendRows('ImportTable', $rows);
}
```

### Data Export naar CSV

```php
$result = $client->query("SELECT * FROM Table");
$fp = fopen('export.csv', 'w');

fputcsv($fp, array_keys($result['results'][0])); // Headers
foreach ($result['results'] as $row) {
    fputcsv($fp, $row);
}

fclose($fp);
```

### Rapport Generatie

```php
$stats = $client->query("
    SELECT
        Department,
        COUNT(*) as total_employees,
        AVG(Salary) as avg_salary,
        SUM(Salary) as total_cost
    FROM Employees
    GROUP BY Department
    ORDER BY total_cost DESC
");

foreach ($stats['results'] as $dept) {
    echo "Departement: {$dept['Department']}\n";
    echo "  Medewerkers: {$dept['total_employees']}\n";
    echo "  Gem. salaris: €" . number_format($dept['avg_salary'], 2) . "\n";
    echo "  Totale kosten: €" . number_format($dept['total_cost'], 2) . "\n\n";
}
```

### Data Synchronisatie

```php
// Haal gewijzigde records op (laatste 24 uur)
$yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));

$changes = $client->query("
    SELECT * FROM SyncTable
    WHERE _mtime >= '$yesterday'
    ORDER BY _mtime ASC
");

// Sync naar extern systeem...
```

## Links

- [SeaTable Website](https://seatable.io)
- [SeaTable API Documentatie](https://api.seatable.io)
- [SeaTable SQL Referentie](https://developer.seatable.io/scripts/sql/reference/)
- [SeaTable Developer Manual](https://developer.seatable.io)
- [SeaTable Forum](https://forum.seatable.io)

## Licentie

Open-source, vrij te gebruiken voor commerciële en niet-commerciële doeleinden.

## Support

Voor vragen over SeaTable zelf:
- [SeaTable Forum](https://forum.seatable.io)
- [SeaTable Documentatie](https://docs.seatable.io)

Voor vragen over deze client:
- Bekijk `examples.php` voor praktische voorbeelden
- Lees de uitgebreide `HANDLEIDING.md`
- Check de broncode in `SeaTableClient.php`

---

**Gemaakt voor eenvoudige SeaTable API integratie met volledige SQL ondersteuning** 🚀

**Veel succes met je project!**
