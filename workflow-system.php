<?php
/**
 * Plugin Name: İş Akışı Yönetim Sistemi
 * Plugin URI: https://yourwebsite.com
 * Description: FluentForms entegrasyonlu modern iş akışı yönetim sistemi
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: workflow-system
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sabitleri
define('WFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WFS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WFS_VERSION', '1.0.0');

// Ana sınıf
class WorkflowSystem {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Çevirileri yükle
        load_plugin_textdomain('workflow-system', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Form builder AJAX handlers - önce bunları ekleyelim
        add_action('wp_ajax_wfs_save_form', array($this, 'ajax_save_form'));
        add_action('wp_ajax_wfs_get_form', array($this, 'ajax_get_form'));
        add_action('wp_ajax_wfs_submit_custom_form', array($this, 'ajax_submit_custom_form'));
        add_action('wp_ajax_nopriv_wfs_submit_custom_form', array($this, 'ajax_submit_custom_form')); // Giriş yapmamış kullanıcılar için
         add_action('wp_ajax_wfs_get_record_details', array($this, 'ajax_get_record_details'));

        // Admin paneli
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('wp_ajax_wfs_get_records', array($this, 'ajax_get_records'));
            add_action('wp_ajax_wfs_update_status', array($this, 'ajax_update_status'));
            add_action('wp_ajax_wfs_assign_record', array($this, 'ajax_assign_record'));
            
        }
        
        // Özel roller oluştur
        $this->create_custom_roles();
        
        // FluentForms hook'u
        add_action('fluentform/submission_inserted', array($this, 'handle_fluent_form_submission'), 10, 3);
        
        // Admin menü gizleme
        add_action('admin_menu', array($this, 'hide_admin_menus'), 999);
        
        // Dashboard yönlendirmesi
        add_action('admin_init', array($this, 'redirect_non_admin_users'));
    }
    
    public function activate() {
        $this->create_tables();
        $this->create_custom_roles();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Ana kayıtlar tablosu
        $table_records = $wpdb->prefix . 'wfs_records';
        $sql_records = "CREATE TABLE $table_records (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            fluent_form_id int(11) NOT NULL,
            submission_id int(11) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            education_level varchar(50),
            department varchar(100),
            age int(3),
            assigned_to int(11) DEFAULT NULL,
            overall_status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fluent_form_id (fluent_form_id),
            KEY submission_id (submission_id),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";
        
        // Dosyalar tablosu
        $table_files = $wpdb->prefix . 'wfs_files';
        $sql_files = "CREATE TABLE $table_files (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            record_id mediumint(9) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_size int(11) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            reviewed_by int(11) DEFAULT NULL,
            review_notes text,
            reviewed_at datetime DEFAULT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY record_id (record_id),
            KEY reviewed_by (reviewed_by)
        ) $charset_collate;";
        
        // Aktiviteler tablosu
        $table_activities = $wpdb->prefix . 'wfs_activities';
        $sql_activities = "CREATE TABLE $table_activities (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            record_id mediumint(9) NOT NULL,
            user_id int(11) NOT NULL,
            action varchar(100) NOT NULL,
            description text,
            old_value varchar(255),
            new_value varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY record_id (record_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_records);
        dbDelta($sql_files);
        dbDelta($sql_activities);
    }
    
    public function create_custom_roles() {
        // Mevcut rolleri kontrol et ve gerekirse güncelle
        $role = get_role('wfs_superadmin');
        if (!$role) {
            add_role('wfs_superadmin', __('İş Akışı Süperadmin', 'workflow-system'), array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'wfs_manage_all' => true,
                'wfs_assign_records' => true,
                'wfs_view_reports' => true,
                'wfs_manage_users' => true,
            ));
        }
        
        $role = get_role('wfs_representative');
        if (!$role) {
            add_role('wfs_representative', __('İş Akışı Temsilci', 'workflow-system'), array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => false,
                'wfs_review_files' => true,
                'wfs_assign_records' => true,
                'wfs_view_assigned' => true,
            ));
        }
        
        $role = get_role('wfs_consultant');
        if (!$role) {
            add_role('wfs_consultant', __('İş Akışı Danışan', 'workflow-system'), array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'wfs_review_files' => true,
                'wfs_view_assigned' => true,
            ));
        }
    }
    
    public function admin_menu() {
        add_menu_page(
            __('İş Akışı Sistemi', 'workflow-system'),
            __('İş Akışı', 'workflow-system'),
            'read', // Temel okuma yetkisi
            'workflow-system',
            array($this, 'admin_page'),
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'workflow-system',
            __('Kayıtlar', 'workflow-system'),
            __('Kayıtlar', 'workflow-system'),
            'read',
            'workflow-system',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'workflow-system',
            __('Raporlar', 'workflow-system'),
            __('Raporlar', 'workflow-system'),
            'read',
            'workflow-reports',
            array($this, 'reports_page')
        );

        add_submenu_page(
            'workflow-system',
            __('Ayarlar', 'workflow-system'),
            __('Ayarlar', 'workflow-system'),
            'manage_options',
            'workflow-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'workflow-system',
            __('Form Builder', 'workflow-system'),
            __('Form Builder', 'workflow-system'),
            'manage_options',
            'workflow-form-builder',
            array($this, 'form_builder_page')
        );
    }
    
    public function hide_admin_menus() {
        $hide_wp_menus = get_option('wfs_hide_wp_menus', false);
        
        if (!$hide_wp_menus || current_user_can('manage_options')) {
            return;
        }
        
        global $menu, $submenu;
        
        // Gizlenecek menüler listesi
        $hidden_menus = array(
            'index.php',                    // Dashboard
            'edit.php',                     // Posts
            'upload.php',                   // Media
            'edit.php?post_type=page',      // Pages
            'edit-comments.php',            // Comments
            'themes.php',                   // Appearance
            'plugins.php',                  // Plugins
            'users.php',                    // Users
            'tools.php',                    // Tools
            'options-general.php',          // Settings
            'hostinger',                    // Hostinger
            'hostinger-ai-assistant'        // Hostinger AI
        );
        
        foreach ($hidden_menus as $menu_slug) {
            remove_menu_page($menu_slug);
        }
        
        // Admin bar'ı da temizle
        add_action('wp_before_admin_bar_render', array($this, 'hide_admin_bar_items'));
    }
    
    public function hide_admin_bar_items() {
        if (current_user_can('manage_options')) {
            return;
        }
        
        global $wp_admin_bar;
        
        $hidden_items = array(
            'wp-logo', 'about', 'wporg', 'documentation', 'support-forums', 'feedback',
            'site-name', 'view-site', 'updates', 'comments', 'new-content', 'edit',
            'appearance', 'themes', 'widgets', 'menus', 'background', 'header', 'customize'
        );
        
        foreach ($hidden_items as $item) {
            $wp_admin_bar->remove_menu($item);
        }
    }
    
    public function redirect_non_admin_users() {
        $hide_wp_menus = get_option('wfs_hide_wp_menus', false);
        
        if (!$hide_wp_menus || current_user_can('manage_options')) {
            return;
        }
        
        $current_screen = get_current_screen();
        if ($current_screen && $current_screen->id === 'dashboard') {
            wp_redirect(admin_url('admin.php?page=workflow-system'));
            exit;
        }
    }
    
    public function admin_scripts($hook) {
        if (strpos($hook, 'workflow') === false) {
            return;
        }
        
        wp_enqueue_script('wfs-admin-js', WFS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WFS_VERSION, true);
        wp_enqueue_style('wfs-admin-css', WFS_PLUGIN_URL . 'assets/css/admin.css', array(), WFS_VERSION);
        
        // Modern UI framework'ler
        wp_enqueue_script('wfs-vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.4/vue.global.min.js', array(), '3.3.4', true);
        wp_enqueue_style('wfs-tailwind', 'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css', array(), '2.2.19');
        
        wp_localize_script('wfs-admin-js', 'wfs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wfs_nonce'),
            'form_nonce' => wp_create_nonce('wfs_form_nonce'),
            'custom_form_nonce' => wp_create_nonce('wfs_custom_form_nonce'),
            'strings' => array(
                'confirm_assign' => __('Bu kaydı atamak istediğinizden emin misiniz?', 'workflow-system'),
                'success' => __('İşlem başarılı', 'workflow-system'),
                'error' => __('Bir hata oluştu', 'workflow-system')
            )
        ));
    }
    
    public function handle_fluent_form_submission($insertId, $formData, $form) {
        global $wpdb;
        
        // Form verilerini parse et
        $data = array();
        foreach ($formData as $field => $value) {
            if (is_array($value)) {
                $data[$field] = implode(', ', $value);
            } else {
                $data[$field] = sanitize_text_field($value);
            }
        }
        
        // Ana kayıt oluştur
        $record_data = array(
            'fluent_form_id' => intval($form->id),
            'submission_id' => intval($insertId),
            'first_name' => isset($data['first_name']) ? $data['first_name'] : '',
            'last_name' => isset($data['last_name']) ? $data['last_name'] : '',
            'email' => isset($data['email']) ? sanitize_email($data['email']) : '',
            'phone' => isset($data['phone']) ? $data['phone'] : '',
            'education_level' => isset($data['education_level']) ? $data['education_level'] : '',
            'department' => isset($data['department']) ? $data['department'] : '',
            'age' => isset($data['age']) && is_numeric($data['age']) ? intval($data['age']) : null,
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'wfs_records', $record_data);
        
        if ($result === false) {
            error_log('WFS: Kayıt eklenirken hata oluştu: ' . $wpdb->last_error);
            return;
        }
        
        $record_id = $wpdb->insert_id;
        
        // Dosya yüklemelerini işle
        if (isset($_FILES) && !empty($_FILES)) {
            $this->handle_file_uploads($record_id, $_FILES);
        }
        
        // Aktivite kaydı
        $this->log_activity($record_id, 0, 'record_created', 'Yeni kayıt oluşturuldu');
    }
    
    private function handle_file_uploads($record_id, $files) {
        global $wpdb;
        
        $upload_dir = wp_upload_dir();
        $wfs_upload_dir = $upload_dir['basedir'] . '/workflow-system/';
        
        if (!file_exists($wfs_upload_dir)) {
            wp_mkdir_p($wfs_upload_dir);
        }
        
        foreach ($files as $field_name => $file_array) {
            if (is_array($file_array['name'])) {
                // Çoklu dosya yükleme
                for ($i = 0; $i < count($file_array['name']); $i++) {
                    if ($file_array['error'][$i] === UPLOAD_ERR_OK) {
                        $this->process_single_file($record_id, array(
                            'name' => $file_array['name'][$i],
                            'type' => $file_array['type'][$i],
                            'tmp_name' => $file_array['tmp_name'][$i],
                            'size' => $file_array['size'][$i]
                        ), $wfs_upload_dir);
                    }
                }
            } else {
                // Tekil dosya yükleme
                if ($file_array['error'] === UPLOAD_ERR_OK) {
                    $this->process_single_file($record_id, $file_array, $wfs_upload_dir);
                }
            }
        }
    }
    
    private function process_single_file($record_id, $file, $upload_dir) {
        global $wpdb;
        
        // Güvenlik kontrolü
        $allowed_types = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            error_log('WFS: İzin verilmeyen dosya türü: ' . $file_extension);
            return false;
        }
        
        // Dosya boyutu kontrolü (5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            error_log('WFS: Dosya boyutu çok büyük: ' . $file['size']);
            return false;
        }
        
        $filename = sanitize_file_name($record_id . '_' . time() . '_' . $file['name']);
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $result = $wpdb->insert($wpdb->prefix . 'wfs_files', array(
                'record_id' => $record_id,
                'file_name' => $file['name'],
                'file_path' => $filepath,
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'status' => 'pending'
            ));
            
            return $result !== false;
        }
        
        return false;
    }
    
    public function log_activity($record_id, $user_id, $action, $description, $old_value = '', $new_value = '') {
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix . 'wfs_activities', array(
            'record_id' => intval($record_id),
            'user_id' => intval($user_id),
            'action' => sanitize_text_field($action),
            'description' => sanitize_text_field($description),
            'old_value' => sanitize_text_field($old_value),
            'new_value' => sanitize_text_field($new_value)
        ));
    }
    
    // AJAX handlers
    public function ajax_get_records() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_nonce')) {
            wp_send_json_error('Güvenlik hatası');
            return;
        }
        
        global $wpdb;
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        $representative_filter = intval($_POST['representative_filter'] ?? 0);
        
        $offset = ($page - 1) * $per_page;
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        if (!empty($search)) {
            $where_conditions[] = "(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "overall_status = %s";
            $where_values[] = $status_filter;
        }
        
        if ($representative_filter > 0) {
            $where_conditions[] = "assigned_to = %d";
            $where_values[] = $representative_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT r.*, u.display_name as assigned_name 
                  FROM {$wpdb->prefix}wfs_records r 
                  LEFT JOIN {$wpdb->users} u ON r.assigned_to = u.ID 
                  WHERE $where_clause 
                  ORDER BY r.created_at DESC 
                  LIMIT %d OFFSET %d";
        
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Her kayıt için dosya bilgilerini getir
        foreach ($results as &$record) {
            $files = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wfs_files WHERE record_id = %d",
                $record->id
            ));
            $record->files = $files;
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_update_status() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_nonce')) {
            wp_send_json_error('Güvenlik hatası');
            return;
        }
        
        global $wpdb;
        
        $file_id = intval($_POST['file_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!in_array($status, array('approved', 'rejected', 'pending'))) {
            wp_send_json_error('Geçersiz statü');
            return;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wfs_files',
            array(
                'status' => $status,
                'review_notes' => $notes,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $file_id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Aktivite kaydı
            $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wfs_files WHERE id = %d", $file_id));
            if ($file) {
                $this->log_activity($file->record_id, get_current_user_id(), 'file_status_updated', 
                    "Dosya statüsü güncellendi: {$file->file_name}", '', $status);
            }
            
            wp_send_json_success('Statü güncellendi');
        } else {
            wp_send_json_error('Güncelleme hatası: ' . $wpdb->last_error);
        }
    }
    public function ajax_get_record_details() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_nonce')) {
        wp_send_json_error('Güvenlik hatası');
        return;
    }
    
    global $wpdb;
    
    $record_id = intval($_POST['record_id'] ?? 0);
    
    // Kayıt bilgilerini getir
    $record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wfs_records WHERE id = %d",
        $record_id
    ));
    
    if (!$record) {
        wp_send_json_error('Kayıt bulunamadı');
        return;
    }
    
    // Dosyaları getir
    $files = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wfs_files WHERE record_id = %d ORDER BY uploaded_at DESC",
        $record_id
    ));
    
    wp_send_json_success(array(
        'record' => $record,
        'files' => $files
    ));
}
    public function ajax_assign_record() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_nonce')) {
            wp_send_json_error('Güvenlik hatası');
            return;
        }
        
        global $wpdb;
        
        $record_id = intval($_POST['record_id'] ?? 0);
        $assigned_to = intval($_POST['assigned_to'] ?? 0);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'wfs_records',
            array('assigned_to' => $assigned_to),
            array('id' => $record_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            // E-posta gönder
            $this->send_assignment_email($record_id, $assigned_to);
            
            // Aktivite kaydı
            $assigned_user = get_user_by('id', $assigned_to);
            if ($assigned_user) {
                $this->log_activity($record_id, get_current_user_id(), 'record_assigned', 
                    "Kayıt atandı: {$assigned_user->display_name}");
            }
            
            wp_send_json_success('Kayıt atandı');
        } else {
            wp_send_json_error('Atama hatası: ' . $wpdb->last_error);
        }
    }
    
    private function send_assignment_email($record_id, $assigned_to) {
        global $wpdb;
        
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wfs_records WHERE id = %d", $record_id
        ));
        
        $user = get_user_by('id', $assigned_to);
        
        if ($record && $user) {
            $subject = sprintf(__('Size yeni bir talep atandı: %s %s', 'workflow-system'), 
                $record->first_name, $record->last_name);
            
            $message = sprintf(
                __("Merhaba %s,\n\nSize yeni bir talep atandı:\n\nAd Soyad: %s %s\nE-posta: %s\nTelefon: %s\nEğitim Durumu: %s\nBölüm: %s\nYaş: %s\n\nLütfen sisteme giriş yaparak talebi inceleyin.\n\nTeşekkürler", 'workflow-system'),
                $user->display_name,
                $record->first_name,
                $record->last_name,
                $record->email,
                $record->phone,
                $record->education_level,
                $record->department,
                $record->age
            );
            
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    // Form Builder AJAX handlers
    public function ajax_save_form() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_form_nonce')) {
            wp_send_json_error('Güvenlik hatası');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
            return;
        }
        
        $form_data = wp_unslash($_POST['form_data'] ?? '');
        
        if (empty($form_data)) {
            wp_send_json_error('Form verisi boş');
            return;
        }
        
        // JSON formatını kontrol et
        $decoded_data = json_decode($form_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Geçersiz JSON formatı: ' . json_last_error_msg());
            return;
        }
        
        $result = update_option('wfs_custom_form', $form_data);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Form başarıyla kaydedildi',
                'timestamp' => current_time('mysql')
            ));
        } else {
            $existing = get_option('wfs_custom_form', '');
            if ($existing === $form_data) {
                wp_send_json_success(array(
                    'message' => 'Form zaten güncel',
                    'timestamp' => current_time('mysql')
                ));
            } else {
                wp_send_json_error('Kaydetme hatası oluştu');
            }
        }
    }

    public function ajax_get_form() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_form_nonce')) {
            wp_send_json_error('Güvenlik hatası');
            return;
        }
        
        $form_data = get_option('wfs_custom_form', '{"fields":[],"settings":{"title":"Özel Form","description":""}}');
        $decoded_data = json_decode($form_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded_data = array(
                'fields' => array(),
                'settings' => array(
                    'title' => 'Özel Form',
                    'description' => ''
                )
            );
        }
        
        wp_send_json_success($decoded_data);
    }
    
    public function ajax_submit_custom_form() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wfs_custom_form_nonce')) {
            wp_send_json_error('Güvenlik hatası');
            return;
        }
        
        global $wpdb;
        
        $form_data = get_option('wfs_custom_form', '{"fields":[]}');
        $form_config = json_decode($form_data, true);
        
        if (empty($form_config['fields'])) {
            wp_send_json_error('Form yapılandırması bulunamadı');
            return;
        }
        
        $record_data = array(
            'fluent_form_id' => 0,
            'submission_id' => 0,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'education_level' => '',
            'department' => '',
            'age' => null,
            'overall_status' => 'pending'
        );
        
        // Form alanlarından verileri çıkar
        foreach ($form_config['fields'] as $field) {
            $field_name = 'field_' . $field['id'];
            $value = '';
            
            if (isset($_POST[$field_name])) {
                if (is_array($_POST[$field_name])) {
                    $value = implode(', ', array_map('sanitize_text_field', $_POST[$field_name]));
                } else {
                    $value = sanitize_text_field($_POST[$field_name]);
                }
            }
            
            // Form alanlarını veritabanı alanlarına eşleştir
            if (stripos($field['label'], 'ad') !== false && stripos($field['label'], 'soyad') === false) {
                $record_data['first_name'] = $value;
            } elseif (stripos($field['label'], 'soyad') !== false) {
                $record_data['last_name'] = $value;
            } elseif (stripos($field['label'], 'email') !== false || stripos($field['label'], 'e-posta') !== false) {
                $record_data['email'] = sanitize_email($value);
            } elseif (stripos($field['label'], 'telefon') !== false) {
                $record_data['phone'] = $value;
            } elseif (stripos($field['label'], 'eğitim') !== false) {
                $record_data['education_level'] = $value;
            } elseif (stripos($field['label'], 'bölüm') !== false || stripos($field['label'], 'department') !== false) {
                $record_data['department'] = $value;
            } elseif (stripos($field['label'], 'yaş') !== false) {
                $record_data['age'] = is_numeric($value) ? intval($value) : null;
            }
        }
        
        // Kayıt ekle
        $result = $wpdb->insert($wpdb->prefix . 'wfs_records', $record_data);
        
        if ($result) {
            $record_id = $wpdb->insert_id;
            
            // Dosya yükleme işle
            if (!empty($_FILES)) {
                $this->handle_file_uploads($record_id, $_FILES);
            }
            
            // Aktivite kaydı
            $user_id = get_current_user_id();
            $this->log_activity($record_id, $user_id, 'record_created', 'Özel form ile kayıt oluşturuldu');
            
            wp_send_json_success('Kayıt başarıyla eklendi');
        } else {
            wp_send_json_error('Kayıt eklenirken hata oluştu: ' . $wpdb->last_error);
        }
    }
    
    // Sayfa fonksiyonları
    public function admin_page() {
        $template_path = WFS_PLUGIN_PATH . 'templates/admin-page.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>' . __('Admin sayfası şablonu bulunamadı.', 'workflow-system') . '</p></div>';
        }
    }
    
    public function reports_page() {
        $template_path = WFS_PLUGIN_PATH . 'templates/reports-page.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>' . __('Raporlar sayfası şablonu bulunamadı.', 'workflow-system') . '</p></div>';
        }
    }
    
    public function settings_page() {
        // Ayarları kaydet
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'wfs_settings_nonce')) {
            $hide_wp_menus = isset($_POST['wfs_hide_wp_menus']) ? 1 : 0;
            update_option('wfs_hide_wp_menus', $hide_wp_menus);
            
            echo '<div class="notice notice-success"><p>' . __('Ayarlar kaydedildi.', 'workflow-system') . '</p></div>';
        }
        
        $template_path = WFS_PLUGIN_PATH . 'templates/settings-page.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Basit ayarlar sayfası oluştur
            $hide_wp_menus = get_option('wfs_hide_wp_menus', false);
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(__('İş Akışı Ayarları', 'workflow-system')); ?></h1>
                <form method="post" action="">
                    <?php wp_nonce_field('wfs_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('WordPress Menülerini Gizle', 'workflow-system'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="wfs_hide_wp_menus" value="1" <?php checked($hide_wp_menus); ?>>
                                    <?php _e('Admin dışındaki kullanıcılar için WordPress menülerini gizle', 'workflow-system'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    }
    
    public function form_builder_page() {
        $template_path = WFS_PLUGIN_PATH . 'templates/form-builder-page.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>' . __('Form Builder sayfası şablonu bulunamadı.', 'workflow-system') . '</p></div>';
        }
    }
    
    // Plugin deaktivasyonu sırasında temizlik
    public static function uninstall() {
        global $wpdb;
        
        // Tabloları sil
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfs_activities");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfs_files");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfs_records");
        
        // Rolleri sil
        remove_role('wfs_superadmin');
        remove_role('wfs_representative');
        remove_role('wfs_consultant');
        
        // Seçenekleri sil
        delete_option('wfs_custom_form');
        delete_option('wfs_hide_wp_menus');
        
        // Upload klasörünü temizle
        $upload_dir = wp_upload_dir();
        $wfs_upload_dir = $upload_dir['basedir'] . '/workflow-system/';
        
        if (file_exists($wfs_upload_dir)) {
            $files = glob($wfs_upload_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($wfs_upload_dir);
        }
    }
}

// Uninstall hook'u
register_uninstall_hook(__FILE__, array('WorkflowSystem', 'uninstall'));

// Eklentiyi başlat
new WorkflowSystem();