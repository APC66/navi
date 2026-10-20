<?php
/**
 * Plugin Name: APC Popup
 * Description: Popups configurables (titre, contenu riche avec images, bouton d'action) avec ciblage des pages, déclencheurs, fréquence d'affichage, planification et remise à zéro des compteurs. Aucune dépendance.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Agence Point Com
 * Text Domain: apc-popup
 */

if (! defined('ABSPATH')) {
    exit;
}

define('APC_POPUP_VERSION', '1.0.0');
define('APC_POPUP_FILE', __FILE__);
define('APC_POPUP_DIR', plugin_dir_path(__FILE__));
define('APC_POPUP_URL', plugin_dir_url(__FILE__));

require_once APC_POPUP_DIR.'includes/class-apc-popup-settings.php';
require_once APC_POPUP_DIR.'includes/class-apc-popup-cache.php';
require_once APC_POPUP_DIR.'includes/class-apc-popup-targeting.php';
require_once APC_POPUP_DIR.'includes/class-apc-popup-post-type.php';
require_once APC_POPUP_DIR.'includes/class-apc-popup-admin.php';
require_once APC_POPUP_DIR.'includes/class-apc-popup-frontend.php';

register_activation_hook(__FILE__, ['APC_Popup_Cache', 'activate']);
register_deactivation_hook(__FILE__, ['APC_Popup_Cache', 'deactivate']);

add_action('plugins_loaded', function () {
    APC_Popup_Cache::hooks();
    (new APC_Popup_Post_Type)->hooks();
    (new APC_Popup_Frontend)->hooks();

    if (is_admin()) {
        (new APC_Popup_Admin)->hooks();
    }
});
