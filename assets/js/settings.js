(function() {
    const catalog = wapidAutomationSettings.catalog;
    const labels = wapidAutomationSettings.labels;
    const templates = wapidAutomationSettings.templates;
    const wcIconUrl = wapidAutomationSettings.wcIconUrl;
    const waIconUrl = wapidAutomationSettings.waIconUrl;
    const rows = document.getElementById('wa_event_rows');
    const eventSelect = document.getElementById('wa_event_key');
    const addBtn = document.getElementById('wa_add_event_btn');

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function allEventsFlat() {
        const list = [];
        Object.keys(catalog).forEach(function(category) {
            Object.keys(catalog[category]).forEach(function(key) {
                list.push({
                    key: key,
                    category: category,
                    label: catalog[category][key]
                });
            });
        });
        return list;
    }

    function existingKeys() {
        return Array.from(rows.querySelectorAll('[data-event-key]')).map(function(el) {
            return el.getAttribute('data-event-key');
        });
    }

    function refreshEventOptions() {
        const used = existingKeys();
        eventSelect.innerHTML = '<option value="">' + wapidAutomationSettings.searchSelectEvent + '</option>';

        allEventsFlat().forEach(function(item) {
            if (used.indexOf(item.key) !== -1) {
                return;
            }

            const opt = document.createElement('option');
            opt.value = item.key;
            opt.textContent = '[' + item.category.charAt(0).toUpperCase() + item.category.slice(1) + '] ' + item.label;
            eventSelect.appendChild(opt);
        });
    }

    function templateOptionsHtml() {
        let html = '<option value="">' + wapidAutomationSettings.useFallbackTextOnly + '</option>';
        templates.forEach(function(template) {
            const templateId = String(template.id || '');
            if (!templateId) {
                return;
            }
            const label = (template.name || wapidAutomationSettings.untitled) + ' (' + (template.category || wapidAutomationSettings.general) + ')';
            html += '<option value="' + escapeHtml(templateId) + '">' + escapeHtml(label) + '</option>';
        });
        return html;
    }

    function setFallbackOpen(row, open) {
        const panel = row.querySelector('.wa-fallback-wrap');
        const toggle = row.querySelector('.wa-toggle-fallback');
        if (!panel || !toggle) {
            return;
        }
        panel.classList.toggle('is-open', !!open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function syncFallbackVisibility(row) {
        const select = row.querySelector('.wa-template-select');
        const textarea = row.querySelector('textarea');
        if (!select || !textarea) {
            return;
        }
        if (select.value === '' && (textarea.value || '').trim() === '') {
            setFallbackOpen(row, true);
        }
    }

    function buildRowHtml(eventKey) {
        const label = labels[eventKey].label;
        const category = labels[eventKey].category;
        return '' +
            '<div class="wa-notif-top">' +
                '<div>' +
                    '<div class="wa-flow">' +
                        '<span class="wa-flow-badge-icon"><img src="' + escapeHtml(wcIconUrl) + '" alt="' + wapidAutomationSettings.woocommerce + '"></span>' +
                        '<span class="wa-flow-arrow">&gt;</span>' +
                        '<span class="wa-flow-badge-icon"><img src="' + escapeHtml(waIconUrl) + '" alt="' + wapidAutomationSettings.whatsapp + '"></span>' +
                        '<h3 class="wa-notif-title">' + escapeHtml(label) + '</h3>' +
                    '</div>' +
                    '<p class="wa-notif-meta">' + escapeHtml(category.charAt(0).toUpperCase() + category.slice(1)) + ' ' + wapidAutomationSettings.event + '</p>' +
                    '<input type="hidden" name="event_configs[' + escapeHtml(eventKey) + '][key]" value="' + escapeHtml(eventKey) + '">' +
                '</div>' +
                '<label class="wa-toggle">' +
                    '<input type="hidden" name="event_configs[' + escapeHtml(eventKey) + '][enabled]" value="0">' +
                    '<input type="checkbox" name="event_configs[' + escapeHtml(eventKey) + '][enabled]" value="1" checked>' +
                    '<span class="wa-toggle-slider" aria-hidden="true"></span>' +
                    '<span class="wa-toggle-label">' + wapidAutomationSettings.enabled + '</span>' +
                '</label>' +
            '</div>' +
            '<div class="wa-notif-controls">' +
                '<div class="wa-notif-field">' +
                    '<label class="wa-label">' + wapidAutomationSettings.messageTemplate + '</label>' +
                    '<select class="wa-template-select" name="event_configs[' + escapeHtml(eventKey) + '][template_id]">' + templateOptionsHtml() + '</select>' +
                '</div>' +
                '<div class="wa-notif-actions">' +
                    '<button type="button" class="button button-secondary wa-toggle-fallback" aria-expanded="true">' + wapidAutomationSettings.customizeFallbackMessage + '</button>' +
                    '<button type="button" class="button wa-remove-row">' + wapidAutomationSettings.delete + '</button>' +
                '</div>' +
            '</div>' +
            '<div class="wa-fallback-wrap is-open">' +
                '<label class="wa-label">' + wapidAutomationSettings.fallbackMessageOptional + '</label>' +
                '<textarea rows="3" class="large-text" name="event_configs[' + escapeHtml(eventKey) + '][fallback]" placeholder="' + wapidAutomationSettings.placeholder + '"></textarea>' +
            '</div>';
    }

    function addRow(eventKey) {
        if (!labels[eventKey]) {
            return;
        }
        if (existingKeys().indexOf(eventKey) !== -1) {
            alert(wapidAutomationSettings.eventAlreadyAdded);
            return;
        }

        const row = document.createElement('div');
        row.className = 'wa-notif-row';
        row.setAttribute('data-event-key', eventKey);
        row.innerHTML = buildRowHtml(eventKey);

        rows.appendChild(row);
        eventSelect.value = '';
        refreshEventOptions();
        syncFallbackVisibility(row);
    }

    addBtn.addEventListener('click', function() {
        const key = eventSelect.value;
        if (key) {
            addRow(key);
        }
    });

    rows.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.wa-remove-row');
        if (removeBtn) {
            const row = removeBtn.closest('.wa-notif-row');
            if (row) {
                row.remove();
                refreshEventOptions();
            }
            return;
        }

        const toggleBtn = e.target.closest('.wa-toggle-fallback');
        if (toggleBtn) {
            const row = toggleBtn.closest('.wa-notif-row');
            if (row) {
                const panel = row.querySelector('.wa-fallback-wrap');
                setFallbackOpen(row, !(panel && panel.classList.contains('is-open')));
            }
        }
    });

    rows.addEventListener('change', function(e) {
        if (!e.target.classList.contains('wa-template-select')) {
            return;
        }
        const row = e.target.closest('.wa-notif-row');
        if (!row) {
            return;
        }
        const fallbackTextarea = row.querySelector('textarea');
        const hasFallbackText = fallbackTextarea && (fallbackTextarea.value || '').trim() !== '';

        if (e.target.value !== '' && !hasFallbackText) {
            setFallbackOpen(row, false);
        }

        if (e.target.value === '') {
            setFallbackOpen(row, true);
        }
    });

    Array.from(rows.querySelectorAll('.wa-notif-row')).forEach(function(row) {
        syncFallbackVisibility(row);
    });

    refreshEventOptions();
})();
