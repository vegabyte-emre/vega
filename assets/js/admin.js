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
        
        if (!confirm('Bu kaydı seçilen kullanıcıya atamak istediğinizden emin misiniz?')) {
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
                showToast('Kayıt başarıyla atandı', 'success');
                setTimeout(() => location.reload(), 1500);
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
                showToast('Statü başarıyla güncellendi', 'success');
                setTimeout(() => location.reload(), 1500);
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
    
    function applyFilters() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            const search = $('#wfs-search').val();
            const statusFilter = $('#wfs-status-filter').val();
            const repFilter = $('#wfs-rep-filter').val();
            
            $('#wfs-loading').show();
            $('#wfs-records-container').hide();
            
            $            .post(wfs_ajax.ajax_url, {
                action: 'wfs_get_records',
                nonce: wfs_ajax.nonce,
                search: search,
                status_filter: statusFilter,
                representative_filter: repFilter,
                page: 1,
                per_page: 20
            })
            .done(function(response) {
                if (response.success) {
                    renderRecords(response.data);
                } else {
                    showToast('Kayıtlar yüklenirken hata oluştu', 'error');
                }
            })
            .fail(function() {
                showToast('Bağlantı hatası', 'error');
            })
            .always(function() {
                $('#wfs-loading').hide();
                $('#wfs-records-container').show();
            });
        }, 500);
    }
    
    // Kayıtları render et
    function renderRecords(records) {
        const $container = $('#wfs-records-container');
        
        if (!records || records.length === 0) {
            $container.html(`
                <div class="wfs-empty-state">
                    <div class="wfs-empty-icon">📋</div>
                    <h3>Kayıt Bulunamadı</h3>
                    <p>Arama kriterlerinize uygun kayıt bulunamadı.</p>
                </div>
            `);
            return;
        }
        
        let html = '';
        records.forEach(function(record) {
            // Kayıt kartını oluştur
            const initials = (record.first_name[0] + record.last_name[0]).toUpperCase();
            const statusInfo = getStatusInfo(record.overall_status);
            
            html += `
                <div class="wfs-record-card">
                    <div class="wfs-card-header">
                        <div class="wfs-user-info">
                            <div class="wfs-avatar" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                                ${initials}
                            </div>
                            <div class="wfs-user-details">
                                <h3 class="wfs-user-name">${record.first_name} ${record.last_name}</h3>
                                <p class="wfs-user-email">${record.email}</p>
                            </div>
                        </div>
                        <div class="wfs-card-actions">
                            <span class="wfs-status-badge" style="background: ${statusInfo.bg}; color: ${statusInfo.color};">
                                <span class="wfs-status-light" style="background: ${statusInfo.color};"></span>
                                ${statusInfo.text}
                            </span>
                            <button class="wfs-toggle-details wfs-btn-link" data-record-id="${record.id}">
                                Detaylar
                            </button>
                        </div>
                    </div>
                    <!-- Detaylar bölümü dinamik olarak yüklenir -->
                </div>
            `;
        });
        
        $container.html(html);
    }
    
    // Statü bilgilerini getir
    function getStatusInfo(status) {
        const statusMap = {
            'pending': { color: '#f59e0b', bg: '#fef3c7', text: 'Beklemede' },
            'processing': { color: '#3b82f6', bg: '#dbeafe', text: 'İşleniyor' },
            'approved': { color: '#10b981', bg: '#d1fae5', text: 'Onaylandı' },
            'rejected': { color: '#ef4444', bg: '#fee2e2', text: 'Reddedildi' },
            'completed': { color: '#8b5cf6', bg: '#ede9fe', text: 'Tamamlandı' }
        };
        
        return statusMap[status] || statusMap['pending'];
    }
    
    // Filtre event listeners
    $('#wfs-search').on('input', applyFilters);
    $('#wfs-status-filter, #wfs-rep-filter').on('change', applyFilters);
    
    // Sayfa yüklendiğinde kayıtları getir (eğer filtreler varsa)
    if ($('#wfs-records-container').length && $('#wfs-search').length) {
        // İlk yüklemede mevcut HTML'i koruyoruz, sadece filtre uygulandığında AJAX çağrısı yapıyoruz
    }
    
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