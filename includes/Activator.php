<?php

namespace WhatsAppAutomation;

class Activator {
    public static function activate() {
        self::create_tables();
        update_option('whatsapp_automation_schema_version', '1');

        $default_selected = array('new_order');
        $default_enabled = array('new_order' => 1);
        if (function_exists('wc_get_order_statuses')) {
            foreach (wc_get_order_statuses() as $status_key => $status_label) {
                $slug = str_replace('wc-', '', (string) $status_key);
                if (in_array($slug, array('processing', 'completed', 'cancelled', 'refunded'), true)) {
                    $event_key = 'order_status_' . sanitize_key($slug);
                    $default_selected[] = $event_key;
                    $default_enabled[$event_key] = 1;
                }
            }
        }

        add_option('whatsapp_automation_api_base_url', WHATSAPP_AUTOMATION_DEFAULT_API_BASE);
        add_option('whatsapp_automation_instance_id', '');
        add_option('whatsapp_automation_selected_events', $default_selected);
        add_option('whatsapp_automation_enabled_events', $default_enabled);
        add_option('whatsapp_automation_template_mappings', array());
        add_option('whatsapp_automation_otp_features', array(
            'login' => 0,
            'register' => 0,
            'checkout' => 0,
        ));
        add_option(
            'whatsapp_automation_otp_message',
            'Your verification code is {{otp}}. It expires in {{minutes}} minutes.'
        );
        add_option('whatsapp_automation_fallback_messages', array(
            'new_order' => 'Thank you {{customer_name}}, your order #{{order_id}} is received.',
            'order_status_processing' => 'Hi {{customer_name}}, your order #{{order_id}} is now processing.',
            'order_status_completed' => 'Great news {{customer_name}}. Order #{{order_id}} is completed.',
            'order_status_cancelled' => 'Order #{{order_id}} has been cancelled. Contact support if needed.',
            'order_status_refunded' => 'Order #{{order_id}} has been refunded.',
            'otp_login' => 'Your login OTP is {{otp}}. Valid for {{minutes}} minutes.',
            'otp_register' => 'Your registration OTP is {{otp}}. Valid for {{minutes}} minutes.',
            'otp_checkout' => 'Your checkout OTP is {{otp}}. Valid for {{minutes}} minutes.',
        ));

        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_whatsapp_automation');
        }
    }

    public static function ensure_schema() {
        global $wpdb;
        $table = $wpdb->prefix . 'wapid_message_history';
        $existing = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($existing !== $table) {
            self::create_tables();
        }
    }

    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = $wpdb->prefix . 'wapid_message_history';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(50) NOT NULL DEFAULT 'plugin',
            event_type VARCHAR(100) NULL,
            instance_id VARCHAR(100) NULL,
            recipient_phone VARCHAR(40) NULL,
            message_text LONGTEXT NULL,
            order_id BIGINT NULL,
            backend_message_id VARCHAR(100) NULL,
            local_status VARCHAR(30) NOT NULL DEFAULT 'queued',
            backend_status VARCHAR(30) NULL,
            response_payload LONGTEXT NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_created_at (created_at),
            KEY idx_recipient_phone (recipient_phone),
            KEY idx_backend_message_id (backend_message_id)
        ) {$charset_collate};";

        dbDelta($sql);
    }
}
