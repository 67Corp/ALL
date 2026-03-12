<?php
/**
 * config/config.example.php
 * ✅ CE FICHIER est versionné sur GitHub (pas de vraie clé ici)
 * 
 * Sur le serveur Infomaniak :
 *   cp config/config.example.php config/config.php
 *   puis remplis les vraies valeurs dans config/config.php
 */

define('GEMINI_API_KEY', 'VOTRE_CLE_GOOGLE_AI_STUDIO_ICI');

define('APP_ENV', 'production'); // 'development' | 'production'
define('APP_NAME', 'StudyAI');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
