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
$instances_response = $client->is_authenticated() ? $client->get_instances() : new WP_Error('wa_no_auth', '');
$instances = !is_wp_error($instances_response) ? $client->extract_items($instances_response) : array();

$pick_numeric = static function($candidates) {
    foreach ($candidates as $candidate) {
        if ($candidate === null || $candidate === '') {
            continue;
        }
        if (is_numeric($candidate)) {
            return (float) $candidate;
        }
    }
    return null;
};

$resolve_connected_instances = static function($items) {
    if (!is_array($items)) {
        return 0;
    }

    $count = 0;
    foreach ($items as $instance) {
        if (!is_array($instance)) {
            continue;
        }

        $status = strtolower((string) ($instance['status'] ?? $instance['connection_status'] ?? ''));
        $is_connected = in_array($status, array('connected', 'active', 'online', 'running'), true);
        if (!$is_connected && !empty($instance['is_connected'])) {
            $is_connected = true;
        }
        if ($is_connected) {
            $count++;
        }
    }
    return $count;
};

$total_instances_value = $pick_numeric(array(
    $stats['instances']['total'] ?? null,
    $stats['totals']['instances'] ?? null,
    $stats['totalInstances'] ?? null,
    $stats['instancesTotal'] ?? null,
    $stats['instance_count'] ?? null,
    $stats['counts']['instances'] ?? null,
));
if ($total_instances_value === null) {
    $total_instances_value = count($instances);
}

$connected_instances_value = $pick_numeric(array(
    $stats['instances']['active'] ?? null,
    $stats['instances']['connected'] ?? null,
    $stats['totals']['connectedInstances'] ?? null,
    $stats['connectedInstances'] ?? null,
    $stats['instancesConnected'] ?? null,
    $stats['instance_connected_count'] ?? null,
    $stats['counts']['connected_instances'] ?? null,
));
if ($connected_instances_value === null) {
    $connected_instances_value = $resolve_connected_instances($instances);
}

$messages_today_value = $pick_numeric(array(
    $stats['messages']['today'] ?? null,
    $stats['messages_today'] ?? null,
    $stats['todayMessages'] ?? null,
    $stats['totals']['messagesToday'] ?? null,
    $stats['counts']['messages_today'] ?? null,
));

$delivery_rate_value = $pick_numeric(array(
    $stats['messages']['deliveryRate'] ?? null,
    $stats['messages']['delivery_rate'] ?? null,
    $stats['deliveryRate'] ?? null,
    $stats['delivery_rate'] ?? null,
    $stats['rates']['delivery'] ?? null,
));

if ($messages_today_value === null || $delivery_rate_value === null) {
    global $wpdb;
    $table = \WhatsAppAutomation\MessageHistory::table_name();
    $today = current_time('Y-m-d');

    if ($messages_today_value === null) {
        $messages_today_value = (float) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", $today)
        );
    }

    if ($delivery_rate_value === null) {
        $status_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) AS total_count,
                    SUM(CASE WHEN backend_status IN ('sent', 'delivered', 'success') THEN 1 ELSE 0 END) AS success_count
                FROM {$table}
                WHERE DATE(created_at) = %s",
                $today
            ),
            ARRAY_A
        );
        $total_today = isset($status_row['total_count']) ? (int) $status_row['total_count'] : 0;
        $success_today = isset($status_row['success_count']) ? (int) $status_row['success_count'] : 0;
        $delivery_rate_value = ($total_today > 0) ? round(($success_today / $total_today) * 100, 2) : 0.0;
    }
}

$total_instances_display = (string) (int) round((float) $total_instances_value);
$connected_instances_display = (string) (int) round((float) $connected_instances_value);
$messages_today_display = (string) (int) round((float) $messages_today_value);
$delivery_rate_display = rtrim(rtrim(number_format((float) $delivery_rate_value, 2, '.', ''), '0'), '.');
$delivery_rate_display = ($delivery_rate_display === '') ? '0' : $delivery_rate_display;
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
            <strong><?php echo esc_html($total_instances_display); ?></strong>
        </div>
        <div class="wa-stat">
            <span><?php esc_html_e('Connected Instances', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html($connected_instances_display); ?></strong>
        </div>
        <div class="wa-stat">
            <span><?php esc_html_e('Messages Today', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html($messages_today_display); ?></strong>
        </div>
        <div class="wa-stat">
            <span><?php esc_html_e('Delivery Rate', 'wapid-automation-for-woocommerce'); ?></span>
            <strong><?php echo esc_html($delivery_rate_display); ?>%</strong>
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
