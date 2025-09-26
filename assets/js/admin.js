jQuery(document).ready(function($) {
    const $recordsContainer = $('#wfs-records-container');
    const $loading = $('#wfs-loading');
    const statusMap = wfs_ajax.statuses || {};
    const fileCategories = wfs_ajax.file_categories || {};
    const hasI18n = typeof window !== 'undefined' && window.wp && wp.i18n && typeof wp.i18n.__ === 'function';
    const __ = (text, fallback) => hasI18n ? wp.i18n.__(text, 'workflow-system') : (fallback !== undefined ? fallback : text);

    function showToast(message, type = 'success') {
        const toastClass = type === 'error' ? 'wfs-toast error' : 'wfs-toast';
        const $toast = $('<div class="' + toastClass + '">' + message + '</div>');

        if ($('#wfs-toast-container').length === 0) {
            $('body').append('<div id="wfs-toast-container"></div>');
        }

        $('#wfs-toast-container').append($toast);
        setTimeout(() => {
            $toast.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
    }

    function formatPhone(phone) {
        const digits = (phone || '').replace(/[^0-9+]/g, '');
        let whatsapp = digits.replace(/[^0-9]/g, '');

        if (whatsapp.startsWith('00')) {
            whatsapp = whatsapp.substring(2);
        }
        if (whatsapp.startsWith('0')) {
            whatsapp = '9' + whatsapp;
        }

        return {
            tel: digits ? 'tel:' + digits : '',
            whatsapp: whatsapp ? 'https://wa.me/' + whatsapp : ''
        };
    }

    function escapeHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function renderDocChips(filesByCategory) {
        const chips = [];
        Object.keys(fileCategories).forEach((slug) => {
            const meta = fileCategories[slug];
            const files = filesByCategory[slug] ? filesByCategory[slug].files || [] : [];
            const hasFile = files.length > 0;
            chips.push(`
                <span class="wfs-doc-chip ${hasFile ? 'is-ready' : 'is-missing'}" data-category="${slug}">
                    <span class="wfs-doc-icon" aria-hidden="true">${escapeHtml(meta.icon || '📁')}</span>
                    <span class="wfs-doc-label">${escapeHtml(meta.label || slug)}</span>
                </span>
            `);
        });
        return chips.join('');
    }

    function renderDocumentsDetails(filesByCategory, canReview) {
        const cards = [];
        Object.keys(filesByCategory).forEach((slug) => {
            const item = filesByCategory[slug];
            const meta = item.meta || { icon: '📁', label: slug };
            const files = item.files || [];
            const hasFile = files.length > 0;
            let fileList = `<p class="wfs-documents-empty">${escapeHtml(__('Doküman eklenmedi.', 'Doküman eklenmedi.'))}</p>`;

            if (hasFile) {
                const fileItems = files.map((file) => {
                    const statusClass = 'is-' + (file.status || 'pending');
                    const selectHtml = canReview ? `
                        <select class="wfs-file-status-select" data-file-id="${file.id}">
                            <option value="pending" ${file.status === 'pending' ? 'selected' : ''}>${escapeHtml(wfs_ajax.strings.pending || __('Beklemede', 'Beklemede'))}</option>
                            <option value="approved" ${file.status === 'approved' ? 'selected' : ''}>${escapeHtml(wfs_ajax.strings.approved || __('Onaylı', 'Onaylı'))}</option>
                            <option value="rejected" ${file.status === 'rejected' ? 'selected' : ''}>${escapeHtml(wfs_ajax.strings.rejected || __('Reddedildi', 'Reddedildi'))}</option>
                        </select>` : '';
                    const safeHref = escapeHtml(file.file_url || file.file_path || '#');
                    return `
                        <li>
                            <a href="${safeHref}" target="_blank" rel="noopener" class="wfs-documents-link">${escapeHtml(file.file_name)}</a>
                            <span class="wfs-documents-status ${statusClass}">${escapeHtml((file.status || 'pending').charAt(0).toUpperCase() + (file.status || 'pending').slice(1))}</span>
                            ${selectHtml}
                        </li>`;
                }).join('');

                fileList = `<ul class="wfs-documents-list">${fileItems}</ul>`;
            }

            cards.push(`
                <div class="wfs-documents-card ${hasFile ? 'has-file' : 'no-file'}">
                    <div class="wfs-documents-card-header">
                        <span class="wfs-documents-icon" aria-hidden="true">${escapeHtml(meta.icon || '📁')}</span>
                        <span class="wfs-documents-title">${escapeHtml(meta.label || slug)}</span>
                    </div>
                    ${fileList}
                </div>
            `);
        });
        return cards.join('');
    }

    function renderPaymentSection(record, canAssign) {
        if (record.overall_status !== 'completed') {
            return '<p class="wfs-payment-hint">' + escapeHtml(__('Ödeme girişi sadece statü "Tamamlandı" olduğunda aktif olur.', 'Ödeme girişi sadece statü "Tamamlandı" olduğunda aktif olur.')) + '</p>';
        }
        const amount = parseFloat(record.payment_amount || 0);
        const formatted = amount ? amount.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
        return `
            <div class="wfs-payment-form">
                <label>${escapeHtml(__('Tahsil Edilecek Ücret', 'Tahsil Edilecek Ücret'))}</label>
                <div class="wfs-payment-controls">
                    <input type="text" class="wfs-payment-input" data-record-id="${record.id}" value="${escapeHtml(formatted)}" placeholder="35000">
                    ${canAssign ? `<button class="wfs-save-payment wfs-btn wfs-btn-primary" data-record-id="${record.id}">${escapeHtml(__('Kaydet', 'Kaydet'))}</button>` : ''}
                </div>
            </div>`;
    }

    function renderRecordDetails(record, filesByCategory) {
        const canAssign = record.can_assign !== undefined ? (record.can_assign === true || record.can_assign === '1') : !!wfs_ajax.can_assign;
        const canReview = record.can_review !== undefined ? (record.can_review === true || record.can_review === '1') : !!wfs_ajax.can_review;
        const status = statusMap[record.overall_status] || { label: record.overall_status, color: '#2563eb', bg: '#dbeafe' };
        const interviewRequired = parseInt(record.interview_required, 10) === 1;
        const interviewLabel = interviewRequired ? __('Evet', 'Evet') : __('Hayır', 'Hayır');
        const interviewDate = record.interview_at ? new Date(record.interview_at.replace(' ', 'T')) : null;
        const interviewDateText = interviewDate ? interviewDate.toLocaleString() : __('Belirtilmemiş', 'Belirtilmemiş');
        const interviewCompleted = parseInt(record.interview_completed, 10) === 1;
        const paymentHtml = renderPaymentSection(record, canAssign);

        return `
            <div class="wfs-details-grid">
                <section class="wfs-info-section">
                    <h4>👤 ${escapeHtml(__('Kişi Kartı', 'Kişi Kartı'))}</h4>
                    <ul class="wfs-info-list">
                        <li><strong>${escapeHtml(__('Ad Soyad', 'Ad Soyad'))}:</strong> ${escapeHtml((record.first_name || '') + ' ' + (record.last_name || ''))}</li>
                        ${record.phone ? `<li><strong>${escapeHtml(__('İletişim', 'İletişim'))}:</strong> <a href="tel:${escapeHtml(record.phone)}">${escapeHtml(record.phone)}</a></li>` : ''}
                        ${record.email ? `<li><strong>${escapeHtml(__('Mail', 'Mail'))}:</strong> <a href="mailto:${escapeHtml(record.email)}">${escapeHtml(record.email)}</a></li>` : ''}
                        ${record.age ? `<li><strong>${escapeHtml(__('Yaş', 'Yaş'))}:</strong> ${escapeHtml(record.age)}</li>` : ''}
                        ${record.education_level ? `<li><strong>${escapeHtml(__('Eğitim Durumu', 'Eğitim Durumu'))}:</strong> ${escapeHtml(record.education_level)}</li>` : ''}
                        ${record.department ? `<li><strong>${escapeHtml(__('Bölüm', 'Bölüm'))}:</strong> ${escapeHtml(record.department)}</li>` : ''}
                        ${record.job_title ? `<li><strong>${escapeHtml(__('Mesleği', 'Mesleği'))}:</strong> ${escapeHtml(record.job_title)}</li>` : ''}
                    </ul>
                </section>
                <section class="wfs-info-section">
                    <h4>📂 ${escapeHtml(__('Dosya Kategorileri', 'Dosya Kategorileri'))}</h4>
                    <div class="wfs-documents-grid">${renderDocumentsDetails(filesByCategory, canReview)}</div>
                </section>
                <section class="wfs-info-section">
                    <h4>🎯 ${escapeHtml(__('Atama', 'Atama'))}</h4>
                    <div class="wfs-assigned-info ${record.assigned_name ? '' : 'is-empty'}">
                        ${record.assigned_name ? `<strong>${escapeHtml(__('Atanan', 'Atanan'))}:</strong> ${escapeHtml(record.assigned_name)}` : escapeHtml(__('Henüz atama yapılmadı.', 'Henüz atama yapılmadı.'))}
                    </div>
                    ${canAssign ? `
                    <div class="wfs-assign-form">
                        <select class="wfs-assign-select wfs-select" data-record-id="${record.id}" data-current="${record.assigned_to || ''}">
                            <option value="">${escapeHtml(__('Kullanıcı Seçin', 'Kullanıcı Seçin'))}</option>
                        </select>
                        <button class="wfs-assign-btn wfs-btn wfs-btn-primary" data-record-id="${record.id}">${escapeHtml(__('Ata', 'Ata'))}</button>
                    </div>` : ''}
                </section>
                <section class="wfs-info-section">
                    <h4>📊 ${escapeHtml(__('Statü', 'Statü'))}</h4>
                    <div class="wfs-current-status">
                        <span class="wfs-status-badge" style="--wfs-status-color: ${status.color}; background: ${status.bg}; color: ${status.color};">
                            <span class="wfs-status-light"></span>
                            <span class="wfs-status-text">${escapeHtml(status.label)}</span>
                        </span>
                    </div>
                    ${canAssign ? `<div class="wfs-status-update">
                        <select class="wfs-status-select wfs-select" data-record-id="${record.id}">
                            ${Object.keys(statusMap).map((key) => `<option value="${key}" ${key === record.overall_status ? 'selected' : ''}>${escapeHtml(statusMap[key].label)}</option>`).join('')}
                        </select>
                        <button class="wfs-update-status-btn wfs-btn wfs-btn-primary" data-record-id="${record.id}">${escapeHtml(__('Statü Güncelle', 'Statü Güncelle'))}</button>
                    </div>` : ''}
                </section>
                <section class="wfs-info-section">
                    <h4>🎥 ${escapeHtml(__('Görüşme Sistemi', 'Görüşme Sistemi'))}</h4>
                    <ul class="wfs-info-list">
                        <li><strong>${escapeHtml(__('Görüşme Gerekiyor mu?', 'Görüşme Gerekiyor mu?'))}:</strong> <span class="wfs-interview-required" data-record-id="${record.id}">${escapeHtml(interviewLabel)}</span></li>
                        <li><strong>${escapeHtml(__('Görüşme Tarihi', 'Görüşme Tarihi'))}:</strong> <span class="wfs-interview-date" data-record-id="${record.id}">${escapeHtml(interviewDateText)}</span></li>
                        <li><strong>${escapeHtml(__('Görüşme Tamamlandı', 'Görüşme Tamamlandı'))}:</strong> <span class="wfs-interview-status" data-record-id="${record.id}">${escapeHtml(interviewCompleted ? __('Evet', 'Evet') : __('Hayır', 'Hayır'))}</span></li>
                    </ul>
                    ${canAssign ? `<div class="wfs-interview-actions">
                        <label>
                            <input type="checkbox" class="wfs-interview-toggle" data-record-id="${record.id}" ${interviewCompleted ? 'checked' : ''}>
                            <span>${escapeHtml(__('Görüşmeyi tamamlandı olarak işaretle', 'Görüşmeyi tamamlandı olarak işaretle'))}</span>
                        </label>
                        <input type="datetime-local" class="wfs-interview-datetime" data-record-id="${record.id}" value="${record.interview_at ? escapeHtml(record.interview_at.replace(' ', 'T')) : ''}">
                    </div>` : ''}
                </section>
                <section class="wfs-info-section wfs-payment-section" data-record-id="${record.id}">
                    <h4>💰 ${escapeHtml(__('Ödeme', 'Ödeme'))}</h4>
                    ${paymentHtml}
                </section>
                <section class="wfs-info-section">
                    <h4>⏱️ ${escapeHtml(__('Zaman Çizelgesi', 'Zaman Çizelgesi'))}</h4>
                    <ul class="wfs-info-list">
                        <li><strong>${escapeHtml(__('Oluşturulma', 'Oluşturulma'))}:</strong> ${escapeHtml(record.created_at)}</li>
                        <li><strong>${escapeHtml(__('Son Güncelleme', 'Son Güncelleme'))}:</strong> ${escapeHtml(record.updated_at)}</li>
                    </ul>
                </section>
            </div>
        `;
    }

    function renderRecordCard(data) {
        const record = data.record;
        const filesByCategory = data.files_by_category || {};
        const status = statusMap[record.overall_status] || { label: record.overall_status, color: '#2563eb', bg: '#dbeafe' };
        const name = `${record.first_name || ''} ${record.last_name || ''}`.trim();
        const initials = ((record.first_name || ' ').charAt(0) + (record.last_name || ' ').charAt(0)).toUpperCase();
        const assigned = record.assigned_name ? escapeHtml(record.assigned_name) : escapeHtml(__('Henüz atama yapılmadı.', 'Henüz atama yapılmadı.'));
        const phoneActions = formatPhone(record.phone || '');

        const contactButtons = [];
        if (phoneActions.tel) {
            contactButtons.push(`<a href="${phoneActions.tel}" class="wfs-contact-btn">Ara</a>`);
        }
        if (phoneActions.whatsapp) {
            contactButtons.push(`<a href="${phoneActions.whatsapp}" class="wfs-contact-btn" target="_blank" rel="noopener">WhatsApp</a>`);
        }
        if (record.email) {
            contactButtons.push(`<a href="mailto:${escapeHtml(record.email)}" class="wfs-contact-btn">Mail</a>`);
        }

        return `
            <div class="wfs-record-card" data-record-id="${record.id}" data-name="${escapeHtml(name)}" data-phone="${escapeHtml(record.phone || '')}" data-email="${escapeHtml(record.email || '')}">
                <div class="wfs-card-header">
                    <div class="wfs-user-info">
                        <div class="wfs-avatar" aria-hidden="true">${escapeHtml(initials || '👤')}</div>
                        <div>
                            <h3 class="wfs-user-name">${escapeHtml(name)}</h3>
                            <div class="wfs-user-meta">
                                ${record.job_title ? `<span>💼 ${escapeHtml(record.job_title)}</span>` : ''}
                                ${record.department ? `<span>🏢 ${escapeHtml(record.department)}</span>` : ''}
                            </div>
                            <div class="wfs-contact-actions">${contactButtons.join('')}</div>
                            <div class="wfs-contact-text">
                                ${record.phone ? `<span>📞 ${escapeHtml(record.phone)}</span>` : ''}
                                ${record.email ? `<span>📧 ${escapeHtml(record.email)}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="wfs-card-actions">
                        <div class="wfs-doc-summary">${renderDocChips(filesByCategory)}</div>
                        <div class="wfs-assignment-chip">
                            <span class="wfs-assignment-icon" aria-hidden="true">🎯</span>
                            <span class="wfs-assignment-label">${assigned}</span>
                        </div>
                        <span class="wfs-status-badge" style="--wfs-status-color: ${status.color}; background: ${status.bg}; color: ${status.color};">
                            <span class="wfs-status-light"></span>
                            <span class="wfs-status-text">${escapeHtml(status.label)}</span>
                        </span>
                        <button class="wfs-btn-link wfs-toggle-details" data-record-id="${record.id}">${escapeHtml(__('Detaylar', 'Detaylar'))}</button>
                    </div>
                </div>
                <div class="wfs-record-details" data-record-id="${record.id}" data-status="${escapeHtml(record.overall_status)}" style="display: none;">
                    ${renderRecordDetails(record, filesByCategory)}
                </div>
            </div>
        `;
    }

    function syncAssignableOptions() {
        $('.wfs-record-card').each(function() {
            const recordId = $(this).data('record-id');
            const $selects = $(`.wfs-assign-select[data-record-id="${recordId}"]`);
            if (!$selects.length) {
                return;
            }
            const options = $('#wfs-rep-filter option').map(function() {
                const val = $(this).attr('value');
                if (!val) {
                    return null;
                }
                return `<option value="${val}">${escapeHtml($(this).text())}</option>`;
            }).get();
            options.unshift(`<option value="">${escapeHtml(__('Kullanıcı Seçin', 'Kullanıcı Seçin'))}</option>`);
            $selects.each(function() {
                const current = $(this).data('current');
                $(this).html(options.join('')).val(current || '');
            });
        });
    }

    function renderRecords(items) {
        if (!items.length) {
            $recordsContainer.html(`
                <div class="wfs-empty-state">
                    <div class="wfs-empty-icon">📋</div>
                    <h3>${escapeHtml(__('Henüz kayıt yok', 'Henüz kayıt yok'))}</h3>
                    <p>${escapeHtml(__('Yeni bir kayıt oluşturduğunuzda burada listelenecek.', 'Yeni bir kayıt oluşturduğunuzda burada listelenecek.'))}</p>
                </div>
            `);
            return;
        }

        const cards = items.map(renderRecordCard).join('');
        $recordsContainer.html(cards);
        syncAssignableOptions();
    }

    function fetchRecords() {
        const search = $('#wfs-search').val() || '';
        const statusFilter = $('#wfs-status-filter').val() || '';
        const repFilter = $('#wfs-rep-filter').val() || '';

        $loading.show();

        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_get_records',
            nonce: wfs_ajax.nonce,
            search: search,
            status_filter: statusFilter,
            representative_filter: repFilter,
            per_page: 50,
            page: 1
        }).done(function(response) {
            if (response.success && response.data && response.data.items) {
                renderRecords(response.data.items);
            } else {
                showToast(response.data || 'Kayıt alınamadı', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        }).always(function() {
            $loading.hide();
        });
    }

    const debouncedFetch = (function() {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchRecords, 350);
        };
    })();

    $('#wfs-search').on('input', debouncedFetch);
    $('#wfs-status-filter, #wfs-rep-filter').on('change', fetchRecords);

    $('#wfs-create-record-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'wfs_create_record');
        formData.append('nonce', wfs_ajax.nonce);

        const $submit = $(this).find('button[type="submit"]');
        const originalText = $submit.text();
        $submit.prop('disabled', true).text('Kaydediliyor...');

        $.ajax({
            url: wfs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            if (response.success && response.data) {
                showToast(wfs_ajax.strings.record_created || 'Kayıt oluşturuldu');
                const existingHtml = $recordsContainer.html();
                const newCard = renderRecordCard(response.data);
                $recordsContainer.html(newCard + existingHtml);
                syncAssignableOptions();
                $('#wfs-create-record-form')[0].reset();
            } else {
                showToast(response.data || 'Kayıt eklenemedi', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        }).always(function() {
            $submit.prop('disabled', false).text(originalText);
        });
    });

    $(document).on('click', '.wfs-toggle-details', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const $details = $(`.wfs-record-details[data-record-id="${recordId}"]`);
        if ($details.is(':visible')) {
            $details.slideUp(200);
            $(this).text(__('Detaylar', 'Detaylar'));
        } else {
            $details.slideDown(200);
            $(this).text(__('Gizle', 'Gizle'));
        }
    });

    $(document).on('click', '.wfs-assign-btn', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const assignedTo = $(`.wfs-assign-select[data-record-id="${recordId}"]`).val();
        const $btn = $(this);

        if (!assignedTo) {
            showToast('Lütfen bir kullanıcı seçin', 'error');
            return;
        }

        if (!confirm(wfs_ajax.strings.confirm_assign)) {
            return;
        }

        $btn.prop('disabled', true).text('Atanıyor...');

        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_assign_record',
            nonce: wfs_ajax.nonce,
            record_id: recordId,
            assigned_to: assignedTo
        }).done(function(response) {
            if (response.success) {
                const assignedName = response.data && response.data.assigned_name ? response.data.assigned_name : '';
                showToast(wfs_ajax.strings.assignment_success, 'success');
                const $card = $(`.wfs-record-card[data-record-id="${recordId}"]`);
                $card.find('.wfs-assignment-label').text(assignedName || wfs_ajax.strings.assignment_none);
                const $detailInfo = $(`.wfs-record-details[data-record-id="${recordId}"] .wfs-assigned-info`);
                if (assignedName) {
                    $detailInfo.removeClass('is-empty').html('<strong>' + __('Atanan', 'Atanan') + ':</strong> ' + escapeHtml(assignedName));
                } else {
                    $detailInfo.addClass('is-empty').text(wfs_ajax.strings.assignment_none);
                }
            } else {
                showToast(response.data || 'Atama yapılamadı', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        }).always(function() {
            $btn.prop('disabled', false).text(__('Ata', 'Ata'));
        });
    });

    function updateStatusBadge(recordId, statusKey, statusData) {
        const $cardBadge = $(`.wfs-record-card[data-record-id="${recordId}"] .wfs-status-badge`);
        $cardBadge.css({ background: statusData.bg, color: statusData.color });
        $cardBadge.find('.wfs-status-text').text(statusData.label);
        $cardBadge.attr('style', `--wfs-status-color: ${statusData.color}; background: ${statusData.bg}; color: ${statusData.color};`);

        const $detailBadge = $(`.wfs-record-details[data-record-id="${recordId}"] .wfs-status-badge`);
        $detailBadge.css({ background: statusData.bg, color: statusData.color });
        $detailBadge.attr('style', `--wfs-status-color: ${statusData.color}; background: ${statusData.bg}; color: ${statusData.color};`);
        $detailBadge.find('.wfs-status-text').text(statusData.label);
    }

    function syncPaymentSection(recordId, statusKey) {
        const $section = $(`.wfs-payment-section[data-record-id="${recordId}"]`);
        if (!$section.length) {
            return;
        }

        if (statusKey === 'completed') {
            if (!$section.find('.wfs-payment-form').length) {
                $section.html(renderPaymentSection({ id: recordId, overall_status: statusKey, payment_amount: 0 }, !!wfs_ajax.can_assign));
            }
        } else {
            $section.html('<p class="wfs-payment-hint">' + escapeHtml(__('Ödeme girişi sadece statü "Tamamlandı" olduğunda aktif olur.', 'Ödeme girişi sadece statü "Tamamlandı" olduğunda aktif olur.')) + '</p>');
        }
    }

    $(document).on('click', '.wfs-update-status-btn', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const newStatus = $(`.wfs-status-select[data-record-id="${recordId}"]`).val();
        const $btn = $(this);

        if (!newStatus) {
            showToast('Lütfen bir statü seçin', 'error');
            return;
        }

        $btn.prop('disabled', true).text('Güncelleniyor...');

        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_update_record_status',
            nonce: wfs_ajax.nonce,
            record_id: recordId,
            status: newStatus
        }).done(function(response) {
            if (response.success && response.data) {
                showToast(wfs_ajax.strings.status_success, 'success');
                updateStatusBadge(recordId, newStatus, response.data);
                syncPaymentSection(recordId, newStatus);
            } else {
                showToast(response.data || 'Statü güncellenemedi', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        }).always(function() {
            $btn.prop('disabled', false).text(__('Statü Güncelle', 'Statü Güncelle'));
        });
    });

    $(document).on('change', '.wfs-file-status-select', function() {
        const fileId = $(this).data('file-id');
        const newStatus = $(this).val();
        const $select = $(this);
        $select.prop('disabled', true);

        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_update_file_status',
            nonce: wfs_ajax.nonce,
            file_id: fileId,
            status: newStatus,
            notes: ''
        }).done(function(response) {
            if (response.success) {
                showToast('Dosya statüsü güncellendi', 'success');
            } else {
                showToast(response.data || 'Güncelleme hatası', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        }).always(function() {
            $select.prop('disabled', false);
        });
    });

    $(document).on('change', '.wfs-interview-toggle, .wfs-interview-datetime', function() {
        const recordId = $(this).data('record-id');
        const completed = $(`.wfs-interview-toggle[data-record-id="${recordId}"]`).is(':checked') ? 1 : 0;
        const interviewAt = $(`.wfs-interview-datetime[data-record-id="${recordId}"]`).val();

        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_toggle_interview',
            nonce: wfs_ajax.nonce,
            record_id: recordId,
            completed: completed,
            interview_at: interviewAt
        }).done(function(response) {
            if (response.success && response.data) {
                showToast(wfs_ajax.strings.interview_marked || 'Görüşme güncellendi');
                const completedText = completed ? __('Evet', 'Evet') : __('Hayır', 'Hayır');
                $(`.wfs-interview-status[data-record-id="${recordId}"]`).text(completedText);
                if (response.data.interview_at) {
                    $(`.wfs-interview-date[data-record-id="${recordId}"]`).text(response.data.interview_at);
                }
            } else {
                showToast(response.data || 'Görüşme güncellenemedi', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        });
    });

    $(document).on('click', '.wfs-save-payment', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const amount = $(`.wfs-payment-input[data-record-id="${recordId}"]`).val();
        const $btn = $(this);

        $btn.prop('disabled', true).text('Kaydediliyor...');

        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_update_payment',
            nonce: wfs_ajax.nonce,
            record_id: recordId,
            payment_amount: amount
        }).done(function(response) {
            if (response.success) {
                showToast(wfs_ajax.strings.payment_saved || 'Ödeme kaydedildi');
            } else {
                showToast(response.data || 'Ödeme güncellenemedi', 'error');
            }
        }).fail(function() {
            showToast('Bağlantı hatası', 'error');
        }).always(function() {
            $btn.prop('disabled', false).text(__('Kaydet', 'Kaydet'));
        });
    });
});
