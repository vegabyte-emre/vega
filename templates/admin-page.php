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
$education_levels = isset($education_levels) && is_array($education_levels) ? $education_levels : array(
    __('Ortaokul', WFS_TEXT_DOMAIN),
    __('Lise', WFS_TEXT_DOMAIN),
    __('Önlisans', WFS_TEXT_DOMAIN),
    __('Lisans', WFS_TEXT_DOMAIN),
    __('Doktora', WFS_TEXT_DOMAIN),
    __('Hiçbiri', WFS_TEXT_DOMAIN),
);

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
    <div class="wfs-header-content">
        <div class="wfs-header-text">
            <h1>Eu WorkFlow</h1>
            <p>Kayıt yönetimi, doküman kontrolü ve görüşme planlaması tek ekranda.</p>
        </div>
        <div class="wfs-header-signature">
            <span>Vegabyte Bilişim</span>
            <span class="wfs-header-accent">✦</span>
            <span>tarafından hazırlanmıştır</span>
        </div>
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

    <div class="wfs-add-record-section" data-collapsed="false">
        <div class="wfs-section-title">
            <h3 id="wfs-create-record-heading" tabindex="0" role="button" aria-controls="wfs-create-record-form" aria-expanded="true">➕ Yeni Kayıt Oluştur</h3>
            <div class="wfs-section-controls" role="group" aria-label="Yeni kayıt formunu göster veya gizle">
                <button type="button" class="wfs-btn-link wfs-toggle-create" data-action="collapse" aria-controls="wfs-create-record-form" aria-expanded="true">Küçült</button>
                <button type="button" class="wfs-btn-link wfs-toggle-create is-hidden" data-action="expand" aria-controls="wfs-create-record-form" aria-expanded="false">Genişlet</button>
            </div>
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
                    <select name="education_level">
                        <option value=""><?php esc_html_e('Seçiniz', WFS_TEXT_DOMAIN); ?></option>
                        <?php foreach ($education_levels as $level): ?>
                            <option value="<?php echo esc_attr($level); ?>"><?php echo esc_html($level); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                $can_manage_record = !empty($record->can_manage);
                $can_review_record = current_user_can('manage_options') || current_user_can('wfs_review_files');
                $can_upload_record = $can_manage_record || $can_review_record;
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
                    data-can-manage="<?php echo $can_manage_record ? '1' : '0'; ?>"
                    data-can-review="<?php echo $can_review_record ? '1' : '0'; ?>"
                    data-can-upload="<?php echo $can_upload_record ? '1' : '0'; ?>">
                    <div class="wfs-card-header">
                        <div class="wfs-card-topline">
                            <div class="wfs-card-select">
                                <input type="checkbox" class="wfs-bulk-checkbox" value="<?php echo intval($record->id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Kaydı seç: %s', WFS_TEXT_DOMAIN), $full_name)); ?>">
                            </div>
                            <div class="wfs-card-topmeta">
                                <div class="wfs-assignment-chip">
                                    <span class="wfs-assignment-icon" aria-hidden="true">🎯</span>
                                    <span class="wfs-assignment-label"><?php echo $assigned_display; ?></span>
                                </div>
                                <span class="wfs-status-badge" style="--wfs-status-color: <?php echo esc_attr($status_config['color']); ?>; background: <?php echo esc_attr($status_config['bg']); ?>; color: <?php echo esc_attr($status_config['color']); ?>;">
                                    <span class="wfs-status-light"></span>
                                    <span class="wfs-status-text"><?php echo esc_html($status_config['label']); ?></span>
                                </span>
                            </div>
                        </div>
                        <div class="wfs-card-body">
                            <div class="wfs-user-info">
                                <div class="wfs-avatar" aria-hidden="true"><?php echo esc_html($initials ?: '👤'); ?></div>
                                <div class="wfs-user-details">
                                    <h3 class="wfs-user-name"><?php echo esc_html($full_name); ?></h3>
                                    <div class="wfs-user-meta">
                                        <?php if (!empty($record->job_title)): ?>
                                            <span>💼 <?php echo esc_html($record->job_title); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($record->department)): ?>
                                            <span>🏢 <?php echo esc_html($record->department); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="wfs-contact-text">
                                        <?php if (!empty($record->phone)): ?><span>📞 <?php echo esc_html($record->phone); ?></span><?php endif; ?>
                                        <?php if (!empty($record->email)): ?><span>📧 <?php echo esc_html($record->email); ?></span><?php endif; ?>
                                    </div>
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
                                </div>
                            </div>
                            <div class="wfs-card-actions">
                                <button class="wfs-btn-link wfs-toggle-details" data-record-id="<?php echo intval($record->id); ?>">Detaylar</button>
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
                            </div>
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
    background: #ffffff;
    padding: 1.75rem 2rem;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    margin-bottom: 2rem;
    transition: box-shadow 0.2s ease;
}

.wfs-add-record-section.is-collapsed {
    padding-bottom: 1rem;
}

.wfs-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.wfs-section-title h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #1f2937;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.wfs-section-title h3:focus-visible {
    outline: 2px solid #2563eb;
    border-radius: 6px;
    padding: 0 0.25rem;
}

.wfs-section-controls {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.wfs-section-title p {
    margin: 0;
    color: #6b7280;
}

.wfs-toggle-create {
    border: none;
    background: transparent;
    color: #2563eb;
    font-weight: 600;
    cursor: pointer;
    padding: 0.15rem 0.35rem;
}

.wfs-toggle-create:hover {
    text-decoration: underline;
}

.wfs-toggle-create.is-hidden {
    display: none;
}

.wfs-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem 1.5rem;
}

.wfs-form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.wfs-form-group label {
    font-weight: 600;
    color: #1f2937;
}

.wfs-form-group input,
.wfs-form-group select {
    padding: 0.65rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.wfs-form-group input:focus,
.wfs-form-group select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
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
    padding: 0.7rem 1.4rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.wfs-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
}

.wfs-btn-primary {
    background: linear-gradient(135deg, #2563eb, #10b981);
    color: #fff;
}

.wfs-btn-secondary {
    background: #e0f2fe;
    color: #0369a1;
}

.wfs-btn-danger {
    background: #ef4444;
    color: #fff;
}

.wfs-btn-link {
    background: none;
    border: none;
    color: #2563eb;
    cursor: pointer;
    font-weight: 600;
    padding: 0.2rem 0.35rem;
}

.wfs-btn-link:hover {
    text-decoration: underline;
}

.wfs-records {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.wfs-record-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.wfs-record-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
}

.wfs-record-card.is-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18), 0 12px 28px rgba(15, 23, 42, 0.08);
}

.wfs-card-header {
    background: #f8fafc;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
}

.wfs-card-select {
    display: flex;
    align-items: flex-start;
    padding-top: 0.25rem;
    cursor: pointer;
}

.wfs-card-select input[type='checkbox'] {
    width: 18px;
    height: 18px;
    border-radius: 6px;
    border: 2px solid #cbd5f5;
}

.wfs-user-info {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    flex: 1 1 auto;
}

.wfs-avatar {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1.05rem;
    box-shadow: 0 6px 14px rgba(59, 130, 246, 0.35);
}

.wfs-user-name {
    margin: 0;
    font-size: 1.15rem;
    color: #0f172a;
}

.wfs-user-meta {
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
    color: #475569;
    font-size: 0.88rem;
}

.wfs-contact-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin: 0.65rem 0 0.25rem;
}

.wfs-contact-btn {
    padding: 0.35rem 0.7rem;
    border-radius: 9999px;
    background: #e0f2fe;
    color: #0c4a6e;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
}

.wfs-contact-btn:hover {
    background: #bae6fd;
    transform: translateY(-1px);
}

.wfs-contact-text {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    font-size: 0.85rem;
    color: #475569;
}

.wfs-card-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.6rem;
    min-width: 200px;
}

.wfs-doc-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    justify-content: flex-end;
}

.wfs-doc-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.55rem;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    background: rgba(148, 163, 184, 0.12);
    color: #475569;
}

.wfs-doc-chip.is-ready {
    background: rgba(34, 197, 94, 0.14);
    color: #166534;
}

.wfs-doc-chip.is-missing {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
}

.wfs-assignment-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    background: #eef2ff;
    color: #4338ca;
    font-weight: 600;
    font-size: 0.78rem;
}

.wfs-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.82rem;
    --wfs-status-color: #2563eb;
}

.wfs-status-light {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--wfs-status-color);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25);
    animation: wfs-pulse 2s ease-in-out infinite;
}

@keyframes wfs-pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
    50% { transform: scale(1.2); box-shadow: 0 0 0 7px rgba(37, 99, 235, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
}

.wfs-record-details {
    padding: 1.5rem;
    background: #ffffff;
}

.wfs-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
}

.wfs-info-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border: 1px solid #e2e8f0;
}

.wfs-info-section h4 {
    margin: 0;
    font-size: 1rem;
    color: #1f2937;
}

.wfs-info-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    font-size: 0.9rem;
    color: #334155;
}

.wfs-documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.wfs-documents-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.wfs-documents-card-header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
}

.wfs-documents-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    font-size: 0.85rem;
}

.wfs-documents-upload {
    margin-top: 0.5rem;
}

.wfs-upload-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.85rem;
    border: 1px dashed #c7d2fe;
    border-radius: 10px;
    color: #3730a3;
    font-weight: 600;
    cursor: pointer;
    background: rgba(99, 102, 241, 0.08);
}

.wfs-upload-label input[type='file'] {
    display: none;
}

.wfs-upload-label.is-disabled {
    color: #94a3b8;
    border-color: #e2e8f0;
    background: #f8fafc;
    cursor: not-allowed;
}

.wfs-bulk-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.9rem 1.25rem;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    background: #eff6ff;
    margin-bottom: 1.5rem;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-6px);
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.wfs-bulk-actions.is-active {
    opacity: 1;
    pointer-events: all;
    transform: translateY(0);
}

.wfs-bulk-checkbox {
    width: 18px;
    height: 18px;
}

.wfs-empty-state {
    background: #f8fafc;
    border: 1px dashed #cbd5f5;
    border-radius: 14px;
    padding: 2rem;
    text-align: center;
    color: #475569;
}

#wfs-confirm-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100000;
}

#wfs-confirm-modal.is-visible {
    display: flex;
    background: rgba(15, 23, 42, 0.45);
}

#wfs-confirm-modal .wfs-modal__backdrop {
    position: absolute;
    inset: 0;
}

#wfs-confirm-modal .wfs-modal__dialog {
    position: relative;
    background: #fff;
    border-radius: 14px;
    padding: 1.5rem;
    width: min(90vw, 360px);
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.25);
    display: flex;
    gap: 1rem;
}

.wfs-modal__icon {
    font-size: 1.75rem;
}

.wfs-modal__title {
    margin: 0 0 0.35rem 0;
    font-size: 1.05rem;
    color: #1f2937;
}

.wfs-modal__body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.wfs-modal__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

#wfs-toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    z-index: 99999;
}

.wfs-toast {
    background: #2563eb;
    color: #fff;
    padding: 0.75rem 1.1rem;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
    font-weight: 600;
}

.wfs-toast.error {
    background: #ef4444;
}

@media (max-width: 900px) {
    .wfs-card-header {
        flex-direction: column;
        align-items: stretch;
    }

    .wfs-card-actions {
        align-items: flex-start;
        min-width: auto;
    }

    .wfs-doc-summary {
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .wfs-add-record-section {
        padding: 1.25rem;
    }

    .wfs-card-header {
        padding: 1.1rem;
    }

    .wfs-user-info {
        flex-direction: column;
        align-items: flex-start;
    }

    .wfs-contact-actions {
        width: 100%;
    }

    .wfs-record-details {
        padding: 1.1rem;
    }

    .wfs-details-grid {
        grid-template-columns: 1fr;
    }

    .wfs-documents-grid {
        grid-template-columns: 1fr;
    }
}

/* overrides */
.wfs-header h1 {
    color: #fff;
}

.wfs-header p {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

.wfs-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.wfs-header-text {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.wfs-header-signature {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.1rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.92);
    font-weight: 600;
    letter-spacing: 0.01em;
}

.wfs-header-accent {
    color: #22c55e;
    font-size: 1.1em;
}

.wfs-card-header {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.wfs-card-topline {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.wfs-card-topmeta {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    margin-left: auto;
    flex-wrap: wrap;
}

.wfs-card-body {
    display: flex;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.wfs-card-actions {
    align-items: flex-end;
    gap: 0.9rem;
}

@media (max-width: 900px) {
    .wfs-card-actions {
        align-items: flex-start;
        width: 100%;
    }

    .wfs-card-body {
        align-items: flex-start;
    }
}

@media (max-width: 600px) {
    .wfs-header-signature {
        margin-left: 0;
    }

    .wfs-card-topline {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .wfs-card-topmeta {
        margin-left: 0;
    }

    .wfs-card-actions {
        align-items: flex-start;
    }

    .wfs-doc-summary {
        justify-content: flex-start;
    }
}
</style>
<div id="wfs-confirm-modal" class="wfs-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="wfs-modal__backdrop" data-modal-dismiss></div>
    <div class="wfs-modal__dialog" role="document">
        <div class="wfs-modal__icon" aria-hidden="true">⚠️</div>
        <div class="wfs-modal__body">
            <h3 class="wfs-modal__title"><?php esc_html_e('Onay', WFS_TEXT_DOMAIN); ?></h3>
            <p class="wfs-modal-message"><?php esc_html_e('Bu kaydı silmek istediğinize emin misiniz?', WFS_TEXT_DOMAIN); ?></p>
            <div class="wfs-modal__actions">
                <button type="button" class="wfs-btn wfs-btn-secondary wfs-modal-cancel"><?php esc_html_e('Vazgeç', WFS_TEXT_DOMAIN); ?></button>
                <button type="button" class="wfs-btn wfs-btn-danger wfs-modal-confirm"><?php esc_html_e('Evet, sil', WFS_TEXT_DOMAIN); ?></button>
            </div>
        </div>
    </div>
</div>

