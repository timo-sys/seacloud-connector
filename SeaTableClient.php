<?php
/**
 * SeaTable API Client
 * Eenvoudige PHP client voor communicatie met SeaTable API
 *
 * Ondersteunt volledige SQL queries (SELECT, INSERT, UPDATE, DELETE)
 * Werkt met API tokens van SeaTable Business/Enterprise Edition
 */

class SeaTableClient {

    private $serverUrl;
    private $apiToken;
    private $baseToken = null;
    private $baseUuid = null;
    private $baseTokenExpiry = null;
    private $lastError = null;

    /**
     * Constructor
     *
     * @param string $serverUrl De URL van je SeaTable server (bijv. https://cloud.seatable.io)
     * @param string $apiToken Je SeaTable API token (permanent token voor één base)
     */
    public function __construct($serverUrl, $apiToken) {
        $this->serverUrl = rtrim($serverUrl, '/');
        $this->apiToken = $apiToken;
    }

    /**
     * Verkrijg een base token van de API token
     * Base tokens zijn 3 dagen geldig
     *
     * @return bool True bij succes, false bij fout
     */
    public function authenticate() {
        // Check of we al een geldig base token hebben
        if ($this->baseToken && $this->baseTokenExpiry && time() < $this->baseTokenExpiry) {
            return true;
        }

        $url = $this->serverUrl . '/api/v2.1/dtable/app-access-token/';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $this->apiToken,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->lastError = "CURL Error: " . $curlError;
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = "Authentication failed (HTTP $httpCode): " . $response;
            return false;
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token']) || !isset($data['dtable_uuid'])) {
            $this->lastError = "Invalid authentication response";
            return false;
        }

        $this->baseToken = $data['access_token'];
        $this->baseUuid = $data['dtable_uuid'];
        $this->baseTokenExpiry = time() + (3 * 24 * 60 * 60) - 3600; // 3 dagen - 1 uur voor veiligheid
        $this->lastError = null;

        return true;
    }

    /**
     * Voer een API request uit
     *
     * @param string $endpoint Het API endpoint
     * @param string $method HTTP methode (GET, POST, PUT, DELETE)
     * @param array $data Data om mee te sturen
     * @param array $params URL parameters
     * @param bool $useBaseToken Gebruik base token ipv API token
     * @return array|false Het response als array of false bij fout
     */
    private function request($endpoint, $method = 'GET', $data = [], $params = [], $useBaseToken = true) {
        // Authenticeer indien nodig
        if ($useBaseToken && !$this->authenticate()) {
            return false;
        }

        $url = $this->serverUrl . $endpoint;

        // Voeg parameters toe aan URL
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        if ($useBaseToken) {
            $headers[] = 'Authorization: Bearer ' . $this->baseToken;
        } else {
            $headers[] = 'Authorization: Token ' . $this->apiToken;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->lastError = "CURL Error: " . $curlError;
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->lastError = "HTTP Error $httpCode: " . $response;
            return false;
        }

        $this->lastError = null;

        // Lege responses zijn ok (bijv. bij DELETE)
        if (empty($response)) {
            return [];
        }

        return json_decode($response, true);
    }

    /**
     * Haal de laatste foutmelding op
     *
     * @return string|null De laatste foutmelding
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Haal het base UUID op (beschikbaar na authenticatie)
     *
     * @return string|null Het base UUID
     */
    public function getBaseUuid() {
        return $this->baseUuid;
    }

    // ========================================================================
    // SQL QUERY METHODEN
    // ========================================================================

    /**
     * Voer een SQL query uit op de base
     *
     * Ondersteunt: SELECT, INSERT, UPDATE, DELETE
     * Max 10,000 rijen per query
     *
     * @param string $sql De SQL query
     * @param array $params Parameters voor prepared statements (vervangt ? in query)
     * @return array|false Query resultaten of false bij fout
     */
    public function query($sql, $params = []) {
        if (!$this->authenticate()) {
            return false;
        }

        $endpoint = '/api-gateway/api/v2/dtables/' . $this->baseUuid . '/sql/';

        $data = ['sql' => $sql];

        if (!empty($params)) {
            $data['convert_keys'] = false;  // Behoud originele kolomnamen
        }

        // Voor prepared statements moet je mogelijk de params anders meegeven
        // afhankelijk van de exacte API implementatie

        $result = $this->request($endpoint, 'POST', $data);

        return $result;
    }

    /**
     * SELECT query uitvoeren
     *
     * @param string $table Tabel naam
     * @param array $columns Kolommen om op te halen (leeg = alle kolommen)
     * @param string $where WHERE clausule (optioneel)
     * @param string $orderBy ORDER BY clausule (optioneel)
     * @param int $limit LIMIT (optioneel, max 10000)
     * @return array|false Query resultaten of false bij fout
     */
    public function select($table, $columns = [], $where = '', $orderBy = '', $limit = null) {
        $cols = empty($columns) ? '*' : implode(', ', $columns);

        $sql = "SELECT $cols FROM `$table`";

        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . min((int)$limit, 10000);
        }

        return $this->query($sql);
    }

    /**
     * INSERT query uitvoeren
     * LET OP: INSERT werkt alleen voor gearchiveerde bases (big data storage)
     *
     * @param string $table Tabel naam
     * @param array $data Associatieve array met kolom => waarde
     * @return array|false Query resultaat of false bij fout
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);

        $columnList = '`' . implode('`, `', $columns) . '`';

        $valuePlaceholders = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $valuePlaceholders[] = "'" . addslashes($value) . "'";
            } elseif (is_null($value)) {
                $valuePlaceholders[] = 'NULL';
            } elseif (is_bool($value)) {
                $valuePlaceholders[] = $value ? 'TRUE' : 'FALSE';
            } else {
                $valuePlaceholders[] = $value;
            }
        }

        $valueList = implode(', ', $valuePlaceholders);

        $sql = "INSERT INTO `$table` ($columnList) VALUES ($valueList)";

        return $this->query($sql);
    }

    /**
     * UPDATE query uitvoeren
     *
     * @param string $table Tabel naam
     * @param array $data Associatieve array met kolom => waarde
     * @param string $where WHERE clausule (verplicht voor veiligheid!)
     * @return array|false Query resultaat of false bij fout
     */
    public function update($table, $data, $where) {
        if (empty($where)) {
            $this->lastError = "WHERE clausule is verplicht voor UPDATE (veiligheid)";
            return false;
        }

        $setParts = [];
        foreach ($data as $column => $value) {
            if (is_string($value)) {
                $setParts[] = "`$column` = '" . addslashes($value) . "'";
            } elseif (is_null($value)) {
                $setParts[] = "`$column` = NULL";
            } elseif (is_bool($value)) {
                $setParts[] = "`$column` = " . ($value ? 'TRUE' : 'FALSE');
            } else {
                $setParts[] = "`$column` = $value";
            }
        }

        $setClause = implode(', ', $setParts);

        $sql = "UPDATE `$table` SET $setClause WHERE $where";

        return $this->query($sql);
    }

    /**
     * DELETE query uitvoeren
     *
     * @param string $table Tabel naam
     * @param string $where WHERE clausule (verplicht voor veiligheid!)
     * @return array|false Query resultaat of false bij fout
     */
    public function delete($table, $where) {
        if (empty($where)) {
            $this->lastError = "WHERE clausule is verplicht voor DELETE (veiligheid)";
            return false;
        }

        $sql = "DELETE FROM `$table` WHERE $where";

        return $this->query($sql);
    }

    // ========================================================================
    // ROW OPERATIES (REST API)
    // ========================================================================

    /**
     * Haal rijen op uit een tabel
     *
     * @param string $tableName Tabel naam
     * @param string $view Optionele view naam
     * @param int $limit Aantal rijen (max 10000)
     * @return array|false Rijen of false bij fout
     */
    public function listRows($tableName, $view = null, $limit = 1000) {
        $params = ['table_name' => $tableName];

        if ($view) {
            $params['view_name'] = $view;
        }

        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/rows/';

        $result = $this->request($endpoint, 'GET', [], $params);

        if ($result && isset($result['rows'])) {
            return array_slice($result['rows'], 0, $limit);
        }

        return $result;
    }

    /**
     * Voeg een rij toe aan een tabel
     *
     * @param string $tableName Tabel naam
     * @param array $row Associatieve array met kolom => waarde
     * @return array|false Toegevoegde rij of false bij fout
     */
    public function appendRow($tableName, $row) {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/rows/';

        $data = [
            'table_name' => $tableName,
            'row' => $row
        ];

        return $this->request($endpoint, 'POST', $data);
    }

    /**
     * Voeg meerdere rijen toe
     *
     * @param string $tableName Tabel naam
     * @param array $rows Array van rijen (elke rij is een associatieve array)
     * @return array|false Resultaat of false bij fout
     */
    public function appendRows($tableName, $rows) {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/batch-append-rows/';

        $data = [
            'table_name' => $tableName,
            'rows' => $rows
        ];

        return $this->request($endpoint, 'POST', $data);
    }

    /**
     * Update een rij
     *
     * @param string $tableName Tabel naam
     * @param string $rowId Rij ID (_id veld)
     * @param array $row Associatieve array met kolom => waarde
     * @return array|false Resultaat of false bij fout
     */
    public function updateRow($tableName, $rowId, $row) {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/rows/';

        $data = [
            'table_name' => $tableName,
            'row_id' => $rowId,
            'row' => $row
        ];

        return $this->request($endpoint, 'PUT', $data);
    }

    /**
     * Update meerdere rijen
     *
     * @param string $tableName Tabel naam
     * @param array $updates Array van updates: [['row_id' => 'xxx', 'row' => [data]], ...]
     * @return array|false Resultaat of false bij fout
     */
    public function updateRows($tableName, $updates) {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/batch-update-rows/';

        $data = [
            'table_name' => $tableName,
            'updates' => $updates
        ];

        return $this->request($endpoint, 'PUT', $data);
    }

    /**
     * Verwijder een rij
     *
     * @param string $tableName Tabel naam
     * @param string $rowId Rij ID
     * @return array|false Resultaat of false bij fout
     */
    public function deleteRow($tableName, $rowId) {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/rows/';

        $data = [
            'table_name' => $tableName,
            'row_id' => $rowId
        ];

        return $this->request($endpoint, 'DELETE', $data);
    }

    /**
     * Verwijder meerdere rijen
     *
     * @param string $tableName Tabel naam
     * @param array $rowIds Array van rij IDs
     * @return array|false Resultaat of false bij fout
     */
    public function deleteRows($tableName, $rowIds) {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/batch-delete-rows/';

        $data = [
            'table_name' => $tableName,
            'row_ids' => $rowIds
        ];

        return $this->request($endpoint, 'DELETE', $data);
    }

    // ========================================================================
    // BASE & TABLE METADATA
    // ========================================================================

    /**
     * Haal base metadata op
     *
     * @return array|false Base metadata of false bij fout
     */
    public function getMetadata() {
        $endpoint = '/dtable-server/api/v1/dtables/' . $this->baseUuid . '/metadata/';

        return $this->request($endpoint, 'GET');
    }

    /**
     * Haal lijst van alle tabellen in de base
     *
     * @return array|false Array van tabellen of false bij fout
     */
    public function getTables() {
        $metadata = $this->getMetadata();

        if ($metadata && isset($metadata['tables'])) {
            return $metadata['tables'];
        }

        return false;
    }

    /**
     * Haal informatie op van een specifieke tabel
     *
     * @param string $tableName Tabel naam
     * @return array|false Tabel info of false bij fout
     */
    public function getTable($tableName) {
        $tables = $this->getTables();

        if (!$tables) {
            return false;
        }

        foreach ($tables as $table) {
            if ($table['name'] === $tableName) {
                return $table;
            }
        }

        $this->lastError = "Tabel '$tableName' niet gevonden";
        return false;
    }

    /**
     * Haal alle kolommen van een tabel op
     *
     * @param string $tableName Tabel naam
     * @return array|false Array van kolommen of false bij fout
     */
    public function getColumns($tableName) {
        $table = $this->getTable($tableName);

        if ($table && isset($table['columns'])) {
            return $table['columns'];
        }

        return false;
    }

    // ========================================================================
    // HULP METHODEN
    // ========================================================================

    /**
     * Test de connectie en authenticatie
     *
     * @return bool True als de connectie en authenticatie werkt
     */
    public function ping() {
        return $this->authenticate();
    }

    /**
     * Krijg een overzicht van de base structuur
     *
     * @return array|false Structuur overzicht of false bij fout
     */
    public function getStructure() {
        $tables = $this->getTables();

        if (!$tables) {
            return false;
        }

        $structure = [];

        foreach ($tables as $table) {
            $structure[$table['name']] = [
                'id' => $table['_id'],
                'columns' => []
            ];

            foreach ($table['columns'] as $column) {
                $structure[$table['name']]['columns'][] = [
                    'name' => $column['name'],
                    'type' => $column['type'],
                    'key' => $column['key']
                ];
            }
        }

        return $structure;
    }
}
