<?php
if (!defined('ABSPATH')) exit;

// Ayarları kaydet
if (isset($_POST['save_permissions'])) {
    // Yetki ayarlarını kaydet
    update_option('wfs_hide_wp_menus', isset($_POST['hide_wp_menus']));
    update_option('wfs_permissions', $_POST['permissions'] ?? array());
    echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
}

if (isset($_POST['save_statuses']) && check_admin_referer('wfs_save_statuses')) {
    $statuses_input = $_POST['statuses'] ?? array();
    $sanitized_statuses = array();

    if (is_array($statuses_input)) {
        foreach ($statuses_input as $status) {
            $slug  = isset($status['slug']) ? sanitize_key($status['slug']) : '';
            $label = isset($status['label']) ? sanitize_text_field($status['label']) : '';
            $color = isset($status['color']) ? sanitize_hex_color($status['color']) : '';
            $bg    = isset($status['bg']) ? sanitize_hex_color($status['bg']) : '';

            if (empty($slug) || empty($label)) {
                continue;
            }

            if (!$color) {
                $color = '#3b82f6';
            }

            if (!$bg) {
                $bg = '#dbeafe';
            }

            $sanitized_statuses[$slug] = array(
                'label' => $label,
                'color' => $color,
                'bg'    => $bg,
            );
        }
    }

    update_option('wfs_status_settings', $sanitized_statuses);
    echo '<div class="notice notice-success"><p>Statüler güncellendi!</p></div>';
}

// Mevcut ayarları getir
$hide_wp_menus = get_option('wfs_hide_wp_menus', false);
$permissions = get_option('wfs_permissions', array());

// Tüm kullanıcıları getir
$all_users = get_users();

$editable_statuses = isset($status_settings) && is_array($status_settings) ? $status_settings : array();
?>

<div class="wrap">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
        <h1 style="margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700;">⚙️ Sistem Ayarları</h1>
        <p style="margin: 0; opacity: 0.9;">İş akışı sistemi yapılandırması</p>
    </div>

    <!-- Tab Navigation -->
    <div style="background: white; margin-bottom: 1rem; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; border-bottom: 1px solid #e5e7eb;">
            <button class="settings-tab active" data-tab="permissions" style="padding: 1rem 2rem; border: none; background: #3b82f6; color: white; cursor: pointer;">
                👥 Kullanıcı Yetkileri
            </button>
            <button class="settings-tab" data-tab="form-builder" style="padding: 1rem 2rem; border: none; background: white; color: #6b7280; cursor: pointer;">
                📝 Form Builder
            </button>
            <button class="settings-tab" data-tab="fluent-forms" style="padding: 1rem 2rem; border: none; background: white; color: #6b7280; cursor: pointer;">
                🔗 FluentForms
            </button>
            <button class="settings-tab" data-tab="email-templates" style="padding: 1rem 2rem; border: none; background: white; color: #6b7280; cursor: pointer;">
                📧 Mail Şablonları
            </button>
            <button class="settings-tab" data-tab="status-management" style="padding: 1rem 2rem; border: none; background: white; color: #6b7280; cursor: pointer;">
                🎯 Statü Yönetimi
            </button>
        </div>
    </div>

    <!-- Tab Contents -->
    <form method="post">
        <!-- Kullanıcı Yetkileri Tab -->
        <div id="permissions" class="tab-content active" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2 style="margin: 0 0 1.5rem 0; color: #1f2937;">👥 Kullanıcı Rolleri ve Yetkileri</h2>

            <!-- WordPress Menü Gizleme -->
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 2rem; border-radius: 4px;">
                <h3 style="margin: 0 0 1rem 0; color: #92400e;">🔒 WordPress Menü Güvenliği</h3>
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="hide_wp_menus" <?php checked($hide_wp_menus); ?>>
                    <span>WordPress varsayılan menülerini İş Akışı rollerinden gizle</span>
                </label>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #92400e;">
                    Bu seçenek aktif edildiğinde, İş Akışı rollerindeki kullanıcılar sadece İş Akışı menülerini görecek.
                </p>
            </div>

            <!-- Kullanıcı Listesi -->
            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1rem 0; color: #374151;">Kullanıcı Yetkileri</h3>
                <div style="background: #f9fafb; border-radius: 8px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #e5e7eb;">
                            <tr>
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Kullanıcı</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600;">Rol</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600;">Kayıt Görme</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600;">Kayıt Oluşturma</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600;">Dosya İnceleme</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600;">Atama Yapma</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600;">Rapor Görme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <?php 
                                $user_roles = $user->roles;
                                $is_workflow_user = array_intersect($user_roles, ['wfs_superadmin', 'wfs_representative', 'wfs_consultant', 'editor']) || in_array('administrator', $user_roles);
                                if (!$is_workflow_user) continue;
                                
                                $user_permissions = $permissions[$user->ID] ?? array();
                                ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?php echo strtoupper(substr($user->display_name, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo esc_html($user->display_name); ?></div>
                                                <div style="font-size: 0.875rem; color: #6b7280;"><?php echo esc_html($user->user_email); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?php 
                                        $role_names = array(
                                            'administrator' => 'Admin',
                                            'wfs_superadmin' => 'Süperadmin',
                                            'wfs_representative' => 'Temsilci',
                                            'wfs_consultant' => 'Danışan'
                                        );
                                        $user_role = array_intersect_key($role_names, array_flip($user_roles));
                                        echo esc_html(implode(', ', $user_role));
                                        ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <input type="checkbox" name="permissions[<?php echo $user->ID; ?>][view_records]" <?php checked(isset($user_permissions['view_records'])); ?>>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <input type="checkbox" name="permissions[<?php echo $user->ID; ?>][create_records]" <?php checked(isset($user_permissions['create_records'])); ?>>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <input type="checkbox" name="permissions[<?php echo $user->ID; ?>][review_files]" <?php checked(isset($user_permissions['review_files'])); ?>>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <input type="checkbox" name="permissions[<?php echo $user->ID; ?>][assign_records]" <?php checked(isset($user_permissions['assign_records'])); ?>>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <input type="checkbox" name="permissions[<?php echo $user->ID; ?>][view_reports]" <?php checked(isset($user_permissions['view_reports'])); ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kaydet Butonu -->
            <button type="submit" name="save_permissions" style="background: #10b981; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                💾 Yetkileri Kaydet
            </button>
        </div>

        <!-- Diğer Tab'lar (Şimdilik Boş) -->
      <div id="form-builder" class="tab-content" style="display: none; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <h2 style="margin: 0 0 2rem 0; color: #1f2937;">📝 Form Builder</h2>
    
    <div class="form-builder-container" style="display: grid; grid-template-columns: 250px 1fr 300px; gap: 2rem; height: 600px;">
        <!-- Sol Panel - Alan Türleri -->
        <div class="field-types-panel" style="background: #f8fafc; border-radius: 8px; padding: 1rem; overflow-y: auto;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: #374151;">Alan Türleri</h3>
            
            <div class="draggable-fields">
                <div class="field-type" draggable="true" data-type="text">
                    <span class="field-icon">📝</span>
                    <span class="field-name">Metin Girişi</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="email">
                    <span class="field-icon">📧</span>
                    <span class="field-name">E-posta</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="phone">
                    <span class="field-icon">📞</span>
                    <span class="field-name">Telefon</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="number">
                    <span class="field-icon">🔢</span>
                    <span class="field-name">Sayı</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="textarea">
                    <span class="field-icon">📄</span>
                    <span class="field-name">Uzun Metin</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="select">
                    <span class="field-icon">📋</span>
                    <span class="field-name">Açılır Liste</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="radio">
                    <span class="field-icon">◯</span>
                    <span class="field-name">Radio Button</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="checkbox">
                    <span class="field-icon">☑️</span>
                    <span class="field-name">Checkbox</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="file">
                    <span class="field-icon">📎</span>
                    <span class="field-name">Dosya Yükleme</span>
                </div>
                
                <div class="field-type" draggable="true" data-type="date">
                    <span class="field-icon">📅</span>
                    <span class="field-name">Tarih</span>
                </div>
            </div>
        </div>
        
        <!-- Orta Panel - Form Tasarım Alanı -->
        <div class="form-design-area" style="background: white; border: 2px dashed #d1d5db; border-radius: 8px; padding: 2rem; position: relative; overflow-y: auto;">
            <div id="form-canvas" class="form-canvas">
                <div class="canvas-placeholder" style="text-align: center; color: #9ca3af; padding: 4rem 0;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎨</div>
                    <h3 style="margin: 0 0 0.5rem 0;">Form Tasarım Alanı</h3>
                    <p style="margin: 0;">Sol panelden alan türlerini sürükleyip buraya bırakın</p>
                </div>
            </div>
        </div>
        
        <!-- Sağ Panel - Alan Özellikleri -->
        <div class="field-properties-panel" style="background: #f8fafc; border-radius: 8px; padding: 1rem; overflow-y: auto;">
            <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: #374151;">Alan Özellikleri</h3>
            
            <div id="field-properties" class="field-properties">
                <div class="no-selection" style="text-align: center; color: #9ca3af; padding: 2rem 0;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div>
                    <p style="margin: 0; font-size: 0.875rem;">Bir alan seçin</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alt Panel - Eylem Butonları -->
    <div class="form-actions" style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
        <div class="form-actions-left">
            <button type="button" id="clear-form" class="btn-secondary" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; margin-right: 0.5rem;">
                🗑️ Temizle
            </button>
            <button type="button" id="preview-form" class="btn-info" style="background: #0ea5e9; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer;">
                👁️ Önizleme
            </button>
        </div>
        
        <div class="form-actions-right">
            <button type="button" id="save-form" class="btn-success" style="background: #10b981; color: white; border: none; padding: 0.75rem 2rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                💾 Formu Kaydet
            </button>
        </div>
    </div>
</div>

<!-- Form Builder Styles -->
<style>
.form-builder-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.field-types-panel, .field-properties-panel {
    border: 1px solid #e5e7eb;
}

.field-type {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: grab;
    transition: all 0.2s ease;
    user-select: none;
}

.field-type:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
    transform: translateY(-1px);
}

.field-type:active {
    cursor: grabbing;
    transform: scale(0.98);
}

.field-icon {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.field-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.form-canvas {
    min-height: 500px;
    position: relative;
}

.form-canvas.dragover {
    background: #eff6ff;
    border-color: #3b82f6;
}

.form-field-container {
    margin-bottom: 1rem;
    padding: 1rem;
    border: 2px solid transparent;
    border-radius: 6px;
    position: relative;
    transition: all 0.2s ease;
    background: #f8fafc;
}

.form-field-container:hover {
    border-color: #e5e7eb;
}

.form-field-container.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

.form-field-container .field-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: none;
    gap: 0.25rem;
}

.form-field-container:hover .field-actions,
.form-field-container.selected .field-actions {
    display: flex;
}

.field-action-btn {
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.field-action-btn.edit {
    background: #3b82f6;
    color: white;
}

.field-action-btn.delete {
    background: #ef4444;
    color: white;
}

.field-action-btn:hover {
    transform: scale(1.1);
}

.form-field-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.form-field-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    background: white;
}

.form-field-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.required-asterisk {
    color: #ef4444;
    margin-left: 2px;
}

.field-properties .property-group {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.field-properties .property-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.property-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.property-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.875rem;
}

.property-checkbox {
    margin-right: 0.5rem;
}

.options-list {
    margin-top: 0.5rem;
}

.option-item {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.option-input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.875rem;
}

.remove-option {
    background: #ef4444;
    color: white;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.75rem;
}

.add-option {
    background: #10b981;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .form-builder-container {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .field-types-panel {
        order: 1;
        height: 150px;
    }
    
    .form-design-area {
        order: 2;
        min-height: 400px;
    }
    
    .field-properties-panel {
        order: 3;
        min-height: 300px;
    }
}

@media (max-width: 768px) {
    .form-builder-container {
        gap: 1rem;
    }
    
    .draggable-fields {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 0.5rem;
    }
    
    .field-type {
        flex-direction: column;
        padding: 0.5rem;
        text-align: center;
    }
    
    .field-name {
        font-size: 0.75rem;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-actions-left,
    .form-actions-right {
        width: 100%;
        text-align: center;
    }
}

/* Drag and Drop Visual Feedback */
.field-type.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.form-canvas.drag-over {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-color: #3b82f6;
    border-style: solid;
}

.drop-zone {
    min-height: 50px;
    border: 2px dashed #cbd5e1;
    border-radius: 4px;
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.drop-zone.active {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #3b82f6;
}

.btn-secondary:hover { background: #4b5563 !important; }
.btn-info:hover { background: #0284c7 !important; }
.btn-success:hover { background: #059669 !important; }

.btn-secondary:active,
.btn-info:active,
.btn-success:active {
    transform: translateY(1px);
}
</style>

<!-- Form Builder JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form builder state
    let formFields = [];
    let selectedField = null;
    let fieldCounter = 0;

    // DOM elements
    const formCanvas = document.getElementById('form-canvas');
    const fieldProperties = document.getElementById('field-properties');
    const draggableFields = document.querySelectorAll('.field-type');

    // Initialize drag and drop
    initializeDragAndDrop();
    
    // Event listeners
    document.getElementById('clear-form').addEventListener('click', clearForm);
    document.getElementById('preview-form').addEventListener('click', previewForm);
    document.getElementById('save-form').addEventListener('click', saveForm);

    function initializeDragAndDrop() {
        // Make field types draggable
        draggableFields.forEach(field => {
            field.addEventListener('dragstart', handleDragStart);
            field.addEventListener('dragend', handleDragEnd);
        });

        // Make canvas droppable
        formCanvas.addEventListener('dragover', handleDragOver);
        formCanvas.addEventListener('drop', handleDrop);
        formCanvas.addEventListener('dragleave', handleDragLeave);
    }

    function handleDragStart(e) {
        e.dataTransfer.setData('text/plain', this.dataset.type);
        this.classList.add('dragging');
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
    }

    function handleDragOver(e) {
        e.preventDefault();
        formCanvas.classList.add('drag-over');
    }

    function handleDragLeave(e) {
        if (!formCanvas.contains(e.relatedTarget)) {
            formCanvas.classList.remove('drag-over');
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        formCanvas.classList.remove('drag-over');
        
        const fieldType = e.dataTransfer.getData('text/plain');
        if (fieldType) {
            addField(fieldType);
        }
    }

    function addField(type) {
        fieldCounter++;
        const fieldId = 'field_' + fieldCounter;
        
        const fieldConfig = {
            id: fieldId,
            type: type,
            label: getDefaultLabel(type),
            placeholder: '',
            required: false,
            options: type === 'select' || type === 'radio' || type === 'checkbox' ? ['Seçenek 1', 'Seçenek 2'] : null,
            validation: {}
        };

        formFields.push(fieldConfig);
        renderField(fieldConfig);
        selectField(fieldConfig);
        
        // Remove placeholder if exists
        const placeholder = formCanvas.querySelector('.canvas-placeholder');
        if (placeholder) {
            placeholder.remove();
        }
    }

    function getDefaultLabel(type) {
        const labels = {
            'text': 'Metin Alanı',
            'email': 'E-posta Adresi',
            'phone': 'Telefon Numarası',
            'number': 'Sayı',
            'textarea': 'Uzun Metin',
            'select': 'Açılır Liste',
            'radio': 'Radio Button',
            'checkbox': 'Checkbox',
            'file': 'Dosya Yükle',
            'date': 'Tarih'
        };
        return labels[type] || 'Alan';
    }

    function renderField(field) {
        const fieldHtml = createFieldHTML(field);
        const fieldElement = document.createElement('div');
        fieldElement.className = 'form-field-container';
        fieldElement.dataset.fieldId = field.id;
        fieldElement.innerHTML = fieldHtml;
        
        // Add event listeners
        fieldElement.addEventListener('click', () => selectField(field));
        fieldElement.querySelector('.field-action-btn.edit').addEventListener('click', (e) => {
            e.stopPropagation();
            selectField(field);
        });
        fieldElement.querySelector('.field-action-btn.delete').addEventListener('click', (e) => {
            e.stopPropagation();
            deleteField(field.id);
        });

        formCanvas.appendChild(fieldElement);
    }

    function createFieldHTML(field) {
        let inputHtml = '';
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'phone':
            case 'number':
                inputHtml = `<input type="${field.type}" class="form-field-input" placeholder="${field.placeholder}" ${field.required ? 'required' : ''} disabled>`;
                break;
            case 'textarea':
                inputHtml = `<textarea class="form-field-input" placeholder="${field.placeholder}" rows="3" ${field.required ? 'required' : ''} disabled></textarea>`;
                break;
            case 'select':
                inputHtml = `<select class="form-field-input" ${field.required ? 'required' : ''} disabled>
                    ${field.options.map(opt => `<option>${opt}</option>`).join('')}
                </select>`;
                break;
            case 'radio':
                inputHtml = field.options.map((opt, index) => 
                    `<label style="display: block; margin: 0.5rem 0;">
                        <input type="radio" name="${field.id}" value="${opt}" disabled style="margin-right: 0.5rem;">
                        ${opt}
                    </label>`
                ).join('');
                break;
            case 'checkbox':
                inputHtml = field.options.map((opt, index) => 
                    `<label style="display: block; margin: 0.5rem 0;">
                        <input type="checkbox" value="${opt}" disabled style="margin-right: 0.5rem;">
                        ${opt}
                    </label>`
                ).join('');
                break;
            case 'file':
                inputHtml = `<input type="file" class="form-field-input" ${field.required ? 'required' : ''} disabled>`;
                break;
            case 'date':
                inputHtml = `<input type="date" class="form-field-input" ${field.required ? 'required' : ''} disabled>`;
                break;
        }

        return `
            <div class="field-actions">
                <button class="field-action-btn edit" title="Düzenle">✏️</button>
                <button class="field-action-btn delete" title="Sil">🗑️</button>
            </div>
            <label class="form-field-label">
                ${field.label}
                ${field.required ? '<span class="required-asterisk">*</span>' : ''}
            </label>
            ${inputHtml}
        `;
    }

    function selectField(field) {
        // Remove previous selection
        document.querySelectorAll('.form-field-container.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Select new field
        const fieldElement = document.querySelector(`[data-field-id="${field.id}"]`);
        if (fieldElement) {
            fieldElement.classList.add('selected');
        }
        
        selectedField = field;
        renderFieldProperties(field);
    }

    function renderFieldProperties(field) {
        let propertiesHtml = `
            <div class="property-group">
                <label class="property-label">Alan Etiketi</label>
                <input type="text" class="property-input" id="field-label" value="${field.label}">
            </div>
            
            <div class="property-group">
                <label class="property-label">Placeholder Metin</label>
                <input type="text" class="property-input" id="field-placeholder" value="${field.placeholder}">
            </div>
            
            <div class="property-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" class="property-checkbox" id="field-required" ${field.required ? 'checked' : ''}>
                    Zorunlu Alan
                </label>
            </div>
        `;

        if (field.options) {
            propertiesHtml += `
                <div class="property-group">
                    <label class="property-label">Seçenekler</label>
                    <div class="options-list" id="options-list">
                        ${field.options.map((option, index) => `
                            <div class="option-item">
                                <input type="text" class="option-input" value="${option}" data-index="${index}">
                                <button class="remove-option" onclick="removeOption(${index})">×</button>
                            </div>
                        `).join('')}
                    </div>
                    <button class="add-option" onclick="addOption()">+ Seçenek Ekle</button>
                </div>
            `;
        }

        fieldProperties.innerHTML = propertiesHtml;

        // Add event listeners
        document.getElementById('field-label').addEventListener('input', updateFieldProperty);
        document.getElementById('field-placeholder').addEventListener('input', updateFieldProperty);
        document.getElementById('field-required').addEventListener('change', updateFieldProperty);
        
        if (field.options) {
            document.querySelectorAll('.option-input').forEach(input => {
                input.addEventListener('input', updateOptions);
            });
        }
    }

    function updateFieldProperty(e) {
        if (!selectedField) return;

        const property = e.target.id.replace('field-', '');
        let value = e.target.type === 'checkbox' ? e.target.checked : e.target.value;

        selectedField[property] = value;

        // Update the field in formFields array
        const fieldIndex = formFields.findIndex(f => f.id === selectedField.id);
        if (fieldIndex !== -1) {
            formFields[fieldIndex] = selectedField;
        }

        // Re-render the field
        const fieldElement = document.querySelector(`[data-field-id="${selectedField.id}"]`);
        if (fieldElement) {
            fieldElement.innerHTML = createFieldHTML(selectedField);
            // Re-add event listeners
            fieldElement.querySelector('.field-action-btn.edit').addEventListener('click', (e) => {
                e.stopPropagation();
                selectField(selectedField);
            });
            fieldElement.querySelector('.field-action-btn.delete').addEventListener('click', (e) => {
                e.stopPropagation();
                deleteField(selectedField.id);
            });
        }
    }

    function updateOptions() {
        if (!selectedField || !selectedField.options) return;

        const optionInputs = document.querySelectorAll('.option-input');
        selectedField.options = Array.from(optionInputs).map(input => input.value);

        // Update the field in formFields array
        const fieldIndex = formFields.findIndex(f => f.id === selectedField.id);
        if (fieldIndex !== -1) {
            formFields[fieldIndex].options = selectedField.options;
        }

        // Re-render the field
        const fieldElement = document.querySelector(`[data-field-id="${selectedField.id}"]`);
        if (fieldElement) {
            fieldElement.innerHTML = createFieldHTML(selectedField);
            fieldElement.querySelector('.field-action-btn.edit').addEventListener('click', (e) => {
                e.stopPropagation();
                selectField(selectedField);
            });
            fieldElement.querySelector('.field-action-btn.delete').addEventListener('click', (e) => {
                e.stopPropagation();
                deleteField(selectedField.id);
            });
        }
    }

    window.addOption = function() {
        if (!selectedField || !selectedField.options) return;
        
        selectedField.options.push('Yeni Seçenek');
        renderFieldProperties(selectedField);
    };

    window.removeOption = function(index) {
        if (!selectedField || !selectedField.options) return;
        
        selectedField.options.splice(index, 1);
        renderFieldProperties(selectedField);
    };

    function deleteField(fieldId) {
        if (confirm('Bu alanı silmek istediğinizden emin misiniz?')) {
            // Remove from formFields array
            formFields = formFields.filter(f => f.id !== fieldId);
            
            // Remove from DOM
            const fieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
            if (fieldElement) {
                fieldElement.remove();
            }
            
            // Clear properties if this field was selected
            if (selectedField && selectedField.id === fieldId) {
                selectedField = null;
                fieldProperties.innerHTML = '<div class="no-selection" style="text-align: center; color: #9ca3af; padding: 2rem 0;"><div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div><p style="margin: 0; font-size: 0.875rem;">Bir alan seçin</p></div>';
            }
            
            // Show placeholder if no fields
            if (formFields.length === 0) {
                formCanvas.innerHTML = '<div class="canvas-placeholder" style="text-align: center; color: #9ca3af; padding: 4rem 0;"><div style="font-size: 3rem; margin-bottom: 1rem;">🎨</div><h3 style="margin: 0 0 0.5rem 0;">Form Tasarım Alanı</h3><p style="margin: 0;">Sol panelden alan türlerini sürükleyip buraya bırakın</p></div>';
            }
        }
    }

    function clearForm() {
        if (confirm('Tüm form alanlarını silmek istediğinizden emin misiniz?')) {
            formFields = [];
            selectedField = null;
            fieldCounter = 0;
            
            formCanvas.innerHTML = '<div class="canvas-placeholder" style="text-align: center; color: #9ca3af; padding: 4rem 0;"><div style="font-size: 3rem; margin-bottom: 1rem;">🎨</div><h3 style="margin: 0 0 0.5rem 0;">Form Tasarım Alanı</h3><p style="margin: 0;">Sol panelden alan türlerini sürükleyip buraya bırakın</p></div>';
            fieldProperties.innerHTML = '<div class="no-selection" style="text-align: center; color: #9ca3af; padding: 2rem 0;"><div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div><p style="margin: 0; font-size: 0.875rem;">Bir alan seçin</p></div>';
        }
    }

    function previewForm() {
        if (formFields.length === 0) {
            alert('Önizleme için en az bir alan eklemelisiniz.');
            return;
        }

        // Create preview modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            border-radius: 8px;
            padding: 2rem;
            overflow-y: auto;
        `;
        
        let previewHtml = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="margin: 0;">Form Önizlemesi</h3>
                <button onclick="this.closest('.modal').remove()" style="background: #ef4444; color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">×</button>
            </div>
            <form style="space-y: 1rem;">
        `;
        
        formFields.forEach(field => {
            previewHtml += createPreviewFieldHTML(field);
        });
        
        previewHtml += `
                <button type="submit" style="background: #3b82f6; color: white; border: none; padding: 1rem 2rem; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 2rem;">
                    Gönder
                </button>
            </form>
        `;
        
        modalContent.innerHTML = previewHtml;
        modal.appendChild(modalContent);
        modal.className = 'modal';
        document.body.appendChild(modal);
    }

    function createPreviewFieldHTML(field) {
        let fieldHtml = `<div style="margin-bottom: 1.5rem;">`;
        fieldHtml += `<label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">${field.label}${field.required ? ' <span style="color: #ef4444;">*</span>' : ''}</label>`;
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'phone':
            case 'number':
                fieldHtml += `<input type="${field.type}" placeholder="${field.placeholder}" ${field.required ? 'required' : ''} style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">`;
                break;
            case 'textarea':
                fieldHtml += `<textarea placeholder="${field.placeholder}" rows="4" ${field.required ? 'required' : ''} style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical;"></textarea>`;
                break;
            case 'select':
                fieldHtml += `<select ${field.required ? 'required' : ''} style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    <option value="">Seçiniz...</option>
                    ${field.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                </select>`;
                break;
            case 'radio':
                field.options.forEach(opt => {
                    fieldHtml += `<label style="display: block; margin: 0.5rem 0;"><input type="radio" name="${field.id}" value="${opt}" ${field.required ? 'required' : ''} style="margin-right: 0.5rem;"> ${opt}</label>`;
                });
                break;
            case 'checkbox':
                field.options.forEach(opt => {
                    fieldHtml += `<label style="display: block; margin: 0.5rem 0;"><input type="checkbox" value="${opt}" style="margin-right: 0.5rem;"> ${opt}</label>`;
                });
                break;
            case 'file':
                fieldHtml += `<input type="file" ${field.required ? 'required' : ''} style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">`;
                break;
            case 'date':
                fieldHtml += `<input type="date" ${field.required ? 'required' : ''} style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">`;
                break;
        }
        
        fieldHtml += `</div>`;
        return fieldHtml;
    }

    function saveForm() {
        if (formFields.length === 0) {
            alert('Kaydetmek için en az bir alan eklemelisiniz.');
            return;
        }

        const formData = {
            fields: formFields,
            updated_at: new Date().toISOString()
        };

        // AJAX ile formu kaydet
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wfs_save_form',
                nonce: '<?php echo wp_create_nonce('wfs_form_nonce'); ?>',
                form_data: JSON.stringify(formData)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Form başarıyla kaydedildi!');
                
                // Success animation
                const saveBtn = document.getElementById('save-form');
                const originalText = saveBtn.textContent;
                saveBtn.textContent = '✅ Kaydedildi!';
                saveBtn.style.background = '#10b981';
                
                setTimeout(() => {
                    saveBtn.textContent = originalText;
                    saveBtn.style.background = '#10b981';
                }, 2000);
            } else {
                alert('Form kaydedilirken hata oluştu: ' + (data.data || ''));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bağlantı hatası oluştu.');
        });
    }

    // Load existing form on page load
    loadExistingForm();

    function loadExistingForm() {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wfs_get_form',
                nonce: '<?php echo wp_create_nonce('wfs_form_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.fields && data.data.fields.length > 0) {
                formFields = data.data.fields;
                fieldCounter = Math.max(...formFields.map(f => parseInt(f.id.replace('field_', '')))) || 0;
                
                // Remove placeholder
                const placeholder = formCanvas.querySelector('.canvas-placeholder');
                if (placeholder) {
                    placeholder.remove();
                }
                
                // Render existing fields
                formFields.forEach(field => {
                    renderField(field);
                });
            }
        })
        .catch(error => {
            console.error('Form yüklenirken hata:', error);
        });
    }
});
</script>

        <div id="fluent-forms" class="tab-content" style="display: none; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2>🔗 FluentForms Entegrasyonu - Yakında...</h2>
        </div>

        <div id="email-templates" class="tab-content" style="display: none; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2>📧 Mail Şablonları - Yakında...</h2>
        </div>

        <div id="status-management" class="tab-content" style="display: none; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h2 style="margin: 0 0 1.5rem 0; color: #1f2937;">🎯 Statü Yönetimi</h2>
            <p style="margin: 0 0 1.5rem 0; color: #4b5563;">Kayıt statülerini özelleştirin. Her statü için benzersiz bir anahtar belirleyin, renkleri seçin ve gerekirse yeni statüler ekleyin.</p>

            <form method="post" id="wfs-status-form" style="display: grid; gap: 1.5rem;">
                <?php wp_nonce_field('wfs_save_statuses'); ?>

                <div style="overflow-x: auto;">
                    <table class="wfs-status-table" style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f3f4f6;">
                            <tr>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Etiket</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Anahtar</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600;">Metin Rengi</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600;">Arkaplan Rengi</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($editable_statuses)): ?>
                                <?php $row_index = 0; ?>
                                <?php foreach ($editable_statuses as $slug => $config): ?>
                                    <tr class="wfs-status-row" data-index="<?php echo esc_attr($row_index); ?>" style="border-bottom: 1px solid #e5e7eb;">
                                        <td style="padding: 0.75rem;">
                                            <input type="text" name="statuses[<?php echo esc_attr($row_index); ?>][label]" value="<?php echo esc_attr($config['label']); ?>" class="wfs-status-input" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <input type="text" name="statuses[<?php echo esc_attr($row_index); ?>][slug]" value="<?php echo esc_attr($slug); ?>" class="wfs-status-slug" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" required>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <input type="color" name="statuses[<?php echo esc_attr($row_index); ?>][color]" value="<?php echo esc_attr($config['color']); ?>" class="wfs-status-color">
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <input type="color" name="statuses[<?php echo esc_attr($row_index); ?>][bg]" value="<?php echo esc_attr($config['bg']); ?>" class="wfs-status-color">
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <button type="button" class="wfs-remove-status" style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; cursor: pointer;">Sil</button>
                                        </td>
                                    </tr>
                                    <?php $row_index++; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="wfs-status-row" data-index="0" style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem;">
                                        <input type="text" name="statuses[0][label]" value="Beklemede" class="wfs-status-input" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                                    </td>
                                    <td style="padding: 0.75rem;">
                                        <input type="text" name="statuses[0][slug]" value="pending" class="wfs-status-slug" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" required>
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="color" name="statuses[0][color]" value="#f59e0b" class="wfs-status-color">
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <input type="color" name="statuses[0][bg]" value="#fef3c7" class="wfs-status-color">
                                    </td>
                                    <td style="padding: 0.75rem; text-align: center;">
                                        <button type="button" class="wfs-remove-status" style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; cursor: pointer;">Sil</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="wfs-add-status" style="background: #3b82f6; color: white; border: none; padding: 0.65rem 1.25rem; border-radius: 8px; font-weight: 500; cursor: pointer;">+ Yeni Statü Ekle</button>
                    <button type="submit" name="save_statuses" class="button button-primary" style="background: #10b981; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer;">💾 Statüleri Kaydet</button>
                </div>
            </form>

            <template id="wfs-status-row-template">
                <tr class="wfs-status-row" data-index="__index__" style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem;">
                        <input type="text" name="statuses[__index__][label]" value="" class="wfs-status-input" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" data-autoslug="true">
                    </td>
                    <td style="padding: 0.75rem;">
                        <input type="text" name="statuses[__index__][slug]" value="" class="wfs-status-slug" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;" required>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <input type="color" name="statuses[__index__][color]" value="#3b82f6" class="wfs-status-color">
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <input type="color" name="statuses[__index__][bg]" value="#dbeafe" class="wfs-status-color">
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <button type="button" class="wfs-remove-status" style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; cursor: pointer;">Sil</button>
                    </td>
                </tr>
            </template>
        </div>
    </form>
</div>

<script>
// Tab Switching
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.settings-tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;

            // Remove active class from all tabs and contents
            tabs.forEach(t => {
                t.classList.remove('active');
                t.style.background = 'white';
                t.style.color = '#6b7280';
            });
            contents.forEach(c => {
                c.classList.remove('active');
                c.style.display = 'none';
            });

            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            this.style.background = '#3b82f6';
            this.style.color = 'white';
            
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
                targetContent.style.display = 'block';
            }
        });
    });

    const statusTable = document.querySelector('.wfs-status-table tbody');
    const addStatusBtn = document.getElementById('wfs-add-status');
    const statusTemplate = document.getElementById('wfs-status-row-template');

    function attachSlugListener(row) {
        const labelInput = row.querySelector('.wfs-status-input');
        const slugInput = row.querySelector('.wfs-status-slug');

        if (!labelInput || !slugInput || labelInput.dataset.autoslug !== 'true') {
            return;
        }

        labelInput.addEventListener('input', function() {
            const value = labelInput.value
                .toLowerCase()
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            slugInput.value = value;
        });
    }

    if (statusTable && addStatusBtn && statusTemplate) {
        let statusIndex = statusTable.querySelectorAll('.wfs-status-row').length;

        statusTable.querySelectorAll('.wfs-status-row').forEach(attachSlugListener);

        addStatusBtn.addEventListener('click', function() {
            const html = statusTemplate.innerHTML.replace(/__index__/g, statusIndex);
            statusTable.insertAdjacentHTML('beforeend', html);

            const newRow = statusTable.querySelector(`.wfs-status-row[data-index="${statusIndex}"]`);
            if (newRow) {
                attachSlugListener(newRow);
            }

            statusIndex++;
        });

        statusTable.addEventListener('click', function(event) {
            if (event.target.classList.contains('wfs-remove-status')) {
                const row = event.target.closest('.wfs-status-row');
                if (row) {
                    row.remove();
                }
            }
        });
    }
});
</script>