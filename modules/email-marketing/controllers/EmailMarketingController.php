<?php

namespace WhatsAppAutomation\Modules\EmailMarketing\Controllers;

use WhatsAppAutomation\Modules\EmailMarketing\Services\EmailMarketingService;

class EmailMarketingController {
    private $service;

    public function __construct(EmailMarketingService $service = null) {
        $this->service = $service ?: new EmailMarketingService();
    }

    public function render() {
        $view = $this->service->get_dashboard_data();
        require WHATSAPP_AUTOMATION_PLUGIN_DIR . 'admin/email-marketing.php';
    }
}
