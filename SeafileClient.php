<?php
/**
 * Seafile API Client
 * Eenvoudige PHP client voor communicatie met Seafile API
 *
 * Werkt met API tokens van Seafile Business/Professional Edition
 */

class SeafileClient {

    private $baseUrl;
    private $token;
    private $lastError = null;

    /**
     * Constructor
     *
     * @param string $baseUrl De basis URL van je Seafile server (bijv. https://seafile.example.com)
     * @param string $token Je Seafile API token
     */
    public function __construct($baseUrl, $token) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
    }

    /**
     * Verkrijg een API token met username en password
     *
     * @param string $baseUrl De basis URL van je Seafile server
     * @param string $username Je gebruikersnaam
     * @param string $password Je wachtwoord
     * @return string|false Het API token of false bij fout
     */
    public static function getToken($baseUrl, $username, $password) {
        $url = rtrim($baseUrl, '/') . '/api2/auth-token/';

        $data = [
            'username' => $username,
            'password' => $password
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['token'] ?? false;
        }

        return false;
    }

    /**
     * Voer een API request uit
     *
     * @param string $endpoint Het API endpoint (bijv. '/api2/repos/')
     * @param string $method HTTP methode (GET, POST, PUT, DELETE)
     * @param array $data Data om mee te sturen (voor POST/PUT)
     * @param array $params URL parameters (voor GET requests)
     * @return array|false Het response als array of false bij fout
     */
    private function request($endpoint, $method = 'GET', $data = [], $params = []) {
        $url = $this->baseUrl . $endpoint;

        // Voeg parameters toe aan URL voor GET requests
        if (!empty($params) && $method === 'GET') {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);

        $headers = [
            'Authorization: Token ' . $this->token,
            'Accept: application/json',
            'Content-Type: application/json'
        ];

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
     * Ping de server om de connectie te testen
     *
     * @return bool True als de connectie werkt
     */
    public function ping() {
        $result = $this->request('/api2/ping/');
        return $result !== false;
    }

    /**
     * Haal account informatie op
     *
     * @return array|false Account informatie of false bij fout
     */
    public function getAccountInfo() {
        return $this->request('/api2/account/info/');
    }

    /**
     * Haal alle bibliotheken (repositories) op
     *
     * @return array|false Lijst van bibliotheken of false bij fout
     */
    public function getLibraries() {
        return $this->request('/api2/repos/');
    }

    /**
     * Haal een specifieke bibliotheek op
     *
     * @param string $repoId De ID van de bibliotheek
     * @return array|false Bibliotheek informatie of false bij fout
     */
    public function getLibrary($repoId) {
        return $this->request("/api2/repos/{$repoId}/");
    }

    /**
     * Zoek in bibliotheken (SQL-achtige query functionaliteit)
     *
     * @param string $query De zoekterm
     * @param int $perPage Aantal resultaten per pagina (standaard 25)
     * @return array|false Zoekresultaten of false bij fout
     */
    public function search($query, $perPage = 25) {
        return $this->request('/api2/search/', 'GET', [], [
            'q' => $query,
            'per_page' => $perPage
        ]);
    }

    /**
     * Geavanceerd zoeken met filters (meer SQL-achtig)
     *
     * @param array $filters Associatieve array met filters:
     *                       - 'query': zoekterm (verplicht)
     *                       - 'repo_id': specifieke bibliotheek
     *                       - 'path': specifiek pad
     *                       - 'obj_type': 'file' of 'dir'
     *                       - 'time_from': timestamp
     *                       - 'time_to': timestamp
     *                       - 'size_from': bytes
     *                       - 'size_to': bytes
     * @return array|false Zoekresultaten of false bij fout
     */
    public function advancedSearch($filters) {
        if (!isset($filters['query'])) {
            $this->lastError = "Query parameter is verplicht";
            return false;
        }

        $params = ['q' => $filters['query']];

        // Voeg optionele filters toe
        $allowedFilters = ['repo_id', 'path', 'obj_type', 'time_from', 'time_to', 'size_from', 'size_to'];
        foreach ($allowedFilters as $filter) {
            if (isset($filters[$filter])) {
                $params[$filter] = $filters[$filter];
            }
        }

        return $this->request('/api2/search/', 'GET', [], $params);
    }

    /**
     * Haal directory inhoud op
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad (standaard '/')
     * @return array|false Directory inhoud of false bij fout
     */
    public function listDirectory($repoId, $path = '/') {
        return $this->request('/api2/repos/' . $repoId . '/dir/', 'GET', [], [
            'p' => $path
        ]);
    }

    /**
     * Haal file details op
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad naar het bestand
     * @return array|false File details of false bij fout
     */
    public function getFileDetail($repoId, $path) {
        return $this->request('/api2/repos/' . $repoId . '/file/detail/', 'GET', [], [
            'p' => $path
        ]);
    }

    /**
     * Download een bestand
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad naar het bestand
     * @return string|false Download URL of false bij fout
     */
    public function getDownloadLink($repoId, $path) {
        $result = $this->request('/api2/repos/' . $repoId . '/file/', 'GET', [], [
            'p' => $path
        ]);

        if ($result && is_string($result)) {
            // Verwijder quotes uit de response
            return trim($result, '"');
        }

        return $result;
    }

    /**
     * Haal upload link op
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad waar het bestand moet komen (standaard '/')
     * @return string|false Upload URL of false bij fout
     */
    public function getUploadLink($repoId, $path = '/') {
        $result = $this->request('/api2/repos/' . $repoId . '/upload-link/', 'GET', [], [
            'p' => $path
        ]);

        if ($result && is_string($result)) {
            return trim($result, '"');
        }

        return $result;
    }

    /**
     * Upload een bestand
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $localPath Lokaal pad naar het bestand
     * @param string $remotePath Remote pad waar het bestand moet komen (standaard '/')
     * @param string $filename Naam van het bestand op de server (optioneel)
     * @return array|false Upload resultaat of false bij fout
     */
    public function uploadFile($repoId, $localPath, $remotePath = '/', $filename = null) {
        if (!file_exists($localPath)) {
            $this->lastError = "Bestand niet gevonden: $localPath";
            return false;
        }

        $uploadLink = $this->getUploadLink($repoId, $remotePath);
        if (!$uploadLink) {
            return false;
        }

        $filename = $filename ?? basename($localPath);

        $ch = curl_init($uploadLink);

        $file = new CURLFile($localPath, mime_content_type($localPath), $filename);

        $postData = [
            'file' => $file,
            'filename' => $filename,
            'parent_dir' => $remotePath
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $this->token
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->lastError = "Upload failed: HTTP $httpCode - $response";
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * Maak een nieuwe directory
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad naar de nieuwe directory
     * @return array|false Resultaat of false bij fout
     */
    public function createDirectory($repoId, $path) {
        return $this->request('/api2/repos/' . $repoId . '/dir/', 'POST', [], [
            'p' => $path,
            'operation' => 'mkdir'
        ]);
    }

    /**
     * Verwijder een bestand of directory
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad naar het bestand/directory
     * @return bool True bij succes
     */
    public function delete($repoId, $path) {
        $result = $this->request('/api2/repos/' . $repoId . '/file/', 'DELETE', [], [
            'p' => $path
        ]);

        return $result !== false;
    }

    /**
     * Hernoem een bestand of directory
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het huidige pad
     * @param string $newName De nieuwe naam
     * @return bool True bij succes
     */
    public function rename($repoId, $path, $newName) {
        $result = $this->request('/api2/repos/' . $repoId . '/file/', 'POST', [], [
            'p' => $path,
            'operation' => 'rename',
            'newname' => $newName
        ]);

        return $result !== false;
    }

    /**
     * Kopieer een bestand of directory
     *
     * @param string $srcRepoId Bron bibliotheek ID
     * @param string $srcPath Bron pad
     * @param string $dstRepoId Doel bibliotheek ID
     * @param string $dstPath Doel pad
     * @return bool True bij succes
     */
    public function copy($srcRepoId, $srcPath, $dstRepoId, $dstPath) {
        $result = $this->request('/api2/repos/' . $srcRepoId . '/file/', 'POST', [], [
            'p' => $srcPath,
            'operation' => 'copy',
            'dst_repo' => $dstRepoId,
            'dst_dir' => $dstPath
        ]);

        return $result !== false;
    }

    /**
     * Verplaats een bestand of directory
     *
     * @param string $srcRepoId Bron bibliotheek ID
     * @param string $srcPath Bron pad
     * @param string $dstRepoId Doel bibliotheek ID
     * @param string $dstPath Doel pad
     * @return bool True bij succes
     */
    public function move($srcRepoId, $srcPath, $dstRepoId, $dstPath) {
        $result = $this->request('/api2/repos/' . $srcRepoId . '/file/', 'POST', [], [
            'p' => $srcPath,
            'operation' => 'move',
            'dst_repo' => $dstRepoId,
            'dst_dir' => $dstPath
        ]);

        return $result !== false;
    }

    /**
     * Haal gedeelde links op
     *
     * @return array|false Lijst van gedeelde links of false bij fout
     */
    public function getSharedLinks() {
        return $this->request('/api/v2.1/share-links/');
    }

    /**
     * Maak een gedeelde link
     *
     * @param string $repoId De ID van de bibliotheek
     * @param string $path Het pad naar het bestand/directory
     * @param string $password Optioneel wachtwoord
     * @param int $expireDays Aantal dagen geldig (optioneel)
     * @return array|false Link informatie of false bij fout
     */
    public function createShareLink($repoId, $path, $password = null, $expireDays = null) {
        $data = [
            'repo_id' => $repoId,
            'path' => $path
        ];

        if ($password) {
            $data['password'] = $password;
        }

        if ($expireDays) {
            $data['expire_days'] = $expireDays;
        }

        return $this->request('/api/v2.1/share-links/', 'POST', $data);
    }

    /**
     * Verwijder een gedeelde link
     *
     * @param string $token De token van de gedeelde link
     * @return bool True bij succes
     */
    public function deleteShareLink($token) {
        $result = $this->request('/api/v2.1/share-links/' . $token . '/', 'DELETE');
        return $result !== false;
    }
}
