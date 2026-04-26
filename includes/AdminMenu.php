<?php

namespace WhatsAppAutomation;

class AdminMenu {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_brand'), 999);
        add_action('admin_head', array($this, 'hide_wordpress_submenu'));
        add_action('admin_head', array($this, 'inject_plugin_favicon'));
        add_action('admin_init', array($this, 'maybe_handle_connect_flow'));
        add_action('admin_notices', array($this, 'render_notices'));

        add_action('admin_post_whatsapp_automation_login', array($this, 'handle_login'));
        add_action('admin_post_whatsapp_automation_register', array($this, 'handle_register'));
        add_action('admin_post_whatsapp_automation_logout', array($this, 'handle_logout'));
        add_action('admin_post_whatsapp_automation_save_settings', array($this, 'handle_save_settings'));
        add_action('admin_post_whatsapp_automation_select_instance', array($this, 'handle_select_instance'));
        add_action('admin_post_whatsapp_automation_instance_action', array($this, 'handle_instance_action'));
        add_action('admin_post_whatsapp_automation_save_template', array($this, 'handle_save_template'));
        add_action('admin_post_whatsapp_automation_delete_template', array($this, 'handle_delete_template'));
        add_action('admin_post_whatsapp_automation_send_test', array($this, 'handle_send_test'));
        add_action('admin_post_whatsapp_automation_save_api_key', array($this, 'handle_save_api_key'));
        add_action('admin_post_whatsapp_automation_generate_api_key', array($this, 'handle_generate_api_key'));
    }

    public function add_menu_pages() {
        add_menu_page(
            __('Wapid Automation', 'wapid-automation-for-woocommerce'),
            __('Wapid', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce',
            array($this, 'render_dashboard'),
            WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/logo-wapid-mark.svg',
            56
        );

        // Hidden internal pages (accessible via URL / plugin sidebar only).
        add_submenu_page(
            null,
            __('Dashboard', 'wapid-automation-for-woocommerce'),
            __('Dashboard', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            null,
            __('Settings', 'wapid-automation-for-woocommerce'),
            __('Settings', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce-settings',
            array($this, 'render_settings')
        );

        add_submenu_page(
            null,
            __('Instances', 'wapid-automation-for-woocommerce'),
            __('Instances', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce-instances',
            array($this, 'render_instances')
        );

        add_submenu_page(
            null,
            __('Templates', 'wapid-automation-for-woocommerce'),
            __('Templates', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce-templates',
            array($this, 'render_templates')
        );

        add_submenu_page(
            null,
            __('Email Marketing', 'wapid-automation-for-woocommerce'),
            __('Email Marketing', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce-email-marketing',
            array($this, 'render_email_marketing')
        );

        add_submenu_page(
            null,
            __('Logs', 'wapid-automation-for-woocommerce'),
            __('Logs', 'wapid-automation-for-woocommerce'),
            'manage_options',
            'wapid-automation-for-woocommerce-logs',
            array($this, 'render_logs')
        );
    }

    public function hide_wordpress_submenu() {
        // Styles moved to admin.css
    }

    public function inject_plugin_favicon() {
        if (!is_admin()) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page === '' || strpos($page, 'wapid-automation-for-woocommerce') !== 0) {
            return;
        }

        $icon = esc_url(WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/logo-wapid-mark.svg');
        echo '<link rel="icon" href="' . $icon . '" type="image/svg+xml">';
        echo '<link rel="shortcut icon" href="' . $icon . '" type="image/svg+xml">';
    }

    public function enqueue_assets($hook) {
        wp_enqueue_style(
            'wapid-automation-for-woocommerce-admin-menu',
            WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/admin-menu.css',
            array(),
            WHATSAPP_AUTOMATION_VERSION
        );

        if (strpos((string) $hook, 'wapid-automation-for-woocommerce') === false) {
            return;
        }

        wp_enqueue_style(
            'wapid-automation-for-woocommerce-admin',
            WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/admin.css',
            array(),
            WHATSAPP_AUTOMATION_VERSION
        );
    }

    public function add_admin_bar_brand($wp_admin_bar) {
        if (!is_admin() || !is_admin_bar_showing() || !current_user_can('manage_options')) {
            return;
        }

        $icon = esc_url(WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/logo-wapid-mark.svg');
        $title = '<span class="ab-item wa-adminbar-brand">'
            . '<span class="wa-adminbar-brand__badge"><img src="' . $icon . '" alt="" /></span>'
            . '<span class="wa-adminbar-brand__text">Wapid</span>'
            . '</span>';

        $wp_admin_bar->add_node(array(
            'id' => 'wapid-automation-brand',
            'parent' => 'top-secondary',
            'title' => $title,
            'href' => admin_url('admin.php?page=wapid-automation-for-woocommerce'),
            'meta' => array(
                'title' => __('Open Wapid Dashboard', 'wapid-automation-for-woocommerce'),
                'class' => 'wapid-automation-adminbar-node',
            ),
        ));
    }

    public function render_dashboard() {
        require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/dashboard.php';
    }

    public function render_settings() {
        require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/settings.php';
    }

    public function render_instances() {
        require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/instances.php';
    }

    public function render_templates() {
        require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/templates.php';
    }

    public function render_logs() {
        require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/logs.php';
    }

    public function render_email_marketing() {
        $controller = new \WhatsAppAutomation\Modules\EmailMarketing\Controllers\EmailMarketingController();
        $controller->render();
    }

    public function render_notices() {
        if (!isset($_GET['wa_notice'], $_GET['wa_message'])) {
            return;
        }

        $notice = sanitize_key(wp_unslash($_GET['wa_notice']));
        $message = sanitize_text_field(rawurldecode(wp_unslash($_GET['wa_message'])));
        $class = $notice === 'error' ? 'notice notice-error' : 'notice notice-success';
        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
    }

    public function handle_login() {
        $this->guard('whatsapp_automation_login');
        $client = new APIClient();
        $response = $client->login(
            isset($_POST['email']) ? wp_unslash($_POST['email']) : '',
            isset($_POST['password']) ? wp_unslash($_POST['password']) : ''
        );

        if (is_wp_error($response)) {
            $this->redirect_notice('error', $response->get_error_message(), 'wapid-automation-for-woocommerce-settings');
        }

        $this->redirect_notice('success', __('Login successful.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
    }

    public function handle_register() {
        $this->guard('whatsapp_automation_register');
        $client = new APIClient();
        $response = $client->register(array(
            'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'password' => (string) wp_unslash($_POST['password'] ?? ''),
            'first_name' => sanitize_text_field(wp_unslash($_POST['first_name'] ?? '')),
            'last_name' => sanitize_text_field(wp_unslash($_POST['last_name'] ?? '')),
            'company_name' => sanitize_text_field(wp_unslash($_POST['company_name'] ?? '')),
        ));

        if (is_wp_error($response)) {
            $this->redirect_notice('error', $response->get_error_message(), 'wapid-automation-for-woocommerce-settings');
        }

        $this->redirect_notice('success', __('Account created and logged in.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
    }

    public function handle_logout() {
        $this->guard('whatsapp_automation_logout');
        (new APIClient())->logout();
        $this->redirect_notice('success', __('Account disconnected.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
    }

    public function handle_save_settings() {
        $this->guard('whatsapp_automation_save_settings');
        if (isset($_POST['api_base_url'])) {
            $base = esc_url_raw(wp_unslash($_POST['api_base_url']));
            if ($base !== '') {
                update_option('whatsapp_automation_api_base_url', untrailingslashit($base));
            }
        }

        $supported_events = $this->get_supported_events();
        $posted_configs = (isset($_POST['event_configs']) && is_array($_POST['event_configs']))
            ? $_POST['event_configs']
            : array();

        $selected_events = array();
        $enabled_events = array();
        $mappings = array();
        $fallbacks = array();

        foreach ($posted_configs as $raw_event_key => $config) {
            $event_key = sanitize_key($raw_event_key);
            if (!isset($supported_events[$event_key])) {
                continue;
            }

            if (in_array($event_key, $selected_events, true)) {
                continue;
            }

            $selected_events[] = $event_key;
            $is_enabled = !empty($config['enabled']) && sanitize_text_field(wp_unslash((string) $config['enabled'])) !== '0';
            if ($is_enabled && ($event_key === 'new_order' || strpos($event_key, 'order_status_') === 0)) {
                $enabled_events[$event_key] = 1;
            }

            $template_id = sanitize_text_field(wp_unslash($config['template_id'] ?? ''));
            if ($template_id !== '') {
                $mappings[$event_key] = $template_id;
            }

            $fallbacks[$event_key] = sanitize_textarea_field(wp_unslash($config['fallback'] ?? ''));
        }

        update_option('whatsapp_automation_selected_events', $selected_events);
        update_option('whatsapp_automation_template_mappings', $mappings);
        update_option('whatsapp_automation_fallback_messages', $fallbacks);

        update_option('whatsapp_automation_enabled_events', $enabled_events);

        $otp_features = array(
            'login' => !empty($posted_configs['otp_login']['enabled']) && sanitize_text_field(wp_unslash((string) $posted_configs['otp_login']['enabled'])) !== '0' ? 1 : 0,
            'register' => !empty($posted_configs['otp_register']['enabled']) && sanitize_text_field(wp_unslash((string) $posted_configs['otp_register']['enabled'])) !== '0' ? 1 : 0,
            'checkout' => !empty($posted_configs['otp_checkout']['enabled']) && sanitize_text_field(wp_unslash((string) $posted_configs['otp_checkout']['enabled'])) !== '0' ? 1 : 0,
        );
        update_option('whatsapp_automation_otp_features', $otp_features);

        $this->redirect_notice('success', __('Settings saved.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
    }

    public function handle_select_instance() {
        $this->guard('whatsapp_automation_select_instance');
        $instance_id = sanitize_text_field(wp_unslash($_POST['instance_id'] ?? ''));

        if ($instance_id !== '') {
            $client = new APIClient();
            $instances_response = $client->get_instances();

            if (is_wp_error($instances_response)) {
                $this->redirect_notice('error', $instances_response->get_error_message(), 'wapid-automation-for-woocommerce-instances');
            }

            $instances = $client->extract_items($instances_response);
            $is_valid = false;
            foreach ($instances as $instance) {
                if (($instance['id'] ?? '') === $instance_id) {
                    $is_valid = true;
                    break;
                }
            }

            if (!$is_valid) {
                $this->redirect_notice('error', __('Selected instance not found in your account.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-instances');
            }
        }

        update_option('whatsapp_automation_instance_id', $instance_id);
        $this->redirect_notice('success', __('Instance selected.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-instances');
    }

    public function handle_instance_action() {
        $this->guard('whatsapp_automation_instance_action');
        $action = sanitize_key(wp_unslash($_POST['instance_action'] ?? ''));
        $instance_id = sanitize_text_field(wp_unslash($_POST['instance_id'] ?? ''));
        $client = new APIClient();

        if ($action === 'start') {
            $response = $client->start_instance($instance_id);
        } elseif ($action === 'stop') {
            $response = $client->stop_instance($instance_id);
        } elseif ($action === 'restart') {
            $response = $client->restart_instance($instance_id);
        } elseif ($action === 'logout') {
            $response = $client->logout_instance($instance_id);
        } else {
            $response = new \WP_Error('wa_invalid_action', __('Invalid instance action.', 'wapid-automation-for-woocommerce'));
        }

        if (is_wp_error($response)) {
            $this->redirect_notice('error', $response->get_error_message(), 'wapid-automation-for-woocommerce-instances');
        }

        $this->redirect_notice('success', __('Instance action executed.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-instances');
    }

    public function handle_save_template() {
        $this->guard('whatsapp_automation_save_template');
        $client = new APIClient();
        $response = $client->create_template(
            sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            sanitize_text_field(wp_unslash($_POST['category'] ?? 'order_update')),
            sanitize_textarea_field(wp_unslash($_POST['content'] ?? ''))
        );

        if (is_wp_error($response)) {
            $this->redirect_notice('error', $response->get_error_message(), 'wapid-automation-for-woocommerce-templates');
        }

        $this->redirect_notice('success', __('Template created.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-templates');
    }

    public function handle_delete_template() {
        $this->guard('whatsapp_automation_delete_template');
        $client = new APIClient();
        $template_id = sanitize_text_field(wp_unslash($_POST['template_id'] ?? ''));
        $response = $client->delete_template($template_id);
        if (is_wp_error($response)) {
            $this->redirect_notice('error', $response->get_error_message(), 'wapid-automation-for-woocommerce-templates');
        }
        $this->redirect_notice('success', __('Template deleted.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-templates');
    }

    public function handle_send_test() {
        $this->guard('whatsapp_automation_send_test');
        $instance_id = get_option('whatsapp_automation_instance_id', '');
        if (empty($instance_id)) {
            whatsapp_automation_log('Manual test aborted: no instance selected.', 'warn');
            $this->redirect_notice('error', __('Please select an instance first.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-logs');
        }

        $client = new APIClient();
        $resolved_instance_id = $client->resolve_sendable_instance_id($instance_id);
        if (is_wp_error($resolved_instance_id)) {
            whatsapp_automation_log('Manual test failed while resolving instance: ' . $resolved_instance_id->get_error_message(), 'error');
            $this->redirect_notice('error', $resolved_instance_id->get_error_message(), 'wapid-automation-for-woocommerce-logs');
        }
        if ($resolved_instance_id !== $instance_id) {
            update_option('whatsapp_automation_instance_id', $resolved_instance_id);
            whatsapp_automation_log('Manual test auto-switched instance from ' . $instance_id . ' to ' . $resolved_instance_id . '.', 'warn');
            $instance_id = $resolved_instance_id;
        }

        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? 'Test message'));
        whatsapp_automation_log('Manual test queued for ' . preg_replace('/\D+/', '', $phone) . ' via instance ' . $instance_id . '.', 'info');
        $history_id = MessageHistory::insert(array(
            'source' => 'manual_test',
            'event_type' => 'manual_test',
            'instance_id' => $instance_id,
            'recipient_phone' => $phone,
            'message_text' => $message,
            'local_status' => 'queued',
        ));

        $response = $client->send_message(
            $instance_id,
            $phone,
            $message
        );

        if (is_wp_error($response)) {
            if ($history_id > 0) {
                MessageHistory::update($history_id, array(
                    'local_status' => 'failed',
                    'error_message' => $response->get_error_message(),
                ));
            }
            whatsapp_automation_log('Manual test failed: ' . $response->get_error_message(), 'error');
            $this->redirect_notice('error', $response->get_error_message(), 'wapid-automation-for-woocommerce-logs');
        }

        $backend_status = (string) ($response['data']['status'] ?? '');
        if ($history_id > 0) {
            MessageHistory::update($history_id, array(
                'backend_message_id' => $response['data']['id'] ?? '',
                'backend_status' => $backend_status,
                'local_status' => ($backend_status === 'sent') ? 'sent' : 'queued',
                'response_payload' => $response,
            ));
        }

        whatsapp_automation_log(
            'Manual test sent for ' . preg_replace('/\D+/', '', $phone) . ' with backend status ' . (($backend_status !== '') ? $backend_status : 'unknown') . '.',
            'info'
        );

        $this->redirect_notice('success', __('Test message queued/sent successfully.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-logs');
    }

    public function handle_save_api_key() {
        $this->guard('whatsapp_automation_save_api_key');
        $api_key = sanitize_text_field(wp_unslash($_POST['api_key'] ?? ''));
        $client = new APIClient();
        $result = $client->save_api_key($api_key);

        if (is_wp_error($result)) {
            $this->redirect_notice('error', $result->get_error_message(), 'wapid-automation-for-woocommerce-settings');
        }

        if ($api_key === '') {
            $this->redirect_notice('success', __('API key removed. Plugin will use token-based fallback if connected.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
        }

        $this->redirect_notice('success', __('API key saved and verified.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
    }

    public function handle_generate_api_key() {
        $this->guard('whatsapp_automation_generate_api_key');
        $client = new APIClient();
        $result = $client->generate_wordpress_api_key();

        if (is_wp_error($result)) {
            $this->redirect_notice('error', $result->get_error_message(), 'wapid-automation-for-woocommerce-settings');
        }

        $masked = sanitize_text_field((string) ($result['masked'] ?? ''));
        $message = __('WordPress API key generated successfully.', 'wapid-automation-for-woocommerce');
        if ($masked !== '') {
            $message .= ' ' . sprintf(__('Key: %s', 'wapid-automation-for-woocommerce'), $masked);
        }

        $this->redirect_notice('success', $message, 'wapid-automation-for-woocommerce-settings');
    }

    private function guard($action) {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wapid-automation-for-woocommerce'));
        }
        check_admin_referer($action);
    }

    private function redirect_notice($type, $message, $page) {
        $url = add_query_arg(array(
            'page' => $page,
            'wa_notice' => $type,
            'wa_message' => rawurlencode($message),
        ), admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    public function maybe_handle_connect_flow() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['wa_connect']) && !isset($_GET['wa_access_token']) && !isset($_GET['wa_auth_error'])) {
            return;
        }

        if (isset($_GET['wa_connect'])) {
            check_admin_referer('whatsapp_automation_connect');

            $current_user_id = get_current_user_id();
            if ($current_user_id <= 0) {
                $this->redirect_notice('error', __('Authentication failed. Please try again.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
            }

            $state = $this->create_connect_state($current_user_id);
            $redirect_to = add_query_arg(
                array(
                    'page' => 'wapid-automation-for-woocommerce-settings',
                    'wa_connect_callback' => 1,
                ),
                admin_url('admin.php')
            );
            $external = add_query_arg(
                array(
                    'source' => 'wp_plugin',
                    'site' => site_url('/'),
                    'redirect_to' => $redirect_to,
                    'state' => $state,
                    'wa_state' => $state,
                ),
                $this->get_connect_url()
            );
            wp_redirect(esc_url_raw($external));
            exit;
        }

        $has_callback_payload = isset($_GET['wa_access_token']) || isset($_GET['wa_auth_error']);
        if ($has_callback_payload) {
            $state = sanitize_text_field(wp_unslash($_GET['state'] ?? $_GET['wa_state'] ?? ''));
            $current_user_id = get_current_user_id();
            if (!$this->consume_connect_state($current_user_id, $state)) {
                whatsapp_automation_log('Connect callback rejected due to invalid or missing state.', 'warn');
                $this->redirect_notice('error', __('Authentication failed. Please reconnect and try again.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
            }
        }

        if (isset($_GET['wa_access_token'])) {
            $access_token = sanitize_text_field(wp_unslash($_GET['wa_access_token']));
            $refresh_token = sanitize_text_field(wp_unslash($_GET['wa_refresh_token'] ?? ''));
            $expires_in = absint($_GET['wa_expires_in'] ?? 900);

            if ($access_token !== '') {
                update_option('whatsapp_automation_access_token', $access_token);
                if ($refresh_token !== '') {
                    update_option('whatsapp_automation_refresh_token', $refresh_token);
                }
                update_option('whatsapp_automation_token_expires_at', time() + max(60, $expires_in));

                // Non-breaking migration path: keep JWT fallback, but auto-provision API key when possible.
                if ((string) get_option('whatsapp_automation_api_key', '') === '') {
                    $client = new APIClient();
                    $generated = $client->generate_wordpress_api_key();
                    if (is_wp_error($generated)) {
                        whatsapp_automation_log('Auto API key generation skipped: ' . $generated->get_error_message(), 'warn');
                    }
                }

                $this->redirect_notice('success', __('Account connected successfully.', 'wapid-automation-for-woocommerce'), 'wapid-automation-for-woocommerce-settings');
            }
        }

        if (isset($_GET['wa_auth_error'])) {
            $error_message = sanitize_text_field(wp_unslash($_GET['wa_auth_error']));
            if ($error_message === '') {
                $error_message = __('Authentication failed.', 'wapid-automation-for-woocommerce');
            }
            $this->redirect_notice('error', $error_message, 'wapid-automation-for-woocommerce-settings');
        }
    }

    private function create_connect_state($user_id) {
        $state = wp_generate_password(32, false, false);
        set_transient($this->get_connect_state_transient_key($user_id), $state, 15 * MINUTE_IN_SECONDS);
        return $state;
    }

    private function consume_connect_state($user_id, $state) {
        if ($user_id <= 0 || $state === '') {
            return false;
        }

        $transient_key = $this->get_connect_state_transient_key($user_id);
        $expected_state = get_transient($transient_key);
        delete_transient($transient_key);

        if (!is_string($expected_state) || $expected_state === '') {
            return false;
        }

        return hash_equals($expected_state, $state);
    }

    private function get_connect_state_transient_key($user_id) {
        return 'wa_connect_state_' . absint($user_id);
    }

    private function get_supported_events() {
        $events = array(
            'new_order' => array('category' => 'order', 'label' => 'New Order'),
            'otp_login' => array('category' => 'account', 'label' => 'Login OTP'),
            'otp_register' => array('category' => 'account', 'label' => 'Register OTP'),
            'otp_checkout' => array('category' => 'checkout', 'label' => 'Checkout OTP'),
        );

        if (function_exists('wc_get_order_statuses')) {
            foreach (wc_get_order_statuses() as $status_key => $status_label) {
                $slug = str_replace('wc-', '', (string) $status_key);
                $events['order_status_' . sanitize_key($slug)] = array(
                    'category' => 'order',
                    'label' => 'Order Status: ' . wp_strip_all_tags((string) $status_label),
                );
            }
        }

        return $events;
    }

    private function get_connect_url() {
        $custom = trim((string) get_option('whatsapp_automation_connect_url', ''));
        if ($custom !== '') {
            return $custom;
        }

        $site_host = wp_parse_url(site_url('/'), PHP_URL_HOST);
        if ($site_host && (stripos($site_host, 'localhost') !== false || stripos($site_host, '.local') !== false)) {
            return 'http://localhost:3000/login';
        }

        return 'https://wapid.net/login';
    }
}
