<?php
/**
 * config/db.example.php
 * ✅ CE FICHIER est versionné sur GitHub (pas de vrais identifiants ici)
 *
 * Sur le serveur Infomaniak :
 *   cp config/db.example.php config/db.php
 *   puis remplis les vraies valeurs dans config/db.php
 */

$db_host    = 'localhost';
$db_name    = 'NOM_DE_TA_BASE_INFOMANIAK';   // ex: mabdd123
$db_user    = 'UTILISATEUR_MYSQL_INFOMANIAK'; // ex: user_mabdd123
$db_pass    = 'MOT_DE_PASSE_MYSQL';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB connection failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Connexion BDD impossible']);
    exit;
}
