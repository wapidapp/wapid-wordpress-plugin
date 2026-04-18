<?php

namespace WhatsAppAutomation;

class MessageHistory {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wapid_message_history';
    }

    public static function insert($payload) {
        global $wpdb;

        $data = array(
            'source' => isset($payload['source']) ? sanitize_key((string) $payload['source']) : 'plugin',
            'event_type' => isset($payload['event_type']) ? sanitize_key((string) $payload['event_type']) : null,
            'instance_id' => isset($payload['instance_id']) ? sanitize_text_field((string) $payload['instance_id']) : null,
            'recipient_phone' => isset($payload['recipient_phone']) ? sanitize_text_field((string) $payload['recipient_phone']) : null,
            'message_text' => isset($payload['message_text']) ? wp_kses_post((string) $payload['message_text']) : '',
            'order_id' => isset($payload['order_id']) ? (int) $payload['order_id'] : null,
            'backend_message_id' => isset($payload['backend_message_id']) ? sanitize_text_field((string) $payload['backend_message_id']) : null,
            'local_status' => isset($payload['local_status']) ? sanitize_key((string) $payload['local_status']) : 'queued',
            'backend_status' => isset($payload['backend_status']) ? sanitize_key((string) $payload['backend_status']) : null,
            'response_payload' => isset($payload['response_payload']) ? wp_json_encode($payload['response_payload']) : null,
            'error_message' => isset($payload['error_message']) ? sanitize_textarea_field((string) $payload['error_message']) : null,
        );

        $wpdb->insert(self::table_name(), $data);
        if (!$wpdb->insert_id) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public static function update($id, $payload) {
        global $wpdb;
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $data = array();
        if (array_key_exists('backend_message_id', $payload)) {
            $data['backend_message_id'] = sanitize_text_field((string) $payload['backend_message_id']);
        }
        if (array_key_exists('local_status', $payload)) {
            $data['local_status'] = sanitize_key((string) $payload['local_status']);
        }
        if (array_key_exists('backend_status', $payload)) {
            $data['backend_status'] = sanitize_key((string) $payload['backend_status']);
        }
        if (array_key_exists('response_payload', $payload)) {
            $data['response_payload'] = wp_json_encode($payload['response_payload']);
        }
        if (array_key_exists('error_message', $payload)) {
            $data['error_message'] = sanitize_textarea_field((string) $payload['error_message']);
        }

        if (empty($data)) {
            return false;
        }

        return (bool) $wpdb->update(
            self::table_name(),
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }

    public static function latest($limit = 100) {
        global $wpdb;
        $limit = max(1, min(500, (int) $limit));
        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit),
            ARRAY_A
        );
    }

    public static function count_all() {
        global $wpdb;
        $table = self::table_name();

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    public static function latest_paginated($limit = 10, $offset = 0) {
        global $wpdb;
        $limit = max(1, min(500, (int) $limit));
        $offset = max(0, (int) $offset);
        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset),
            ARRAY_A
        );
    }
}
