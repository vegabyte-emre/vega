<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

global $wpdb;

$total_records   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records");
$pending_records = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'pending'");
$approved_records = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'approved'");
$rejected_records = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'rejected'");
$completed_records = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'completed'");

$total_files   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files");
$pending_files = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files WHERE status = 'pending'");
$approved_files = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files WHERE status = 'approved'");
$rejected_files = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files WHERE status = 'rejected'");

$recent_records = $wpdb->get_results(
    "SELECT r.*, u.display_name AS assigned_name
     FROM {$wpdb->prefix}wfs_records r
     LEFT JOIN {$wpdb->users} u ON r.assigned_to = u.ID
     ORDER BY r.created_at DESC
     LIMIT 5"
);

$pending_ratio  = $total_records > 0 ? round(($pending_records / $total_records) * 100, 1) : 0;
$approved_ratio = $total_records > 0 ? round(($approved_records / $total_records) * 100, 1) : 0;
$rejected_ratio = $total_records > 0 ? round(($rejected_records / $total_records) * 100, 1) : 0;

$pending_file_percent  = $total_files > 0 ? round(($pending_files / $total_files) * 100, 1) : 0;
$approved_file_percent = $total_files > 0 ? round(($approved_files / $total_files) * 100, 1) : 0;
$rejected_file_percent = $total_files > 0 ? round(($rejected_files / $total_files) * 100, 1) : 0;

$status_labels = array(
    'pending'    => array('label' => __('Beklemede', WFS_TEXT_DOMAIN), 'color' => '#f59e0b'),
    'processing' => array('label' => __('İşleniyor', WFS_TEXT_DOMAIN), 'color' => '#3b82f6'),
    'approved'   => array('label' => __('Onaylı', WFS_TEXT_DOMAIN), 'color' => '#10b981'),
    'rejected'   => array('label' => __('Reddedildi', WFS_TEXT_DOMAIN), 'color' => '#ef4444'),
    'completed'  => array('label' => __('Tamamlandı', WFS_TEXT_DOMAIN), 'color' => '#8b5cf6'),
);
?>

<div class="wrap">
    <div class="wfs-report-header">
        <div class="wfs-report-header__text">
            <h1>📊 <?php esc_html_e('Analiz ve Raporlar', WFS_TEXT_DOMAIN); ?></h1>
            <p><?php esc_html_e('Kapsamlı iş akışı performans analizi', WFS_TEXT_DOMAIN); ?></p>
        </div>
        <button type="button" class="wfs-btn wfs-btn-secondary wfs-report-download" onclick="exportReport()">
            📥 <?php esc_html_e('Raporu İndir', WFS_TEXT_DOMAIN); ?>
        </button>
    </div>

    <div class="wfs-report-metrics">
        <div class="wfs-report-metric wfs-report-metric--total">
            <span class="wfs-report-metric__value"><?php echo number_format_i18n($total_records); ?></span>
            <span class="wfs-report-metric__label"><?php esc_html_e('Toplam Kayıt', WFS_TEXT_DOMAIN); ?></span>
            <span class="wfs-report-metric__meta">
                <?php
                printf(
                    esc_html__('%1$s onaylı • %2$s beklemede', WFS_TEXT_DOMAIN),
                    number_format_i18n($approved_records),
                    number_format_i18n($pending_records)
                );
                ?>
            </span>
        </div>
        <div class="wfs-report-metric wfs-report-metric--pending">
            <span class="wfs-report-metric__value"><?php echo number_format_i18n($pending_records); ?></span>
            <span class="wfs-report-metric__label"><?php esc_html_e('Beklemede', WFS_TEXT_DOMAIN); ?></span>
            <span class="wfs-report-metric__meta"><?php echo esc_html($pending_ratio); ?>%</span>
        </div>
        <div class="wfs-report-metric wfs-report-metric--approved">
            <span class="wfs-report-metric__value"><?php echo number_format_i18n($approved_records); ?></span>
            <span class="wfs-report-metric__label"><?php esc_html_e('Onaylı', WFS_TEXT_DOMAIN); ?></span>
            <span class="wfs-report-metric__meta"><?php echo esc_html($approved_ratio); ?>%</span>
        </div>
        <div class="wfs-report-metric wfs-report-metric--rejected">
            <span class="wfs-report-metric__value"><?php echo number_format_i18n($rejected_records); ?></span>
            <span class="wfs-report-metric__label"><?php esc_html_e('Reddedilen', WFS_TEXT_DOMAIN); ?></span>
            <span class="wfs-report-metric__meta"><?php echo esc_html($rejected_ratio); ?>%</span>
        </div>
    </div>

    <div class="wfs-report-content">
        <div class="wfs-report-card">
            <h3 class="wfs-report-card__title">📁 <?php esc_html_e('Dosya İnceleme Durumu', WFS_TEXT_DOMAIN); ?></h3>
            <div class="wfs-report-file-total">
                <span class="wfs-report-file-total__icon" aria-hidden="true">📄</span>
                <div>
                    <span class="wfs-report-file-total__label"><?php esc_html_e('Toplam Dosya', WFS_TEXT_DOMAIN); ?></span>
                    <span class="wfs-report-file-total__value"><?php echo number_format_i18n($total_files); ?></span>
                </div>
            </div>

            <?php if ($total_files > 0): ?>
                <ul class="wfs-report-file-list">
                    <li class="wfs-report-file-list__item">
                        <span class="wfs-report-file-list__label">
                            <span class="wfs-report-progress__dot wfs-report-progress__dot--pending" aria-hidden="true"></span>
                            <?php esc_html_e('İnceleme Bekleyen', WFS_TEXT_DOMAIN); ?>
                        </span>
                        <span class="wfs-report-file-list__value"><?php echo number_format_i18n($pending_files); ?></span>
                        <div class="wfs-report-progress" role="presentation">
                            <span class="wfs-report-progress__bar" style="width: <?php echo esc_attr(min(100, $pending_file_percent)); ?>%;"></span>
                        </div>
                    </li>
                    <li class="wfs-report-file-list__item">
                        <span class="wfs-report-file-list__label">
                            <span class="wfs-report-progress__dot wfs-report-progress__dot--approved" aria-hidden="true"></span>
                            <?php esc_html_e('Onaylanan', WFS_TEXT_DOMAIN); ?>
                        </span>
                        <span class="wfs-report-file-list__value"><?php echo number_format_i18n($approved_files); ?></span>
                        <div class="wfs-report-progress" role="presentation">
                            <span class="wfs-report-progress__bar" style="width: <?php echo esc_attr(min(100, $approved_file_percent)); ?>%;"></span>
                        </div>
                    </li>
                    <li class="wfs-report-file-list__item">
                        <span class="wfs-report-file-list__label">
                            <span class="wfs-report-progress__dot wfs-report-progress__dot--rejected" aria-hidden="true"></span>
                            <?php esc_html_e('Reddedilen', WFS_TEXT_DOMAIN); ?>
                        </span>
                        <span class="wfs-report-file-list__value"><?php echo number_format_i18n($rejected_files); ?></span>
                        <div class="wfs-report-progress" role="presentation">
                            <span class="wfs-report-progress__bar" style="width: <?php echo esc_attr(min(100, $rejected_file_percent)); ?>%;"></span>
                        </div>
                    </li>
                </ul>
            <?php else: ?>
                <div class="wfs-report-empty">
                    <span aria-hidden="true">📂</span>
                    <p><?php esc_html_e('Henüz dosya yüklenmemiş.', WFS_TEXT_DOMAIN); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="wfs-report-card">
            <h3 class="wfs-report-card__title">🕒 <?php esc_html_e('Son Kayıtlar', WFS_TEXT_DOMAIN); ?></h3>

            <?php if (!empty($recent_records)): ?>
                <ul class="wfs-report-recent-list">
                    <?php foreach ($recent_records as $record):
                        $status_key = $record->overall_status ?: 'pending';
                        $status_config = $status_labels[$status_key] ?? array('label' => ucfirst($status_key), 'color' => '#3b82f6');
                        $full_name = trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? ''));
                        ?>
                        <li class="wfs-report-recent-item">
                            <div class="wfs-report-recent-item__info">
                                <span class="wfs-report-recent-item__name"><?php echo esc_html($full_name); ?></span>
                                <?php if (!empty($record->email)): ?>
                                    <span class="wfs-report-recent-item__email"><?php echo esc_html($record->email); ?></span>
                                <?php endif; ?>
                                <span class="wfs-report-recent-item__date"><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($record->created_at))); ?></span>
                            </div>
                            <span class="wfs-report-status" style="--wfs-status-color: <?php echo esc_attr($status_config['color']); ?>;">
                                <span class="wfs-report-status__dot" aria-hidden="true"></span>
                                <span class="wfs-report-status__label"><?php echo esc_html($status_config['label']); ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="wfs-report-empty">
                    <span aria-hidden="true">📋</span>
                    <p><?php esc_html_e('Henüz kayıt bulunmuyor.', WFS_TEXT_DOMAIN); ?></p>
                    <small><?php esc_html_e('FluentForms üzerinden gönderilen başvurular burada görünecek.', WFS_TEXT_DOMAIN); ?></small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="wfs-report-card wfs-report-card--system">
        <h3 class="wfs-report-card__title">⚙️ <?php esc_html_e('Sistem Durumu', WFS_TEXT_DOMAIN); ?></h3>
        <div class="wfs-report-system-grid">
            <div class="wfs-report-system-item">
                <span class="wfs-report-system-item__label">WordPress</span>
                <span class="wfs-report-system-item__value"><?php echo esc_html(get_bloginfo('version')); ?></span>
            </div>
            <div class="wfs-report-system-item">
                <span class="wfs-report-system-item__label">PHP</span>
                <span class="wfs-report-system-item__value"><?php echo esc_html(PHP_VERSION); ?></span>
            </div>
            <div class="wfs-report-system-item">
                <span class="wfs-report-system-item__label">FluentForms</span>
                <?php $fluent_active = is_plugin_active('fluentform/fluentform.php'); ?>
                <span class="wfs-report-system-item__badge <?php echo $fluent_active ? 'is-success' : 'is-warning'; ?>">
                    <?php echo $fluent_active ? '✅ ' . esc_html__('Aktif', WFS_TEXT_DOMAIN) : '❌ ' . esc_html__('Pasif', WFS_TEXT_DOMAIN); ?>
                </span>
            </div>
            <div class="wfs-report-system-item">
                <span class="wfs-report-system-item__label"><?php esc_html_e('Veritabanı', WFS_TEXT_DOMAIN); ?></span>
                <span class="wfs-report-system-item__badge is-success">✅ <?php esc_html_e('Bağlı', WFS_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport() {
    const csvContent = 'Eu WorkFlow Raporu\n\n' +
        'Toplam Kayıt,<?php echo $total_records; ?>\n' +
        'Bekleyen,<?php echo $pending_records; ?>\n' +
        'Onaylı,<?php echo $approved_records; ?>\n' +
        'Reddedilen,<?php echo $rejected_records; ?>\n' +
        'Tamamlanan,<?php echo $completed_records; ?>\n' +
        'Toplam Dosya,<?php echo $total_files; ?>\n' +
        'Bekleyen Dosya,<?php echo $pending_files; ?>\n' +
        'Onaylı Dosya,<?php echo $approved_files; ?>\n' +
        'Reddedilen Dosya,<?php echo $rejected_files; ?>\n' +
        '\nRapor Tarihi,' + new Date().toLocaleDateString('tr-TR');

    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', 'eu-workflow-raporu-' + new Date().toISOString().split('T')[0] + '.csv');

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
