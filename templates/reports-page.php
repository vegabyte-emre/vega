<?php
// templates/reports-page.php
if (!defined('ABSPATH')) exit;

global $wpdb;

// İstatistikleri hesapla
$total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records");
$pending_records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'pending'");
$approved_records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'approved'");
$rejected_records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_records WHERE overall_status = 'rejected'");

$total_files = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files");
$pending_files = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files WHERE status = 'pending'");
$approved_files = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files WHERE status = 'approved'");
$rejected_files = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wfs_files WHERE status = 'rejected'");

// Son kayıtlar
$recent_records = $wpdb->get_results("
    SELECT r.*, u.display_name as assigned_name 
    FROM {$wpdb->prefix}wfs_records r 
    LEFT JOIN {$wpdb->users} u ON r.assigned_to = u.ID 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
?>

<div class="wrap">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700;">📊 Analiz ve Raporlar</h1>
                <p style="margin: 0; opacity: 0.9;">Kapsamlı iş akışı performans analizi</p>
            </div>
            <button onclick="exportReport()" style="background: white; color: #8b5cf6; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; cursor: pointer;">
                📥 Raporu İndir
            </button>
        </div>
    </div>

    <!-- Ana İstatistikler -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <!-- Toplam Kayıt -->
        <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 2rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo number_format($total_records); ?></div>
            <div style="opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.875rem;">Toplam Kayıt</div>
            <div style="margin-top: 1rem; font-size: 0.875rem;">
                <span style="color: #bfdbfe;">✅ <?php echo $approved_records; ?> Onaylı</span>
            </div>
        </div>

        <!-- Beklemede -->
        <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 2rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo number_format($pending_records); ?></div>
            <div style="opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.875rem;">Beklemede</div>
            <div style="margin-top: 1rem; font-size: 0.875rem;">
                <span style="color: #fde68a;"><?php echo $total_records > 0 ? round(($pending_records / $total_records) * 100, 1) : 0; ?>% toplam içinde</span>
            </div>
        </div>

        <!-- Onaylı -->
        <div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 2rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo number_format($approved_records); ?></div>
            <div style="opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.875rem;">Onaylı</div>
            <div style="margin-top: 1rem; font-size: 0.875rem;">
                <span style="color: #bbf7d0;"><?php echo $total_records > 0 ? round(($approved_records / $total_records) * 100, 1) : 0; ?>% başarı oranı</span>
            </div>
        </div>

        <!-- Reddedilen -->
        <div style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 2rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo number_format($rejected_records); ?></div>
            <div style="opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.875rem;">Reddedilen</div>
            <div style="margin-top: 1rem; font-size: 0.875rem;">
                <span style="color: #fecaca;"><?php echo $total_records > 0 ? round(($rejected_records / $total_records) * 100, 1) : 0; ?>% ret oranı</span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Dosya İstatistikleri -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                📁 Dosya İnceleme Durumu
            </h3>
            
            <div style="space-y: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 32px; height: 32px; background: #ddd6fe; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            📄
                        </div>
                        <div>
                            <div style="font-weight: 600;">Toplam Dosya</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">Sisteme yüklenen tüm dosyalar</div>
                        </div>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?php echo number_format($total_files); ?></div>
                </div>

                <?php if ($total_files > 0): ?>
                    <!-- Bekleyen Dosyalar -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 12px; height: 12px; background: #f59e0b; border-radius: 50%; animation: pulse 2s infinite;"></div>
                            <span>İnceleme Bekleyen</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 600; color: #f59e0b;"><?php echo $pending_files; ?></span>
                            <div style="width: 64px; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #f59e0b; border-radius: 4px; width: <?php echo $total_files > 0 ? ($pending_files / $total_files) * 100 : 0; ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Onaylanan Dosyalar -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></div>
                            <span>Onaylanan</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 600; color: #10b981;"><?php echo $approved_files; ?></span>
                            <div style="width: 64px; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #10b981; border-radius: 4px; width: <?php echo $total_files > 0 ? ($approved_files / $total_files) * 100 : 0; ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Reddedilen Dosyalar -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%;"></div>
                            <span>Reddedilen</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 600; color: #ef4444;"><?php echo $rejected_files; ?></span>
                            <div style="width: 64px; height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: #ef4444; border-radius: 4px; width: <?php echo $total_files > 0 ? ($rejected_files / $total_files) * 100 : 0; ?>%;"></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <p>Henüz dosya yüklenmemiş.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Son Kayıtlar -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                🕒 Son Kayıtlar
            </h3>

            <?php if (!empty($recent_records)): ?>
                <div style="space-y: 1rem;">
                    <?php foreach ($recent_records as $record): ?>
                        <?php 
                        $status_colors = array(
                            'pending' => '#f59e0b',
                            'approved' => '#10b981', 
                            'rejected' => '#ef4444'
                        );
                        ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <div>
                                <div style="font-weight: 600;"><?php echo esc_html($record->first_name . ' ' . $record->last_name); ?></div>
                                <div style="font-size: 0.875rem; color: #6b7280;"><?php echo esc_html($record->email); ?></div>
                                <div style="font-size: 0.75rem; color: #9ca3af;"><?php echo date('d.m.Y H:i', strtotime($record->created_at)); ?></div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="width: 8px; height: 8px; background: <?php echo $status_colors[$record->overall_status]; ?>; border-radius: 50%;"></div>
                                <span style="font-size: 0.875rem; color: <?php echo $status_colors[$record->overall_status]; ?>; font-weight: 500;">
                                    <?php echo $record->overall_status == 'pending' ? 'Beklemede' : ($record->overall_status == 'approved' ? 'Onaylı' : 'Reddedildi'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                    <p>Henüz kayıt bulunmuyor.</p>
                    <p style="font-size: 0.875rem;">FluentForms üzerinden gönderilen başvurular burada görünecek.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sistem Durumu -->
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
            ⚙️ Sistem Durumu
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f0f9ff; border-radius: 8px;">
                <span style="font-weight: 500;">WordPress</span>
                <span style="color: #0369a1;"><?php echo get_bloginfo('version'); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f0f9ff; border-radius: 8px;">
                <span style="font-weight: 500;">PHP</span>
                <span style="color: #0369a1;"><?php echo PHP_VERSION; ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 1rem; background: <?php echo is_plugin_active('fluentform/fluentform.php') ? '#f0fdf4' : '#fef2f2'; ?>; border-radius: 8px;">
                <span style="font-weight: 500;">FluentForms</span>
                <span style="color: <?php echo is_plugin_active('fluentform/fluentform.php') ? '#059669' : '#dc2626'; ?>;">
                    <?php echo is_plugin_active('fluentform/fluentform.php') ? '✅ Aktif' : '❌ Pasif'; ?>
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f0f9ff; border-radius: 8px;">
                <span style="font-weight: 500;">Veritabanı</span>
                <span style="color: #0369a1;">✅ Bağlı</span>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.05);
    }
}
</style>

<script>
function exportReport() {
    const csvContent = 'İş Akışı Raporu\n\n' +
        'Toplam Kayıt,<?php echo $total_records; ?>\n' +
        'Bekleyen,<?php echo $pending_records; ?>\n' +
        'Onaylı,<?php echo $approved_records; ?>\n' +
        'Reddedilen,<?php echo $rejected_records; ?>\n' +
        'Toplam Dosya,<?php echo $total_files; ?>\n' +
        'Bekleyen Dosya,<?php echo $pending_files; ?>\n' +
        'Onaylı Dosya,<?php echo $approved_files; ?>\n' +
        'Reddedilen Dosya,<?php echo $rejected_files; ?>\n' +
        '\nRapor Tarihi,' + new Date().toLocaleDateString('tr-TR');

    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'workflow-raporu-' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert('Rapor indiriliyor...');
}
</script>