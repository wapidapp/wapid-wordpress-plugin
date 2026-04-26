<?php
/**
 * Plugin Name: Wapid Automation for WooCommerce
 * Plugin URI: https://wapid.net/plugins/wapid-automation-for-woocommerce
 * Description: Connect WooCommerce with your Wapid backend for automated customer notifications and instance control.
 * Version: 2.0.0
 * Author: Wapid
 * Author URI: https://wapid.net
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wapid-automation-for-woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WHATSAPP_AUTOMATION_VERSION', '2.0.0');
define('WHATSAPP_AUTOMATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATSAPP_AUTOMATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHATSAPP_AUTOMATION_PLUGIN_FILE', __FILE__);
define('WHATSAPP_AUTOMATION_DEFAULT_API_BASE', 'https://api.wapid.net/api/v1');

require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/Logger.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/MessageHistory.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/APIClient.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/AdminMenu.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/WooCommerceIntegration.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/OTPIntegration.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/Activator.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'includes/Deactivator.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'modules/email-marketing/models/FeatureGate.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'modules/email-marketing/services/EmailMarketingService.php';
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'modules/email-marketing/controllers/EmailMarketingController.php';

function whatsapp_automation_activate() {
    \WhatsAppAutomation\Activator::activate();
}

function whatsapp_automation_deactivate() {
    \WhatsAppAutomation\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'whatsapp_automation_activate');
register_deactivation_hook(__FILE__, 'whatsapp_automation_deactivate');

add_action('plugins_loaded', function () {
    load_plugin_textdomain('wapid-automation-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    \WhatsAppAutomation\Activator::ensure_schema();

    if (is_admin()) {
        new \WhatsAppAutomation\AdminMenu();
    }

    if (class_exists('WooCommerce')) {
        new \WhatsAppAutomation\WooCommerceIntegration();
        new \WhatsAppAutomation\OTPIntegration();
    }
});

function whatsapp_automation_get_api_client() {
    return new \WhatsAppAutomation\APIClient();
}

function whatsapp_automation_log($message, $level = 'info') {
    \WhatsAppAutomation\Logger::log($message, $level);
}

function whatsapp_automation_get_setting($key, $default = null) {
    $settings = array(
        'api_base_url' => get_option('whatsapp_automation_api_base_url', WHATSAPP_AUTOMATION_DEFAULT_API_BASE),
        'instance_id' => get_option('whatsapp_automation_instance_id', ''),
        'enabled_events' => get_option('whatsapp_automation_enabled_events', array()),
        'template_mappings' => get_option('whatsapp_automation_template_mappings', array()),
    );

    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}
