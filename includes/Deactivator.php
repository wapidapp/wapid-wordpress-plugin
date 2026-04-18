<?php

namespace WhatsAppAutomation;

class Deactivator {
    public static function deactivate() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('manage_whatsapp_automation');
        }
    }
}
