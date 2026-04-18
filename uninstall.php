<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Data retention policy:
 * By default, keep user data on uninstall so reconnecting the plugin
 * restores sender settings, event mappings, and message history.
 *
 * To fully purge plugin data on uninstall, set in wp-config.php:
 * define('WHATSAPP_AUTOMATION_PURGE_ON_UNINSTALL', true);
 */
$purge_on_uninstall = defined('WHATSAPP_AUTOMATION_PURGE_ON_UNINSTALL') && WHATSAPP_AUTOMATION_PURGE_ON_UNINSTALL;

if ($purge_on_uninstall) {
    $option_keys = array(
        'whatsapp_automation_api_base_url',
        'whatsapp_automation_instance_id',
        'whatsapp_automation_enabled_events',
        'whatsapp_automation_template_mappings',
        'whatsapp_automation_selected_events',
        'whatsapp_automation_fallback_messages',
        'whatsapp_automation_otp_features',
        'whatsapp_automation_access_token',
        'whatsapp_automation_refresh_token',
        'whatsapp_automation_token_expires_at',
        'whatsapp_automation_api_key',
        'whatsapp_automation_connect_url',
        'whatsapp_automation_schema_version',
    );

    foreach ($option_keys as $key) {
        delete_option($key);
    }

    // Remove plugin local log file and directory when possible.
    $upload = wp_upload_dir();
    $log_dir = trailingslashit($upload['basedir']) . 'wapid-automation-for-woocommerce-logs';
    $log_file = trailingslashit($log_dir) . 'wapid-automation-for-woocommerce.log';

    if (file_exists($log_file)) {
        wp_delete_file($log_file);
    }

    if (is_dir($log_dir)) {
        @rmdir($log_dir);
    }

    // Purge local message history table.
    global $wpdb;
    $table = $wpdb->prefix . 'wapid_message_history';
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}
