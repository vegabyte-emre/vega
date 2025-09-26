<?php
if (!defined('ABSPATH')) {
    exit;
}

$records = isset($records) && is_array($records) ? $records : array();
$status_settings = isset($status_settings) && is_array($status_settings) ? $status_settings : array();
$assignable_users = isset($assignable_users) && is_array($assignable_users) ? $assignable_users : array();
$file_categories = isset($file_categories) && is_array($file_categories) ? $file_categories : array();
$grouped_files = isset($grouped_files) && is_array($grouped_files) ? $grouped_files : array();
$active_filters = isset($active_filters) && is_array($active_filters)
    ? wp_parse_args($active_filters, array('search' => '', 'status' => '', 'rep' => 0))
    : array('search' => '', 'status' => '', 'rep' => 0);
$can_assign_records = current_user_can('manage_options') || current_user_can('wfs_assign_records');
$can_review_files = current_user_can('manage_options') || current_user_can('wfs_review_files');

if (empty($status_settings)) {
    $status_settings = array(
        'pending' => array('label' => __('Beklemede', WFS_TEXT_DOMAIN), 'color' => '#f59e0b', 'bg' => '#fef3c7'),
    );
}

if (empty($file_categories)) {
    $file_categories = array(
        'diploma'    => array('label' => __('Diploma', WFS_TEXT_DOMAIN), 'icon' => '🎓'),
        'transcript' => array('label' => __('Transkript', WFS_TEXT_DOMAIN), 'icon' => '📜'),
        'sgk'        => array('label' => __('SGK Hizmet Dökümü', WFS_TEXT_DOMAIN), 'icon' => '📋'),
        'cv'         => array('label' => __('CV', WFS_TEXT_DOMAIN), 'icon' => '📄'),
        'other'      => array('label' => __('Diğer Belgeler', WFS_TEXT_DOMAIN), 'icon' => '📂'),
    );
}

if (!function_exists('wfs_get_status_config')) {
    function wfs_get_status_config($status_key, $statuses)
    {
        $default = reset($statuses);
        return $statuses[$status_key] ?? $default;
    }
}

function wfs_prepare_category_display($record_id, $grouped_files, $file_categories)
{
    $prepared = array();

    foreach ($file_categories as $slug => $meta) {
        $prepared[$slug] = array(
            'meta' => $meta,
            'files' => array(),
        );
    }

    if (isset($grouped_files[$record_id]) && is_array($grouped_files[$record_id])) {
        foreach ($grouped_files[$record_id] as $slug => $data) {
            if (!isset($prepared[$slug])) {
                $prepared[$slug] = array(
                    'meta' => array('label' => ucfirst($slug), 'icon' => '📁'),
                    'files' => array(),
                );
            }
            $prepared[$slug]['files'] = $data['files'];
        }
    }

    return $prepared;
}

function wfs_format_phone_for_actions($phone)
{
    $digits = preg_replace('/[^0-9+]/', '', $phone);
    $whatsapp = preg_replace('/[^0-9]/', '', $digits);

    if (strpos($whatsapp, '00') === 0) {
        $whatsapp = substr($whatsapp, 2);
    }

    if ($whatsapp && $whatsapp[0] === '0') {
        $whatsapp = '9' . $whatsapp;
    }

    return array(
        'tel' => $digits ? 'tel:' . $digits : '',
        'whatsapp' => $whatsapp ? 'https://wa.me/' . $whatsapp : '',
    );
}

?>

<div class="wrap">
    <div class="wfs-header">
        <div>
            <h1>Eu WorkFlow</h1>
            <p>Kayıt yönetimi, doküman kontrolü ve görüşme planlaması tek ekranda.</p>
        </div>
    </div>

    <div class="wfs-filters-container">
        <div class="wfs-filter-item wfs-filter-item--search">
            <label>🔍 Gerçek Zamanlı Arama</label>
            <div class="wfs-search-wrapper">
                <input type="text" id="wfs-search" class="wfs-input" placeholder="İsim, telefon veya e-posta ile arayın" value="<?php echo esc_attr($active_filters['search']); ?>" autocomplete="off">
                <button type="button" id="wfs-search-button" class="wfs-btn wfs-btn-secondary">Ara</button>
                <div id="wfs-search-suggestions" class="wfs-search-suggestions" role="listbox" aria-hidden="true"></div>
            </div>
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
            <label>👤 Atanan Kullanıcı</label>
            <select id="wfs-rep-filter" class="wfs-select">
                <option value="">Tümü</option>
                <?php foreach ($assignable_users as $rep): ?>
                    <option value="<?php echo esc_attr($rep->ID); ?>" <?php selected(intval($active_filters['rep']), $rep->ID); ?>><?php echo esc_html($rep->display_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="wfs-add-record-section">
        <div class="wfs-section-title">
            <h3>➕ Yeni Kayıt Oluştur</h3>
            <button type="button" class="wfs-btn-link wfs-toggle-create" aria-expanded="true" aria-controls="wfs-create-record-form">Küçült</button>
        </div>
        <form id="wfs-create-record-form" enctype="multipart/form-data">
            <div class="wfs-form-grid">
                <div class="wfs-form-group">
                    <label>Ad</label>
                    <input type="text" name="first_name">
                </div>
                <div class="wfs-form-group">
                    <label>Soyad</label>
                    <input type="text" name="last_name">
                </div>
                <div class="wfs-form-group">
                    <label>E-posta</label>
                    <input type="email" name="email">
                </div>
                <div class="wfs-form-group">
                    <label>Telefon</label>
                    <input type="tel" name="phone">
                </div>
                <div class="wfs-form-group">
                    <label>Yaş</label>
                    <input type="number" name="age" min="0" max="120">
                </div>
                <div class="wfs-form-group">
                    <label>Eğitim Durumu</label>
                    <input type="text" name="education_level">
                </div>
                <div class="wfs-form-group">
                    <label>Bölüm</label>
                    <input type="text" name="department">
                </div>
                <div class="wfs-form-group">
                    <label>Meslek</label>
                    <input type="text" name="job_title">
                </div>
                <div class="wfs-form-group">
                    <label>Başlangıç Statüsü</label>
                    <select name="status">
                        <?php foreach ($status_settings as $status_key => $status_info): ?>
                            <option value="<?php echo esc_attr($status_key); ?>"><?php echo esc_html($status_info['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="wfs-files-upload">
                <h4>📎 Dokümanlar</h4>
                <div class="wfs-form-grid">
                    <div class="wfs-form-group">
                        <label><?php echo esc_html($file_categories['diploma']['label']); ?></label>
                        <input type="file" name="diploma_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                    </div>
                    <div class="wfs-form-group">
                        <label><?php echo esc_html($file_categories['transcript']['label']); ?></label>
                        <input type="file" name="transcript_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                    </div>
                    <div class="wfs-form-group">
                        <label><?php echo esc_html($file_categories['sgk']['label']); ?></label>
                        <input type="file" name="sgk_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                    </div>
                    <div class="wfs-form-group">
                        <label><?php echo esc_html($file_categories['cv']['label']); ?></label>
                        <input type="file" name="cv_file" accept=".pdf,.doc,.docx">
                    </div>
                    <div class="wfs-form-group">
                        <label><?php echo esc_html($file_categories['other']['label']); ?></label>
                        <input type="file" name="other_file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                    </div>
                </div>
            </div>

            <div class="wfs-form-actions">
                <button type="submit" class="wfs-btn wfs-btn-primary">Kaydı Oluştur</button>
            </div>
        </form>
    </div>

    <div id="wfs-loading" class="wfs-loading-container" style="display: none;">
        <div class="wfs-spinner"></div>
        <p>Yükleniyor...</p>
    </div>

    <?php if ($can_assign_records): ?>
        <div class="wfs-bulk-actions" id="wfs-bulk-actions" aria-hidden="true">
            <div class="wfs-bulk-actions__left">
                <label class="wfs-bulk-select-all">
                    <input type="checkbox" id="wfs-bulk-select-all">
                    <span><?php esc_html_e('Tümünü Seç', WFS_TEXT_DOMAIN); ?></span>
                </label>
                <span class="wfs-bulk-count" id="wfs-bulk-count"><?php esc_html_e('0 kayıt seçildi', WFS_TEXT_DOMAIN); ?></span>
            </div>
            <div class="wfs-bulk-actions__controls">
                <div class="wfs-bulk-control">
                    <select id="wfs-bulk-status" class="wfs-select">
                        <option value=""><?php esc_html_e('Statü Seçin', WFS_TEXT_DOMAIN); ?></option>
                        <?php foreach ($status_settings as $status_key => $status_info): ?>
                            <option value="<?php echo esc_attr($status_key); ?>"><?php echo esc_html($status_info['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="wfs-bulk-status-apply" class="wfs-btn wfs-btn-primary"><?php esc_html_e('Statü Güncelle', WFS_TEXT_DOMAIN); ?></button>
                </div>
                <div class="wfs-bulk-control">
                    <select id="wfs-bulk-assign" class="wfs-select">
                        <option value=""><?php esc_html_e('Kullanıcı Seçin', WFS_TEXT_DOMAIN); ?></option>
                        <?php foreach ($assignable_users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="wfs-bulk-assign-apply" class="wfs-btn wfs-btn-secondary"><?php esc_html_e('Atama Yap', WFS_TEXT_DOMAIN); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="wfs-records-container" class="wfs-records">
        <?php if (!empty($records)): ?>
            <?php foreach ($records as $record): ?>
                <?php
                $status_config = wfs_get_status_config($record->overall_status, $status_settings);
                $initials = strtoupper(mb_substr($record->first_name, 0, 1) . mb_substr($record->last_name, 0, 1));
                $assigned_display = $record->assigned_name ? esc_html($record->assigned_name) : __('Henüz atama yapılmadı.', WFS_TEXT_DOMAIN);
                $categories_display = wfs_prepare_category_display($record->id, $grouped_files, $file_categories);
                $phone_actions = wfs_format_phone_for_actions($record->phone);
                $full_name = trim($record->first_name . ' ' . $record->last_name);
                ?>
                <div class="wfs-record-card"
                    data-record-id="<?php echo intval($record->id); ?>"
                    data-name="<?php echo esc_attr($full_name); ?>"
                    data-phone="<?php echo esc_attr($record->phone); ?>"
                    data-email="<?php echo esc_attr($record->email); ?>"
                    data-first-name="<?php echo esc_attr($record->first_name); ?>"
                    data-last-name="<?php echo esc_attr($record->last_name); ?>"
                    data-education="<?php echo esc_attr($record->education_level); ?>"
                    data-department="<?php echo esc_attr($record->department); ?>"
                    data-job-title="<?php echo esc_attr($record->job_title); ?>"
                    data-age="<?php echo esc_attr($record->age); ?>"
                    data-status="<?php echo esc_attr($record->overall_status); ?>"
                    data-can-manage="<?php echo !empty($record->can_manage) ? '1' : '0'; ?>">
                    <div class="wfs-card-header">
                        <div class="wfs-card-select">
                            <input type="checkbox" class="wfs-bulk-checkbox" value="<?php echo intval($record->id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Kaydı seç: %s', WFS_TEXT_DOMAIN), $full_name)); ?>">
                        </div>
                        <div class="wfs-user-info">
                            <div class="wfs-avatar" aria-hidden="true"><?php echo esc_html($initials ?: '👤'); ?></div>
                            <div>
                                <h3 class="wfs-user-name"><?php echo esc_html($full_name); ?></h3>
                                <div class="wfs-user-meta">
                                    <?php if (!empty($record->job_title)): ?>
                                        <span>💼 <?php echo esc_html($record->job_title); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($record->department)): ?>
                                        <span>🏢 <?php echo esc_html($record->department); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="wfs-contact-actions">
                                    <?php if ($phone_actions['tel']): ?>
                                        <a href="<?php echo esc_url($phone_actions['tel']); ?>" class="wfs-contact-btn">Ara</a>
                                    <?php endif; ?>
                                    <?php if ($phone_actions['whatsapp']): ?>
                                        <a href="<?php echo esc_url($phone_actions['whatsapp']); ?>" class="wfs-contact-btn" target="_blank" rel="noopener">WhatsApp</a>
                                    <?php endif; ?>
                                    <?php if (!empty($record->email)): ?>
                                        <a href="mailto:<?php echo esc_attr($record->email); ?>" class="wfs-contact-btn">Mail</a>
                                    <?php endif; ?>
                                </div>
                                <div class="wfs-contact-text">
                                    <?php if (!empty($record->phone)): ?><span>📞 <?php echo esc_html($record->phone); ?></span><?php endif; ?>
                                    <?php if (!empty($record->email)): ?><span>📧 <?php echo esc_html($record->email); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="wfs-card-actions">
                            <div class="wfs-doc-summary">
                                <?php foreach ($categories_display as $category_slug => $category_data):
                                    $has_file = !empty($category_data['files']);
                                    ?>
                                    <span class="wfs-doc-chip <?php echo $has_file ? 'is-ready' : 'is-missing'; ?>" data-category="<?php echo esc_attr($category_slug); ?>">
                                        <span class="wfs-doc-icon" aria-hidden="true"><?php echo esc_html($category_data['meta']['icon']); ?></span>
                                        <span class="wfs-doc-label"><?php echo esc_html($category_data['meta']['label']); ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div class="wfs-assignment-chip">
                                <span class="wfs-assignment-icon" aria-hidden="true">🎯</span>
                                <span class="wfs-assignment-label"><?php echo $assigned_display; ?></span>
                            </div>
                            <span class="wfs-status-badge" style="--wfs-status-color: <?php echo esc_attr($status_config['color']); ?>; background: <?php echo esc_attr($status_config['bg']); ?>; color: <?php echo esc_attr($status_config['color']); ?>;">
                                <span class="wfs-status-light"></span>
                                <span class="wfs-status-text"><?php echo esc_html($status_config['label']); ?></span>
                            </span>
                            <button class="wfs-btn-link wfs-toggle-details" data-record-id="<?php echo intval($record->id); ?>">Detaylar</button>
                        </div>
                    </div>

                    <?php
                    $files_by_category = $categories_display;
                    include WFS_PLUGIN_PATH . 'templates/partials/record-details.php';
                    ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="wfs-empty-state">
                <div class="wfs-empty-icon">📋</div>
                <h3><?php esc_html_e('Henüz kayıt yok', WFS_TEXT_DOMAIN); ?></h3>
                <p><?php esc_html_e('Yeni bir kayıt oluşturduğunuzda burada listelenecek.', WFS_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div id="wfs-toast-container"></div>
</div>

<style>
.wfs-add-record-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(15, 23, 42, 0.08);
    margin-bottom: 2rem;
}

.wfs-section-title {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.wfs-section-title h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #111827;
}

.wfs-section-title p {
    margin: 0;
    color: #6b7280;
}

.wfs-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem 1.5rem;
}

.wfs-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.wfs-form-group label {
    font-weight: 600;
    color: #1f2937;
}

.wfs-form-group input,
.wfs-form-group select {
    padding: 0.65rem 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.wfs-form-group input:focus,
.wfs-form-group select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.wfs-form-group--full {
    grid-column: 1 / -1;
}

.wfs-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    color: #374151;
}

.wfs-files-upload {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.wfs-files-upload h4 {
    margin: 0 0 1rem 0;
    color: #111827;
}

.wfs-form-actions {
    margin-top: 1.5rem;
    text-align: right;
}

.wfs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
}

.wfs-btn-primary {
    background: linear-gradient(135deg, #2563eb, #10b981);
    color: white;
    border: none;
}

.wfs-btn-primary:hover {
    opacity: 0.9;
}

.wfs-contact-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0.5rem 0 0.25rem 0;
}

.wfs-contact-btn {
    padding: 0.35rem 0.75rem;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 6px;
    font-size: 0.85rem;
    text-decoration: none;
    font-weight: 600;
}

.wfs-contact-btn:hover {
    background: #bae6fd;
}

.wfs-user-meta {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    color: #4b5563;
    font-size: 0.9rem;
}

.wfs-contact-text {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: #4b5563;
}

.wfs-doc-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
}

.wfs-doc-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid transparent;
    transition: all 0.2s ease;
}

.wfs-doc-chip.is-ready {
    background: rgba(34, 197, 94, 0.12);
    color: #166534;
    border-color: rgba(34, 197, 94, 0.2);
}

.wfs-doc-chip.is-missing {
    background: rgba(248, 113, 113, 0.12);
    color: #b91c1c;
    border-color: rgba(248, 113, 113, 0.2);
}

.wfs-doc-icon {
    font-size: 0.9rem;
}

.wfs-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.9rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.85rem;
    --wfs-status-color: #2563eb;
}

.wfs-status-light {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--wfs-status-color);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25);
    animation: wfs-pulse 2s ease-in-out infinite;
}

.wfs-add-record-section.is-collapsed form {
    display: none;
}

.wfs-toggle-create {
    border: none;
    background: transparent;
    color: #2563eb;
    font-weight: 600;
    cursor: pointer;
}

.wfs-toggle-create:hover {
    text-decoration: underline;
}

.wfs-details-toolbar {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.wfs-edit-record-form {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.wfs-edit-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
}

.wfs-btn-danger {
    background: #ef4444;
    color: #fff;
}

.wfs-btn-danger:hover {
    background: #dc2626;
}

.wfs-documents-upload {
    margin-top: 0.75rem;
}

.wfs-upload-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border: 1px dashed #c7d2fe;
    border-radius: 8px;
    color: #3730a3;
    font-weight: 600;
    cursor: pointer;
    background: rgba(99, 102, 241, 0.08);
}

.wfs-upload-label input[type="file"] {
    display: none;
}

@keyframes wfs-pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.45);
    }
    50% {
        transform: scale(1.2);
        box-shadow: 0 0 0 8px rgba(37, 99, 235, 0);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.45);
    }
}

@media (max-width: 900px) {
    .wfs-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .wfs-card-actions {
        width: 100%;
        align-items: flex-start;
    }

    .wfs-doc-summary {
        justify-content: flex-start;
    }
}
</style>
