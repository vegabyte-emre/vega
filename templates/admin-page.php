<?php
// templates/admin-page.php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Özel formu getir
$custom_form_data = get_option('wfs_custom_form', '{"fields":[]}');
$custom_form = json_decode($custom_form_data, true);
$has_custom_form = !empty($custom_form['fields']);

// Test verisi ekle (geliştirme amaçlı)
$test_records = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wfs_records LIMIT 5");

// Eğer veri yoksa test verisi oluştur
if (empty($test_records)) {
    // Test verisi ekleme
    $wpdb->insert($wpdb->prefix . 'wfs_records', array(
        'fluent_form_id' => 1,
        'submission_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Kullanıcı',
        'email' => 'test@example.com',
        'phone' => '+90 555 123 45 67',
        'education_level' => 'Lisans',
        'department' => 'Bilgisayar Mühendisliği',
        'age' => 25,
        'overall_status' => 'pending'
    ));
    
    $test_records = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wfs_records LIMIT 5");
}

$representatives = get_users(array('role__in' => array('administrator', 'wfs_representative', 'wfs_superadmin')));
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
            <input type="text" id="wfs-search" placeholder="Ad, soyad veya e-posta ile arayın..." class="wfs-input">
        </div>
        
        <div class="wfs-filter-item">
            <label>📊 Statü</label>
            <select id="wfs-status-filter" class="wfs-select">
                <option value="">Tüm Statüler</option>
                <option value="pending">Beklemede</option>
                <option value="approved">Onaylandı</option>
                <option value="rejected">Reddedildi</option>
            </select>
        </div>
        
        <div class="wfs-filter-item">
            <label>👤 Temsilci</label>
            <select id="wfs-rep-filter" class="wfs-select">
                <option value="">Tüm Temsilciler</option>
                <?php foreach ($representatives as $rep): ?>
                    <option value="<?php echo $rep->ID; ?>"><?php echo esc_html($rep->display_name); ?></option>
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
        <?php
// templates/admin-page.php - Güncellenmiş kayıt görüntüleme kısmı
// Bu kod bloğunu mevcut admin-page.php'deki kayıt kartları bölümüne ekleyin

// Kayıt detaylarını gösterirken tüm form verilerini dinamik olarak göster
function display_record_details($record, $custom_form) {
    global $wpdb;
    
    // Form builder'dan kaydedilen verileri al
    $submission_data = array();
    
    // Eğer custom form ile oluşturulmuşsa
    if ($record->fluent_form_id == 0) {
        // wp_wfs_form_submissions tablosundan verileri çek
        $form_data = $wpdb->get_var($wpdb->prepare(
            "SELECT form_data FROM {$wpdb->prefix}wfs_form_submissions WHERE record_id = %d",
            $record->id
        ));
        
        if ($form_data) {
            $submission_data = json_decode($form_data, true);
        }
    }
    ?>
    
    <div class="wfs-record-details" data-record-id="<?php echo $record->id; ?>" style="display: none;">
        <div class="wfs-details-grid">
            <!-- Standart Alanlar -->
            <div class="wfs-info-section">
                <h4>👤 Kişisel Bilgiler</h4>
                <div class="wfs-info-list">
                    <?php if (!empty($record->first_name)): ?>
                        <div><strong>Ad:</strong> <?php echo esc_html($record->first_name); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record->last_name)): ?>
                        <div><strong>Soyad:</strong> <?php echo esc_html($record->last_name); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record->email)): ?>
                        <div><strong>📧 E-posta:</strong> <?php echo esc_html($record->email); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record->phone)): ?>
                        <div><strong>📞 Telefon:</strong> <?php echo esc_html($record->phone); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record->age)): ?>
                        <div><strong>🎂 Yaş:</strong> <?php echo esc_html($record->age); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record->education_level)): ?>
                        <div><strong>🎓 Eğitim:</strong> <?php echo esc_html($record->education_level); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record->department)): ?>
                        <div><strong>🏢 Bölüm:</strong> <?php echo esc_html($record->department); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dinamik Form Alanları -->
            <?php if (!empty($submission_data) && !empty($custom_form['fields'])): ?>
                <div class="wfs-info-section">
                    <h4>📝 Ek Bilgiler</h4>
                    <div class="wfs-info-list">
                        <?php 
                        foreach ($custom_form['fields'] as $field):
                            $field_key = 'field_' . $field['id'];
                            if (isset($submission_data[$field_key]) && !empty($submission_data[$field_key])):
                                // Standart alanları tekrar gösterme
                                $skip_labels = ['ad', 'soyad', 'e-posta', 'email', 'telefon', 'yaş', 'eğitim', 'bölüm', 'department'];
                                $should_skip = false;
                                foreach ($skip_labels as $skip) {
                                    if (stripos($field['label'], $skip) !== false) {
                                        $should_skip = true;
                                        break;
                                    }
                                }
                                
                                if (!$should_skip):
                                    ?>
                                    <div>
                                        <strong><?php echo esc_html($field['label']); ?>:</strong>
                                        <?php 
                                        if (is_array($submission_data[$field_key])) {
                                            echo esc_html(implode(', ', $submission_data[$field_key]));
                                        } else {
                                            echo esc_html($submission_data[$field_key]);
                                        }
                                        ?>
                                    </div>
                                    <?php
                                endif;
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Atama -->
            <div class="wfs-info-section">
                <h4>🎯 Atama</h4>
                <?php 
                // Tüm workflow rollerini getir
                $assignable_users = get_users(array(
                    'role__in' => array(
                        'administrator',
                        'wfs_superadmin',
                        'wfs_representative', 
                        'wfs_consultant',
                        'editor' // Editor rolü de ekleyelim
                    ),
                    'orderby' => 'display_name',
                    'order' => 'ASC'
                ));
                ?>
                
                <?php if ($record->assigned_to): ?>
                    <?php $assigned_user = get_user_by('id', $record->assigned_to); ?>
                    <div class="wfs-assigned-info">
                        <strong>Atanan:</strong> <?php echo $assigned_user ? esc_html($assigned_user->display_name) : 'Bilinmiyor'; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (current_user_can('manage_options') || current_user_can('wfs_assign_records')): ?>
                    <div class="wfs-assign-form">
                        <select class="wfs-assign-select wfs-select" data-record-id="<?php echo $record->id; ?>">
                            <option value="">Kullanıcı Seçin</option>
                            <?php foreach ($assignable_users as $user): ?>
                                <?php 
                                $role_names = array(
                                    'administrator' => 'Admin',
                                    'wfs_superadmin' => 'Süperadmin',
                                    'wfs_representative' => 'Temsilci',
                                    'wfs_consultant' => 'Danışan',
                                    'editor' => 'Editör'
                                );
                                $user_role_display = '';
                                foreach ($user->roles as $role) {
                                    if (isset($role_names[$role])) {
                                        $user_role_display = $role_names[$role];
                                        break;
                                    }
                                }
                                ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected($record->assigned_to, $user->ID); ?>>
                                    <?php echo esc_html($user->display_name); ?> (<?php echo $user_role_display; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="wfs-assign-btn wfs-btn wfs-btn-primary" data-record-id="<?php echo $record->id; ?>">
                            Ata
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Statü Yönetimi -->
            <div class="wfs-info-section">
                <h4>📊 Statü</h4>
                <?php 
                // Statüleri ayarlardan al
                $status_settings = get_option('wfs_status_settings', array(
                    'pending' => array('label' => 'Beklemede', 'color' => '#f59e0b', 'bg' => '#fef3c7'),
                    'approved' => array('label' => 'Onaylandı', 'color' => '#10b981', 'bg' => '#d1fae5'),
                    'rejected' => array('label' => 'Reddedildi', 'color' => '#ef4444', 'bg' => '#fee2e2'),
                    'processing' => array('label' => 'İşleniyor', 'color' => '#3b82f6', 'bg' => '#dbeafe'),
                    'completed' => array('label' => 'Tamamlandı', 'color' => '#8b5cf6', 'bg' => '#ede9fe')
                ));
                ?>
                
                <div class="wfs-current-status" style="margin-bottom: 1rem;">
                    <strong>Mevcut Statü:</strong>
                    <span class="wfs-status-badge" style="background: <?php echo $status_settings[$record->overall_status]['bg']; ?>; color: <?php echo $status_settings[$record->overall_status]['color']; ?>;">
                        <span class="wfs-status-light" style="background: <?php echo $status_settings[$record->overall_status]['color']; ?>;"></span>
                        <?php echo $status_settings[$record->overall_status]['label']; ?>
                    </span>
                </div>
                
                <?php if (current_user_can('manage_options') || current_user_can('wfs_update_status')): ?>
                    <div class="wfs-status-update">
                        <select class="wfs-status-select wfs-select" data-record-id="<?php echo $record->id; ?>">
                            <?php foreach ($status_settings as $status_key => $status_info): ?>
                                <option value="<?php echo $status_key; ?>" <?php selected($record->overall_status, $status_key); ?>>
                                    <?php echo esc_html($status_info['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="wfs-update-status-btn wfs-btn wfs-btn-primary" data-record-id="<?php echo $record->id; ?>">
                            Statü Güncelle
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tarihler -->
            <div class="wfs-info-section">
                <h4>📅 Tarihler</h4>
                <div class="wfs-info-list">
                    <div><strong>Oluşturulma:</strong> <?php echo date('d.m.Y H:i', strtotime($record->created_at)); ?></div>
                    <div><strong>Güncellenme:</strong> <?php echo date('d.m.Y H:i', strtotime($record->updated_at)); ?></div>
                </div>
            </div>
        </div>

        <!-- Dosyalar -->
        <div class="wfs-files-section">
            <h4>📁 Yüklenen Dosyalar</h4>
            <?php 
            $files = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wfs_files WHERE record_id = %d ORDER BY uploaded_at DESC",
                $record->id
            ));
            
            if (!empty($files)): 
            ?>
                <div class="wfs-files-grid">
                    <?php foreach ($files as $file): ?>
                        <?php 
                        $file_url = str_replace(ABSPATH, home_url('/'), $file->file_path);
                        $file_icon = '📄';
                        if (strpos($file->file_type, 'image') !== false) $file_icon = '🖼️';
                        elseif (strpos($file->file_type, 'pdf') !== false) $file_icon = '📑';
                        
                        $file_status_colors = array(
                            'pending' => '#f59e0b',
                            'approved' => '#10b981',
                            'rejected' => '#ef4444'
                        );
                        ?>
                        <div class="wfs-file-item" style="border: 1px solid #e5e7eb; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span style="font-size: 1.5rem; margin-right: 0.5rem;"><?php echo $file_icon; ?></span>
                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" style="color: #3b82f6; text-decoration: none;">
                                        <?php echo esc_html($file->file_name); ?>
                                    </a>
                                    <span style="color: #6b7280; font-size: 0.875rem; margin-left: 0.5rem;">
                                        (<?php echo round($file->file_size / 1024, 2); ?> KB)
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="padding: 0.25rem 0.75rem; background: <?php echo $file_status_colors[$file->status]; ?>20; color: <?php echo $file_status_colors[$file->status]; ?>; border-radius: 4px; font-size: 0.875rem;">
                                        <?php echo ucfirst($file->status); ?>
                                    </span>
                                    <?php if (current_user_can('wfs_review_files')): ?>
                                        <select class="wfs-file-status-select" data-file-id="<?php echo $file->id; ?>" style="padding: 0.25rem; border: 1px solid #d1d5db; border-radius: 4px;">
                                            <option value="pending" <?php selected($file->status, 'pending'); ?>>Beklemede</option>
                                            <option value="approved" <?php selected($file->status, 'approved'); ?>>Onayla</option>
                                            <option value="rejected" <?php selected($file->status, 'rejected'); ?>>Reddet</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($file->review_notes): ?>
                                <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f9fafb; border-radius: 4px;">
                                    <small style="color: #6b7280;">Not: <?php echo esc_html($file->review_notes); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="wfs-no-files">
                    <p>Bu kayıt için henüz dosya yüklenmemiş.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>
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