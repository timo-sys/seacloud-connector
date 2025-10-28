<?php
/**
 * Voorbeelden van SeaTable API gebruik
 *
 * Dit bestand toont praktische voorbeelden van hoe je de SeaTableClient kunt gebruiken
 * met volledige SQL query ondersteuning
 */

require_once 'SeaTableClient.php';

// ============================================================================
// CONFIGURATIE
// ============================================================================

$SEATABLE_URL = 'https://cloud.seatable.io';  // Of je eigen SeaTable server
$SEATABLE_API_TOKEN = 'jouw-api-token-hier';  // API token van je base

// Pas bovenstaande configuratie aan naar jouw situatie!

// ============================================================================
// CLIENT INITIALISATIE
// ============================================================================

echo "=== SeaTable API Client Voorbeelden ===\n\n";

$client = new SeaTableClient($SEATABLE_URL, $SEATABLE_API_TOKEN);

// ============================================================================
// VOORBEELD 1: Authenticatie en connectie testen
// ============================================================================

echo "=== VOORBEELD 1: Authenticatie ===\n\n";

if ($client->ping()) {
    echo "✓ Verbinding en authenticatie OK!\n";
    echo "  Base UUID: " . $client->getBaseUuid() . "\n\n";
} else {
    echo "✗ Authenticatie mislukt: " . $client->getLastError() . "\n\n";
    exit(1);
}

// ============================================================================
// VOORBEELD 2: Base structuur ophalen
// ============================================================================

echo "=== VOORBEELD 2: Base structuur ===\n\n";

$structure = $client->getStructure();

if ($structure) {
    echo "Tabellen in deze base:\n\n";

    foreach ($structure as $tableName => $tableInfo) {
        echo "📊 Tabel: $tableName\n";
        echo "   Kolommen:\n";

        foreach ($tableInfo['columns'] as $column) {
            echo "   - {$column['name']} ({$column['type']})\n";
        }
        echo "\n";
    }
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// Voor de volgende voorbeelden gebruiken we de eerste tabel
$tables = $client->getTables();
$firstTable = $tables[0]['name'] ?? 'Table1';  // Standaard SeaTable tabel naam

echo "Voor volgende voorbeelden gebruiken we tabel: '$firstTable'\n\n";

// ============================================================================
// VOORBEELD 3: SQL SELECT - Alle rijen ophalen
// ============================================================================

echo "=== VOORBEELD 3: SQL SELECT - Alle rijen ===\n\n";

$result = $client->query("SELECT * FROM `$firstTable` LIMIT 10");

if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "Gevonden rijen: " . count($rows) . "\n\n";

    if (count($rows) > 0) {
        // Toon eerste rij als voorbeeld
        echo "Eerste rij:\n";
        print_r($rows[0]);
        echo "\n";
    }
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 4: SQL SELECT met WHERE clausule
// ============================================================================

echo "=== VOORBEELD 4: SQL SELECT met WHERE ===\n\n";

// Pas de kolom naam aan naar een kolom die in jouw tabel bestaat
$sql = "SELECT * FROM `$firstTable` WHERE Name IS NOT NULL LIMIT 5";
$result = $client->query($sql);

if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "SQL: $sql\n";
    echo "Resultaten: " . count($rows) . "\n\n";
} else {
    echo "Query: $sql\n";
    echo "Let op: Pas de kolomnaam 'Name' aan naar een kolom in jouw tabel\n";
    echo "Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 5: SQL SELECT met ORDER BY en LIMIT
// ============================================================================

echo "=== VOORBEELD 5: SQL SELECT met ORDER BY ===\n\n";

$sql = "SELECT * FROM `$firstTable` ORDER BY _ctime DESC LIMIT 5";
$result = $client->query($sql);

if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "Laatste 5 aangemakte rijen:\n";
    echo "Gevonden: " . count($rows) . " rijen\n\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 6: SQL SELECT met aggregatie (COUNT, SUM, AVG)
// ============================================================================

echo "=== VOORBEELD 6: SQL aggregatie functies ===\n\n";

$sql = "SELECT COUNT(*) as total FROM `$firstTable`";
$result = $client->query($sql);

if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    if (count($rows) > 0) {
        echo "Totaal aantal rijen: " . $rows[0]['total'] . "\n\n";
    }
}

// ============================================================================
// VOORBEELD 7: SQL SELECT met GROUP BY
// ============================================================================

echo "=== VOORBEELD 7: SQL GROUP BY ===\n\n";

// Voorbeeld: groepeer per status (pas aan naar jouw kolommen)
$sql = "SELECT _creator, COUNT(*) as count FROM `$firstTable` GROUP BY _creator";
$result = $client->query($sql);

if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "Rijen per maker:\n";

    foreach ($rows as $row) {
        echo "  {$row['_creator']}: {$row['count']} rijen\n";
    }
    echo "\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 8: Helper methode SELECT
// ============================================================================

echo "=== VOORBEELD 8: Helper methode SELECT ===\n\n";

// Eenvoudigere manier om SELECT queries te doen
$result = $client->select(
    $firstTable,           // Tabel
    [],                    // Kolommen (leeg = alle)
    '_ctime IS NOT NULL',  // WHERE
    '_ctime DESC',         // ORDER BY
    5                      // LIMIT
);

if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "Resultaten met helper methode: " . count($rows) . " rijen\n\n";
}

// ============================================================================
// VOORBEELD 9: Rijen toevoegen (REST API methode)
// ============================================================================

echo "=== VOORBEELD 9: Rij toevoegen ===\n\n";

// Voeg een test rij toe
$newRow = [
    'Name' => 'Test Entry ' . date('Y-m-d H:i:s'),
    'Description' => 'Aangemaakt via API voorbeeld'
];

echo "Nieuwe rij toevoegen...\n";
$result = $client->appendRow($firstTable, $newRow);

if ($result) {
    echo "✓ Rij toegevoegd!\n";
    if (isset($result['_id'])) {
        echo "  Rij ID: {$result['_id']}\n";
        $testRowId = $result['_id'];
    }
    echo "\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n";
    echo "Let op: Pas de kolomnamen 'Name' en 'Description' aan naar jouw tabel\n\n";
    $testRowId = null;
}

// ============================================================================
// VOORBEELD 10: Meerdere rijen toevoegen
// ============================================================================

echo "=== VOORBEELD 10: Meerdere rijen toevoegen ===\n\n";

$rows = [
    [
        'Name' => 'Batch Entry 1',
        'Description' => 'Eerste batch entry'
    ],
    [
        'Name' => 'Batch Entry 2',
        'Description' => 'Tweede batch entry'
    ],
    [
        'Name' => 'Batch Entry 3',
        'Description' => 'Derde batch entry'
    ]
];

echo "3 rijen tegelijk toevoegen...\n";
$result = $client->appendRows($firstTable, $rows);

if ($result) {
    echo "✓ Rijen toegevoegd!\n\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 11: Rij updaten
// ============================================================================

echo "=== VOORBEELD 11: Rij updaten ===\n\n";

if ($testRowId) {
    $updates = [
        'Description' => 'Geüpdatet op ' . date('Y-m-d H:i:s')
    ];

    echo "Rij updaten...\n";
    $result = $client->updateRow($firstTable, $testRowId, $updates);

    if ($result) {
        echo "✓ Rij geüpdatet!\n\n";
    } else {
        echo "✗ Fout: " . $client->getLastError() . "\n\n";
    }
}

// ============================================================================
// VOORBEELD 12: SQL UPDATE query
// ============================================================================

echo "=== VOORBEELD 12: SQL UPDATE ===\n\n";

// Update via SQL (pas aan naar jouw kolommen)
$sql = "UPDATE `$firstTable` SET Description = 'Bulk update via SQL'
        WHERE Name LIKE 'Batch Entry%'";

echo "SQL: $sql\n";
$result = $client->query($sql);

if ($result) {
    echo "✓ Update uitgevoerd!\n\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 13: Complexe SQL queries
// ============================================================================

echo "=== VOORBEELD 13: Complexe SQL queries ===\n\n";

// Query 1: SELECT met meerdere voorwaarden
echo "Query 1: Meerdere WHERE voorwaarden\n";
$sql = "SELECT * FROM `$firstTable`
        WHERE _ctime > '2024-01-01'
        AND Name IS NOT NULL
        ORDER BY _mtime DESC
        LIMIT 10";

$result = $client->query($sql);
if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "  Resultaten: " . count($rows) . " rijen\n";
}
echo "\n";

// Query 2: Gebruik van LIKE
echo "Query 2: LIKE operator\n";
$sql = "SELECT Name, _ctime FROM `$firstTable`
        WHERE Name LIKE '%Test%'
        LIMIT 5";

$result = $client->query($sql);
if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "  Resultaten met 'Test' in naam: " . count($rows) . " rijen\n";
}
echo "\n";

// Query 3: COUNT met GROUP BY
echo "Query 3: COUNT met GROUP BY\n";
$sql = "SELECT _creator, COUNT(*) as total, MAX(_mtime) as last_modified
        FROM `$firstTable`
        GROUP BY _creator";

$result = $client->query($sql);
if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    echo "  Statistieken per gebruiker: " . count($rows) . " gebruikers\n";

    foreach ($rows as $row) {
        echo "    - {$row['_creator']}: {$row['total']} rijen\n";
    }
}
echo "\n";

// ============================================================================
// VOORBEELD 14: Rijen ophalen met filters (REST API)
// ============================================================================

echo "=== VOORBEELD 14: Rijen ophalen ===\n\n";

$rows = $client->listRows($firstTable, null, 10);

if ($rows) {
    echo "Eerste 10 rijen via REST API: " . count($rows) . " rijen\n\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 15: Rijen verwijderen
// ============================================================================

echo "=== VOORBEELD 15: Rijen verwijderen ===\n\n";

// Verwijder test rijen via SQL DELETE
$sql = "DELETE FROM `$firstTable` WHERE Name LIKE 'Test Entry%'";

echo "Test rijen verwijderen via SQL...\n";
echo "SQL: $sql\n";
$result = $client->query($sql);

if ($result) {
    echo "✓ Test rijen verwijderd!\n\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// Verwijder batch entries
$sql = "DELETE FROM `$firstTable` WHERE Name LIKE 'Batch Entry%'";
$result = $client->query($sql);

if ($result) {
    echo "✓ Batch entries verwijderd!\n\n";
}

// ============================================================================
// VOORBEELD 16: Geavanceerde query voorbeelden
// ============================================================================

echo "=== VOORBEELD 16: Geavanceerde query voorbeelden ===\n\n";

echo "1. Datum filtering:\n";
$sql = "SELECT * FROM `$firstTable`
        WHERE _ctime >= '2024-01-01 00:00:00'
        AND _ctime < '2025-01-01 00:00:00'
        LIMIT 5";
echo "   $sql\n\n";

echo "2. Numerieke vergelijkingen:\n";
$sql = "SELECT * FROM `$firstTable`
        WHERE _id IS NOT NULL
        LIMIT 5";
echo "   $sql\n\n";

echo "3. IN operator:\n";
$sql = "SELECT * FROM `$firstTable`
        WHERE _creator IN ('user1@example.com', 'user2@example.com')
        LIMIT 5";
echo "   $sql\n\n";

echo "4. Subquery (indien ondersteund):\n";
echo "   SELECT * FROM `$firstTable` WHERE ... \n\n";

echo "5. DISTINCT:\n";
$sql = "SELECT DISTINCT _creator FROM `$firstTable`";
echo "   $sql\n\n";

// ============================================================================
// VOORBEELD 17: Foutafhandeling
// ============================================================================

echo "=== VOORBEELD 17: Foutafhandeling ===\n\n";

// Probeer een ongeldige query
$result = $client->query("SELECT * FROM niet_bestaande_tabel");

if ($result === false) {
    echo "✓ Foutafhandeling werkt correct\n";
    echo "  Foutmelding: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 18: Praktisch gebruik - Data analyse
// ============================================================================

echo "=== VOORBEELD 18: Praktisch voorbeeld - Data analyse ===\n\n";

// Haal statistieken op
$stats = [];

// Totaal aantal rijen
$result = $client->query("SELECT COUNT(*) as total FROM `$firstTable`");
if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    $stats['total_rows'] = $rows[0]['total'] ?? 0;
}

// Aantal unieke makers
$result = $client->query("SELECT COUNT(DISTINCT _creator) as creators FROM `$firstTable`");
if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    $stats['unique_creators'] = $rows[0]['creators'] ?? 0;
}

// Nieuwste entry
$result = $client->query("SELECT * FROM `$firstTable` ORDER BY _ctime DESC LIMIT 1");
if ($result) {
    $rows = $result['results'] ?? $result['rows'] ?? [];
    if (count($rows) > 0) {
        $stats['newest_entry'] = $rows[0]['_ctime'] ?? 'Onbekend';
    }
}

echo "📊 Base Statistieken:\n";
echo "   Totaal rijen: " . ($stats['total_rows'] ?? 0) . "\n";
echo "   Unieke makers: " . ($stats['unique_creators'] ?? 0) . "\n";
echo "   Nieuwste entry: " . ($stats['newest_entry'] ?? 'Onbekend') . "\n";
echo "\n";

// ============================================================================
// KLAAR
// ============================================================================

echo "=== KLAAR ===\n\n";
echo "Alle voorbeelden zijn uitgevoerd!\n\n";

echo "💡 Tips:\n";
echo "   - Pas de kolomnamen aan naar jouw tabel structuur\n";
echo "   - Gebruik \$client->getStructure() om je tabel structuur te zien\n";
echo "   - SQL queries ondersteunen max 10,000 rijen\n";
echo "   - Base tokens zijn 3 dagen geldig en worden automatisch vernieuwd\n";
echo "   - Bekijk SeaTableClient.php voor alle beschikbare methoden\n";
echo "\n";

echo "📚 Documentatie:\n";
echo "   - SeaTable API: https://api.seatable.io\n";
echo "   - SQL Reference: https://developer.seatable.io/scripts/sql/reference/\n";
echo "\n";
