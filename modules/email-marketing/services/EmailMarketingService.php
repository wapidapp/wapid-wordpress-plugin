<?php

namespace WhatsAppAutomation\Modules\EmailMarketing\Services;

use WhatsAppAutomation\APIClient;
use WhatsAppAutomation\Modules\EmailMarketing\Models\FeatureGate;

class EmailMarketingService {
    private $client;

    public function __construct(APIClient $client = null) {
        $this->client = $client ?: new APIClient();
    }

    public function get_dashboard_data() {
        $status_response = $this->client->is_authenticated()
            ? $this->client->get_email_feature_status()
            : array();

        $status = FeatureGate::normalize_status($status_response);

        if (!$status['enabled'] || !$this->client->is_authenticated()) {
            return array(
                'enabled' => false,
                'upgrade_url' => $status['upgrade_url'],
                'campaigns' => array(),
                'templates' => array(),
                'contacts' => array(),
            );
        }

        $campaigns = $this->client->extract_items($this->client->get_email_campaigns());
        $templates = $this->client->extract_items($this->client->get_email_templates());
        $contacts = $this->client->extract_items($this->client->get_email_contacts(20, 0));

        return array(
            'enabled' => true,
            'upgrade_url' => $status['upgrade_url'],
            'campaigns' => is_array($campaigns) ? $campaigns : array(),
            'templates' => is_array($templates) ? $templates : array(),
            'contacts' => is_array($contacts) ? $contacts : array(),
        );
    }
}
