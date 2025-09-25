<?php
if (!defined('ABSPATH')) {
    exit;
}

$record_id = intval($record->id);
$assigned_user = $record->assigned_to ? get_user_by('id', $record->assigned_to) : null;
$status_config = wfs_get_status_config($record->overall_status, $status_settings);
?>
<div class="wfs-record-details" data-record-id="<?php echo $record_id; ?>" style="display: none;">
    <div class="wfs-details-grid">
        <div class="wfs-info-section">
            <h4>👤 <?php esc_html_e('Kişisel Bilgiler', 'workflow-system'); ?></h4>
            <div class="wfs-info-list">
                <?php if (!empty($record->first_name)): ?>
                    <div><strong><?php esc_html_e('Ad', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->first_name); ?></div>
                <?php endif; ?>
                <?php if (!empty($record->last_name)): ?>
                    <div><strong><?php esc_html_e('Soyad', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->last_name); ?></div>
                <?php endif; ?>
                <?php if (!empty($record->email)): ?>
                    <div><strong>📧 <?php esc_html_e('E-posta', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->email); ?></div>
                <?php endif; ?>
                <?php if (!empty($record->phone)): ?>
                    <div><strong>📞 <?php esc_html_e('Telefon', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->phone); ?></div>
                <?php endif; ?>
                <?php if (!empty($record->age)): ?>
                    <div><strong>🎂 <?php esc_html_e('Yaş', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->age); ?></div>
                <?php endif; ?>
                <?php if (!empty($record->education_level)): ?>
                    <div><strong>🎓 <?php esc_html_e('Eğitim', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->education_level); ?></div>
                <?php endif; ?>
                <?php if (!empty($record->department)): ?>
                    <div><strong>🏢 <?php esc_html_e('Bölüm', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->department); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($submission_data) && !empty($custom_form['fields'])): ?>
            <div class="wfs-info-section">
                <h4>📝 <?php esc_html_e('Ek Bilgiler', 'workflow-system'); ?></h4>
                <div class="wfs-info-list">
                    <?php
                    foreach ($custom_form['fields'] as $field) {
                        $field_key = 'field_' . $field['id'];
                        if (empty($submission_data[$field_key])) {
                            continue;
                        }

                        $label = isset($field['label']) ? $field['label'] : $field_key;
                        $lower_label = mb_strtolower($label);
                        $skip_labels = array('ad', 'soyad', 'e-posta', 'email', 'telefon', 'yaş', 'eğitim', 'bölüm', 'department');
                        $should_skip = false;
                        foreach ($skip_labels as $skip) {
                            if (strpos($lower_label, $skip) !== false) {
                                $should_skip = true;
                                break;
                            }
                        }
                        if ($should_skip) {
                            continue;
                        }

                        $value = $submission_data[$field_key];
                        if (is_array($value)) {
                            $value = array_filter($value, function ($item) {
                                return $item !== '';
                            });
                            $display_value = implode(', ', array_map('esc_html', $value));
                        } else {
                            $display_value = esc_html($value);
                        }
                        ?>
                        <div>
                            <strong><?php echo esc_html($label); ?>:</strong> <?php echo $display_value; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="wfs-info-section">
            <h4>🎯 <?php esc_html_e('Atama', 'workflow-system'); ?></h4>
            <?php if ($assigned_user): ?>
                <div class="wfs-assigned-info">
                    <strong><?php esc_html_e('Atanan', 'workflow-system'); ?>:</strong> <?php echo esc_html($assigned_user->display_name); ?>
                </div>
            <?php else: ?>
                <div class="wfs-assigned-info is-empty"><?php esc_html_e('Henüz atama yapılmadı.', 'workflow-system'); ?></div>
            <?php endif; ?>

            <?php if (current_user_can('manage_options') || current_user_can('wfs_assign_records')): ?>
                <div class="wfs-assign-form">
                    <select class="wfs-assign-select wfs-select" data-record-id="<?php echo $record_id; ?>">
                        <option value=""><?php esc_html_e('Kullanıcı Seçin', 'workflow-system'); ?></option>
                        <?php foreach ($assignable_users as $user): ?>
                            <?php
                            $role_names = array(
                                'administrator' => __('Admin', 'workflow-system'),
                                'wfs_superadmin' => __('Süperadmin', 'workflow-system'),
                                'wfs_representative' => __('Temsilci', 'workflow-system'),
                                'wfs_consultant' => __('Danışan', 'workflow-system'),
                                'editor' => __('Editör', 'workflow-system'),
                            );
                            $user_role_display = '';
                            foreach ($user->roles as $role) {
                                if (isset($role_names[$role])) {
                                    $user_role_display = $role_names[$role];
                                    break;
                                }
                            }
                            ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($record->assigned_to, $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?><?php echo $user_role_display ? ' (' . esc_html($user_role_display) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="wfs-assign-btn wfs-btn wfs-btn-primary" data-record-id="<?php echo $record_id; ?>">
                        <?php esc_html_e('Ata', 'workflow-system'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="wfs-info-section">
            <h4>📊 <?php esc_html_e('Statü', 'workflow-system'); ?></h4>
            <div class="wfs-current-status">
                <strong><?php esc_html_e('Mevcut Statü', 'workflow-system'); ?>:</strong>
                <span class="wfs-status-badge" style="background: <?php echo esc_attr($status_config['bg']); ?>; color: <?php echo esc_attr($status_config['color']); ?>;">
                    <span class="wfs-status-light" style="background: <?php echo esc_attr($status_config['color']); ?>;"></span>
                    <span class="wfs-status-text"><?php echo esc_html($status_config['label']); ?></span>
                </span>
            </div>
            <?php if (current_user_can('manage_options') || current_user_can('wfs_assign_records')): ?>
                <div class="wfs-status-update">
                    <select class="wfs-status-select wfs-select" data-record-id="<?php echo $record_id; ?>">
                        <?php foreach ($status_settings as $status_key => $status_info): ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($record->overall_status, $status_key); ?>>
                                <?php echo esc_html($status_info['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="wfs-update-status-btn wfs-btn wfs-btn-primary" data-record-id="<?php echo $record_id; ?>">
                        <?php esc_html_e('Statü Güncelle', 'workflow-system'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="wfs-info-section">
            <h4>📅 <?php esc_html_e('Tarihler', 'workflow-system'); ?></h4>
            <div class="wfs-info-list">
                <div><strong><?php esc_html_e('Oluşturulma', 'workflow-system'); ?>:</strong> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($record->created_at))); ?></div>
                <div><strong><?php esc_html_e('Güncellenme', 'workflow-system'); ?>:</strong> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($record->updated_at))); ?></div>
            </div>
        </div>
    </div>

    <div class="wfs-files-section">
        <h4>📁 <?php esc_html_e('Yüklenen Dosyalar', 'workflow-system'); ?></h4>
        <?php if (!empty($files)): ?>
            <div class="wfs-files-grid">
                <?php foreach ($files as $file): ?>
                    <?php
                    $file_url = str_replace(ABSPATH, home_url('/'), $file->file_path);
                    $file_icon = '📄';
                    if (strpos($file->file_type, 'image') !== false) {
                        $file_icon = '🖼️';
                    } elseif (strpos($file->file_type, 'pdf') !== false) {
                        $file_icon = '📑';
                    }

                    $file_status_colors = array(
                        'pending' => '#f59e0b',
                        'approved' => '#10b981',
                        'rejected' => '#ef4444',
                    );
                    $file_status = isset($file_status_colors[$file->status]) ? $file_status_colors[$file->status] : '#6b7280';
                    ?>
                    <div class="wfs-file-item">
                        <div class="wfs-file-header">
                            <div>
                                <span class="wfs-file-icon" aria-hidden="true"><?php echo $file_icon; ?></span>
                                <a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener" class="wfs-file-link">
                                    <?php echo esc_html($file->file_name); ?>
                                </a>
                                <span class="wfs-file-meta"><?php echo esc_html(round($file->file_size / 1024, 2) . ' KB'); ?></span>
                            </div>
                            <div class="wfs-file-actions">
                                <span class="wfs-file-status" style="background: <?php echo esc_attr($file_status); ?>20; color: <?php echo esc_attr($file_status); ?>;">
                                    <?php echo esc_html(ucfirst($file->status)); ?>
                                </span>
                                <?php if (current_user_can('wfs_review_files')): ?>
                                    <select class="wfs-file-status-select" data-file-id="<?php echo intval($file->id); ?>">
                                        <option value="pending" <?php selected($file->status, 'pending'); ?>><?php esc_html_e('Beklemede', 'workflow-system'); ?></option>
                                        <option value="approved" <?php selected($file->status, 'approved'); ?>><?php esc_html_e('Onayla', 'workflow-system'); ?></option>
                                        <option value="rejected" <?php selected($file->status, 'rejected'); ?>><?php esc_html_e('Reddet', 'workflow-system'); ?></option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($file->review_notes)): ?>
                            <div class="wfs-file-notes">
                                <small><?php esc_html_e('Not', 'workflow-system'); ?>: <?php echo esc_html($file->review_notes); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="wfs-no-files">
                <p><?php esc_html_e('Bu kayıt için henüz dosya yüklenmemiş.', 'workflow-system'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
