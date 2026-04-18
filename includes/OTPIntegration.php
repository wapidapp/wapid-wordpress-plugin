<?php

namespace WhatsAppAutomation;

class OTPIntegration {
    const OTP_TTL = 300;
    const VERIFIED_TTL = 900;

    private $client;

    public function __construct() {
        $this->client = whatsapp_automation_get_api_client();

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_whatsapp_automation_send_otp', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_nopriv_whatsapp_automation_send_otp', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_whatsapp_automation_verify_otp', array($this, 'ajax_verify_otp'));
        add_action('wp_ajax_nopriv_whatsapp_automation_verify_otp', array($this, 'ajax_verify_otp'));

        add_action('woocommerce_login_form_end', array($this, 'render_login_otp_ui'));
        add_action('woocommerce_register_form_start', array($this, 'render_register_phone_field'));
        add_action('woocommerce_register_form_end', array($this, 'render_register_otp_ui'));
        add_filter('woocommerce_process_login_errors', array($this, 'validate_login_otp'), 10, 3);
        add_action('woocommerce_register_post', array($this, 'validate_register_otp'), 10, 3);
        add_action('woocommerce_created_customer', array($this, 'save_register_phone'));

        add_action('woocommerce_after_checkout_billing_form', array($this, 'render_checkout_otp_ui'));
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_checkout_otp'), 10, 2);
    }

    public function enqueue_assets() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        if (!is_account_page() && !is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'wapid-automation-for-woocommerce-otp',
            WHATSAPP_AUTOMATION_PLUGIN_URL . 'assets/css/otp.css',
            array(),
            WHATSAPP_AUTOMATION_VERSION
        );

        wp_enqueue_script(
            'wapid-automation-for-woocommerce-otp',
            WHATSAPP_AUTOMATION_PLUGIN_URL . 'assets/js/otp.js',
            array('jquery'),
            WHATSAPP_AUTOMATION_VERSION,
            true
        );

        wp_localize_script('wapid-automation-for-woocommerce-otp', 'wapidAutomationOtp', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('whatsapp_automation_otp_nonce'),
            'messages' => array(
                'sent' => __('OTP sent successfully.', 'wapid-automation-for-woocommerce'),
                'verified' => __('OTP verified.', 'wapid-automation-for-woocommerce'),
                'sending' => __('Sending OTP...', 'wapid-automation-for-woocommerce'),
                'verifying' => __('Verifying OTP...', 'wapid-automation-for-woocommerce'),
            ),
        ));
    }

    public function render_login_otp_ui() {
        if (!$this->is_otp_enabled('login')) {
            return;
        }
        echo $this->otp_markup('login');
    }

    public function render_register_phone_field() {
        if (!$this->is_otp_enabled('register')) {
            return;
        }
        ?>
        <p class="form-row form-row-wide">
            <label for="wa_reg_phone"><?php esc_html_e('Phone Number', 'wapid-automation-for-woocommerce'); ?>&nbsp;<span class="required">*</span></label>
            <input type="tel" class="input-text" name="wa_reg_phone" id="wa_reg_phone" value="<?php echo esc_attr(wp_unslash($_POST['wa_reg_phone'] ?? '')); ?>" />
        </p>
        <?php
    }

    public function render_register_otp_ui() {
        if (!$this->is_otp_enabled('register')) {
            return;
        }
        echo $this->otp_markup('register');
    }

    public function render_checkout_otp_ui() {
        if (!$this->is_otp_enabled('checkout')) {
            return;
        }
        echo $this->otp_markup('checkout');
    }

    public function ajax_send_otp() {
        check_ajax_referer('whatsapp_automation_otp_nonce', 'nonce');

        if (!$this->client->is_authenticated()) {
            wp_send_json_error(array('message' => __('Service not connected.', 'wapid-automation-for-woocommerce')), 400);
        }

        $instance_id = get_option('whatsapp_automation_instance_id', '');
        if (empty($instance_id)) {
            wp_send_json_error(array('message' => __('No active messaging instance selected.', 'wapid-automation-for-woocommerce')), 400);
        }

        $resolved_instance_id = $this->client->resolve_sendable_instance_id($instance_id);
        if (is_wp_error($resolved_instance_id)) {
            wp_send_json_error(array('message' => $resolved_instance_id->get_error_message()), 400);
        }
        if ($resolved_instance_id !== $instance_id) {
            update_option('whatsapp_automation_instance_id', $resolved_instance_id);
            $instance_id = $resolved_instance_id;
        }

        $context = sanitize_key(wp_unslash($_POST['context'] ?? ''));
        if (!in_array($context, array('login', 'register', 'checkout'), true)) {
            wp_send_json_error(array('message' => __('Invalid OTP context.', 'wapid-automation-for-woocommerce')), 400);
        }

        $target = '';
        $phone = '';

        if ($context === 'login') {
            $identifier = sanitize_text_field(wp_unslash($_POST['identifier'] ?? ''));
            $user = get_user_by('login', $identifier);
            if (!$user && is_email($identifier)) {
                $user = get_user_by('email', $identifier);
            }

            if (!$user) {
                wp_send_json_error(array('message' => __('User not found for OTP.', 'wapid-automation-for-woocommerce')), 404);
            }

            $phone = (string) get_user_meta($user->ID, 'billing_phone', true);
            if ($phone === '') {
                $phone = (string) get_user_meta($user->ID, 'phone_number', true);
            }

            if ($phone === '') {
                wp_send_json_error(array('message' => __('No phone number found for this account.', 'wapid-automation-for-woocommerce')), 400);
            }

            $target = 'user:' . $user->ID;
        } else {
            $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
            if ($phone === '') {
                wp_send_json_error(array('message' => __('Phone number is required.', 'wapid-automation-for-woocommerce')), 400);
            }
            $target = 'phone:' . $this->normalize_phone($phone);
        }

        $normalized_phone = $this->normalize_phone($phone);
        if (strlen($normalized_phone) < 8) {
            wp_send_json_error(array('message' => __('Invalid phone number.', 'wapid-automation-for-woocommerce')), 400);
        }

        $otp = (string) random_int(100000, 999999);
        $challenge_id = wp_generate_uuid4();
        $payload = array(
            'code' => $otp,
            'phone' => $normalized_phone,
            'context' => $context,
            'target' => $target,
            'created_at' => time(),
        );

        set_transient($this->challenge_key($challenge_id), $payload, self::OTP_TTL);

        $message = $this->resolve_otp_message($context, $otp);

        $send = $this->client->send_message($instance_id, $normalized_phone, $message);
        if (is_wp_error($send)) {
            delete_transient($this->challenge_key($challenge_id));
            wp_send_json_error(array('message' => $send->get_error_message()), 400);
        }

        wp_send_json_success(array(
            'challenge_id' => $challenge_id,
            'masked_phone' => $this->mask_phone($normalized_phone),
        ));
    }

    public function ajax_verify_otp() {
        check_ajax_referer('whatsapp_automation_otp_nonce', 'nonce');

        $context = sanitize_key(wp_unslash($_POST['context'] ?? ''));
        $challenge_id = sanitize_text_field(wp_unslash($_POST['challenge_id'] ?? ''));
        $code = preg_replace('/\D+/', '', (string) wp_unslash($_POST['otp'] ?? ''));

        if ($challenge_id === '' || strlen($code) < 4) {
            wp_send_json_error(array('message' => __('OTP or challenge is missing.', 'wapid-automation-for-woocommerce')), 400);
        }

        $challenge = get_transient($this->challenge_key($challenge_id));
        if (!is_array($challenge)) {
            wp_send_json_error(array('message' => __('OTP expired. Please resend.', 'wapid-automation-for-woocommerce')), 400);
        }

        if (($challenge['context'] ?? '') !== $context) {
            wp_send_json_error(array('message' => __('OTP context mismatch.', 'wapid-automation-for-woocommerce')), 400);
        }

        if (!hash_equals((string) ($challenge['code'] ?? ''), $code)) {
            wp_send_json_error(array('message' => __('Invalid OTP code.', 'wapid-automation-for-woocommerce')), 400);
        }

        $verify_token = wp_generate_password(32, false, false);
        set_transient(
            $this->verified_key($verify_token),
            array(
                'phone' => $challenge['phone'],
                'context' => $challenge['context'],
                'target' => $challenge['target'],
                'verified_at' => time(),
            ),
            self::VERIFIED_TTL
        );

        delete_transient($this->challenge_key($challenge_id));

        wp_send_json_success(array('verify_token' => $verify_token));
    }

    public function validate_login_otp($errors, $username, $password) {
        if (!$this->is_otp_enabled('login')) {
            return $errors;
        }

        $token = sanitize_text_field(wp_unslash($_POST['wa_otp_verified_token_login'] ?? ''));
        if ($token === '') {
            $errors->add('wa_otp_required', __('OTP verification required before login.', 'wapid-automation-for-woocommerce'));
            return $errors;
        }

        $record = get_transient($this->verified_key($token));
        if (!is_array($record) || ($record['context'] ?? '') !== 'login') {
            $errors->add('wa_otp_invalid', __('OTP verification is invalid or expired.', 'wapid-automation-for-woocommerce'));
            return $errors;
        }

        $user = get_user_by('login', $username);
        if (!$user && is_email($username)) {
            $user = get_user_by('email', $username);
        }

        if (!$user || ('user:' . $user->ID) !== ($record['target'] ?? '')) {
            $errors->add('wa_otp_user', __('OTP was not verified for this account.', 'wapid-automation-for-woocommerce'));
            return $errors;
        }

        delete_transient($this->verified_key($token));
        return $errors;
    }

    public function validate_register_otp($username, $email, $errors) {
        if (!$this->is_otp_enabled('register')) {
            return;
        }

        $phone = sanitize_text_field(wp_unslash($_POST['wa_reg_phone'] ?? ''));
        if ($phone === '') {
            $errors->add('wa_reg_phone_missing', __('Phone number is required for registration OTP.', 'wapid-automation-for-woocommerce'));
            return;
        }

        $token = sanitize_text_field(wp_unslash($_POST['wa_otp_verified_token_register'] ?? ''));
        if (!$this->is_verified_token_valid($token, 'register', $this->normalize_phone($phone))) {
            $errors->add('wa_reg_otp_invalid', __('Please verify OTP before registration.', 'wapid-automation-for-woocommerce'));
            return;
        }
    }

    public function save_register_phone($customer_id) {
        if (!$this->is_otp_enabled('register')) {
            return;
        }

        $phone = sanitize_text_field(wp_unslash($_POST['wa_reg_phone'] ?? ''));
        if ($phone !== '') {
            update_user_meta($customer_id, 'billing_phone', $this->normalize_phone($phone));
        }

        $token = sanitize_text_field(wp_unslash($_POST['wa_otp_verified_token_register'] ?? ''));
        if ($token !== '') {
            delete_transient($this->verified_key($token));
        }
    }

    public function validate_checkout_otp($data, $errors) {
        if (!$this->is_otp_enabled('checkout')) {
            return;
        }

        $phone = sanitize_text_field($data['billing_phone'] ?? '');
        $token = sanitize_text_field(wp_unslash($_POST['wa_otp_verified_token_checkout'] ?? ''));

        if (!$this->is_verified_token_valid($token, 'checkout', $this->normalize_phone($phone))) {
            $errors->add('wa_checkout_otp', __('Please verify OTP before placing order.', 'wapid-automation-for-woocommerce'));
        } else {
            delete_transient($this->verified_key($token));
        }
    }

    private function otp_markup($context) {
        ob_start();
        ?>
        <div class="wa-otp-box" data-wa-context="<?php echo esc_attr($context); ?>">
            <p class="wa-otp-title"><?php esc_html_e('Phone OTP Verification', 'wapid-automation-for-woocommerce'); ?></p>
            <p class="wa-otp-row">
                <button type="button" class="button wa-send-otp"><?php esc_html_e('Send OTP', 'wapid-automation-for-woocommerce'); ?></button>
            </p>
            <p class="wa-otp-row">
                <input type="text" maxlength="6" class="input-text wa-otp-input" placeholder="<?php esc_attr_e('Enter OTP', 'wapid-automation-for-woocommerce'); ?>">
                <button type="button" class="button wa-verify-otp"><?php esc_html_e('Verify OTP', 'wapid-automation-for-woocommerce'); ?></button>
            </p>
            <input type="hidden" class="wa-otp-challenge" value="">
            <input type="hidden" class="wa-otp-token" name="wa_otp_verified_token_<?php echo esc_attr($context); ?>" value="">
            <p class="wa-otp-feedback"></p>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function is_otp_enabled($context) {
        $config = get_option('whatsapp_automation_otp_features', array());
        return !empty($config[$context]);
    }

    private function normalize_phone($phone) {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    private function mask_phone($phone) {
        $len = strlen($phone);
        if ($len <= 4) {
            return $phone;
        }
        return str_repeat('*', max(0, $len - 4)) . substr($phone, -4);
    }

    private function challenge_key($challenge_id) {
        return 'wa_otp_ch_' . md5($challenge_id);
    }

    private function verified_key($verify_token) {
        return 'wa_otp_ok_' . md5($verify_token);
    }

    private function is_verified_token_valid($token, $context, $phone) {
        if ($token === '' || $phone === '') {
            return false;
        }

        $record = get_transient($this->verified_key($token));
        if (!is_array($record)) {
            return false;
        }

        if (($record['context'] ?? '') !== $context) {
            return false;
        }

        if (($record['target'] ?? '') !== 'phone:' . $phone) {
            return false;
        }

        return true;
    }

    private function resolve_otp_message($context, $otp) {
        $event_key = 'otp_' . $context;
        $minutes = (string) floor(self::OTP_TTL / 60);
        $site_name = get_bloginfo('name');

        $template_mappings = get_option('whatsapp_automation_template_mappings', array());
        if (!empty($template_mappings[$event_key])) {
            $templates_response = $this->client->get_templates();
            if (!is_wp_error($templates_response)) {
                $templates = $this->client->extract_items($templates_response);
                foreach ($templates as $template) {
                    if (($template['id'] ?? '') === $template_mappings[$event_key]) {
                        $content = (string) ($template['content'] ?? '');
                        $content = str_replace('{{otp}}', $otp, $content);
                        $content = str_replace('{{minutes}}', $minutes, $content);
                        $content = str_replace('{{site_name}}', $site_name, $content);
                        if (trim($content) !== '') {
                            return $content;
                        }
                    }
                }
            }
        }

        $fallbacks = get_option('whatsapp_automation_fallback_messages', array());
        $message_template = (string) ($fallbacks[$event_key] ?? get_option(
            'whatsapp_automation_otp_message',
            'Your verification code is {{otp}}. It expires in {{minutes}} minutes.'
        ));

        $message = str_replace('{{otp}}', $otp, $message_template);
        $message = str_replace('{{minutes}}', $minutes, $message);
        $message = str_replace('{{site_name}}', $site_name, $message);
        return $message;
    }
}
