<?php
/*
Plugin Name: Subdomain Language Switcher
Description: مدیریت زبان‌ها و ریدایرکت بین ساب‌دامین‌ها بر اساس انتخاب زبان کاربر
Version: 1.8
Author: Vahid Bagheri
*/

if (!defined('ABSPATH')) exit;

class Subdomain_Language_Switcher {
    private $option_name = 'sls_languages';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('language_switcher', [$this, 'render_switcher']);
        add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
        add_action('template_redirect', [$this, 'handle_redirect']);
    }

    public function admin_styles($hook) {
        if ($hook !== 'toplevel_page_sls-language-switcher') return;
        wp_add_inline_style('wp-admin', '
            #language-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            #language-table th, #language-table td { padding: 10px; border: 1px solid #ccd0d4; text-align: center; }
            #language-table input[type="text"], #language-table input[type="url"] { width: 100%; padding: 6px 8px; border: 1px solid #ccd0d4; border-radius: 6px; box-sizing: border-box; }
            #language-table th { background-color: #f1f1f1; font-weight: bold; }
            .wrap h1 { margin-bottom: 20px; font-size: 24px; }
            .button.add-row { background-color: #46b450; color: white; border: none; }
            .button.add-row:hover { background-color: #3da141; }
            .button.remove-row { background-color: #dc3232; color: white; border: none; }
            .button.remove-row:hover { background-color: #b52727; }
            .form-table td button { border-radius: 6px; }
            .form-table td input { text-align: center; }
            .form-table td img { max-height: 20px; }
        ');
    }

    public function admin_menu() {
        add_menu_page('مدیریت زبان‌ها', 'زبان‌ها', 'manage_options', 'sls-language-switcher', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('sls_settings_group', $this->option_name, [$this, 'sanitize_languages']);
    }

    public function sanitize_languages($input) {
        $cleaned = [];
        foreach ($input as $lang) {
            if (!empty($lang['name']) || !empty($lang['url']) || !empty($lang['flag'])) {
                $cleaned[] = [
                    'name' => sanitize_text_field($lang['name']),
                    'url'  => esc_url_raw($lang['url']),
                    'flag' => esc_url_raw($lang['flag'] ?? ''),
                ];
            }
        }
        return $cleaned;
    }

    public function settings_page() {
        $languages = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>مدیریت زبان‌ها</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sls_settings_group'); ?>
                <table class="form-table" id="language-table">
                    <thead>
                        <tr><th>نام زبان</th><th>لینک</th><th>آدرس پرچم</th><th>عملیات</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $index => $lang): ?>
                            <tr>
                                <td><input name="<?= $this->option_name ?>[<?= $index ?>][name]" value="<?= esc_attr($lang['name']) ?>" /></td>
                                <td><input name="<?= $this->option_name ?>[<?= $index ?>][url]" value="<?= esc_attr($lang['url']) ?>" /></td>
                                <td><input name="<?= $this->option_name ?>[<?= $index ?>][flag]" value="<?= esc_attr($lang['flag'] ?? '') ?>" /></td>
                                <td><button class="remove-row button">حذف</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="empty-row screen-reader-text">
                            <td><input name="<?= $this->option_name ?>[][name]" /></td>
                            <td><input name="<?= $this->option_name ?>[][url]" /></td>
                            <td><input name="<?= $this->option_name ?>[][flag]" /></td>
                            <td><button class="remove-row button">حذف</button></td>
                        </tr>
                    </tbody>
                </table>
                <p><button type="button" class="button add-row">افزودن زبان</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.add-row').addEventListener('click', function () {
                    var table = document.getElementById('language-table').querySelector('tbody');
                    var newRow = document.querySelector('.empty-row').cloneNode(true);
                    newRow.classList.remove('empty-row', 'screen-reader-text');
                    table.appendChild(newRow);
                });
                document.addEventListener('click', function (e) {
                    if (e.target.classList.contains('remove-row')) {
                        e.preventDefault();
                        var row = e.target.closest('tr');
                        if (row && !row.classList.contains('empty-row')) row.remove();
                    }
                });
            });
        </script>
        <?php
    }

    public function render_switcher() {
        $languages = get_option($this->option_name, []);
        if (empty($languages)) return '';

        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

        ob_start();
        ?>
        <style>
            .custom-language-switcher { position: relative; display: inline-block; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .custom-language-switcher { position: absolute; top: -11px; right: 49px; }
            @media (min-width: 1200px) {
                .custom-language-switcher { position: relative !important; top: 7px; right: 23px; }
                .custom-language-switcher::after { content: "\25BE"; position: absolute; top: 25% !important; right: 23px; transform: translateY(-50%); font-size: 14px; color: #777; pointer-events: none; }
            }
            .custom-language-switcher select { padding: 0px 20px; border-radius: 12px; background: unset; color: #333; font-size: 15px; appearance: none; -webkit-appearance: none; -moz-appearance: none; transition: all 0.3s ease; padding-right: 40px; }
            .custom-language-switcher select:hover { }
            #language-switcher-select { text-align: center; }
            .custom-language-switcher::after { content: "\25BE"; position: absolute; top: 45%; right: 23px; transform: translateY(-50%); font-size: 14px; color: #777; pointer-events: none; }
            @media (max-width: 480px) { .custom-language-switcher select { width: 100%; } }
        </style>
        <div class="custom-language-switcher">
            <select id="language-switcher-select">
                <option value="">language 🌐</option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?= esc_url($lang['url']) ?>" <?= $current_url === $lang['url'] ? 'selected' : '' ?>>
                        <?= esc_html($lang['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const select = document.getElementById("language-switcher-select");
                select.addEventListener("change", function () {
                    const url = this.value;
                    const currentUrl = window.location.protocol + "//" + window.location.host;
                    if (url && url !== currentUrl) {
                        // پاک کردن کوکی قبلی
                        document.cookie = "preferred_lang=; path=/; domain=.trendyflight.com.tr; max-age=0";
                        // تنظیم کوکی جدید برای دامنه اصلی و ساب‌دامین‌ها
                        document.cookie = "preferred_lang=" + encodeURIComponent(url) + "; path=/; domain=.trendyflight.com.tr; max-age=" + (60*60*24*30);
                        window.location.href = url;
                    }
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_redirect() {
        if (is_admin() || wp_doing_ajax()) return;

        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $cookie_lang = isset($_COOKIE['preferred_lang']) ? $_COOKIE['preferred_lang'] : '';
        $languages = get_option($this->option_name, []);

        error_log("Current URL: $current_url | Cookie: $cookie_lang");

        if (!$languages) return;

        $valid_urls = array_column($languages, 'url');

        // اگه URL فعلی توی لیست زبان‌ها هست، ریدایرکت نکن
        if (in_array($current_url, $valid_urls)) {
            error_log("URL is valid: $current_url - No redirect needed");
            return;
        }

        // اگه کوکی معتبر داریم و URL فعلی توی لیست نیست، ریدایرکت کن
        if ($cookie_lang && in_array($cookie_lang, $valid_urls)) {
            error_log("Redirecting from $current_url to $cookie_lang");
            wp_redirect($cookie_lang);
            exit;
        }

        error_log("No valid redirect condition met for $current_url");
    }
}

new Subdomain_Language_Switcher();