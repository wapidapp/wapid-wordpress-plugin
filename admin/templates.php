<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Unauthorized', 'wapid-automation-for-woocommerce'));
}
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/partials/shell.php';

$client = whatsapp_automation_get_api_client();
$templates_response = $client->is_authenticated() ? $client->get_templates() : new WP_Error('wa_no_auth', '');
$templates = !is_wp_error($templates_response) ? $client->extract_items($templates_response) : array();
$templates_error = is_wp_error($templates_response) ? $templates_response->get_error_message() : '';
whatsapp_automation_admin_shell_start(
    __('Message Templates', 'wapid-automation-for-woocommerce'),
    __('Manage reusable templates for order lifecycle notifications.', 'wapid-automation-for-woocommerce')
);
?>

    <div class="wa-grid wa-grid-2">
        <section class="wa-card">
            <h2><?php esc_html_e('Create Template', 'wapid-automation-for-woocommerce'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('whatsapp_automation_save_template'); ?>
                <input type="hidden" name="action" value="whatsapp_automation_save_template">
                <p><input type="text" name="name" class="regular-text" placeholder="<?php esc_attr_e('Template name', 'wapid-automation-for-woocommerce'); ?>" required></p>
                <p><input type="text" name="category" class="regular-text" value="order_update" required></p>
                <p><textarea name="content" rows="6" class="large-text" placeholder="Hi {{customer_name}}, order #{{order_id}} update..." required></textarea></p>
                <?php submit_button(__('Create Template', 'wapid-automation-for-woocommerce')); ?>
            </form>
        </section>

        <section class="wa-card">
            <h2><?php esc_html_e('Available Templates', 'wapid-automation-for-woocommerce'); ?></h2>
            <?php if ($templates_error !== '') : ?>
                <p><?php echo esc_html($templates_error); ?></p>
            <?php endif; ?>
            <?php if (empty($templates)) : ?>
                <p><?php esc_html_e('No templates found or account is not connected.', 'wapid-automation-for-woocommerce'); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'wapid-automation-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Category', 'wapid-automation-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Message', 'wapid-automation-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Action', 'wapid-automation-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template) : ?>
                            <tr>
                                <td><?php echo esc_html($template['name'] ?? ''); ?></td>
                                <td><?php echo esc_html($template['category'] ?? ''); ?></td>
                                <td><?php echo esc_html(wp_trim_words($template['content'] ?? '', 18)); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('whatsapp_automation_delete_template'); ?>
                                        <input type="hidden" name="action" value="whatsapp_automation_delete_template">
                                        <input type="hidden" name="template_id" value="<?php echo esc_attr($template['id'] ?? ''); ?>">
                                        <button class="button button-secondary" type="submit"><?php esc_html_e('Delete', 'wapid-automation-for-woocommerce'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
<?php whatsapp_automation_admin_shell_end(); ?>
