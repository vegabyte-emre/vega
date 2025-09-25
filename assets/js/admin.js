// assets/js/admin.js - Güncellenmiş versiyon

jQuery(document).ready(function($) {
    
    // Toast bildirimi gösterme
    function showToast(message, type = 'success') {
        const toastClass = type === 'error' ? 'wfs-toast error' : 'wfs-toast';
        const $toast = $('<div class="' + toastClass + '">' + message + '</div>');
        
        if ($('#wfs-toast-container').length === 0) {
            $('body').append('<div id="wfs-toast-container"></div>');
        }
        
        $('#wfs-toast-container').append($toast);
        
        setTimeout(function() {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Kayıt detaylarını aç/kapat
    $(document).on('click', '.wfs-toggle-details', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const $details = $('.wfs-record-details[data-record-id="' + recordId + '"]');
        const $btn = $(this);
        
        if ($details.is(':visible')) {
            $details.slideUp(300);
            $btn.text('Detaylar');
        } else {
            $details.slideDown(300);
            $btn.text('Gizle');
        }
    });
    
    // Kayıt atama
    $(document).on('click', '.wfs-assign-btn', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const assignedTo = $('.wfs-assign-select[data-record-id="' + recordId + '"]').val();
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
        })
        .done(function(response) {
            if (response.success) {
                const assignedName = response.data && response.data.assigned_name ? response.data.assigned_name : '';
                const displayName = assignedName || wfs_ajax.strings.assignment_none;

                showToast(wfs_ajax.strings.assignment_success, 'success');

                const $card = $('.wfs-record-card[data-record-id="' + recordId + '"]');
                $card.find('.wfs-assignment-label').text(displayName);

                const $detailInfo = $('.wfs-record-details[data-record-id="' + recordId + '"] .wfs-assigned-info');
                if ($detailInfo.length) {
                    if (assignedName) {
                        $detailInfo.removeClass('is-empty').html('<strong>Atanan:</strong> ' + assignedName);
                    } else {
                        $detailInfo.addClass('is-empty').text(wfs_ajax.strings.assignment_none);
                    }
                }
            } else {
                showToast('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
            }
        })
        .fail(function() {
            showToast('Bağlantı hatası', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Ata');
        });
    });
    
    // Statü güncelleme
    $(document).on('click', '.wfs-update-status-btn', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const newStatus = $('.wfs-status-select[data-record-id="' + recordId + '"]').val();
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
        })
        .done(function(response) {
            if (response.success) {
                const data = response.data || {};
                const badgeColor = data.color || '#3b82f6';
                const badgeBg = data.bg || '#dbeafe';
                const badgeLabel = data.label || newStatus;

                showToast(wfs_ajax.strings.status_success, 'success');

                const $card = $('.wfs-record-card[data-record-id="' + recordId + '"]');
                const $cardBadge = $card.find('.wfs-status-badge');
                $cardBadge.css({ background: badgeBg, color: badgeColor });
                $cardBadge.find('.wfs-status-light').css('background', badgeColor);
                $cardBadge.find('.wfs-status-text').text(badgeLabel);

                $('.wfs-status-select[data-record-id="' + recordId + '"]').val(newStatus);

                const $detailBadge = $('.wfs-record-details[data-record-id="' + recordId + '"] .wfs-current-status .wfs-status-badge');
                if ($detailBadge.length) {
                    $detailBadge.css({ background: badgeBg, color: badgeColor });
                    $detailBadge.find('.wfs-status-light').css('background', badgeColor);
                    $detailBadge.find('.wfs-status-text').text(badgeLabel);
                }
            } else {
                showToast('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
            }
        })
        .fail(function() {
            showToast('Bağlantı hatası', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false).text('Statü Güncelle');
        });
    });
    
    // Dosya statüsü güncelleme
    $(document).on('change', '.wfs-file-status-select', function() {
        const fileId = $(this).data('file-id');
        const newStatus = $(this).val();
        const $select = $(this);
        
        // Not almak için modal göster
        const notes = prompt('İnceleme notu eklemek ister misiniz? (İsteğe bağlı)');
        
        $select.prop('disabled', true);
        
        $.post(wfs_ajax.ajax_url, {
            action: 'wfs_update_file_status',
            nonce: wfs_ajax.nonce,
            file_id: fileId,
            status: newStatus,
            notes: notes || ''
        })
        .done(function(response) {
            if (response.success) {
                showToast('Dosya statüsü güncellendi', 'success');
                // Statü badge'ini güncelle
                const $fileItem = $select.closest('.wfs-file-item');
                const statusColors = {
                    'pending': '#f59e0b',
                    'approved': '#10b981',
                    'rejected': '#ef4444'
                };
                const statusLabels = {
                    'pending': 'Beklemede',
                    'approved': 'Onaylandı',
                    'rejected': 'Reddedildi'
                };
                
                $fileItem.find('span').filter(function() {
                    return $(this).css('padding') === '0.25rem 0.75rem';
                }).css({
                    'background': statusColors[newStatus] + '20',
                    'color': statusColors[newStatus]
                }).text(statusLabels[newStatus]);
                
                // Not varsa ekle
                if (notes) {
                    if ($fileItem.find('small').length) {
                        $fileItem.find('small').text('Not: ' + notes);
                    } else {
                        $fileItem.append(`
                            <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;">
                                <small style="color: #6b7280;">Not: ${notes}</small>
                            </div>
                        `);
                    }
                }
            } else {
                showToast('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                // Seçimi eski haline getir
                $select.val($select.data('original-value'));
            }
        })
        .fail(function() {
            showToast('Bağlantı hatası', 'error');
            $select.val($select.data('original-value'));
        })
        .always(function() {
            $select.prop('disabled', false);
        });
    });
    
    // Dosya statü seçiminin orijinal değerini sakla
    $(document).on('focus', '.wfs-file-status-select', function() {
        $(this).data('original-value', $(this).val());
    });
    
    // Form toggle
    $('#toggle-form').on('click', function() {
        $('#custom-form-container').slideToggle(300);
        $(this).text($('#custom-form-container').is(':visible') ? 'Formu Gizle' : 'Formu Göster');
    });
    
    // Özel form gönderimi
    $('#wfs-custom-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wfs_submit_custom_form');
        formData.append('nonce', wfs_ajax.custom_form_nonce);
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        
        submitBtn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: wfs_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast('Kayıt başarıyla eklendi!', 'success');
                    $('#wfs-custom-form')[0].reset();
                    $('#custom-form-container').slideUp(300);
                    $('#toggle-form').text('Formu Göster');
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                }
            },
            error: function() {
                showToast('Bağlantı hatası', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Filtreleme
    let filterTimeout;

    function redirectWithFilters() {
        const search = $('#wfs-search').val().trim();
        const statusFilter = $('#wfs-status-filter').val();
        const repFilter = $('#wfs-rep-filter').val();

        const params = new URLSearchParams();
        if (search) {
            params.set('wfs_search', search);
        }
        if (statusFilter) {
            params.set('wfs_status', statusFilter);
        }
        if (repFilter) {
            params.set('wfs_rep', repFilter);
        }

        const query = params.toString();
        const url = query ? `${wfs_ajax.filters_base_url}&${query}` : wfs_ajax.filters_base_url;
        window.location.href = url;
    }

    $('#wfs-search').on('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(redirectWithFilters, 500);
    });

    $('#wfs-status-filter, #wfs-rep-filter').on('change', redirectWithFilters);
    
    // FluentForms alan eşleştirme modal
    $(document).on('click', '.map-fields-btn', function() {
        const formId = $(this).data('form-id');
        
        // Modal oluştur ve göster
        const modal = $(`
            <div class="wfs-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                <div class="wfs-modal-content" style="background: white; max-width: 600px; width: 90%; max-height: 80vh; border-radius: 8px; padding: 2rem; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h3 style="margin: 0;">Alan Eşleştirme - Form #${formId}</h3>
                        <button class="close-modal" style="background: #ef4444; color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">×</button>
                    </div>
                    <div class="modal-body">
                        <p>Alan eşleştirme özelliği yakında eklenecek...</p>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.find('.close-modal').on('click', function() {
            modal.remove();
        });
        
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
    });
});