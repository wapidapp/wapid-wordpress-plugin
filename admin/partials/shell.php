<?php
if (!defined('ABSPATH')) {
    exit;
}

function whatsapp_automation_admin_shell_start($title, $subtitle = '') {
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'wapid-automation-for-woocommerce';
    $is_connected = !empty(get_option('whatsapp_automation_api_key', '')) || (!empty(get_option('whatsapp_automation_access_token', '')) && !empty(get_option('whatsapp_automation_refresh_token', '')));
    $groups = array(
        'GENERAL' => array(
            array('slug' => 'wapid-automation-for-woocommerce', 'label' => __('Dashboard', 'wapid-automation-for-woocommerce')),
            array('slug' => 'wapid-automation-for-woocommerce-instances', 'label' => __('Sender Settings', 'wapid-automation-for-woocommerce')),
            array('slug' => 'wapid-automation-for-woocommerce-templates', 'label' => __('Templates', 'wapid-automation-for-woocommerce')),
        ),
        'ENGAGEMENT' => array(
            array('slug' => 'wapid-automation-for-woocommerce-settings', 'label' => __('Automated Notifications', 'wapid-automation-for-woocommerce')),
        ),
        'OTHER' => array(
            array('slug' => 'wapid-automation-for-woocommerce-logs', 'label' => __('Logs', 'wapid-automation-for-woocommerce')),
        ),
    );
    ?>
        <div class="wrap wa-wrap">
            <div class="wa-shell">
                <aside class="wa-sidebar">
                    <div class="wa-brand">
                        <img src="<?php echo esc_url(WHATSAPP_AUTOMATION_PLUGIN_URL . 'admin/assets/logo-wapid-mark.svg'); ?>" alt="Wapid logo">
                        <div>
                            <strong>Wapid</strong>
                            <span><?php esc_html_e('Automation Suite', 'wapid-automation-for-woocommerce'); ?></span>
                        </div>
                    </div>
                    <nav class="wa-nav">
                        <?php foreach ($groups as $group_title => $items) : ?>
                            <div class="wa-nav-group">
                                <p class="wa-nav-title"><?php echo esc_html($group_title); ?></p>
                                <?php foreach ($items as $item) : ?>
                                    <?php
                                    $is_active = ($current_page === $item['slug']);
                                    $href = admin_url('admin.php?page=' . $item['slug']);
                                    ?>
                                    <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($href); ?>">
                                        <span><?php echo esc_html($item['label']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </nav>
                </aside>
                <main class="wa-main">
                    <details class="wa-mobile-nav">
                        <summary><?php esc_html_e('Open navigation', 'wapid-automation-for-woocommerce'); ?></summary>
                        <nav class="wa-mobile-nav-list">
                            <?php foreach ($groups as $group_title => $items) : ?>
                                <div class="wa-mobile-nav-group">
                                    <p class="wa-mobile-nav-title"><?php echo esc_html($group_title); ?></p>
                                    <?php foreach ($items as $item) : ?>
                                        <?php
                                        $is_active = ($current_page === $item['slug']);
                                        $href = admin_url('admin.php?page=' . $item['slug']);
                                        ?>
                                        <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($href); ?>">
                                            <span><?php echo esc_html($item['label']); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </nav>
                    </details>
                    <header class="wa-page-head">
                        <div class="wa-head-row">
                            <div>
                                <h1><?php echo esc_html($title); ?></h1>
                                <?php if ($subtitle !== '') : ?>
                                    <p><?php echo esc_html($subtitle); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="wa-head-actions">
                                <?php if ($is_connected) : ?>
                                    <span class="wa-conn-pill"><?php esc_html_e('Connected', 'wapid-automation-for-woocommerce'); ?></span>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wa-head-inline-form">
                                        <?php wp_nonce_field('whatsapp_automation_logout'); ?>
                                        <input type="hidden" name="action" value="whatsapp_automation_logout">
                                        <button type="submit" class="button wa-btn-disconnect" title="<?php esc_attr_e('Disconnect', 'wapid-automation-for-woocommerce'); ?>" aria-label="<?php esc_attr_e('Disconnect', 'wapid-automation-for-woocommerce'); ?>">
                                            <svg class="wa-icon-exit" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path fill="currentColor" d="M14.08 15.59L15.5 17l5-5l-5-5l-1.42 1.41L16.67 11H8v2h8.67zM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else : ?>
                                    <a class="button button-primary wa-btn-connect" href="<?php echo esc_url(admin_url('admin.php?page=wapid-automation-for-woocommerce-settings&wa_connect=1')); ?>">
                                        <?php esc_html_e('Connect', 'wapid-automation-for-woocommerce'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </header>
    <?php
}

function whatsapp_automation_admin_shell_end() {
    ?>
                </main>
            </div>
        </div>
    <?php
}
