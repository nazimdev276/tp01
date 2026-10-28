<?php
// tests.php : vérification locale des pages et des parcours principaux.
session_start();
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/donnees.php';

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$localRequest = in_array($remoteAddress, ['127.0.0.1', '::1'], true)
    && preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(?::[0-9]{1,5})?$/i', $host) === 1;
$base = '';
$titre = 'Diagnostic du site';
$results = [];
$runRequested = $_SERVER['REQUEST_METHOD'] === 'POST';
$csrfValid = !$runRequested
    || (isset($_SESSION['diagnostic_csrf'], $_POST['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['diagnostic_csrf'], $_POST['csrf']));
$_SESSION['diagnostic_csrf'] = bin2hex(random_bytes(32));
$databaseDiagnosticMode = true;
$databaseError = null;

try {
    require __DIR__ . '/config/database.php';
} catch (PDOException $e) {
    $databaseError = $e->getMessage();
}

function diagnostic_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function diagnostic_request(string $url, string $method = 'GET', array|string $data = [], ?string $contentType = null): array
{
    $headers = [];
    $options = [
        'method' => $method,
        'ignore_errors' => true,
        'timeout' => 12,
        'follow_location' => 0,
    ];
    if ($method === 'POST') {
        $options['header'] = 'Content-Type: ' . ($contentType ?? 'application/x-www-form-urlencoded') . "\r\n";
        $options['content'] = is_array($data) ? http_build_query($data) : $data;
    }
    $context = stream_context_create(['http' => $options]);
    set_error_handler(static function (int $severity, string $message): never {
        throw new RuntimeException($message);
    });
    try {
        $body = file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
    } finally {
        restore_error_handler();
    }
    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+([0-9]{3})/', $header, $match)) {
            $status = (int)$match[1];
        }
    }
    if ($status === 0 || $body === false) {
        throw new RuntimeException("Pas de réponse HTTP exploitable (code $status).");
    }
    return ['status' => $status, 'body' => $body];
}

function diagnostic_database_value(PDO $pdo, string $sql, array $params = []): mixed
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchColumn();
}

function diagnostic_response_message(string $body): string
{
    if (preg_match('/<p class="erreur">(.+?)<\/p>/s', $body, $match) === 1) {
        $message = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    } else {
        $message = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }
    return $message !== '' ? mb_substr($message, 0, 280) : "Le formulaire n'a pas confirmé l'opération attendue.";
}

function diagnostic_post(string $url, array $data, string $successText): string
{
    $response = diagnostic_request($url, 'POST', $data);
    diagnostic_assert($response['status'] >= 200 && $response['status'] < 400, "Réponse HTTP {$response['status']}.");
    if (!str_contains($response['body'], $successText)) {
        throw new RuntimeException('Réponse du formulaire : ' . diagnostic_response_message($response['body']));
    }
    return $response['body'];
}

function diagnostic_png_fixture(): string
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC', true);
    if ($png === false) {
        throw new RuntimeException('Impossible de préparer le fichier image de test.');
    }
    return $png;
}

function diagnostic_multipart_request(
    string $url,
    array $data,
    string $fileField,
    string $fileName,
    string $mime,
    string $contents
): array {
    $boundary = '----tp01diagnostic' . bin2hex(random_bytes(8));
    $parts = [];
    foreach ($data as $name => $value) {
        $fieldName = is_array($value) ? $name . '[]' : $name;
        foreach (is_array($value) ? $value : [$value] as $item) {
            if (!is_string($item) && !is_int($item)) {
                throw new RuntimeException('Donnée de formulaire multipart invalide.');
            }
            $parts[] = "--$boundary\r\n"
                . "Content-Disposition: form-data; name=\"$fieldName\"\r\n\r\n"
                . (string)$item . "\r\n";
        }
    }
    $parts[] = "--$boundary\r\n"
        . "Content-Disposition: form-data; name=\"$fileField\"; filename=\"$fileName\"\r\n"
        . "Content-Type: $mime\r\n\r\n"
        . $contents . "\r\n--$boundary--\r\n";
    return diagnostic_request(
        $url,
        'POST',
        implode('', $parts),
        'multipart/form-data; boundary=' . $boundary
    );
}

function diagnostic_run(string $label, callable $test, array &$results): bool
{
    try {
        $detail = $test();
        $results[] = ['label' => $label, 'ok' => true, 'detail' => $detail ?: 'Réussi'];
        return true;
    } catch (Throwable $e) {
        $results[] = ['label' => $label, 'ok' => false, 'detail' => $e->getMessage()];
        return false;
    }
}

if ($runRequested) {
    if (!$localRequest) {
        http_response_code(403);
        $results[] = ['label' => 'Accès au diagnostic', 'ok' => false, 'detail' => 'Disponible uniquement depuis cette machine.'];
    } elseif (!$csrfValid) {
        http_response_code(400);
        $results[] = ['label' => 'Sécurité du formulaire', 'ok' => false, 'detail' => 'Jeton de sécurité expiré. Rechargez la page et relancez le test.'];
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptDirectory = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/tests.php'), '/.');
        $applicationPath = $scriptDirectory === '' ? '' : $scriptDirectory;
        $applicationUrl = $scheme . '://' . $host . $applicationPath . '/';
        $routes = [
            ['Accueil', 'index.php', 'class="home-intro"'],
            ['Formulaire étudiants', 'personne/formulaire.php', 'id="form-personne"'],
            ['Liste étudiants', 'personne/liste.php', 'Liste des personnes'],
            ['Affichage PHP étudiant', 'personne/afficher_php.php', 'Aucune donnée reçue.'],
            ['Insertion image étudiant', 'personne/upload_image.php', 'Insérer Image'],
            ['Enregistrement étudiant', 'personne/enregistrer.php', null],
            ['Suppression étudiant', 'personne/supprimer.php', 'La suppression doit être demandée'],
            ['Modification étudiant', 'personne/modifier.php', 'Identifiant invalide.'],
            ['Recherche voyageur', 'personne/voyageurs.php', 'ID du voyageur'],
            ['Formulaire enseignants', 'enseignant/enseignant.php', 'id="form-enseignant"'],
            ['Liste enseignants', 'enseignant/liste.php', 'Liste des enseignants'],
            ['Enregistrement enseignant', 'enseignant/enregistrer.php', null],
            ['Formulaire modules', 'module/module.php', 'id="form-module"'],
            ['Liste modules', 'module/liste.php', 'Liste des modules'],
            ['Enregistrement module', 'module/enregistrer.php', null],
            ['Formulaire Bulletin de Notes', 'notes/bulletin.php', 'Saisie Bulletin de Notes'],
            ['Formulaire filières', 'filiere/filiere.php', 'id="form-filiere"'],
            ['Liste filières', 'filiere/liste.php', 'Liste des filières'],
            ['Enregistrement filière', 'filiere/enregistrer.php', null],
            ['Formulaire nationalités', 'nationalite/nationalite.php', 'id="form-nationalite"'],
            ['Liste nationalités', 'nationalite/liste.php', 'Liste des nationalités'],
            ['Enregistrement nationalité', 'nationalite/enregistrer.php', null],
            ['Formulaire sports', 'sport/sport.php', 'id="form-sport"'],
            ['Liste sports', 'sport/liste.php', 'Liste des sports'],
            ['Enregistrement sport', 'sport/enregistrer.php', null],
            ['Formulaire transports', 'transport/transport.php', 'id="form-transport"'],
            ['Liste transports', 'transport/liste.php', 'Liste des moyens de transport'],
            ['Enregistrement transport', 'transport/enregistrer.php', null],
        ];

        foreach ($routes as [$label, $path, $expectedMarker]) {
            diagnostic_run("Page : $label", static function () use ($applicationUrl, $path, $expectedMarker): string {
                $response = diagnostic_request($applicationUrl . $path);
                diagnostic_assert($response['status'] >= 200 && $response['status'] < 400, "Réponse HTTP {$response['status']}.");
                diagnostic_assert(
                    !str_contains($response['body'], 'Erreur de connexion à la base de données'),
                    'La page indique une erreur de connexion à MySQL.'
                );
                if ($expectedMarker !== null && $response['status'] < 300) {
                    diagnostic_assert(str_contains($response['body'], $expectedMarker), "Contenu attendu introuvable : $expectedMarker");
                }
                return "Réponse HTTP {$response['status']}.";
            }, $results);
        }

        diagnostic_run('Chargement du JavaScript', static function () use ($applicationUrl): string {
            $response = diagnostic_request($applicationUrl . 'js/script.js');
            diagnostic_assert($response['status'] === 200, "Réponse HTTP {$response['status']}.");
            diagnostic_assert(str_contains($response['body'], 'DOMContentLoaded'), 'Le fichier JavaScript semble incomplet.');
            return 'Script accessible.';
        }, $results);

        if ($databaseError !== null) {
            $results[] = ['label' => 'Connexion à MySQL', 'ok' => false, 'detail' => $databaseError];
        } elseif (!$localRequest) {
            $results[] = ['label' => 'Opérations de test', 'ok' => false, 'detail' => 'Les opérations d’écriture sont réservées à une requête locale.'];
        } else {
            $token = strtoupper(bin2hex(random_bytes(5)));
            $filiereCode = 'T' . substr($token, 0, 9);
            $filiereLibelle = 'M' . substr($token, 0, 9);
            $nationaliteCode = '';
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $candidateCode = strtoupper(substr(bin2hex(random_bytes(2)), 0, 3));
                if ((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM nationalite WHERE code = :code', [':code' => $candidateCode]) === 0) {
                    $nationaliteCode = $candidateCode;
                    break;
                }
            }
            if ($nationaliteCode === '') {
                $results[] = ['label' => 'Préparation du test', 'ok' => false, 'detail' => 'Impossible de réserver un code de nationalité temporaire libre.'];
            }
            $nationaliteLibelle = 'TEST-' . $token;
            $sportName = 'TEST-' . $token;
            $transportName = 'TR-' . $token;
            $moduleCode = 'T' . $token;
            $moduleCreatedDesignation = 'TEST-' . $token;
            $moduleUpdatedDesignation = 'MOD-' . $token;
            $teacherName = 'TEST-' . $token;
            $teacherUpdatedName = 'MOD-' . $token;
            $studentName = 'TEST-' . $token;
            $studentUpdatedName = 'MOD-' . $token;
            $voyageurName = 'TEST-' . $token;
            $voyageurUpdatedName = 'MOD-' . $token;
            $studentNumber = (string)random_int(100000000, 999999999);
            $bulletinModuleCode = 'B' . $token;
            $bulletinModuleName = 'NOTE-' . $token;
            $teacherNumber = (string)random_int(100000000, 999999999);
            $voyageurNumber = (string)random_int(100000000, 999999999);
            $imageFilename = null;
            $voyageurImageFilename = null;

            try {
                diagnostic_run('Création et modification de filière', static function () use ($pdo, $applicationUrl, $filiereCode, $filiereLibelle): string {
                    diagnostic_post($applicationUrl . 'filiere/filiere.php', [
                        'action' => 'enregistrer', 'code' => $filiereCode, 'libelle' => $filiereCode,
                    ], 'Filière enregistrée.');
                    diagnostic_post($applicationUrl . 'filiere/filiere.php', [
                        'action' => 'modifier', 'code_original' => $filiereCode,
                        'code' => $filiereCode, 'libelle' => $filiereLibelle,
                    ], 'Modifications enregistrées.');
                    diagnostic_assert(
                        diagnostic_database_value($pdo, 'SELECT libelle FROM filiere WHERE code = :code', [':code' => $filiereCode]) === $filiereLibelle,
                        'La modification n’est pas présente dans la base de données.'
                    );
                    $page = diagnostic_request($applicationUrl . 'filiere/liste.php');
                    diagnostic_assert(str_contains($page['body'], $filiereLibelle), 'La filière modifiée n’apparaît pas dans sa liste.');
                    return 'Création et modification confirmées.';
                }, $results);

                diagnostic_run('Création de nationalité, sport et transport', static function () use ($pdo, $applicationUrl, $nationaliteCode, $nationaliteLibelle, $sportName, $transportName): string {
                    diagnostic_post($applicationUrl . 'nationalite/enregistrer.php', [
                        'code' => $nationaliteCode, 'libelle' => $nationaliteLibelle,
                    ], 'Nationalité enregistrée');
                    diagnostic_post($applicationUrl . 'sport/enregistrer.php', ['libelle' => $sportName], 'Sport enregistré');
                    $transportDescription = 'Description de test';
                    diagnostic_post($applicationUrl . 'transport/enregistrer.php', ['libelle' => $transportName, 'description' => $transportDescription], 'Moyen de transport enregistré');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM nationalite WHERE code = :code', [':code' => $nationaliteCode]) === 1, 'Nationalité absente de la base.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM sport WHERE libelle = :libelle', [':libelle' => $sportName]) === 1, 'Sport absent de la base.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM moyen_transport WHERE libelle = :libelle', [':libelle' => $transportName]) === 1, 'Transport absent de la base.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT description FROM moyen_transport WHERE libelle = :libelle', [':libelle' => $transportName]) === $transportDescription, 'Description du transport absente de la base.');
                    foreach ([
                        ['nationalite/liste.php', $nationaliteLibelle],
                        ['sport/liste.php', $sportName],
                        ['transport/liste.php', $transportName],
                    ] as [$path, $value]) {
                        $page = diagnostic_request($applicationUrl . $path);
                        diagnostic_assert(str_contains($page['body'], $value), "La valeur de test n’apparaît pas dans $path.");
                    }
                    return 'Les trois formulaires enregistrent correctement.';
                }, $results);

                $teacherId = null;
                diagnostic_run('Cycle CRUD enseignant', static function () use ($pdo, $applicationUrl, $teacherNumber, $teacherName, $teacherUpdatedName, $CIVILITES, $PAYS, $GRADES_ENSEIGNANT, $SPECIALITES_ENSEIGNANT, &$teacherId): string {
                    $teacherData = [
                        'numero' => $teacherNumber, 'civilite' => $CIVILITES[0],
                        'nom_prenom' => $teacherName, 'adresse' => 'Adresse de test',
                        'date_naissance' => '1980-01-02', 'lieu_naissance' => 'Ville de test',
                        'pays' => $PAYS[0], 'grade' => $GRADES_ENSEIGNANT[0],
                        'specialite' => $SPECIALITES_ENSEIGNANT[0],
                    ];
                    diagnostic_post($applicationUrl . 'enseignant/enseignant.php', ['action' => 'enregistrer'] + $teacherData, 'Enseignant enregistré.');
                    $teacherId = diagnostic_database_value($pdo, 'SELECT id FROM enseignant WHERE numero = :numero AND nom_prenom = :nom', [':numero' => $teacherNumber, ':nom' => $teacherName]);
                    diagnostic_assert($teacherId !== false, 'Enseignant introuvable après création.');
                    $teacherData['nom_prenom'] = $teacherUpdatedName;
                    diagnostic_post($applicationUrl . 'enseignant/enseignant.php', ['action' => 'modifier', 'id' => (string)$teacherId] + $teacherData, 'Modifications enregistrées.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT nom_prenom FROM enseignant WHERE id = :id', [':id' => $teacherId]) === $teacherUpdatedName, 'La modification de l’enseignant n’a pas été enregistrée.');
                    $page = diagnostic_request($applicationUrl . 'enseignant/enseignant.php?id=' . rawurlencode((string)$teacherId));
                    diagnostic_assert(str_contains($page['body'], $teacherUpdatedName), 'La recherche de l’enseignant ne retourne pas sa fiche.');
                    diagnostic_post($applicationUrl . 'enseignant/enseignant.php', ['action' => 'supprimer', 'id' => (string)$teacherId], 'Enseignant supprimé.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM enseignant WHERE id = :id', [':id' => $teacherId]) === 0, 'L’enseignant existe encore après suppression.');
                    return 'Création, recherche, modification et suppression confirmées.';
                }, $results);

                $moduleId = null;
                diagnostic_run('Cycle CRUD module', static function () use ($pdo, $applicationUrl, $moduleCode, $moduleCreatedDesignation, $moduleUpdatedDesignation, $filiereCode, &$moduleId): string {
                    $moduleData = [
                        'code' => $moduleCode, 'designation' => $moduleCreatedDesignation,
                        'coefficient' => '2', 'volume_horaire' => '12', 'filiere' => $filiereCode,
                    ];
                    diagnostic_post($applicationUrl . 'module/module.php', ['action' => 'enregistrer'] + $moduleData, 'Module enregistré.');
                    $moduleId = diagnostic_database_value($pdo, 'SELECT id FROM module WHERE code = :code', [':code' => $moduleCode]);
                    diagnostic_assert($moduleId !== false, 'Module introuvable après création.');
                    $moduleData['designation'] = $moduleUpdatedDesignation;
                    diagnostic_post($applicationUrl . 'module/module.php', ['action' => 'modifier', 'id' => (string)$moduleId] + $moduleData, 'Modifications enregistrées.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT designation FROM module WHERE id = :id', [':id' => $moduleId]) === $moduleUpdatedDesignation, 'La modification du module n’a pas été enregistrée.');
                    $page = diagnostic_request($applicationUrl . 'module/module.php?id=' . rawurlencode((string)$moduleId));
                    diagnostic_assert(str_contains($page['body'], $moduleUpdatedDesignation), 'La recherche du module ne retourne pas sa fiche.');
                    diagnostic_post($applicationUrl . 'module/module.php', ['action' => 'supprimer', 'id' => (string)$moduleId], 'Module supprimé.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM module WHERE id = :id', [':id' => $moduleId]) === 0, 'Le module existe encore après suppression.');
                    return 'Création, recherche, modification et suppression confirmées.';
                }, $results);

                diagnostic_run('Cycle CRUD voyageur et photo', static function () use ($pdo, $applicationUrl, $voyageurNumber, $voyageurName, $voyageurUpdatedName, $filiereCode, $transportName, $PAYS, $CIVILITES, &$voyageurImageFilename): string {
                    $voyageurData = [
                        'action' => 'creer_voyageur',
                        'numero' => $voyageurNumber,
                        'type_foyer' => 'Famille',
                        'civilite' => $CIVILITES[0],
                        'nom_prenom' => $voyageurName,
                        'adresse' => 'Adresse de test voyageur',
                        'code_postal' => '12345',
                        'pays' => $PAYS[0],
                        'filiere_code' => $filiereCode,
                        'transport' => [$transportName],
                        'description' => 'Description de test voyageur',
                    ];
                    $creation = diagnostic_multipart_request(
                        $applicationUrl . 'personne/voyageurs.php',
                        $voyageurData,
                        'photo',
                        'voyageur.png',
                        'image/png',
                        diagnostic_png_fixture()
                    );
                    if ($creation['status'] !== 200 || !str_contains($creation['body'], 'Le voyageur a été enregistré.')) {
                        throw new RuntimeException('Réponse du formulaire : ' . diagnostic_response_message($creation['body']));
                    }
                    $voyageurId = diagnostic_database_value(
                        $pdo,
                        'SELECT id FROM voyageur WHERE numero = :numero AND nom_prenom = :nom',
                        [':numero' => $voyageurNumber, ':nom' => $voyageurName]
                    );
                    diagnostic_assert($voyageurId !== false, 'Voyageur introuvable après création.');
                    $voyageurId = (string)$voyageurId;
                    $voyageurImageFilename = diagnostic_database_value(
                        $pdo,
                        'SELECT photo FROM voyageur WHERE id = :id',
                        [':id' => $voyageurId]
                    );
                    diagnostic_assert(
                        is_string($voyageurImageFilename)
                        && preg_match('/^[a-f0-9]{16}\.(?:png|jpg|gif)$/i', $voyageurImageFilename) === 1
                        && is_file(__DIR__ . '/uploads/' . $voyageurImageFilename),
                        'La photo du voyageur n’a pas été enregistrée correctement.'
                    );

                    $searchPage = diagnostic_request($applicationUrl . 'personne/voyageurs.php?id=' . rawurlencode($voyageurId));
                    diagnostic_assert(
                        str_contains($searchPage['body'], $voyageurName)
                        && str_contains($searchPage['body'], 'Informations du voyageur ID')
                        && str_contains($searchPage['body'], 'name="photo"')
                        && str_contains($searchPage['body'], $voyageurImageFilename)
                        && !str_contains($searchPage['body'], '<h2>Ajouter un voyageur</h2>'),
                        'La recherche n’affiche pas uniquement la fiche modifiable du voyageur.'
                    );

                    $voyageurData['action'] = 'modifier_voyageur';
                    $voyageurData['nom_prenom'] = $voyageurUpdatedName;
                    $voyageurData['adresse'] = 'Adresse modifiée du voyageur';
                    $updateData = $voyageurData;
                    $updateData['id'] = $voyageurId;
                    diagnostic_post($applicationUrl . 'personne/voyageurs.php', $updateData, 'ont été modifiées.');
                    diagnostic_assert(
                        diagnostic_database_value($pdo, 'SELECT nom_prenom FROM voyageur WHERE id = :id', [':id' => $voyageurId]) === $voyageurUpdatedName
                        && diagnostic_database_value($pdo, 'SELECT adresse FROM voyageur WHERE id = :id', [':id' => $voyageurId]) === 'Adresse modifiée du voyageur'
                        && diagnostic_database_value($pdo, 'SELECT photo FROM voyageur WHERE id = :id', [':id' => $voyageurId]) === $voyageurImageFilename,
                        'La modification du voyageur n’a pas été enregistrée ou a remplacé sa photo.'
                    );
                    diagnostic_post($applicationUrl . 'personne/voyageur_supprimer.php', ['id' => $voyageurId], 'a été supprimé.');
                    diagnostic_assert(
                        (int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM voyageur WHERE id = :id', [':id' => $voyageurId]) === 0,
                        'Le voyageur existe encore après suppression.'
                    );
                    return 'Création avec photo, recherche, modification et suppression confirmées.';
                }, $results);

                $personId = null;
                diagnostic_run('Cycle CRUD étudiant', static function () use ($pdo, $applicationUrl, $studentNumber, $studentName, $studentUpdatedName, $nationaliteCode, $filiereCode, $transportName, $sportName, $bulletinModuleCode, $bulletinModuleName, $PAYS, $CIVILITES, &$personId, &$results): string {
                    $studentData = [
                        'numero' => $studentNumber, 'type_foyer' => 'Famille', 'civilite' => $CIVILITES[0],
                        'nom_prenom' => $studentName, 'adresse' => 'Adresse de test',
                        'code_postal' => '12345', 'localite' => 'Ville de test',
                        'pays' => $PAYS[0], 'nationalite' => $nationaliteCode,
                        'filiere' => $filiereCode, 'transport' => [$transportName], 'description' => 'Description étudiant test', 'sport' => $sportName,
                    ];
                    diagnostic_post($applicationUrl . 'personne/enregistrer.php', $studentData, 'Personne enregistrée : ' . $studentName);
                    $personId = diagnostic_database_value($pdo, 'SELECT id FROM personne WHERE numero = :numero AND nom_prenom = :nom', [':numero' => $studentNumber, ':nom' => $studentName]);
                    diagnostic_assert($personId !== false, 'Étudiant introuvable après création.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT type_foyer FROM personne WHERE id = :id', [':id' => $personId]) === 'Famille', 'Le type de foyer n’a pas été enregistré.');
                    $searchPage = diagnostic_request($applicationUrl . 'personne/formulaire.php?id=' . rawurlencode((string)$personId));
                    diagnostic_assert(str_contains($searchPage['body'], $studentName) && str_contains($searchPage['body'], 'value="Famille" checked'), 'La recherche ne retourne pas les informations de l’étudiant.');
                    $listPage = diagnostic_request($applicationUrl . 'personne/liste.php?id=' . rawurlencode((string)$personId));
                    diagnostic_assert(str_contains($listPage['body'], $studentName) && str_contains($listPage['body'], 'Famille'), 'La liste ne retourne pas l’étudiant.');

                    diagnostic_run('Cycle CRUD Bulletin de Notes', static function () use ($pdo, $applicationUrl, $studentNumber, $bulletinModuleCode, $bulletinModuleName, $filiereCode): string {
                        $notFound = diagnostic_request($applicationUrl . 'notes/bulletin.php', 'POST', [
                            'action' => 'rechercher',
                            'numero' => (string)random_int(100000000, 999999999),
                        ]);
                        diagnostic_assert(str_contains($notFound['body'], 'Etudiant Inéxistant'), 'La recherche d’un étudiant absent n’affiche pas le message attendu.');

                        try {
                            $stmt = $pdo->prepare(
                                'INSERT INTO module (code, designation, coefficient, volume_horaire, filiere_code)
                                 VALUES (:code, :designation, :coefficient, :volume_horaire, :filiere_code)'
                            );
                            $stmt->execute([
                                ':code' => $bulletinModuleCode,
                                ':designation' => $bulletinModuleName,
                                ':coefficient' => '3.50',
                                ':volume_horaire' => 12,
                                ':filiere_code' => $filiereCode,
                            ]);

                        $form = diagnostic_request($applicationUrl . 'notes/bulletin.php');
                        diagnostic_assert(str_contains($form['body'], "confirm('Supprimer cette note ?')"), 'La suppression ne demande pas de confirmation.');

                        $selectionData = ['action' => 'rechercher', 'numero' => $studentNumber, 'module' => $bulletinModuleCode];
                        $selection = diagnostic_request($applicationUrl . 'notes/bulletin.php', 'POST', $selectionData);
                        diagnostic_assert(
                            str_contains($selection['body'], 'value="' . $bulletinModuleCode . '"')
                            && str_contains($selection['body'], 'name="coefficient" id="coefficient" step="0.01" value="3.50"')
                            && str_contains($selection['body'], 'name="note" id="note" step="0.01" value=""'),
                            'Un module sans note doit afficher son code et coefficient, en laissant la note vide.'
                        );

                        $missingModification = diagnostic_request(
                            $applicationUrl . 'notes/bulletin.php',
                            'POST',
                            $selectionData + ['modifier' => '1', 'note' => '']
                        );
                        diagnostic_assert(
                            str_contains($missingModification['body'], "rien à modifier"),
                            'La modification d’une note inexistante doit signaler qu’il n’y a rien à modifier.'
                        );

                        $saveData = $selectionData + ['enregistrer' => '1', 'note' => '15.25'];
                        diagnostic_post($applicationUrl . 'notes/bulletin.php', $saveData, 'Note enregistrée avec succès.');
                        $savedNote = diagnostic_database_value(
                            $pdo,
                            'SELECT `Note` FROM Notes WHERE Num_Etudiant = :numero AND Nom_module = :nom_module',
                            [':numero' => $studentNumber, ':nom_module' => $bulletinModuleName]
                        );
                        diagnostic_assert($savedNote === '15.25', 'La note n’a pas été insérée dans Notes.');

                        $duplicate = diagnostic_request($applicationUrl . 'notes/bulletin.php', 'POST', $saveData);
                        diagnostic_assert(
                            str_contains($duplicate['body'], 'Une note existe déjà pour cet étudiant et ce module.'),
                            'Une seconde insertion doit signaler le doublon.'
                        );

                        $modifyData = $selectionData + ['modifier' => '1', 'note' => '17.50'];
                        diagnostic_post($applicationUrl . 'notes/bulletin.php', $modifyData, 'modifiées avec succès.');
                        diagnostic_assert(
                            diagnostic_database_value(
                                $pdo,
                                'SELECT `Note` FROM Notes WHERE Num_Etudiant = :numero AND Nom_module = :nom_module',
                                [':numero' => $studentNumber, ':nom_module' => $bulletinModuleName]
                            ) === '17.50',
                            'La modification n’a pas été persistée.'
                        );

                        $deleteData = $selectionData + ['supprimer' => '1'];
                        $deleted = diagnostic_request($applicationUrl . 'notes/bulletin.php', 'POST', $deleteData);
                        diagnostic_assert(
                            str_contains($deleted['body'], 'Note supprimée avec succès.')
                            && str_contains($deleted['body'], 'name="code_module" id="code-module" value="' . $bulletinModuleCode . '"')
                            && str_contains($deleted['body'], 'name="coefficient" id="coefficient" step="0.01" value="3.50"')
                            && str_contains($deleted['body'], 'name="note" id="note" step="0.01" value="17.50"'),
                            'La suppression doit confirmer et réafficher les valeurs de la note supprimée.'
                        );
                        diagnostic_assert(
                            (int)diagnostic_database_value(
                                $pdo,
                                'SELECT COUNT(*) FROM Notes WHERE Num_Etudiant = :numero AND Nom_module = :nom_module',
                                [':numero' => $studentNumber, ':nom_module' => $bulletinModuleName]
                            ) === 0,
                            'La ligne de note existe encore après suppression.'
                        );

                        $deleteMissing = diagnostic_request($applicationUrl . 'notes/bulletin.php', 'POST', $deleteData);
                        diagnostic_assert(
                            str_contains($deleteMissing['body'], 'Note Inéxistante pour ce module'),
                            'La suppression d’une note inexistante doit afficher le message attendu.'
                        );
                        return 'Recherche, sélection, création, modification, doublon et suppressions vérifiés.';
                        } finally {
                            $stmt = $pdo->prepare(
                                'DELETE FROM Notes WHERE Num_Etudiant = :numero AND Nom_module = :nom_module'
                            );
                            $stmt->execute([':numero' => $studentNumber, ':nom_module' => $bulletinModuleName]);
                            $stmt = $pdo->prepare('DELETE FROM module WHERE code = :code');
                            $stmt->execute([':code' => $bulletinModuleCode]);
                        }
                    }, $results);

                    $studentData['id'] = (string)$personId;
                    $studentData['nom_prenom'] = $studentUpdatedName;
                    $studentData['type_foyer'] = 'Individuelle';
                    diagnostic_post($applicationUrl . 'personne/enregistrer.php', $studentData, 'ont été modifiées.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT nom_prenom FROM personne WHERE id = :id', [':id' => $personId]) === $studentUpdatedName, 'La modification de l’étudiant n’a pas été enregistrée.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT type_foyer FROM personne WHERE id = :id', [':id' => $personId]) === 'Individuelle', 'La modification du type de foyer n’a pas été enregistrée.');
                    diagnostic_assert(diagnostic_database_value($pdo, 'SELECT description FROM personne WHERE id = :id', [':id' => $personId]) === 'Description étudiant test', 'La description de l’étudiant n’a pas été enregistrée.');
                    diagnostic_post($applicationUrl . 'personne/supprimer.php', ['id' => (string)$personId], 'a été supprimée.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM personne WHERE id = :id', [':id' => $personId]) === 0, 'L’étudiant existe encore après suppression.');
                    diagnostic_post($applicationUrl . 'transport/supprimer.php', ['libelle' => $transportName], 'a été supprimé.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM moyen_transport WHERE libelle = :libelle', [':libelle' => $transportName]) === 0, 'Le moyen de transport existe encore après suppression.');
                    return 'Création, recherche, liste, modification et suppression confirmées.';
                }, $results);

                diagnostic_run('Affichage PHP et validation image', static function () use ($applicationUrl, $studentNumber, $studentName, $nationaliteCode, $filiereCode, $transportName, $PAYS, $CIVILITES, &$imageFilename): string {
                    $display = diagnostic_request($applicationUrl . 'personne/afficher_php.php', 'POST', [
                        'numero' => $studentNumber, 'civilite' => $CIVILITES[0],
                        'nom_prenom' => $studentName, 'adresse' => 'Adresse de test',
                        'code_postal' => '12345', 'localite' => 'Ville de test',
                        'pays' => $PAYS[0], 'nationalite' => $nationaliteCode,
                        'filiere' => $filiereCode, 'transport' => [$transportName],
                    ]);
                    diagnostic_assert($display['status'] === 200 && str_contains($display['body'], $studentName), 'L’affichage PHP ne restitue pas les données envoyées.');
                    $image = diagnostic_request($applicationUrl . 'personne/upload_image.php', 'POST', []);
                    diagnostic_assert($image['status'] === 200 && str_contains($image['body'], 'Veuillez choisir une image.'), 'Le contrôle du formulaire d’image ne répond pas comme prévu.');
                    $boundary = '----tp01diagnostic' . bin2hex(random_bytes(8));
                    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC', true);
                    diagnostic_assert($png !== false, 'Impossible de préparer le fichier image de test.');
                    $multipart = "--$boundary\r\n"
                        . "Content-Disposition: form-data; name=\"photo\"; filename=\"diagnostic.png\"\r\n"
                        . "Content-Type: image/png\r\n\r\n"
                        . $png . "\r\n--$boundary--\r\n";
                    $upload = diagnostic_request(
                        $applicationUrl . 'personne/upload_image.php',
                        'POST',
                        $multipart,
                        'multipart/form-data; boundary=' . $boundary
                    );
                    diagnostic_assert($upload['status'] === 200 && str_contains($upload['body'], 'Image envoyée.'), 'Le transfert de l’image de test a échoué.');
                    diagnostic_assert(
                        preg_match('#\.\./uploads/([a-f0-9]{16}\.(?:png|jpg|gif))#i', $upload['body'], $match) === 1,
                        'Le fichier image créé ne porte pas le nom attendu.'
                    );
                    $imageFilename = $match[1];
                    return 'Affichage PHP, contrôle et transfert d’image confirmés.';
                }, $results);

                diagnostic_run('Suppression de la filière de test', static function () use ($pdo, $applicationUrl, $filiereCode): string {
                    diagnostic_post($applicationUrl . 'filiere/filiere.php', [
                        'action' => 'supprimer', 'code_original' => $filiereCode,
                        'code' => $filiereCode, 'libelle' => $filiereCode,
                    ], 'Filière supprimée.');
                    diagnostic_assert((int)diagnostic_database_value($pdo, 'SELECT COUNT(*) FROM filiere WHERE code = :code', [':code' => $filiereCode]) === 0, 'La filière existe encore après suppression.');
                    return 'Suppression confirmée.';
                }, $results);
            } finally {
                try {
                    $storedVoyageurPhoto = diagnostic_database_value(
                        $pdo,
                        'SELECT photo FROM voyageur WHERE numero = :value AND nom_prenom IN (:created, :modified)',
                        [':value' => $voyageurNumber, ':created' => $voyageurName, ':modified' => $voyageurUpdatedName]
                    );
                    if (is_string($storedVoyageurPhoto) && $storedVoyageurPhoto !== '') {
                        $voyageurImageFilename = $storedVoyageurPhoto;
                    }
                } catch (Throwable $e) {
                    $results[] = ['label' => 'Nettoyage du fichier image voyageur', 'ok' => false, 'detail' => $e->getMessage()];
                }
                $cleanups = [
                    ['DELETE FROM Notes WHERE Num_Etudiant = :numero AND Nom_module = :nom_module', 'SELECT COUNT(*) FROM Notes WHERE Num_Etudiant = :numero AND Nom_module = :nom_module', [':numero' => $studentNumber, ':nom_module' => $bulletinModuleName]],
                    ['DELETE FROM module WHERE code = :code', 'SELECT COUNT(*) FROM module WHERE code = :code', [':code' => $bulletinModuleCode]],
                    ['DELETE FROM personne WHERE numero = :value AND nom_prenom IN (:created, :modified)', 'SELECT COUNT(*) FROM personne WHERE numero = :value AND nom_prenom IN (:created, :modified)', [':value' => $studentNumber, ':created' => $studentName, ':modified' => $studentUpdatedName]],
                    ['DELETE FROM module WHERE code = :value AND designation IN (:created, :modified)', 'SELECT COUNT(*) FROM module WHERE code = :value AND designation IN (:created, :modified)', [':value' => $moduleCode, ':created' => $moduleCreatedDesignation, ':modified' => $moduleUpdatedDesignation]],
                    ['DELETE FROM enseignant WHERE numero = :value AND nom_prenom IN (:created, :modified)', 'SELECT COUNT(*) FROM enseignant WHERE numero = :value AND nom_prenom IN (:created, :modified)', [':value' => $teacherNumber, ':created' => $teacherName, ':modified' => $teacherUpdatedName]],
                    ['DELETE FROM voyageur WHERE numero = :value AND nom_prenom IN (:created, :modified)', 'SELECT COUNT(*) FROM voyageur WHERE numero = :value AND nom_prenom IN (:created, :modified)', [':value' => $voyageurNumber, ':created' => $voyageurName, ':modified' => $voyageurUpdatedName]],
                    ['DELETE FROM filiere WHERE code = :value AND libelle IN (:created, :modified)', 'SELECT COUNT(*) FROM filiere WHERE code = :value AND libelle IN (:created, :modified)', [':value' => $filiereCode, ':created' => $filiereCode, ':modified' => $filiereLibelle]],
                    ['DELETE FROM nationalite WHERE code = :value AND libelle = :marker', 'SELECT COUNT(*) FROM nationalite WHERE code = :value AND libelle = :marker', [':value' => $nationaliteCode, ':marker' => $nationaliteLibelle]],
                    ['DELETE FROM sport WHERE libelle = :value', 'SELECT COUNT(*) FROM sport WHERE libelle = :value', [':value' => $sportName]],
                    ['DELETE FROM moyen_transport WHERE libelle = :value', 'SELECT COUNT(*) FROM moyen_transport WHERE libelle = :value', [':value' => $transportName]],
                ];
                $cleanupSucceeded = true;
                foreach ($cleanups as [$deleteSql, $verifySql, $params]) {
                    try {
                        $statement = $pdo->prepare($deleteSql);
                        $statement->execute($params);
                        if ((int)diagnostic_database_value($pdo, $verifySql, $params) !== 0) {
                            throw new RuntimeException('Des données de test sont encore présentes.');
                        }
                    } catch (Throwable $e) {
                        $cleanupSucceeded = false;
                        $results[] = ['label' => 'Nettoyage des données temporaires', 'ok' => false, 'detail' => $e->getMessage()];
                    }
                }
                if ($imageFilename !== null) {
                    try {
                        $imagePath = __DIR__ . '/uploads/' . $imageFilename;
                        if (is_file($imagePath) && !unlink($imagePath)) {
                            throw new RuntimeException('Impossible de supprimer le fichier image de test.');
                        }
                    } catch (Throwable $e) {
                        $cleanupSucceeded = false;
                        $results[] = ['label' => 'Nettoyage du fichier image', 'ok' => false, 'detail' => $e->getMessage()];
                    }
                }
                if ($voyageurImageFilename !== null) {
                    try {
                        $imagePath = __DIR__ . '/uploads/' . $voyageurImageFilename;
                        if (is_file($imagePath) && !unlink($imagePath)) {
                            throw new RuntimeException('Impossible de supprimer la photo de test du voyageur.');
                        }
                    } catch (Throwable $e) {
                        $cleanupSucceeded = false;
                        $results[] = ['label' => 'Nettoyage du fichier image voyageur', 'ok' => false, 'detail' => $e->getMessage()];
                    }
                }
                if ($cleanupSucceeded) {
                    $detail = $imageFilename === null && $voyageurImageFilename === null
                        ? 'Enregistrements temporaires supprimés et vérifiés.'
                        : 'Enregistrements et fichiers image supprimés et vérifiés.';
                    $results[] = ['label' => 'Nettoyage des données temporaires', 'ok' => true, 'detail' => $detail];
                }
            }
        }
    }
}

if (!$localRequest) {
    http_response_code(403);
}
$base = '';
require __DIR__ . '/includes/header.php';
?>
<p class="diagnostic-intro">Vérifie l’accès aux pages et exécute des parcours réels de création, recherche, modification et suppression. Les données générées sont temporaires et supprimées à la fin du test.</p>
<?php if (!$localRequest): ?>
    <p class="diagnostic-notice diagnostic-fail">Cette page de diagnostic est accessible uniquement depuis la machine qui héberge le site.</p>
<?php elseif (!$runRequested): ?>
    <p class="diagnostic-notice">Les tests écrivent temporairement dans la base de données. Ils utilisent des valeurs uniques et nettoient leurs enregistrements à la fin.</p>
    <form method="post" action="tests.php">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['diagnostic_csrf']) ?>">
        <button type="submit" class="diagnostic-run">Lancer tous les tests</button>
    </form>
<?php endif; ?>

<?php if ($results): ?>
    <?php
    $successCount = count(array_filter($results, static fn(array $result): bool => $result['ok']));
    $failureCount = count($results) - $successCount;
    ?>
    <p class="diagnostic-summary <?= $failureCount === 0 ? 'diagnostic-pass' : 'diagnostic-fail' ?>">
        <?= $successCount ?> test(s) réussi(s), <?= $failureCount ?> échec(s).
    </p>
    <div class="table-scroll">
        <table class="liste diagnostic-results">
            <thead><tr><th>Vérification</th><th>Résultat</th><th>Détail</th></tr></thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr class="<?= $result['ok'] ? 'diagnostic-row-pass' : 'diagnostic-row-fail' ?>">
                        <td><?= h($result['label']) ?></td>
                        <td><?= $result['ok'] ? 'OK' : 'ÉCHEC' ?></td>
                        <td><?= h($result['detail']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="get" action="tests.php"><button type="submit">Effacer les résultats</button></form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
