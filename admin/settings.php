<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Unauthorized', 'wapid-automation-for-woocommerce'));
}
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/partials/shell.php';

$client = whatsapp_automation_get_api_client();
$is_authenticated = $client->is_authenticated();
$saved_api_key = (string) get_option('whatsapp_automation_api_key', '');
$masked_api_key = method_exists($client, 'get_masked_api_key') ? $client->get_masked_api_key() : '';
$has_api_key = ($saved_api_key !== '');
$has_legacy_tokens = !empty(get_option('whatsapp_automation_access_token', '')) && !empty(get_option('whatsapp_automation_refresh_token', ''));
$fallback_messages = get_option('whatsapp_automation_fallback_messages', array());
$template_mappings = get_option('whatsapp_automation_template_mappings', array());
$selected_events = get_option('whatsapp_automation_selected_events', array());
$enabled_events_state = get_option('whatsapp_automation_enabled_events', array());
$otp_features_state = get_option('whatsapp_automation_otp_features', array());
$has_enabled_state = !empty($enabled_events_state) || !empty(array_filter((array) $otp_features_state));

$order_events = array(
    'new_order' => __('New Order', 'wapid-automation-for-woocommerce'),
);
if (function_exists('wc_get_order_statuses')) {
    foreach (wc_get_order_statuses() as $status_key => $status_label) {
        $slug = str_replace('wc-', '', (string) $status_key);
        $order_events['order_status_' . sanitize_key($slug)] = sprintf(__('Order Status: %s', 'wapid-automation-for-woocommerce'), $status_label);
    }
}

$event_catalog = array(
    'order' => $order_events,
    'account' => array(
        'otp_login' => __('Login OTP', 'wapid-automation-for-woocommerce'),
        'otp_register' => __('Register OTP', 'wapid-automation-for-woocommerce'),
    ),
    'checkout' => array(
        'otp_checkout' => __('Checkout OTP', 'wapid-automation-for-woocommerce'),
    ),
);

$event_meta = array();
foreach ($event_catalog as $category => $events) {
    foreach ($events as $key => $label) {
        $event_meta[$key] = array(
            'category' => $category,
            'label' => $label,
        );
    }
}

if (empty($selected_events)) {
    if (!empty($enabled_events_state['new_order'])) {
        $selected_events[] = 'new_order';
    }
    foreach (array_keys($order_events) as $event_key) {
        if (strpos($event_key, 'order_status_') === 0 && !empty($enabled_events_state[$event_key])) {
            $selected_events[] = $event_key;
        }
    }
    if (!empty($otp_features_state['login'])) {
        $selected_events[] = 'otp_login';
    }
    if (!empty($otp_features_state['register'])) {
        $selected_events[] = 'otp_register';
    }
    if (!empty($otp_features_state['checkout'])) {
        $selected_events[] = 'otp_checkout';
    }
}

$templates_response = $is_authenticated ? $client->get_templates() : new WP_Error('wa_no_auth', '');
$templates = !is_wp_error($templates_response) ? $client->extract_items($templates_response) : array();
$wc_icon_url = esc_url(WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/icons/woocommerce.svg');
$wa_icon_url = esc_url(WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/icons/whatsapp.svg');

wp_enqueue_script(
    'wapid-automation-settings',
    WHATSAPP_AUTOMATION_PLUGIN_URL . 'assets/js/settings.js',
    array('jquery'),
    WHATSAPP_AUTOMATION_VERSION,
    true
);

wp_localize_script('wapid-automation-settings', 'wapidAutomationSettings', array(
    'catalog' => $event_catalog,
    'labels' => $event_meta,
    'templates' => $templates,
    'wcIconUrl' => $wc_icon_url,
    'waIconUrl' => $wa_icon_url,
    'searchSelectEvent' => __('Search and select event', 'wapid-automation-for-woocommerce'),
    'useFallbackTextOnly' => __('Use fallback text only', 'wapid-automation-for-woocommerce'),
    'untitled' => __('Untitled', 'wapid-automation-for-woocommerce'),
    'general' => __('General', 'wapid-automation-for-woocommerce'),
    'woocommerce' => __('WooCommerce', 'wapid-automation-for-woocommerce'),
    'whatsapp' => __('WhatsApp', 'wapid-automation-for-woocommerce'),
    'event' => __('event', 'wapid-automation-for-woocommerce'),
    'enabled' => __('Enabled', 'wapid-automation-for-woocommerce'),
    'messageTemplate' => __('Message Template', 'wapid-automation-for-woocommerce'),
    'customizeFallbackMessage' => __('Customize fallback message', 'wapid-automation-for-woocommerce'),
    'delete' => __('Delete', 'wapid-automation-for-woocommerce'),
    'fallbackMessageOptional' => __('Fallback Message (optional)', 'wapid-automation-for-woocommerce'),
    'placeholder' => __('Hi {{customer_name}}, your order #{{order_id}} is now {{order_status}}.', 'wapid-automation-for-woocommerce'),
    'eventAlreadyAdded' => __('This event is already added.', 'wapid-automation-for-woocommerce'),
));

whatsapp_automation_admin_shell_start(
    __('Automated Notifications', 'wapid-automation-for-woocommerce'),
    __('Define how and when your WooCommerce notifications are sent via Wapid.', 'wapid-automation-for-woocommerce')
);
?>

<section class="wa-card" style="margin-bottom: 16px;">
    <h2><?php esc_html_e('API Authentication', 'wapid-automation-for-woocommerce'); ?></h2>
    <p class="wa-notif-intro">
        <?php esc_html_e('Recommended mode: API key. Existing token-based users will continue to work as fallback.', 'wapid-automation-for-woocommerce'); ?>
    </p>

    <div class="wa-grid wa-grid-2">
        <div>
            <p><strong><?php esc_html_e('Current mode:', 'wapid-automation-for-woocommerce'); ?></strong>
                <?php
                if ($has_api_key) {
                    esc_html_e('API key (active)', 'wapid-automation-for-woocommerce');
                } elseif ($has_legacy_tokens) {
                    esc_html_e('Legacy token (fallback)', 'wapid-automation-for-woocommerce');
                } else {
                    esc_html_e('Not connected', 'wapid-automation-for-woocommerce');
                }
                ?>
            </p>
            <?php if ($has_api_key && $masked_api_key !== '') : ?>
                <p><strong><?php esc_html_e('Saved key:', 'wapid-automation-for-woocommerce'); ?></strong> <code><?php echo esc_html($masked_api_key); ?></code></p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
                <?php wp_nonce_field('whatsapp_automation_save_api_key'); ?>
                <input type="hidden" name="action" value="whatsapp_automation_save_api_key">
                <label class="wa-label" for="wa_plugin_api_key"><?php esc_html_e('Paste API Key', 'wapid-automation-for-woocommerce'); ?></label>
                <input id="wa_plugin_api_key" class="regular-text" type="text" name="api_key" value="" placeholder="sk_live_..." autocomplete="off">
                <p class="description"><?php esc_html_e('Leave empty and save to remove API key.', 'wapid-automation-for-woocommerce'); ?></p>
                <?php submit_button(__('Save API Key', 'wapid-automation-for-woocommerce'), 'secondary', 'submit', false); ?>
            </form>
        </div>

        <div>
            <p><strong><?php esc_html_e('Auto-generate for this site', 'wapid-automation-for-woocommerce'); ?></strong></p>
            <p class="description"><?php esc_html_e('If already connected via legacy token, generate a dedicated WordPress key in one click.', 'wapid-automation-for-woocommerce'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
                <?php wp_nonce_field('whatsapp_automation_generate_api_key'); ?>
                <input type="hidden" name="action" value="whatsapp_automation_generate_api_key">
                <?php if ($has_legacy_tokens) : ?>
                    <?php submit_button(__('Generate WordPress API Key', 'wapid-automation-for-woocommerce'), 'secondary', 'submit', false); ?>
                <?php else : ?>
                    <?php submit_button(__('Generate WordPress API Key', 'wapid-automation-for-woocommerce'), 'secondary', 'submit', false, array('disabled' => 'disabled')); ?>
                <?php endif; ?>
            </form>
            <?php if (!$has_legacy_tokens) : ?>
                <p class="description"><?php esc_html_e('Connect once from header button to enable auto-generation, or create key from dashboard/settings manually.', 'wapid-automation-for-woocommerce'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wa-notif-form">
    <?php wp_nonce_field('whatsapp_automation_save_settings'); ?>
    <input type="hidden" name="action" value="whatsapp_automation_save_settings">

    <div class="wa-grid wa-grid-2">
        <section class="wa-card">
            <h2><?php esc_html_e('Setup Automated Notifications', 'wapid-automation-for-woocommerce'); ?></h2>
            <p class="wa-notif-intro"><?php esc_html_e('Choose events, map template messages, and only customize fallback text when needed.', 'wapid-automation-for-woocommerce'); ?></p>

            <?php if (!$is_authenticated) : ?>
                <p><?php esc_html_e('Please add API key above or use Connect button in header to authenticate this plugin first.', 'wapid-automation-for-woocommerce'); ?></p>
            <?php endif; ?>

            <div class="wa-event-builder wa-event-builder-single">
                <div>
                    <label class="wa-label" for="wa_event_key"><?php esc_html_e('Event', 'wapid-automation-for-woocommerce'); ?></label>
                    <select id="wa_event_key" <?php disabled(!$is_authenticated); ?>>
                        <option value=""><?php esc_html_e('Search and select event', 'wapid-automation-for-woocommerce'); ?></option>
                    </select>
                </div>
                <div class="wa-event-builder-action">
                    <button type="button" class="button button-secondary" id="wa_add_event_btn" <?php disabled(!$is_authenticated); ?>><?php esc_html_e('Add Event', 'wapid-automation-for-woocommerce'); ?></button>
                </div>
            </div>

            <?php submit_button(__('Save Notifications', 'wapid-automation-for-woocommerce')); ?>
        </section>

        <section class="wa-card">
            <h2><?php esc_html_e('Added Notification Events', 'wapid-automation-for-woocommerce'); ?></h2>
            <div id="wa_event_rows" class="wa-notif-list">
                <?php foreach ($selected_events as $event_key) : ?>
                    <?php if (!isset($event_meta[$event_key])) { continue; } ?>
                    <?php
                    $saved_fallback = (string) ($fallback_messages[$event_key] ?? '');
                    $saved_template = (string) ($template_mappings[$event_key] ?? '');
                    $fallback_open = ($saved_template === '' || trim($saved_fallback) !== '');
                    $is_enabled = true;
                    if ($has_enabled_state) {
                        if ($event_key === 'otp_login') {
                            $is_enabled = !empty($otp_features_state['login']);
                        } elseif ($event_key === 'otp_register') {
                            $is_enabled = !empty($otp_features_state['register']);
                        } elseif ($event_key === 'otp_checkout') {
                            $is_enabled = !empty($otp_features_state['checkout']);
                        } else {
                            $is_enabled = !empty($enabled_events_state[$event_key]);
                        }
                    }
                    ?>
                    <div class="wa-notif-row" data-event-key="<?php echo esc_attr($event_key); ?>">
                        <div class="wa-notif-top">
                            <div>
                                <div class="wa-flow">
                                    <span class="wa-flow-badge-icon"><img src="<?php echo $wc_icon_url; ?>" alt="<?php esc_attr_e('WooCommerce', 'wapid-automation-for-woocommerce'); ?>"></span>
                                    <span class="wa-flow-arrow">&gt;</span>
                                    <span class="wa-flow-badge-icon"><img src="<?php echo $wa_icon_url; ?>" alt="<?php esc_attr_e('WhatsApp', 'wapid-automation-for-woocommerce'); ?>"></span>
                                    <h3 class="wa-notif-title"><?php echo esc_html($event_meta[$event_key]['label']); ?></h3>
                                </div>
                                <p class="wa-notif-meta"><?php echo esc_html(ucfirst($event_meta[$event_key]['category'])); ?> <?php esc_html_e('event', 'wapid-automation-for-woocommerce'); ?></p>
                                <input type="hidden" name="event_configs[<?php echo esc_attr($event_key); ?>][key]" value="<?php echo esc_attr($event_key); ?>">
                            </div>
                            <label class="wa-toggle">
                                <input type="hidden" name="event_configs[<?php echo esc_attr($event_key); ?>][enabled]" value="0">
                                <input type="checkbox" name="event_configs[<?php echo esc_attr($event_key); ?>][enabled]" value="1" <?php checked($is_enabled); ?>>
                                <span class="wa-toggle-slider" aria-hidden="true"></span>
                                <span class="wa-toggle-label"><?php esc_html_e('Enabled', 'wapid-automation-for-woocommerce'); ?></span>
                            </label>
                        </div>
                        <div class="wa-notif-controls">
                            <div class="wa-notif-field">
                                <label class="wa-label"><?php esc_html_e('Message Template', 'wapid-automation-for-woocommerce'); ?></label>
                                <select class="wa-template-select" name="event_configs[<?php echo esc_attr($event_key); ?>][template_id]">
                                    <option value=""><?php esc_html_e('Use fallback text only', 'wapid-automation-for-woocommerce'); ?></option>
                                    <?php foreach ($templates as $template) : ?>
                                        <?php
                                        $template_id = (string) ($template['id'] ?? '');
                                        if ($template_id === '') {
                                            continue;
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($template_id); ?>" <?php selected($saved_template, $template_id); ?>>
                                            <?php echo esc_html(($template['name'] ?? __('Untitled', 'wapid-automation-for-woocommerce')) . ' (' . ($template['category'] ?? __('General', 'wapid-automation-for-woocommerce')) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="wa-notif-actions">
                                <button type="button" class="button button-secondary wa-toggle-fallback" aria-expanded="<?php echo $fallback_open ? 'true' : 'false'; ?>">
                                    <?php esc_html_e('Customize fallback message', 'wapid-automation-for-woocommerce'); ?>
                                </button>
                                <button type="button" class="button wa-remove-row"><?php esc_html_e('Delete', 'wapid-automation-for-woocommerce'); ?></button>
                            </div>
                        </div>
                        <div class="wa-fallback-wrap <?php echo $fallback_open ? 'is-open' : ''; ?>">
                            <label class="wa-label"><?php esc_html_e('Fallback Message (optional)', 'wapid-automation-for-woocommerce'); ?></label>
                            <textarea rows="3" class="large-text" name="event_configs[<?php echo esc_attr($event_key); ?>][fallback]" placeholder="<?php esc_attr_e('Hi {{customer_name}}, your order #{{order_id}} is now {{order_status}}.', 'wapid-automation-for-woocommerce'); ?>"><?php echo esc_textarea($saved_fallback); ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</form>

<?php whatsapp_automation_admin_shell_end(); ?>
