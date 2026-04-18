<?php
/**
 * Logger
 * File: includes/Logger.php
 *
 * Logging functionality for plugin
 */

namespace WhatsAppAutomation;

class Logger {
    private static $log_dir = null;

    public static function init() {
        self::$log_dir = wp_upload_dir()['basedir'] . '/wapid-automation-for-woocommerce-logs';

        if (!is_dir(self::$log_dir)) {
            wp_mkdir_p(self::$log_dir);
        }
    }

    /**
     * Log message
     */
    public static function log($message, $level = 'info') {
        if (self::$log_dir === null) {
            self::init();
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$level}] {$message}\n";

        $log_file = self::$log_dir . '/wapid-automation-for-woocommerce.log';

        // Append to log file
        error_log($log_message, 3, $log_file);

        // Also log to WordPress error log in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Wapid Automation] ' . $message);
        }
    }

    /**
     * Get logs
     */
    public static function get_logs($limit = 100) {
        if (self::$log_dir === null) {
            self::init();
        }

        $log_file = self::$log_dir . '/wapid-automation-for-woocommerce.log';

        if (!file_exists($log_file)) {
            return array();
        }

        $lines = array_reverse(file($log_file));
        return array_slice($lines, 0, $limit);
    }

    /**
     * Clear logs
     */
    public static function clear_logs() {
        if (self::$log_dir === null) {
            self::init();
        }

        $log_file = self::$log_dir . '/wapid-automation-for-woocommerce.log';

        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
}

Logger::init();

