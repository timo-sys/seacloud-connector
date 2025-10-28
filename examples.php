<?php
/**
 * Voorbeelden van Seafile API gebruik
 *
 * Dit bestand toont praktische voorbeelden van hoe je de SeafileClient kunt gebruiken
 */

require_once 'SeafileClient.php';

// ============================================================================
// CONFIGURATIE
// ============================================================================

$SEAFILE_URL = 'https://jouw-seafile-server.com';  // Pas aan naar jouw server
$SEAFILE_USERNAME = 'jouw@email.com';              // Pas aan naar jouw username
$SEAFILE_PASSWORD = 'jouw-wachtwoord';             // Pas aan naar jouw wachtwoord

// Of gebruik direct een API token als je die al hebt:
$SEAFILE_TOKEN = null;  // Bijvoorbeeld: 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0'

// ============================================================================
// VOORBEELD 1: API Token verkrijgen
// ============================================================================

echo "=== VOORBEELD 1: API Token verkrijgen ===\n\n";

if (!$SEAFILE_TOKEN) {
    echo "Token verkrijgen met username en password...\n";
    $token = SeafileClient::getToken($SEAFILE_URL, $SEAFILE_USERNAME, $SEAFILE_PASSWORD);

    if ($token) {
        echo "✓ Token verkregen: $token\n";
        echo "  Bewaar dit token veilig! Je kunt het hergebruiken.\n\n";
        $SEAFILE_TOKEN = $token;
    } else {
        echo "✗ Kon geen token verkrijgen. Controleer je inloggegevens.\n\n";
        exit(1);
    }
} else {
    echo "Bestaand token wordt gebruikt.\n\n";
}

// Maak client aan met token
$client = new SeafileClient($SEAFILE_URL, $SEAFILE_TOKEN);

// ============================================================================
// VOORBEELD 2: Connectie testen
// ============================================================================

echo "=== VOORBEELD 2: Connectie testen ===\n\n";

if ($client->ping()) {
    echo "✓ Verbinding met server is OK!\n\n";
} else {
    echo "✗ Kan geen verbinding maken: " . $client->getLastError() . "\n\n";
    exit(1);
}

// ============================================================================
// VOORBEELD 3: Account informatie ophalen
// ============================================================================

echo "=== VOORBEELD 3: Account informatie ===\n\n";

$accountInfo = $client->getAccountInfo();
if ($accountInfo) {
    echo "Gebruiker: {$accountInfo['email']}\n";
    echo "Naam: {$accountInfo['name']}\n";
    echo "Ruimte gebruikt: " . formatBytes($accountInfo['usage']) . "\n";
    echo "Totale ruimte: " . formatBytes($accountInfo['total']) . "\n\n";
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 4: Bibliotheken ophalen (SELECT * FROM libraries)
// ============================================================================

echo "=== VOORBEELD 4: Alle bibliotheken ophalen ===\n\n";

$libraries = $client->getLibraries();
if ($libraries) {
    echo "Gevonden bibliotheken: " . count($libraries) . "\n\n";

    foreach ($libraries as $lib) {
        echo "ID: {$lib['id']}\n";
        echo "  Naam: {$lib['name']}\n";
        echo "  Type: {$lib['type']}\n";
        echo "  Grootte: " . formatBytes($lib['size']) . "\n";
        echo "  Eigenaar: {$lib['owner']}\n";
        echo "  Encrypted: " . ($lib['encrypted'] ? 'Ja' : 'Nee') . "\n";
        echo "\n";
    }

    // Sla eerste bibliotheek ID op voor verdere voorbeelden
    $firstLibraryId = $libraries[0]['id'] ?? null;
} else {
    echo "✗ Fout: " . $client->getLastError() . "\n\n";
    $firstLibraryId = null;
}

// ============================================================================
// VOORBEELD 5: Directory inhoud ophalen (SELECT * FROM files WHERE path = '/')
// ============================================================================

echo "=== VOORBEELD 5: Directory inhoud ophalen ===\n\n";

if ($firstLibraryId) {
    $items = $client->listDirectory($firstLibraryId, '/');

    if ($items) {
        echo "Items in root directory: " . count($items) . "\n\n";

        foreach ($items as $item) {
            $type = $item['type'] === 'dir' ? '[DIR] ' : '[FILE]';
            $size = $item['type'] === 'file' ? ' (' . formatBytes($item['size']) . ')' : '';
            echo "$type {$item['name']}$size\n";
        }
        echo "\n";
    } else {
        echo "✗ Fout: " . $client->getLastError() . "\n\n";
    }
}

// ============================================================================
// VOORBEELD 6: Zoeken (SELECT * FROM files WHERE name LIKE '%query%')
// ============================================================================

echo "=== VOORBEELD 6: Zoeken in bestanden ===\n\n";

$zoekterm = 'rapport';  // Pas aan naar wat je wilt zoeken
$zoekresultaten = $client->search($zoekterm);

if ($zoekresultaten) {
    $total = $zoekresultaten['total'] ?? 0;
    $results = $zoekresultaten['results'] ?? [];

    echo "Zoeken naar '$zoekterm': $total resultaten gevonden\n\n";

    foreach ($results as $result) {
        echo "Bestand: {$result['name']}\n";
        echo "  Pad: {$result['fullpath']}\n";
        echo "  Bibliotheek: {$result['repo_name']}\n";
        if (isset($result['size'])) {
            echo "  Grootte: " . formatBytes($result['size']) . "\n";
        }
        echo "\n";
    }
} else {
    echo "Geen resultaten of fout: " . $client->getLastError() . "\n\n";
}

// ============================================================================
// VOORBEELD 7: Geavanceerd zoeken met filters
// ============================================================================

echo "=== VOORBEELD 7: Geavanceerd zoeken met filters ===\n\n";

// Zoek naar PDF bestanden die groter zijn dan 1MB
$filters = [
    'query' => 'pdf',
    'obj_type' => 'file',
    'size_from' => 1048576,  // 1MB in bytes
];

$results = $client->advancedSearch($filters);

if ($results) {
    $total = $results['total'] ?? 0;
    echo "PDF bestanden > 1MB: $total gevonden\n\n";

    foreach ($results['results'] ?? [] as $result) {
        echo "{$result['name']} - " . formatBytes($result['size'] ?? 0) . "\n";
    }
    echo "\n";
}

// ============================================================================
// VOORBEELD 8: Bestand details ophalen
// ============================================================================

echo "=== VOORBEELD 8: Bestand details ophalen ===\n\n";

if ($firstLibraryId && isset($items) && count($items) > 0) {
    // Zoek eerste bestand in de lijst
    $firstFile = null;
    foreach ($items as $item) {
        if ($item['type'] === 'file') {
            $firstFile = $item;
            break;
        }
    }

    if ($firstFile) {
        $filePath = '/' . $firstFile['name'];
        $details = $client->getFileDetail($firstLibraryId, $filePath);

        if ($details) {
            echo "Details van '{$firstFile['name']}':\n";
            echo "  ID: {$details['id']}\n";
            echo "  Grootte: " . formatBytes($details['size']) . "\n";
            echo "  Laatst gewijzigd: " . date('d-m-Y H:i:s', $details['mtime']) . "\n";
            echo "\n";
        }
    }
}

// ============================================================================
// VOORBEELD 9: Download link verkrijgen
// ============================================================================

echo "=== VOORBEELD 9: Download link verkrijgen ===\n\n";

if ($firstLibraryId && isset($firstFile)) {
    $filePath = '/' . $firstFile['name'];
    $downloadLink = $client->getDownloadLink($firstLibraryId, $filePath);

    if ($downloadLink) {
        echo "Download link voor '{$firstFile['name']}':\n";
        echo "$downloadLink\n\n";
        echo "Je kunt dit gebruiken om het bestand te downloaden:\n";
        echo "  curl -H 'Authorization: Token $SEAFILE_TOKEN' '$downloadLink' -o bestand.ext\n\n";
    }
}

// ============================================================================
// VOORBEELD 10: Bestand uploaden
// ============================================================================

echo "=== VOORBEELD 10: Bestand uploaden ===\n\n";

// Maak een test bestand
$testFile = '/tmp/test-upload.txt';
file_put_contents($testFile, "Dit is een test bestand, aangemaakt op " . date('Y-m-d H:i:s'));

if ($firstLibraryId && file_exists($testFile)) {
    echo "Test bestand uploaden...\n";
    $result = $client->uploadFile($firstLibraryId, $testFile, '/', 'test-upload.txt');

    if ($result) {
        echo "✓ Bestand succesvol geupload!\n";
        echo "  ID: {$result['id']}\n";
        echo "  Naam: {$result['name']}\n\n";
    } else {
        echo "✗ Upload mislukt: " . $client->getLastError() . "\n\n";
    }

    // Opruimen
    unlink($testFile);
}

// ============================================================================
// VOORBEELD 11: Directory aanmaken
// ============================================================================

echo "=== VOORBEELD 11: Directory aanmaken ===\n\n";

if ($firstLibraryId) {
    $newDir = '/TestMap-' . time();

    echo "Nieuwe map aanmaken: $newDir\n";
    $result = $client->createDirectory($firstLibraryId, $newDir);

    if ($result !== false) {
        echo "✓ Map succesvol aangemaakt!\n\n";

        // Verwijder de testmap weer
        echo "Testmap verwijderen...\n";
        if ($client->delete($firstLibraryId, $newDir)) {
            echo "✓ Map verwijderd\n\n";
        }
    } else {
        echo "✗ Fout: " . $client->getLastError() . "\n\n";
    }
}

// ============================================================================
// VOORBEELD 12: Gedeelde link maken
// ============================================================================

echo "=== VOORBEELD 12: Gedeelde link maken ===\n\n";

if ($firstLibraryId && isset($firstFile)) {
    $filePath = '/' . $firstFile['name'];

    echo "Gedeelde link maken voor '{$firstFile['name']}'...\n";
    $shareLink = $client->createShareLink(
        $firstLibraryId,
        $filePath,
        null,  // Geen wachtwoord
        7      // Geldig voor 7 dagen
    );

    if ($shareLink) {
        echo "✓ Gedeelde link aangemaakt!\n";
        echo "  Link: {$shareLink['link']}\n";
        echo "  Token: {$shareLink['token']}\n";
        echo "  Verloopt: " . date('d-m-Y', strtotime($shareLink['expire_date'])) . "\n\n";

        // Verwijder de link weer
        echo "Gedeelde link verwijderen...\n";
        if ($client->deleteShareLink($shareLink['token'])) {
            echo "✓ Link verwijderd\n\n";
        }
    } else {
        echo "✗ Fout: " . $client->getLastError() . "\n\n";
    }
}

// ============================================================================
// VOORBEELD 13: SQL-achtige queries met complexe filters
// ============================================================================

echo "=== VOORBEELD 13: SQL-achtige queries ===\n\n";

echo "Query 1: SELECT * FROM files WHERE type='file' AND name LIKE '%2024%'\n";
$result1 = $client->advancedSearch([
    'query' => '2024',
    'obj_type' => 'file'
]);
echo "  Resultaten: " . ($result1['total'] ?? 0) . "\n\n";

echo "Query 2: SELECT * FROM files WHERE size > 10MB AND modified > laatste_week\n";
$weekAgo = strtotime('-1 week');
$result2 = $client->advancedSearch([
    'query' => '*',  // Zoek alles
    'size_from' => 10485760,  // 10MB
    'time_from' => $weekAgo
]);
echo "  Resultaten: " . ($result2['total'] ?? 0) . "\n\n";

if ($firstLibraryId) {
    echo "Query 3: SELECT * FROM files WHERE library_id = '$firstLibraryId' AND path LIKE '/Documents%'\n";
    $result3 = $client->advancedSearch([
        'query' => '*',
        'repo_id' => $firstLibraryId,
        'path' => '/Documents'
    ]);
    echo "  Resultaten: " . ($result3['total'] ?? 0) . "\n\n";
}

// ============================================================================
// HULPFUNCTIES
// ============================================================================

/**
 * Format bytes naar leesbare grootte
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

echo "=== KLAAR ===\n\n";
echo "Alle voorbeelden zijn uitgevoerd!\n";
echo "Bekijk de code in examples.php om te zien hoe elk voorbeeld werkt.\n";
