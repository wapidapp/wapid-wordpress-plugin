<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Unauthorized', 'wapid-automation-for-woocommerce'));
}
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/partials/shell.php';

$client = whatsapp_automation_get_api_client();
$instances_response = $client->is_authenticated() ? $client->get_instances() : new WP_Error('wa_no_auth', '');
$instances = !is_wp_error($instances_response) ? $client->extract_items($instances_response) : array();
$instances_error = is_wp_error($instances_response) ? $instances_response->get_error_message() : '';
$selected_instance = get_option('whatsapp_automation_instance_id', '');
whatsapp_automation_admin_shell_start(
    __('Messaging Instances', 'wapid-automation-for-woocommerce'),
    __('Select and manage your backend instances from WordPress.', 'wapid-automation-for-woocommerce')
);
?>

    <div class="wa-grid wa-grid-1">
        <section class="wa-card">
            <h2><?php esc_html_e('Select Active Instance', 'wapid-automation-for-woocommerce'); ?></h2>
            <?php if ($instances_error !== '') : ?>
                <p><?php echo esc_html($instances_error); ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('whatsapp_automation_select_instance'); ?>
                <input type="hidden" name="action" value="whatsapp_automation_select_instance">
                <select name="instance_id" class="regular-text">
                    <option value=""><?php esc_html_e('Choose instance', 'wapid-automation-for-woocommerce'); ?></option>
                    <?php foreach ($instances as $instance) : ?>
                        <option value="<?php echo esc_attr($instance['id']); ?>" <?php selected($selected_instance, $instance['id']); ?>>
                            <?php echo esc_html(($instance['name'] ?? $instance['id']) . ' [' . ($instance['status'] ?? 'unknown') . ']'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php submit_button(__('Save Active Instance', 'wapid-automation-for-woocommerce')); ?>
            </form>
        </section>
    </div>

    <section class="wa-card">
        <h2><?php esc_html_e('Instance Controls', 'wapid-automation-for-woocommerce'); ?></h2>
        <?php if ($instances_error !== '') : ?>
            <p><?php echo esc_html($instances_error); ?></p>
        <?php endif; ?>
        <?php if (empty($instances)) : ?>
            <p><?php esc_html_e('No instances found.', 'wapid-automation-for-woocommerce'); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Name', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Phone', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Status', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Action', 'wapid-automation-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instances as $instance) : ?>
                        <tr>
                            <td><?php echo esc_html($instance['id'] ?? ''); ?></td>
                            <td><?php echo esc_html($instance['name'] ?? ''); ?></td>
                            <td><?php echo esc_html($instance['phone_number'] ?? '-'); ?></td>
                            <td><?php echo esc_html($instance['status'] ?? 'unknown'); ?></td>
                            <td>
                                <?php $is_connected = (($instance['status'] ?? '') === 'connected'); ?>
                                <?php if ($is_connected) : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                        <?php wp_nonce_field('whatsapp_automation_instance_action'); ?>
                                        <input type="hidden" name="action" value="whatsapp_automation_instance_action">
                                        <input type="hidden" name="instance_id" value="<?php echo esc_attr($instance['id'] ?? ''); ?>">
                                        <input type="hidden" name="instance_action" value="stop">
                                        <button class="button button-small" type="submit"><?php esc_html_e('Stop', 'wapid-automation-for-woocommerce'); ?></button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                        <?php wp_nonce_field('whatsapp_automation_instance_action'); ?>
                                        <input type="hidden" name="action" value="whatsapp_automation_instance_action">
                                        <input type="hidden" name="instance_id" value="<?php echo esc_attr($instance['id'] ?? ''); ?>">
                                        <input type="hidden" name="instance_action" value="restart">
                                        <button class="button button-small" type="submit"><?php esc_html_e('Restart', 'wapid-automation-for-woocommerce'); ?></button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                        <?php wp_nonce_field('whatsapp_automation_instance_action'); ?>
                                        <input type="hidden" name="action" value="whatsapp_automation_instance_action">
                                        <input type="hidden" name="instance_id" value="<?php echo esc_attr($instance['id'] ?? ''); ?>">
                                        <input type="hidden" name="instance_action" value="logout">
                                        <button class="button button-small button-link-delete" type="submit"><?php esc_html_e('Logout', 'wapid-automation-for-woocommerce'); ?></button>
                                    </form>
                                <?php else : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                        <?php wp_nonce_field('whatsapp_automation_instance_action'); ?>
                                        <input type="hidden" name="action" value="whatsapp_automation_instance_action">
                                        <input type="hidden" name="instance_id" value="<?php echo esc_attr($instance['id'] ?? ''); ?>">
                                        <input type="hidden" name="instance_action" value="start">
                                        <button class="button button-small" type="submit"><?php esc_html_e('Start', 'wapid-automation-for-woocommerce'); ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php whatsapp_automation_admin_shell_end(); ?>
