<?php
if (!defined('ABSPATH')) {
    exit;
}

$record_id = intval($record->id);
$assigned_user = $record->assigned_to ? get_user_by('id', $record->assigned_to) : null;
$status_config = wfs_get_status_config($record->overall_status, $status_settings);
$can_assign = current_user_can('manage_options') || current_user_can('wfs_assign_records');
$can_review = current_user_can('manage_options') || current_user_can('wfs_review_files');
$interview_required = intval($record->interview_required) === 1;
$interview_completed = intval($record->interview_completed) === 1;
$interview_label = $interview_required ? __('Evet', 'workflow-system') : __('Hayır', 'workflow-system');
$interview_date = $record->interview_at ? date_i18n('d.m.Y H:i', strtotime($record->interview_at)) : __('Belirtilmemiş', 'workflow-system');
$payment_amount = floatval($record->payment_amount);
$payment_formatted = $payment_amount > 0 ? number_format($payment_amount, 2, ',', '.') : '';
?>
<div class="wfs-record-details" data-record-id="<?php echo $record_id; ?>" data-status="<?php echo esc_attr($record->overall_status); ?>" style="display: none;">
    <div class="wfs-details-grid">
        <section class="wfs-info-section">
            <h4>👤 <?php esc_html_e('Kişi Kartı', 'workflow-system'); ?></h4>
            <ul class="wfs-info-list">
                <li><strong><?php esc_html_e('Ad Soyad', 'workflow-system'); ?>:</strong> <?php echo esc_html(trim($record->first_name . ' ' . $record->last_name)); ?></li>
                <?php if (!empty($record->phone)): ?>
                    <li><strong><?php esc_html_e('İletişim', 'workflow-system'); ?>:</strong> <a href="tel:<?php echo esc_attr($record->phone); ?>"><?php echo esc_html($record->phone); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($record->email)): ?>
                    <li><strong><?php esc_html_e('Mail', 'workflow-system'); ?>:</strong> <a href="mailto:<?php echo esc_attr($record->email); ?>"><?php echo esc_html($record->email); ?></a></li>
                <?php endif; ?>
                <?php if (!empty($record->age)): ?>
                    <li><strong><?php esc_html_e('Yaş', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->age); ?></li>
                <?php endif; ?>
                <?php if (!empty($record->education_level)): ?>
                    <li><strong><?php esc_html_e('Eğitim Durumu', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->education_level); ?></li>
                <?php endif; ?>
                <?php if (!empty($record->department)): ?>
                    <li><strong><?php esc_html_e('Bölüm', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->department); ?></li>
                <?php endif; ?>
                <?php if (!empty($record->job_title)): ?>
                    <li><strong><?php esc_html_e('Mesleği', 'workflow-system'); ?>:</strong> <?php echo esc_html($record->job_title); ?></li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="wfs-info-section">
            <h4>📂 <?php esc_html_e('Dosya Kategorileri', 'workflow-system'); ?></h4>
            <div class="wfs-documents-grid">
                <?php foreach ($files_by_category as $category_slug => $category_data):
                    $has_file = !empty($category_data['files']);
                    ?>
                    <div class="wfs-documents-card <?php echo $has_file ? 'has-file' : 'no-file'; ?>">
                        <div class="wfs-documents-card-header">
                            <span class="wfs-documents-icon" aria-hidden="true"><?php echo esc_html($category_data['meta']['icon']); ?></span>
                            <span class="wfs-documents-title"><?php echo esc_html($category_data['meta']['label']); ?></span>
                        </div>
                        <?php if ($has_file): ?>
                            <ul class="wfs-documents-list">
                                <?php foreach ($category_data['files'] as $file):
                                    $file_url = str_replace(ABSPATH, home_url('/'), $file->file_path);
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener" class="wfs-documents-link"><?php echo esc_html($file->file_name); ?></a>
                                        <span class="wfs-documents-status is-<?php echo esc_attr($file->status); ?>"><?php echo esc_html(ucfirst($file->status)); ?></span>
                                        <?php if ($can_review): ?>
                                            <select class="wfs-file-status-select" data-file-id="<?php echo intval($file->id); ?>">
                                                <option value="pending" <?php selected($file->status, 'pending'); ?>><?php esc_html_e('Beklemede', 'workflow-system'); ?></option>
                                                <option value="approved" <?php selected($file->status, 'approved'); ?>><?php esc_html_e('Onaylı', 'workflow-system'); ?></option>
                                                <option value="rejected" <?php selected($file->status, 'rejected'); ?>><?php esc_html_e('Reddedildi', 'workflow-system'); ?></option>
                                            </select>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="wfs-documents-empty"><?php esc_html_e('Doküman eklenmedi.', 'workflow-system'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="wfs-info-section">
            <h4>🎯 <?php esc_html_e('Atama', 'workflow-system'); ?></h4>
            <div class="wfs-assigned-info <?php echo $assigned_user ? '' : 'is-empty'; ?>">
                <?php if ($assigned_user): ?>
                    <strong><?php esc_html_e('Atanan', 'workflow-system'); ?>:</strong> <?php echo esc_html($assigned_user->display_name); ?>
                <?php else: ?>
                    <?php esc_html_e('Henüz atama yapılmadı.', 'workflow-system'); ?>
                <?php endif; ?>
            </div>
            <?php if ($can_assign): ?>
                <div class="wfs-assign-form">
                    <select class="wfs-assign-select wfs-select" data-record-id="<?php echo $record_id; ?>" data-current="<?php echo esc_attr($record->assigned_to); ?>">
                        <option value=""><?php esc_html_e('Kullanıcı Seçin', 'workflow-system'); ?></option>
                        <?php foreach ($assignable_users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($record->assigned_to, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="wfs-assign-btn wfs-btn wfs-btn-primary" data-record-id="<?php echo $record_id; ?>"><?php esc_html_e('Ata', 'workflow-system'); ?></button>
                </div>
            <?php endif; ?>
        </section>

        <section class="wfs-info-section">
            <h4>📊 <?php esc_html_e('Statü', 'workflow-system'); ?></h4>
            <div class="wfs-current-status">
                <span class="wfs-status-badge" style="--wfs-status-color: <?php echo esc_attr($status_config['color']); ?>; background: <?php echo esc_attr($status_config['bg']); ?>; color: <?php echo esc_attr($status_config['color']); ?>;">
                    <span class="wfs-status-light"></span>
                    <span class="wfs-status-text"><?php echo esc_html($status_config['label']); ?></span>
                </span>
            </div>
            <?php if ($can_assign): ?>
                <div class="wfs-status-update">
                    <select class="wfs-status-select wfs-select" data-record-id="<?php echo $record_id; ?>">
                        <?php foreach ($status_settings as $status_key => $status_info): ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($record->overall_status, $status_key); ?>><?php echo esc_html($status_info['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="wfs-update-status-btn wfs-btn wfs-btn-primary" data-record-id="<?php echo $record_id; ?>"><?php esc_html_e('Statü Güncelle', 'workflow-system'); ?></button>
                </div>
            <?php endif; ?>
        </section>

        <section class="wfs-info-section">
            <h4>🎥 <?php esc_html_e('Görüşme Sistemi', 'workflow-system'); ?></h4>
            <ul class="wfs-info-list">
                <li><strong><?php esc_html_e('Görüşme Gerekiyor mu?', 'workflow-system'); ?>:</strong> <span class="wfs-interview-required" data-record-id="<?php echo $record_id; ?>"><?php echo esc_html($interview_label); ?></span></li>
                <li><strong><?php esc_html_e('Görüşme Tarihi', 'workflow-system'); ?>:</strong> <span class="wfs-interview-date" data-record-id="<?php echo $record_id; ?>"><?php echo esc_html($interview_date); ?></span></li>
                <li><strong><?php esc_html_e('Görüşme Tamamlandı', 'workflow-system'); ?>:</strong> <span class="wfs-interview-status" data-record-id="<?php echo $record_id; ?>"><?php echo $interview_completed ? __('Evet', 'workflow-system') : __('Hayır', 'workflow-system'); ?></span></li>
            </ul>
            <?php if ($can_assign): ?>
                <div class="wfs-interview-actions">
                    <label>
                        <input type="checkbox" class="wfs-interview-toggle" data-record-id="<?php echo $record_id; ?>" <?php checked($interview_completed); ?>>
                        <span><?php esc_html_e('Görüşmeyi tamamlandı olarak işaretle', 'workflow-system'); ?></span>
                    </label>
                    <input type="datetime-local" class="wfs-interview-datetime" data-record-id="<?php echo $record_id; ?>" value="<?php echo $record->interview_at ? esc_attr(date('Y-m-d\\TH:i', strtotime($record->interview_at))) : ''; ?>">
                </div>
            <?php endif; ?>
        </section>

        <section class="wfs-info-section wfs-payment-section" data-record-id="<?php echo $record_id; ?>">
            <h4>💰 <?php esc_html_e('Ödeme', 'workflow-system'); ?></h4>
            <?php if ($record->overall_status === 'completed'): ?>
                <div class="wfs-payment-form">
                    <label><?php esc_html_e('Tahsil Edilecek Ücret', 'workflow-system'); ?></label>
                    <div class="wfs-payment-controls">
                        <input type="text" class="wfs-payment-input" data-record-id="<?php echo $record_id; ?>" value="<?php echo esc_attr($payment_formatted); ?>" placeholder="35000">
                        <?php if ($can_assign): ?>
                            <button class="wfs-save-payment wfs-btn wfs-btn-primary" data-record-id="<?php echo $record_id; ?>"><?php esc_html_e('Kaydet', 'workflow-system'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="wfs-payment-hint"><?php esc_html_e('Ödeme girişi sadece statü "Tamamlandı" olduğunda aktif olur.', 'workflow-system'); ?></p>
            <?php endif; ?>
        </section>

        <section class="wfs-info-section">
            <h4>⏱️ <?php esc_html_e('Zaman Çizelgesi', 'workflow-system'); ?></h4>
            <ul class="wfs-info-list">
                <li><strong><?php esc_html_e('Oluşturulma', 'workflow-system'); ?>:</strong> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($record->created_at))); ?></li>
                <li><strong><?php esc_html_e('Son Güncelleme', 'workflow-system'); ?>:</strong> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($record->updated_at))); ?></li>
            </ul>
        </section>
    </div>
</div>
