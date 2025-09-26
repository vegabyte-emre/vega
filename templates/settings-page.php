<?php
if (!defined('ABSPATH')) {
    exit;
}

$hide_wp_menus = get_option('wfs_hide_wp_menus', false);
$editable_statuses = isset($status_settings) && is_array($status_settings) ? $status_settings : array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_permissions']) && check_admin_referer('wfs_save_permissions')) {
        $hide_wp_menus = isset($_POST['hide_wp_menus']);
        update_option('wfs_hide_wp_menus', $hide_wp_menus);
        echo '<div class="notice notice-success"><p>' . esc_html__('Ayarlar güncellendi.', WFS_TEXT_DOMAIN) . '</p></div>';
    }

    if (isset($_POST['save_statuses']) && check_admin_referer('wfs_save_statuses')) {
        $statuses_input = $_POST['statuses'] ?? array();
        $sanitized = array();

        if (is_array($statuses_input)) {
            foreach ($statuses_input as $row) {
                $slug = isset($row['slug']) ? sanitize_key($row['slug']) : '';
                $label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
                $color = isset($row['color']) ? sanitize_hex_color($row['color']) : '#2563eb';
                $bg = isset($row['bg']) ? sanitize_hex_color($row['bg']) : '#dbeafe';

                if (empty($slug) || empty($label)) {
                    continue;
                }

                $sanitized[$slug] = array(
                    'label' => $label,
                    'color' => $color,
                    'bg' => $bg,
                );
            }
        }

        update_option('wfs_status_settings', $sanitized);
        $editable_statuses = $sanitized;
        echo '<div class="notice notice-success"><p>' . esc_html__('Statüler kaydedildi.', WFS_TEXT_DOMAIN) . '</p></div>';
    }
}
?>

<div class="wrap">
    <div class="wfs-settings-header">
        <h1>⚙️ Eu WorkFlow Ayarları</h1>
        <p><?php esc_html_e('Sistem görünürlüğünü ve statü yapılandırmasını yönetin.', WFS_TEXT_DOMAIN); ?></p>
    </div>

    <div class="wfs-settings-card">
        <h2>🔒 WordPress Menü Kontrolü</h2>
        <form method="post">
            <?php wp_nonce_field('wfs_save_permissions'); ?>
            <label class="wfs-settings-checkbox">
                <input type="checkbox" name="hide_wp_menus" <?php checked($hide_wp_menus); ?>>
                <span><?php esc_html_e('İş akışı rollerinden varsayılan WordPress menülerini gizle', WFS_TEXT_DOMAIN); ?></span>
            </label>
            <p class="description"><?php esc_html_e('Yalnızca yönetici olmayan kullanıcılar etkilenir.', WFS_TEXT_DOMAIN); ?></p>
            <button type="submit" name="save_permissions" class="button button-primary"><?php esc_html_e('Kaydet', WFS_TEXT_DOMAIN); ?></button>
        </form>
    </div>

    <div class="wfs-settings-card">
        <h2>🎯 Statü Yönetimi</h2>
        <form method="post" id="wfs-status-form">
            <?php wp_nonce_field('wfs_save_statuses'); ?>
            <table class="widefat fixed wfs-status-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Slug', WFS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Etiket', WFS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Renk', WFS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Arkaplan', WFS_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Önizleme', WFS_TEXT_DOMAIN); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="wfs-status-rows">
                    <?php if (!empty($editable_statuses)): ?>
                        <?php foreach ($editable_statuses as $slug => $data): ?>
                            <tr>
                                <td><input type="text" name="statuses[][slug]" value="<?php echo esc_attr($slug); ?>" required></td>
                                <td><input type="text" name="statuses[][label]" value="<?php echo esc_attr($data['label']); ?>" required></td>
                                <td><input type="color" name="statuses[][color]" value="<?php echo esc_attr($data['color']); ?>"></td>
                                <td><input type="color" name="statuses[][bg]" value="<?php echo esc_attr($data['bg']); ?>"></td>
                                <td><span class="wfs-status-preview" style="background: <?php echo esc_attr($data['bg']); ?>; color: <?php echo esc_attr($data['color']); ?>;"><?php echo esc_html($data['label']); ?></span></td>
                                <td><button type="button" class="button-link-delete wfs-remove-status"><?php esc_html_e('Sil', WFS_TEXT_DOMAIN); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="wfs-status-empty">
                            <td colspan="6"><?php esc_html_e('Henüz statü tanımı yok. Yeni satır ekleyebilirsiniz.', WFS_TEXT_DOMAIN); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="wfs-status-actions">
                <button type="button" class="button" id="wfs-add-status"><?php esc_html_e('Yeni Statü Ekle', WFS_TEXT_DOMAIN); ?></button>
                <button type="submit" name="save_statuses" class="button button-primary"><?php esc_html_e('Statüleri Kaydet', WFS_TEXT_DOMAIN); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
.wfs-settings-header {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.wfs-settings-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.1rem;
}

.wfs-settings-card {
    background: white;
    border-radius: 12px;
    padding: 1.75rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(15, 23, 42, 0.08);
}

.wfs-settings-checkbox {
    display: flex;
    gap: 0.6rem;
    align-items: center;
    font-weight: 600;
    color: #1f2937;
}

.wfs-status-table input[type="text"] {
    width: 100%;
}

.wfs-status-table input[type="color"] {
    width: 60px;
    height: 34px;
    padding: 0;
}

.wfs-status-preview {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    font-weight: 600;
}

.wfs-status-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}
</style>

<script>
(function($) {
    $('#wfs-add-status').on('click', function() {
        const row = `
            <tr>
                <td><input type="text" name="statuses[][slug]" required></td>
                <td><input type="text" name="statuses[][label]" required></td>
                <td><input type="color" name="statuses[][color]" value="#2563eb"></td>
                <td><input type="color" name="statuses[][bg]" value="#dbeafe"></td>
                <td><span class="wfs-status-preview" style="background: #dbeafe; color: #2563eb;">Önizleme</span></td>
                <td><button type="button" class="button-link-delete wfs-remove-status"><?php echo esc_js(__('Sil', WFS_TEXT_DOMAIN)); ?></button></td>
            </tr>`;
        $('#wfs-status-rows').append(row);
        $('#wfs-status-rows .wfs-status-empty').remove();
    });

    $(document).on('click', '.wfs-remove-status', function() {
        $(this).closest('tr').remove();
        if (!$('#wfs-status-rows tr').length) {
            $('#wfs-status-rows').append('<tr class="wfs-status-empty"><td colspan="6"><?php echo esc_js(__('Henüz statü tanımı yok. Yeni satır ekleyebilirsiniz.', WFS_TEXT_DOMAIN)); ?></td></tr>');
        }
    });

    $(document).on('input change', '#wfs-status-rows input[type="text"], #wfs-status-rows input[type="color"]', function() {
        const $row = $(this).closest('tr');
        const label = $row.find('input[name$="[label]"]').val() || 'Önizleme';
        const color = $row.find('input[name$="[color]"]').val() || '#2563eb';
        const bg = $row.find('input[name$="[bg]"]').val() || '#dbeafe';
        $row.find('.wfs-status-preview').text(label).css({ background: bg, color: color });
    });
})(jQuery);
</script>
