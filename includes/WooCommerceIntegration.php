<?php

namespace WhatsAppAutomation;

class WooCommerceIntegration {
    private $api_client = null;

    public function __construct() {
        $this->api_client = whatsapp_automation_get_api_client();
        add_action('woocommerce_new_order', array($this, 'on_new_order'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'on_status_changed'), 10, 4);
    }

    public function on_new_order($order_id) {
        $order = wc_get_order($order_id);
        $this->send_order_event($order, 'new_order');
    }

    public function on_status_changed($order_id, $old_status, $new_status, $order = null) {
        if (!$order instanceof \WC_Order) {
            $order = wc_get_order($order_id);
        }
        $this->send_order_event($order, 'order_status_' . sanitize_key($new_status));
    }

    private function send_order_event($order, $event_type) {
        if (!$order instanceof \WC_Order) {
            return;
        }

        $selected_events = get_option('whatsapp_automation_selected_events', array());
        if (!in_array($event_type, $selected_events, true)) {
            return;
        }

        $instance_id = whatsapp_automation_get_setting('instance_id');
        if (empty($instance_id)) {
            whatsapp_automation_log('Instance not selected. Skipping ' . $event_type, 'warn');
            return;
        }

        $resolved_instance_id = $this->api_client->resolve_sendable_instance_id($instance_id);
        if (is_wp_error($resolved_instance_id)) {
            whatsapp_automation_log(
                'No sendable instance for ' . $event_type . ' (selected: ' . $instance_id . '): ' . $resolved_instance_id->get_error_message(),
                'error'
            );
            return;
        }
        if ($resolved_instance_id !== $instance_id) {
            update_option('whatsapp_automation_instance_id', $resolved_instance_id);
            whatsapp_automation_log(
                'Auto-switched instance from ' . $instance_id . ' to ' . $resolved_instance_id . ' for ' . $event_type,
                'warn'
            );
            $instance_id = $resolved_instance_id;
        }

        $phone = $order->get_billing_phone();
        if (empty($phone)) {
            whatsapp_automation_log('Order #' . $order->get_id() . ' has no phone number', 'warn');
            return;
        }

        $normalized_phone = $this->normalize_order_phone($order, $phone);
        if ($normalized_phone === '') {
            whatsapp_automation_log('Order #' . $order->get_id() . ' has invalid phone number after normalization', 'warn');
            return;
        }

        $variables = array(
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'order_id' => $order->get_id(),
            'order_total' => wc_format_decimal($order->get_total(), 2),
            'order_status' => wc_get_order_status_name($order->get_status()),
            'shop_name' => get_bloginfo('name'),
            'shop_url' => site_url(),
        );

        $message = $this->resolve_message($event_type, $variables);
        if ($message === '') {
            whatsapp_automation_log('Skipped ' . $event_type . ' for order #' . $order->get_id() . ' because message resolved empty.', 'warn');
            return;
        }

        whatsapp_automation_log(
            'Queueing ' . $event_type . ' for order #' . $order->get_id() . ' to ' . $normalized_phone . ' via instance ' . $instance_id . '.',
            'info'
        );
        $history_id = MessageHistory::insert(array(
            'source' => 'woocommerce',
            'event_type' => $event_type,
            'instance_id' => $instance_id,
            'recipient_phone' => $normalized_phone,
            'message_text' => $message,
            'order_id' => (int) $order->get_id(),
            'local_status' => 'queued',
        ));

        $response = $this->api_client->send_message($instance_id, $normalized_phone, $message);
        if (is_wp_error($response)) {
            if ($history_id > 0) {
                MessageHistory::update($history_id, array(
                    'local_status' => 'failed',
                    'error_message' => $response->get_error_message(),
                ));
            }
            whatsapp_automation_log('Failed to send ' . $event_type . ': ' . $response->get_error_message(), 'error');
            return;
        }

        $message_id = $response['data']['id'] ?? '';
        $backend_status = $response['data']['status'] ?? '';
        $local_status = ($backend_status === 'failed') ? 'failed' : (($backend_status === 'sent') ? 'sent' : 'queued');

        if ($history_id > 0) {
            MessageHistory::update($history_id, array(
                'backend_message_id' => $message_id,
                'backend_status' => $backend_status,
                'local_status' => $local_status,
                'response_payload' => $response,
            ));
        }

        whatsapp_automation_log(
            'Sent ' . $event_type . ' for order #' . $order->get_id() . ' with backend status ' . (($backend_status !== '') ? $backend_status : 'unknown') . '.',
            'info'
        );

        $order->update_meta_data('_whatsapp_message_id_' . $event_type, $message_id);
        $order->save();
    }

    private function resolve_message($event_type, $variables) {
        $mappings = get_option('whatsapp_automation_template_mappings', array());
        if (!empty($mappings[$event_type])) {
            $templates_response = $this->api_client->get_templates();
            if (!is_wp_error($templates_response)) {
                $templates = $this->api_client->extract_items($templates_response);
                foreach ($templates as $template) {
                    if (($template['id'] ?? '') === $mappings[$event_type]) {
                        $content = (string) ($template['content'] ?? '');
                        return $this->render_template($content, $variables);
                    }
                }
            }
        }

        $fallbacks = get_option('whatsapp_automation_fallback_messages', array());
        $fallback = $fallbacks[$event_type] ?? '';
        if ($fallback === '') {
            $legacy = array(
                'order_status_processing' => 'order_processing',
                'order_status_completed' => 'order_completed',
                'order_status_cancelled' => 'order_cancelled',
                'order_status_refunded' => 'order_refunded',
            );
            if (isset($legacy[$event_type]) && !empty($fallbacks[$legacy[$event_type]])) {
                $fallback = $fallbacks[$legacy[$event_type]];
            }
        }
        return $this->render_template($fallback, $variables);
    }

    private function render_template($template, $variables) {
        $message = (string) $template;
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', (string) $value, $message);
        }
        return trim($message);
    }

    /**
     * Convert checkout phone to international digits format for WhatsApp JID usage.
     * Example: PK + 03301231231 => 923301231231
     */
    private function normalize_order_phone($order, $raw_phone) {
        $raw_phone = trim((string) $raw_phone);
        if ($raw_phone === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw_phone);
        if ($digits === '') {
            return '';
        }

        // Already international with plus (e.g. +923001234567).
        if (strpos($raw_phone, '+') === 0) {
            return $digits;
        }

        // International prefix 00 (e.g. 00923001234567).
        if (strpos($digits, '00') === 0) {
            return ltrim(substr($digits, 2), '0');
        }

        $country = $this->resolve_order_country($order);
        $calling_code = $this->get_country_calling_code($country);

        // If number already starts with country calling code, keep it.
        if ($calling_code !== '' && strpos($digits, $calling_code) === 0) {
            return $digits;
        }

        // Local/trunk format: remove leading zeros and prepend country calling code.
        if ($calling_code !== '') {
            $local = ltrim($digits, '0');
            if ($local === '') {
                return '';
            }
            $normalized = $calling_code . $local;

            if ($normalized !== $digits) {
                whatsapp_automation_log(
                    'Order #' . $order->get_id() . ' phone normalized using country ' . $country . ': ' . $digits . ' -> ' . $normalized,
                    'info'
                );
            }
            return $normalized;
        }

        // Fallback when country is unavailable: keep digits as-is.
        return $digits;
    }

    private function resolve_order_country($order) {
        $billing_country = strtoupper(trim((string) $order->get_billing_country()));
        if ($billing_country !== '') {
            return $billing_country;
        }

        $shipping_country = strtoupper(trim((string) $order->get_shipping_country()));
        if ($shipping_country !== '') {
            return $shipping_country;
        }

        $default_country = strtoupper(trim((string) get_option('woocommerce_default_country', '')));
        if (strpos($default_country, ':') !== false) {
            $parts = explode(':', $default_country, 2);
            $default_country = $parts[0];
        }

        return $default_country;
    }

    private function get_country_calling_code($country) {
        $country = strtoupper(trim((string) $country));
        if ($country === '') {
            return '';
        }

        if (!class_exists('\WC_Countries')) {
            return '';
        }

        $countries = new \WC_Countries();
        $value = $countries->get_country_calling_code($country);

        if (is_array($value)) {
            $value = reset($value);
        }

        return preg_replace('/\D+/', '', (string) $value);
    }
}
