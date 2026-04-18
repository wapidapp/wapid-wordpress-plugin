<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Unauthorized', 'wapid-automation-for-woocommerce'));
}
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/partials/shell.php';

$client = whatsapp_automation_get_api_client();
$stats_response = $client->is_authenticated() ? $client->get_dashboard_stats() : new WP_Error('wa_no_auth', '');
$stats = !is_wp_error($stats_response) ? $client->extract_payload($stats_response) : array();
$selected_instance = get_option('whatsapp_automation_instance_id', '');
$instance_status = null;
if (!empty($selected_instance) && $client->is_authenticated()) {
    $status_response = $client->get_instance_status($selected_instance);
    if (!is_wp_error($status_response)) {
        $instance_status = $client->extract_payload($status_response);
    }
}
whatsapp_automation_admin_shell_start(
    __('Wapid Automation Dashboard', 'wapid-automation-for-woocommerce'),
    __('Dashboard for monitoring and managing Wapid Automation instances.', 'wapid-automation-for-woocommerce')
);
?>

    <div class="wa-grid wa-grid-4">
        <div class="wa-stat">
            <span><?php esc_html_e('Total Instances', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html((string) ($stats['instances']['total'] ?? '0')); ?></strong>
        </div>
        <div class="wa-stat">
            <span><?php esc_html_e('Connected Instances', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html((string) ($stats['instances']['active'] ?? '0')); ?></strong>
        </div>
        <div class="wa-stat">
            <span><?php esc_html_e('Messages Today', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html((string) ($stats['messages']['today'] ?? '0')); ?></strong>
        </div>
        <div class="wa-stat">
            <span><?php esc_html_e('Delivery Rate', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html((string) ($stats['messages']['deliveryRate'] ?? '0')); ?>%</strong>
        </div>
    </div>

    <div class="wa-grid wa-grid-2">
        <section class="wa-card">
            <h2><?php esc_html_e('Selected Instance', 'wapid-automation-for-woocommerce'); ?></h2>
            <?php if ($instance_status) : ?>
                <p><strong><?php esc_html_e('ID:', 'wapid-automation-for-woocommerce'); ?></strong> <?php echo esc_html($selected_instance); ?></p>
                <p><strong><?php esc_html_e('Phone:', 'wapid-automation-for-woocommerce'); ?></strong> <?php echo esc_html($instance_status['phone_number'] ?? '-'); ?></p>
                <p><strong><?php esc_html_e('Status:', 'wapid-automation-for-woocommerce'); ?></strong> <?php echo esc_html($instance_status['status'] ?? 'unknown'); ?></p>
            <?php else : ?>
                <p><?php esc_html_e('No active instance status available. Please configure in Instances tab.', 'wapid-automation-for-woocommerce'); ?></p>
            <?php endif; ?>
        </section>

        <section class="wa-card">
            <h2><?php esc_html_e('Automation Scope', 'wapid-automation-for-woocommerce'); ?></h2>
            <ul>
                <li><?php esc_html_e('Login/Register directly to your backend', 'wapid-automation-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Select/start/stop messaging instances', 'wapid-automation-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Map templates for order events', 'wapid-automation-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Auto-send on Woo new order and status changes', 'wapid-automation-for-woocommerce'); ?></li>
            </ul>
        </section>
    </div>
<?php whatsapp_automation_admin_shell_end(); ?>
