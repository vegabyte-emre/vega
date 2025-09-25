<?php
// templates/admin-page.php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Özel formu getir
$custom_form_data = get_option('wfs_custom_form', '{"fields":[]}');
$custom_form = json_decode($custom_form_data, true);
if (!is_array($custom_form)) {
    $custom_form = array('fields' => array(), 'settings' => array());
}
$has_custom_form = !empty($custom_form['fields']);

$records = isset($records) && is_array($records) ? $records : array();
$status_settings = isset($status_settings) && is_array($status_settings) ? $status_settings : array();
$assignable_users = isset($assignable_users) && is_array($assignable_users) ? $assignable_users : array();
$active_filters = isset($active_filters) && is_array($active_filters)
    ? wp_parse_args($active_filters, array('search' => '', 'status' => '', 'rep' => 0))
    : array('search' => '', 'status' => '', 'rep' => 0);

if (empty($status_settings)) {
    $status_settings = array(
        'pending' => array('label' => __('Beklemede', 'workflow-system'), 'color' => '#f59e0b', 'bg' => '#fef3c7'),
    );
}

if (!function_exists('wfs_get_status_config')) {
    function wfs_get_status_config($status_key, $statuses) {
        $default = reset($statuses);
        return $statuses[$status_key] ?? $default;
    }
}

if (!function_exists('wfs_display_record_details')) {
    function wfs_display_record_details($record, $custom_form, $status_settings, $assignable_users) {
        global $wpdb;

        $submission_data = array();

        $form_data = $wpdb->get_var($wpdb->prepare(
            "SELECT form_data FROM {$wpdb->prefix}wfs_form_submissions WHERE record_id = %d",
            $record->id
        ));

        if ($form_data) {
            $decoded = json_decode($form_data, true);
            if (is_array($decoded)) {
                $submission_data = $decoded;
            }
        }

        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wfs_files WHERE record_id = %d ORDER BY uploaded_at DESC",
            $record->id
        ));

        $status_config = wfs_get_status_config($record->overall_status, $status_settings);

        include WFS_PLUGIN_PATH . 'templates/partials/record-details.php';
    }
}

// Temsilci filtre listesi
$representatives = $assignable_users;
?>

<div class="wrap">
    <!-- Header -->
    <div class="wfs-header">
        <h1>İş Akışı Yönetim Sistemi</h1>
        <p>FluentForms entegrasyonlu modern iş akışı sistemi</p>
    </div>

    <!-- Filtreler -->
    <div class="wfs-filters-container">
        <div class="wfs-filter-item">
            <label>🔍 Arama</label>
            <input type="text" id="wfs-search" placeholder="Ad, soyad veya e-posta ile arayın..." class="wfs-input" value="<?php echo esc_attr($active_filters['search']); ?>">
        </div>
        
        <div class="wfs-filter-item">
            <label>📊 Statü</label>
            <select id="wfs-status-filter" class="wfs-select">
                <option value="">Tüm Statüler</option>
                <?php foreach ($status_settings as $status_key => $status_info): ?>
                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($active_filters['status'], $status_key); ?>><?php echo esc_html($status_info['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="wfs-filter-item">
            <label>👤 Temsilci</label>
            <select id="wfs-rep-filter" class="wfs-select">
                <option value="">Tüm Temsilciler</option>
                <?php foreach ($representatives as $rep): ?>
                    <option value="<?php echo esc_attr($rep->ID); ?>" <?php selected(intval($active_filters['rep']), $rep->ID); ?>><?php echo esc_html($rep->display_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($has_custom_form): ?>
    <!-- Kayıt Ekleme Formu -->
    <div class="wfs-add-record-section" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; color: #1f2937; display: flex; align-items: center; gap: 0.5rem;">
                ➕ Yeni Kayıt Ekle
            </h3>
            <button id="toggle-form" class="wfs-btn-link" style="background: none; border: none; color: #3b82f6; cursor: pointer; padding: 0.5rem;">
                Formu Göster/Gizle
            </button>
        </div>
        
        <div id="custom-form-container" style="display: none;">
            <form id="wfs-custom-form" enctype="multipart/form-data">
                <div class="wfs-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($custom_form['fields'] as $field): ?>
                        <div class="wfs-form-field">
                            <label class="wfs-form-label" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">
                                <?php echo esc_html($field['label']); ?>
                                <?php if ($field['required']): ?>
                                    <span style="color: #ef4444;">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php
                            $field_name = 'field_' . $field['id'];
                            $required = $field['required'] ? 'required' : '';
                            $placeholder = !empty($field['placeholder']) ? 'placeholder="' . esc_attr($field['placeholder']) . '"' : '';
                            
                            switch ($field['type']):
                                case 'text':
                                case 'email':
                                case 'phone':
                                case 'number':
                                case 'date':
                                    echo "<input type='{$field['type']}' name='{$field_name}' class='wfs-form-input' {$placeholder} {$required} style='width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem;'>";
                                    break;
                                    
                                case 'textarea':
                                    echo "<textarea name='{$field_name}' class='wfs-form-input' rows='4' {$placeholder} {$required} style='width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; resize: vertical;'></textarea>";
                                    break;
                                    
                                case 'select':
                                    echo "<select name='{$field_name}' class='wfs-form-input' {$required} style='width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem;'>";
                                    echo "<option value=''>Seçiniz...</option>";
                                    if (!empty($field['options'])) {
                                        foreach ($field['options'] as $option) {
                                            echo "<option value='" . esc_attr($option) . "'>" . esc_html($option) . "</option>";
                                        }
                                    }
                                    echo "</select>";
                                    break;
                                    
                                case 'radio':
                                    if (!empty($field['options'])) {
                                        foreach ($field['options'] as $option) {
                                            echo "<label style='display: block; margin: 0.5rem 0;'>";
                                            echo "<input type='radio' name='{$field_name}' value='" . esc_attr($option) . "' {$required} style='margin-right: 0.5rem;'>";
                                            echo esc_html($option);
                                            echo "</label>";
                                        }
                                    }
                                    break;
                                    
                                case 'checkbox':
                                    if (!empty($field['options'])) {
                                        foreach ($field['options'] as $i => $option) {
                                            echo "<label style='display: block; margin: 0.5rem 0;'>";
                                            echo "<input type='checkbox' name='{$field_name}[]' value='" . esc_attr($option) . "' style='margin-right: 0.5rem;'>";
                                            echo esc_html($option);
                                            echo "</label>";
                                        }
                                    }
                                    break;
                                    
                                case 'file':
                                    echo "<input type='file' name='{$field_name}[]' class='wfs-form-input' multiple accept='.pdf,.doc,.docx,.jpg,.jpeg,.png' {$required} style='width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;'>";
                                    break;
                            endswitch;
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <button type="submit" class="wfs-btn wfs-btn-primary" style="background: #10b981; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                        💾 Kayıt Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Form Builder Uyarısı -->
    <div class="wfs-no-form-notice" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem 1.5rem; margin-bottom: 2rem; border-radius: 4px;">
        <p style="margin: 0; color: #92400e;">
            📝 <strong>Kayıt formu oluşturulmamış.</strong> 
            <a href="?page=workflow-settings" style="color: #92400e; text-decoration: underline;">Ayarlar → Form Builder</a> 
            bölümünden form oluşturun.
        </p>
    </div>
    <?php endif; ?>

    <!-- Loading -->
    <div id="wfs-loading" style="display: none;" class="wfs-loading-container">
        <div class="wfs-spinner"></div>
        <p>Yükleniyor...</p>
    </div>

    <!-- Kayıtlar -->
    <div id="wfs-records-container" class="wfs-records">
        <?php if (!empty($records)): ?>
            <?php foreach ($records as $record): ?>
                <?php
                $first_initial = $record->first_name ? mb_substr($record->first_name, 0, 1) : '';
                $last_initial = $record->last_name ? mb_substr($record->last_name, 0, 1) : '';
                $initials = strtoupper($first_initial . $last_initial);
                $status_config = wfs_get_status_config($record->overall_status, $status_settings);
                $assigned_display = $record->assigned_name ? esc_html($record->assigned_name) : __('Henüz atama yapılmadı.', 'workflow-system');
                ?>
                <div class="wfs-record-card" data-record-id="<?php echo $record->id; ?>">
                    <div class="wfs-card-header">
                        <div class="wfs-user-info">
                            <div class="wfs-avatar" aria-hidden="true">
                                <?php echo esc_html($initials ?: '🗂️'); ?>
                            </div>
                            <div class="wfs-user-details">
                                <h3 class="wfs-user-name">
                                    <?php echo esc_html(trim($record->first_name . ' ' . $record->last_name)); ?>
                                </h3>
                                <?php if (!empty($record->email)): ?>
                                    <p class="wfs-user-email"><?php echo esc_html($record->email); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($record->phone)): ?>
                                    <p class="wfs-user-phone">📞 <?php echo esc_html($record->phone); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="wfs-card-actions">
                            <div class="wfs-assignment-chip">
                                <span class="wfs-assignment-icon" aria-hidden="true">🎯</span>
                                <span class="wfs-assignment-label"><?php echo $assigned_display; ?></span>
                            </div>
                            <span class="wfs-status-badge" style="background: <?php echo esc_attr($status_config['bg']); ?>; color: <?php echo esc_attr($status_config['color']); ?>;">
                                <span class="wfs-status-light" style="background: <?php echo esc_attr($status_config['color']); ?>;"></span>
                                <span class="wfs-status-text"><?php echo esc_html($status_config['label']); ?></span>
                            </span>
                            <button class="wfs-btn-link wfs-toggle-details" data-record-id="<?php echo $record->id; ?>">
                                <?php esc_html_e('Detaylar', 'workflow-system'); ?>
                            </button>
                        </div>
                    </div>

                    <?php wfs_display_record_details($record, $custom_form, $status_settings, $assignable_users); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="wfs-empty-state">
                <div class="wfs-empty-icon">📋</div>
                <h3><?php esc_html_e('Henüz kayıt yok', 'workflow-system'); ?></h3>
                <p><?php esc_html_e('Yeni kayıt eklemek için formu kullanın.', 'workflow-system'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Toast Container -->
    <div id="wfs-toast-container"></div>
</div>

<style>
/* Base Styles */
.wrap {
    margin: 20px 20px 0 2px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.wfs-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.wfs-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.wfs-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Filters */
.wfs-filters-container {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 1rem;
}

.wfs-filter-item label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.wfs-input, .wfs-select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.wfs-input:focus, .wfs-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading */
.wfs-loading-container {
    text-align: center;
    padding: 2rem;
}

.wfs-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #f3f4f6;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

/* Records */
.wfs-records {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.wfs-record-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    overflow: hidden;
    transition: all 0.3s ease;
}

.wfs-record-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.wfs-card-header {
    background: #f8fafc;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wfs-user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.wfs-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
}

.wfs-user-name {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.wfs-user-email {
    margin: 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.wfs-card-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.wfs-assignment-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e0f2fe;
    color: #0369a1;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}
.wfs-assignment-icon {
    font-size: 1rem;
}
.wfs-assignment-label {
    white-space: nowrap;
}
.wfs-user-phone {
    margin: 0;
    color: #4b5563;
    font-size: 0.9rem;
}

.wfs-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.wfs-status-light {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}
.wfs-status-text {
    display: inline-block;
    margin-left: 0.25rem;
}

.wfs-btn-link {
    background: none;
    border: none;
    color: #3b82f6;
    font-weight: 500;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.wfs-btn-link:hover {
    background: #eff6ff;
    color: #1d4ed8;
}

/* Record Details */
.wfs-record-details {
    padding: 1.5rem;
}

.wfs-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}
.wfs-assigned-info.is-empty {
    color: #9ca3af;
    font-style: italic;
}

.wfs-info-section h4 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 0.5rem;
}

.wfs-info-list > div {
    margin-bottom: 0.5rem;
    padding: 0.25rem 0;
}

.wfs-assigned-info {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f0f9ff;
    border-radius: 6px;
    border-left: 3px solid #3b82f6;
}

.wfs-assign-form {
    display: flex;
    gap: 0.5rem;
}

.wfs-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.wfs-btn-primary {
    background: #3b82f6;
    color: white;
}

.wfs-btn-primary:hover {
    background: #1d4ed8;
}

/* Files */
.wfs-files-section {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
}
.wfs-files-grid {
    display: grid;
    gap: 1rem;
}
.wfs-file-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    background: #f9fafb;
}
.wfs-file-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}
.wfs-file-icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}
.wfs-file-link {
    color: #3b82f6;
    font-weight: 500;
    text-decoration: none;
}
.wfs-file-link:hover {
    text-decoration: underline;
}
.wfs-file-meta {
    margin-left: 0.5rem;
    color: #6b7280;
    font-size: 0.85rem;
}
.wfs-file-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.wfs-file-status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 500;
}
.wfs-file-status-select {
    padding: 0.25rem 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.85rem;
}
.wfs-file-notes {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #fff;
    border-radius: 6px;
    border: 1px dashed #d1d5db;
}

.wfs-files-section h4 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.wfs-no-files {
    text-align: center;
    padding: 2rem;
    background: #f8fafc;
    border-radius: 8px;
    color: #6b7280;
}

.wfs-small-text {
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Empty State */
.wfs-empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.wfs-empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.wfs-empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #374151;
}

.wfs-empty-state p {
    margin: 0;
    color: #6b7280;
}

/* Toast */
#wfs-toast-container {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 9999;
}

.wfs-toast {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    margin-bottom: 0.5rem;
    border-left: 4px solid #10b981;
    animation: slideIn 0.3s ease;
}

.wfs-toast.error {
    border-left-color: #ef4444;
}

/* Animations */
@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.05); }
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .wrap {
        margin: 10px;
    }
    
    .wfs-header {
        padding: 1.5rem 1rem;
        margin-bottom: 1rem;
    }
    
    .wfs-header h1 {
        font-size: 1.8rem;
    }
    
    .wfs-filters-container {
        grid-template-columns: 1fr;
        padding: 1rem;
    }
    
    .wfs-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
    }
    
    .wfs-user-info {
        width: 100%;
    }
    
    .wfs-card-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .wfs-details-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .wfs-assign-form {
        flex-direction: column;
    }
    
    .wfs-assign-form select {
        margin-bottom: 0.5rem;
    }
    
    #wfs-toast-container {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
    }
}

@media (max-width: 480px) {
    .wfs-header {
        padding: 1rem;
    }
    
    .wfs-header h1 {
        font-size: 1.5rem;
    }
    
    .wfs-record-card {
        border-radius: 8px;
    }
    
    .wfs-avatar {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .wfs-user-name {
        font-size: 1.1rem;
    }
    
    .wfs-user-email {
        font-size: 0.9rem;
    }
}
</style>

<script>
// Bu satırı buraya ekleyin
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

jQuery(document).ready(function($) {
    // Toggle Details
    // Toggle Details - GÜNCELLENMİŞ VERSİYON
$(document).on('click', '.wfs-toggle-details', function(e) {
    e.preventDefault();
    const recordId = $(this).data('record-id');
    const $details = $('.wfs-record-details[data-record-id="' + recordId + '"]');
    const $btn = $(this);
    
    if ($details.is(':visible')) {
        $details.slideUp(300);
        $btn.text('Detaylar');
    } else {
        // Detayları yükle
        if ($details.find('p').text() === 'Kayıt detayları yükleniyor...') {
            loadRecordDetails(recordId);
        }
        $details.slideDown(300);
        $btn.text('Gizle');
    }
});

function loadRecordDetails(recordId) {
    $.post(ajaxurl, {
        action: 'wfs_get_record_details',
        nonce: '<?php echo wp_create_nonce('wfs_nonce'); ?>',
        record_id: recordId
    })
    .done(function(response) {
        if (response.success) {
            const $details = $('.wfs-record-details[data-record-id="' + recordId + '"]');
            $details.html(generateRecordDetailsHTML(response.data));
        }
    })
    .fail(function() {
        const $details = $('.wfs-record-details[data-record-id="' + recordId + '"]');
        $details.html('<p style="color: red;">Detaylar yüklenirken hata oluştu.</p>');
    });
}

function generateRecordDetailsHTML(data) {
    const record = data.record;
    const files = data.files || [];
    
    return `
        <div class="wfs-details-grid">
            <div class="wfs-info-section">
                <h4>Kişisel Bilgiler</h4>
                <div class="wfs-info-list">
                    ${record.first_name ? `<div><strong>Ad:</strong> ${record.first_name}</div>` : ''}
                    ${record.last_name ? `<div><strong>Soyad:</strong> ${record.last_name}</div>` : ''}
                    ${record.email ? `<div><strong>E-posta:</strong> ${record.email}</div>` : ''}
                    ${record.phone ? `<div><strong>Telefon:</strong> ${record.phone}</div>` : ''}
                    ${record.age ? `<div><strong>Yaş:</strong> ${record.age}</div>` : ''}
                    ${record.education_level ? `<div><strong>Eğitim:</strong> ${record.education_level}</div>` : ''}
                    ${record.department ? `<div><strong>Bölüm:</strong> ${record.department}</div>` : ''}
                </div>
            </div>
            
            <div class="wfs-info-section">
                <h4>Statü</h4>
                <div class="wfs-current-status">
                    <strong>Mevcut Statü:</strong>
                    ${getStatusBadge(record.overall_status)}
                </div>
            </div>
            
            <div class="wfs-info-section">
                <h4>Tarihler</h4>
                <div class="wfs-info-list">
                    <div><strong>Oluşturulma:</strong> ${formatDate(record.created_at)}</div>
                    <div><strong>Güncellenme:</strong> ${formatDate(record.updated_at)}</div>
                </div>
            </div>
        </div>
    `;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR') + ' ' + date.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'});
}

    // Assign Record
    $('.wfs-assign-btn').on('click', function(e) {
        e.preventDefault();
        const recordId = $(this).data('record-id');
        const assignedTo = $('.wfs-assign-select[data-record-id="' + recordId + '"]').val();
        const $btn = $(this);
        
        if (!assignedTo) {
            showToast('Lütfen bir temsilci seçin', 'error');
            return;
        }

        if (!confirm('Bu kaydı seçilen temsilciye atamak istediğinizden emin misiniz?')) {
            return;
        }

        $btn.prop('disabled', true).text('Atanıyor...');

        $.post(ajaxurl, {
            action: 'wfs_assign_record',
            nonce: '<?php echo wp_create_nonce('wfs_nonce'); ?>',
            record_id: recordId,
            assigned_to: assignedTo
        })
        .done(function(response) {
            if (response.success) {
                showToast('Kayıt başarıyla atandı', 'success');
                location.reload();
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

    // Form toggle
    $('#toggle-form').on('click', function() {
        $('#custom-form-container').slideToggle(300);
    });

    // Custom form submission
    $('#wfs-custom-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wfs_submit_custom_form');
        formData.append('nonce', '<?php echo wp_create_nonce('wfs_custom_form_nonce'); ?>');
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        
        submitBtn.prop('disabled', true).text('Kaydediliyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast('Kayıt başarıyla eklendi!', 'success');
                    $('#wfs-custom-form')[0].reset();
                    $('#custom-form-container').slideUp(300);
                    
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

    function showToast(message, type = 'success') {
        const toastClass = type === 'error' ? 'wfs-toast error' : 'wfs-toast';
        const $toast = $('<div class="' + toastClass + '">' + message + '</div>');
        
        $('#wfs-toast-container').append($toast);
        
        setTimeout(function() {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    // KAYITLARI YÜKLE - Bu kodu showToast fonksiyonundan ÖNCE ekleyin
    loadRecords();
    
    function loadRecords() {
        $('#wfs-loading').show();
        
        $.post(ajaxurl, {
            action: 'wfs_get_records',
            nonce: '<?php echo wp_create_nonce('wfs_nonce'); ?>',
            page: 1,
            per_page: 20
        })
        .done(function(response) {
            console.log('AJAX Response:', response); // Debug için
            if (response.success && response.data) {
                displayRecords(response.data);
            } else {
                $('#wfs-records-container').html('<div class="wfs-empty-state"><div class="wfs-empty-icon">📋</div><h3>Henüz kayıt yok</h3><p>İlk kaydınızı oluşturmak için form doldurun.</p></div>');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX Error:', error); // Debug için
            $('#wfs-records-container').html('<div class="wfs-empty-state"><div class="wfs-empty-icon">⚠️</div><h3>Yükleme Hatası</h3><p>Kayıtlar yüklenirken bir hata oluştu.</p></div>');
        })
        .always(function() {
            $('#wfs-loading').hide();
        });
    }
    
    function displayRecords(records) {
        console.log('Displaying records:', records); // Debug için
        
        if (records.length === 0) {
            $('#wfs-records-container').html('<div class="wfs-empty-state"><div class="wfs-empty-icon">📋</div><h3>Henüz kayıt yok</h3><p>İlk kaydınızı oluşturmak için form doldurun.</p></div>');
            return;
        }
        
        let html = '';
        records.forEach(function(record) {
            const avatarColor = getAvatarColor(record.first_name + record.last_name);
            const initials = (record.first_name.charAt(0) + record.last_name.charAt(0)).toUpperCase();
            
            html += `
                <div class="wfs-record-card" data-record-id="${record.id}">
                    <div class="wfs-card-header">
                        <div class="wfs-user-info">
                            <div class="wfs-avatar" style="background: ${avatarColor};">
                                ${initials}
                            </div>
                            <div>
                                <h3 class="wfs-user-name">${record.first_name} ${record.last_name}</h3>
                                <p class="wfs-user-email">${record.email}</p>
                                ${record.phone ? `<p class="wfs-small-text">📞 ${record.phone}</p>` : ''}
                            </div>
                        </div>
                        <div class="wfs-card-actions">
                            ${getStatusBadge(record.overall_status)}
                            <button class="wfs-btn-link wfs-toggle-details" data-record-id="${record.id}">
                                Detaylar
                            </button>
                        </div>
                    </div>
                    <div class="wfs-record-details" data-record-id="${record.id}" style="display: none;">
                        <p>Kayıt detayları yükleniyor...</p>
                    </div>
                </div>
            `;
        });
        
        $('#wfs-records-container').html(html);
    }
    
    function getAvatarColor(name) {
        const colors = ['#f59e0b', '#10b981', '#ef4444', '#3b82f6', '#8b5cf6', '#f97316'];
        let hash = 0;
        for (let i = 0; i < name.length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    }
    
    function getStatusBadge(status) {
        const statusConfig = {
            'pending': { label: 'Beklemede', color: '#f59e0b', bg: '#fef3c7' },
            'approved': { label: 'Onaylandı', color: '#10b981', bg: '#d1fae5' },
            'rejected': { label: 'Reddedildi', color: '#ef4444', bg: '#fee2e2' }
        };
        
        const config = statusConfig[status] || statusConfig.pending;
        return `<span class="wfs-status-badge" style="background: ${config.bg}; color: ${config.color};">
            <span class="wfs-status-light" style="background: ${config.color};"></span>
            ${config.label}
        </span>`;
    }
});
</script>