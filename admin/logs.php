<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Unauthorized', 'wapid-automation-for-woocommerce'));
}
require_once WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/partials/shell.php';

$client = whatsapp_automation_get_api_client();
$selected_instance = get_option('whatsapp_automation_instance_id', '');
$per_page = 10;
$backend_page = isset($_GET['wa_backend_page']) ? max(1, absint(wp_unslash($_GET['wa_backend_page']))) : 1;
$local_page = isset($_GET['wa_local_page']) ? max(1, absint(wp_unslash($_GET['wa_local_page']))) : 1;
$backend_offset = ($backend_page - 1) * $per_page;
$local_offset = ($local_page - 1) * $per_page;

$messages_response = $client->is_authenticated() ? $client->get_messages($selected_instance, $per_page, $backend_offset) : new WP_Error('wa_no_auth', '');
$messages = !is_wp_error($messages_response) ? $client->extract_items($messages_response) : array();
$messages_error = is_wp_error($messages_response) ? $messages_response->get_error_message() : '';
$backend_total = null;
$backend_has_more = null;
$backend_pagination_signal = '';
if (!is_wp_error($messages_response) && is_array($messages_response)) {
    $total_candidates = array(
        $messages_response['total'] ?? null,
        $messages_response['count'] ?? null,
        $messages_response['meta']['total'] ?? null,
        $messages_response['pagination']['total'] ?? null,
        $messages_response['data']['total'] ?? null,
        $messages_response['data']['count'] ?? null,
        $messages_response['data']['meta']['total'] ?? null,
        $messages_response['data']['pagination']['total'] ?? null,
    );
    foreach ($total_candidates as $candidate) {
        if (is_numeric($candidate)) {
            $backend_total = max(0, (int) $candidate);
            break;
        }
    }

    $has_more_candidates = array(
        $messages_response['has_more'] ?? null,
        $messages_response['hasMore'] ?? null,
        $messages_response['pagination']['has_more'] ?? null,
        $messages_response['pagination']['hasMore'] ?? null,
        $messages_response['data']['has_more'] ?? null,
        $messages_response['data']['hasMore'] ?? null,
        $messages_response['data']['pagination']['has_more'] ?? null,
        $messages_response['data']['pagination']['hasMore'] ?? null,
    );
    foreach ($has_more_candidates as $candidate) {
        if (is_bool($candidate)) {
            $backend_has_more = $candidate;
            break;
        }
    }
}

if ($backend_has_more === null && $backend_total !== null) {
    $backend_has_more = (($backend_offset + count($messages)) < $backend_total);
}

if ($backend_has_more === null && !is_wp_error($messages_response) && $client->is_authenticated()) {
    $probe_response = $client->get_messages($selected_instance, 1, $backend_offset + $per_page);
    if (!is_wp_error($probe_response)) {
        $probe_items = $client->extract_items($probe_response);
        $backend_has_more = !empty($probe_items);
        $backend_pagination_signal = $backend_has_more
            ? __('Pagination check: next page returned records.', 'wapid-automation-for-woocommerce')
            : __('Pagination check: next page returned no records.', 'wapid-automation-for-woocommerce');
    } else {
        $backend_pagination_signal = __('Pagination check could not be completed.', 'wapid-automation-for-woocommerce');
    }
}

$backend_total_pages = ($backend_total !== null && $backend_total > 0) ? max(1, (int) ceil($backend_total / $per_page)) : null;
$backend_by_id = array();
foreach ($messages as $msg) {
    $mid = isset($msg['id']) ? (string) $msg['id'] : '';
    if ($mid !== '') {
        $backend_by_id[$mid] = $msg;
    }
}
$local_total = \WhatsAppAutomation\MessageHistory::count_all();
$local_total_pages = max(1, (int) ceil($local_total / $per_page));
if ($local_page > $local_total_pages) {
    $local_page = $local_total_pages;
    $local_offset = ($local_page - 1) * $per_page;
}
$local_message_history = \WhatsAppAutomation\MessageHistory::latest_paginated($per_page, $local_offset);
$local_logs = \WhatsAppAutomation\Logger::get_logs(30);
$local_log_file = \WhatsAppAutomation\Logger::get_log_file_path();
$logs_page_url = admin_url('admin.php?page=wapid-automation-for-woocommerce-logs');
$backend_has_prev = ($backend_page > 1);
$backend_has_next = false;
if ($backend_total_pages !== null) {
    $backend_has_next = ($backend_page < $backend_total_pages);
} elseif ($backend_has_more === true) {
    $backend_has_next = true;
}
$local_has_prev = ($local_page > 1);
$local_has_next = ($local_page < $local_total_pages);
$build_page_items = static function($current, $total) {
    $current = max(1, (int) $current);
    $total = max(1, (int) $total);
    if ($total <= 7) {
        return range(1, $total);
    }

    $items = array(1);
    $start = max(2, $current - 1);
    $end = min($total - 1, $current + 1);

    if ($start > 2) {
        $items[] = 'dots';
    }

    for ($i = $start; $i <= $end; $i++) {
        $items[] = $i;
    }

    if ($end < ($total - 1)) {
        $items[] = 'dots';
    }

    $items[] = $total;
    return $items;
};

$backend_page_items = array();
if ($backend_total_pages !== null && $backend_total_pages > 1) {
    $backend_page_items = $build_page_items($backend_page, $backend_total_pages);
}
$local_page_items = array();
if ($local_total_pages > 1) {
    $local_page_items = $build_page_items($local_page, $local_total_pages);
}
whatsapp_automation_admin_shell_start(
    __('Logs & Test Message', 'wapid-automation-for-woocommerce'),
    __('Live message history, manual tests aur plugin diagnostics.', 'wapid-automation-for-woocommerce')
);
?>

    <div class="wa-grid wa-grid-2">
        <section class="wa-card">
            <h2><?php esc_html_e('Send Test Message', 'wapid-automation-for-woocommerce'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('whatsapp_automation_send_test'); ?>
                <input type="hidden" name="action" value="whatsapp_automation_send_test">
                <p><input class="regular-text" type="text" name="phone" placeholder="<?php esc_attr_e('Phone number with country code', 'wapid-automation-for-woocommerce'); ?>" required></p>
                <p><textarea class="large-text" rows="4" name="message" placeholder="<?php esc_attr_e('Test message text', 'wapid-automation-for-woocommerce'); ?>" required></textarea></p>
                <?php submit_button(__('Send Test', 'wapid-automation-for-woocommerce')); ?>
            </form>
        </section>

        <section class="wa-card">
            <h2><?php esc_html_e('Backend Message History', 'wapid-automation-for-woocommerce'); ?></h2>
            <?php if ($messages_error !== '') : ?>
                <p><?php echo esc_html($messages_error); ?></p>
            <?php endif; ?>
            <?php if ($backend_pagination_signal !== '') : ?>
                <p><?php echo esc_html($backend_pagination_signal); ?></p>
            <?php endif; ?>
            <?php if (empty($messages)) : ?>
                <p><?php esc_html_e('No messages found.', 'wapid-automation-for-woocommerce'); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('To', 'wapid-automation-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Status', 'wapid-automation-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Message', 'wapid-automation-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Date', 'wapid-automation-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg) : ?>
                            <tr>
                                <td><?php echo esc_html($msg['recipient_phone'] ?? ''); ?></td>
                                <td><?php echo esc_html($msg['status'] ?? ''); ?></td>
                                <td><?php echo esc_html(wp_trim_words($msg['message_text'] ?? '', 16)); ?></td>
                                <td><?php echo esc_html($msg['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="wa-pagination">
                    <div class="wa-pagination__controls">
                        <?php if ($backend_has_prev) : ?>
                            <a class="wa-page-btn" href="<?php echo esc_url(add_query_arg(array('wa_backend_page' => $backend_page - 1, 'wa_local_page' => $local_page), $logs_page_url)); ?>">
                                <?php esc_html_e('Previous', 'wapid-automation-for-woocommerce'); ?>
                            </a>
                        <?php else : ?>
                            <span class="wa-page-btn is-disabled"><?php esc_html_e('Previous', 'wapid-automation-for-woocommerce'); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($backend_page_items)) : ?>
                            <?php foreach ($backend_page_items as $item) : ?>
                                <?php if ($item === 'dots') : ?>
                                    <span class="wa-page-dots" aria-hidden="true">...</span>
                                <?php elseif ((int) $item === $backend_page) : ?>
                                    <span class="wa-page-btn is-current" aria-current="page"><?php echo esc_html((string) $item); ?></span>
                                <?php else : ?>
                                    <a class="wa-page-btn" href="<?php echo esc_url(add_query_arg(array('wa_backend_page' => (int) $item, 'wa_local_page' => $local_page), $logs_page_url)); ?>">
                                        <?php echo esc_html((string) $item); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($backend_has_next) : ?>
                            <a class="wa-page-btn" href="<?php echo esc_url(add_query_arg(array('wa_backend_page' => $backend_page + 1, 'wa_local_page' => $local_page), $logs_page_url)); ?>">
                                <?php esc_html_e('Next', 'wapid-automation-for-woocommerce'); ?>
                            </a>
                        <?php else : ?>
                            <span class="wa-page-btn is-disabled"><?php esc_html_e('Next', 'wapid-automation-for-woocommerce'); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="wa-page-meta">
                        <?php
                        if ($backend_total_pages !== null) {
                            echo esc_html(sprintf(__('Page %1$d of %2$d', 'wapid-automation-for-woocommerce'), $backend_page, $backend_total_pages));
                        } else {
                            echo esc_html(sprintf(__('Page %d', 'wapid-automation-for-woocommerce'), $backend_page));
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="wa-card">
        <h2><?php esc_html_e('WordPress Local Message History', 'wapid-automation-for-woocommerce'); ?></h2>
        <?php if (empty($local_message_history)) : ?>
            <p><?php esc_html_e('No local history found yet.', 'wapid-automation-for-woocommerce'); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Source', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Event', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('To', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Message', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Backend', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Backend ID', 'wapid-automation-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Error', 'wapid-automation-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($local_message_history as $row) : ?>
                        <?php
                        $backend_id = (string) ($row['backend_message_id'] ?? '');
                        $resolved_backend_status = (string) ($row['backend_status'] ?? '');
                        if ($backend_id !== '' && isset($backend_by_id[$backend_id]['status'])) {
                            $resolved_backend_status = (string) $backend_by_id[$backend_id]['status'];
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html((string) ($row['created_at'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($row['source'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($row['event_type'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($row['recipient_phone'] ?? '')); ?></td>
                            <td><?php echo esc_html(wp_trim_words((string) ($row['message_text'] ?? ''), 14)); ?></td>
                            <td><?php echo esc_html($resolved_backend_status); ?></td>
                            <td><?php echo esc_html($backend_id); ?></td>
                            <td><?php echo esc_html((string) ($row['error_message'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="wa-pagination">
                <div class="wa-pagination__controls">
                    <?php if ($local_has_prev) : ?>
                        <a class="wa-page-btn" href="<?php echo esc_url(add_query_arg(array('wa_local_page' => $local_page - 1, 'wa_backend_page' => $backend_page), $logs_page_url)); ?>">
                            <?php esc_html_e('Previous', 'wapid-automation-for-woocommerce'); ?>
                        </a>
                    <?php else : ?>
                        <span class="wa-page-btn is-disabled"><?php esc_html_e('Previous', 'wapid-automation-for-woocommerce'); ?></span>
                    <?php endif; ?>
                    <?php foreach ($local_page_items as $item) : ?>
                        <?php if ($item === 'dots') : ?>
                            <span class="wa-page-dots" aria-hidden="true">...</span>
                        <?php elseif ((int) $item === $local_page) : ?>
                            <span class="wa-page-btn is-current" aria-current="page"><?php echo esc_html((string) $item); ?></span>
                        <?php else : ?>
                            <a class="wa-page-btn" href="<?php echo esc_url(add_query_arg(array('wa_local_page' => (int) $item, 'wa_backend_page' => $backend_page), $logs_page_url)); ?>">
                                <?php echo esc_html((string) $item); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($local_has_next) : ?>
                        <a class="wa-page-btn" href="<?php echo esc_url(add_query_arg(array('wa_local_page' => $local_page + 1, 'wa_backend_page' => $backend_page), $logs_page_url)); ?>">
                            <?php esc_html_e('Next', 'wapid-automation-for-woocommerce'); ?>
                        </a>
                    <?php else : ?>
                        <span class="wa-page-btn is-disabled"><?php esc_html_e('Next', 'wapid-automation-for-woocommerce'); ?></span>
                    <?php endif; ?>
                </div>
                <span class="wa-page-meta">
                    <?php echo esc_html(sprintf(__('Page %1$d of %2$d', 'wapid-automation-for-woocommerce'), $local_page, $local_total_pages)); ?>
                </span>
            </div>
        <?php endif; ?>
    </section>

    <section class="wa-card">
        <h2><?php esc_html_e('Plugin Local Logs', 'wapid-automation-for-woocommerce'); ?></h2>
        <?php if (empty($local_logs)) : ?>
            <p><?php esc_html_e('No plugin log lines yet. New entries appear when plugin events run (test send, WooCommerce automation, auth/actions).', 'wapid-automation-for-woocommerce'); ?></p>
            <p><code><?php echo esc_html($local_log_file); ?></code></p>
        <?php else : ?>
            <pre class="wa-pre"><?php echo esc_html(implode('', $local_logs)); ?></pre>
            <p><code><?php echo esc_html($local_log_file); ?></code></p>
        <?php endif; ?>
    </section>
<?php whatsapp_automation_admin_shell_end(); ?>
