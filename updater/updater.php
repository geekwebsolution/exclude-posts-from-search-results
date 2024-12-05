<?php

if (!defined('ABSPATH')) exit;

/**
 * License manager module
 */
function epfsr_updater_utility() {
    $prefix = 'GWSEPFSR_';
    $settings = [
        'prefix' => $prefix,
        'get_base' => GWSEPFSR_PLUGIN_BASENAME,
        'get_slug' => GWSEPFSR_PLUGIN_DIR,
        'get_version' => GWSEPFSR_BUILD,
        'get_api' => 'https://download.geekcodelab.com/',
        'license_update_class' => $prefix . 'Update_Checker'
    ];

    return $settings;
}

function epfsr_updater_activate() {

    // Refresh transients
    delete_site_transient('update_plugins');
    delete_transient('epfsr_plugin_updates');
    delete_transient('epfsr_plugin_auto_updates');
}

require_once(GWSEPFSR_PLUGIN_DIR_PATH . 'updater/class-update-checker.php');
