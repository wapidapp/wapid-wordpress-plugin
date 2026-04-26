<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Unauthorized', 'wapid-automation-for-woocommerce'));
}

require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/partials/shell.php';

$enabled = !empty($view['enabled']);
$upgrade_url = !empty($view['upgrade_url']) ? $view['upgrade_url'] : 'https://wapid.net/pricing';
$campaigns = is_array($view['campaigns'] ?? null) ? $view['campaigns'] : array();
$templates = is_array($view['templates'] ?? null) ? $view['templates'] : array();
$contacts = is_array($view['contacts'] ?? null) ? $view['contacts'] : array();

whatsapp_automation_admin_shell_start(
    __('Email Marketing', 'wapid-automation-for-woocommerce'),
    __('Create campaigns, manage contacts, and build email templates with plan-based access.', 'wapid-automation-for-woocommerce')
);
?>

<?php if (!$enabled) : ?>
<section class="wa-card">
    <h2><?php esc_html_e('Email Marketing is Locked', 'wapid-automation-for-woocommerce'); ?></h2>
    <p><?php esc_html_e('Your current plan does not include Email Marketing. Upgrade to unlock campaigns, templates, and contact sync.', 'wapid-automation-for-woocommerce'); ?></p>
    <a class="button button-primary" href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener noreferrer">
        <?php esc_html_e('Upgrade Plan', 'wapid-automation-for-woocommerce'); ?>
    </a>
</section>
<?php else : ?>
<div class="wa-grid wa-grid-3">
    <section class="wa-card">
        <h2><?php esc_html_e('Campaigns', 'wapid-automation-for-woocommerce'); ?></h2>
        <p><?php echo esc_html(sprintf(_n('%d campaign available', '%d campaigns available', count($campaigns), 'wapid-automation-for-woocommerce'), count($campaigns))); ?></p>
        <ul>
            <?php foreach (array_slice($campaigns, 0, 8) as $campaign) : ?>
                <li>
                    <strong><?php echo esc_html($campaign['name'] ?? __('Untitled Campaign', 'wapid-automation-for-woocommerce')); ?></strong>
                    <span> - <?php echo esc_html($campaign['status'] ?? 'draft'); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="wa-card">
        <h2><?php esc_html_e('Templates', 'wapid-automation-for-woocommerce'); ?></h2>
        <p><?php echo esc_html(sprintf(_n('%d template available', '%d templates available', count($templates), 'wapid-automation-for-woocommerce'), count($templates))); ?></p>
        <ul>
            <?php foreach (array_slice($templates, 0, 8) as $template) : ?>
                <li>
                    <strong><?php echo esc_html($template['name'] ?? __('Untitled Template', 'wapid-automation-for-woocommerce')); ?></strong>
                    <span> - <?php echo esc_html($template['category'] ?? 'campaign'); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="wa-card">
        <h2><?php esc_html_e('Contacts', 'wapid-automation-for-woocommerce'); ?></h2>
        <p><?php echo esc_html(sprintf(_n('%d contact synced', '%d contacts synced', count($contacts), 'wapid-automation-for-woocommerce'), count($contacts))); ?></p>
        <ul>
            <?php foreach (array_slice($contacts, 0, 8) as $contact) : ?>
                <li>
                    <strong><?php echo esc_html($contact['email'] ?? '-'); ?></strong>
                    <?php if (!empty($contact['name'])) : ?>
                        <span> - <?php echo esc_html($contact['name']); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="wa-card" style="margin-top:16px;">
    <h2><?php esc_html_e('Compliance & Deliverability', 'wapid-automation-for-woocommerce'); ?></h2>
    <ul>
        <li><?php esc_html_e('Every marketing email includes a one-click unsubscribe link.', 'wapid-automation-for-woocommerce'); ?></li>
        <li><?php esc_html_e('Only subscribed contacts are queued for send.', 'wapid-automation-for-woocommerce'); ?></li>
        <li><?php esc_html_e('Batch queue sending prevents blocking and reduces spam risk.', 'wapid-automation-for-woocommerce'); ?></li>
    </ul>
</section>
<?php endif; ?>

<?php whatsapp_automation_admin_shell_end(); ?>
