<?php
/*
Plugin Name: Subdomain Language Switcher
Description: مدیریت زبان‌ها و ریدایرکت بین ساب‌دامین‌ها بر اساس انتخاب زبان کاربر
Version: 1.1
Author: ChatGPT & User
*/

if (!defined('ABSPATH')) exit;

class Subdomain_Language_Switcher {
    private $option_name = 'sls_languages';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('language_switcher', [$this, 'render_switcher']);
        add_action('init', [$this, 'handle_redirect']);
    }

    public function admin_menu() {
        add_menu_page('مدیریت زبان‌ها', 'زبان‌ها', 'manage_options', 'sls-language-switcher', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('sls_settings_group', $this->option_name);
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
                        if (row && !row.classList.contains('empty-row')) {
                            row.remove();
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function render_switcher() {
        $languages = get_option($this->option_name, []);
        $html = '<ul class="language-switcher">';
        foreach ($languages as $lang) {
            $flag = !empty($lang['flag']) ? '<img src="' . esc_url($lang['flag']) . '" alt="' . esc_attr($lang['name']) . '" style="width:20px;height:auto;margin-left:5px;" />' : '';
            $html .= '<li><a href="#" data-url="' . esc_url($lang['url']) . '" class="switch-lang">' . $flag . esc_html($lang['name']) . '</a></li>';
        }
        $html .= '</ul>';
        $html .= '<script>
            document.querySelectorAll(".switch-lang").forEach(link => {
                link.addEventListener("click", function(e) {
                    e.preventDefault();
                    const url = this.getAttribute("data-url");
                    document.cookie = "preferred_lang=" + encodeURIComponent(url) + "; path=/; max-age=" + (60*60*24*30);
                    window.location.href = url;
                });
            });
        </script>';
        return $html;
    }

    public function handle_redirect() {
        if (is_admin()) return;
        $current_url = home_url();
        $cookie_lang = isset($_COOKIE['preferred_lang']) ? $_COOKIE['preferred_lang'] : '';

        if (!$cookie_lang) return;

        $languages = get_option($this->option_name, []);
        $current_lang_url = ''; $cookie_match = false;

        foreach ($languages as $lang) {
            if (strpos($current_url, $lang['url']) === 0) {
                $current_lang_url = $lang['url'];
            }
            if ($lang['url'] === $cookie_lang) {
                $cookie_match = true;
            }
        }

        if ($cookie_lang !== $current_url && $cookie_match && $current_lang_url !== $cookie_lang) {
            wp_redirect($cookie_lang);
            exit;
        }
    }
}

new Subdomain_Language_Switcher();