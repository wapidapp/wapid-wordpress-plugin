<?php

namespace WhatsAppAutomation\Modules\EmailMarketing\Models;

class FeatureGate {
    public static function normalize_status($response) {
        if (!is_array($response)) {
            return array(
                'enabled' => false,
                'upgrade_url' => 'https://wapid.net/pricing',
                'raw' => array(),
            );
        }

        $data = isset($response['data']) && is_array($response['data'])
            ? $response['data']
            : $response;

        $enabled = !empty($data['email_marketing']);
        $upgrade_url = isset($data['upgrade_url']) && is_string($data['upgrade_url']) && $data['upgrade_url'] !== ''
            ? $data['upgrade_url']
            : 'https://wapid.net/pricing';
        if (strpos($upgrade_url, 'http') !== 0) {
            $upgrade_url = 'https://wapid.net' . '/' . ltrim($upgrade_url, '/');
        }

        return array(
            'enabled' => $enabled,
            'upgrade_url' => $upgrade_url,
            'raw' => $data,
        );
    }
}
