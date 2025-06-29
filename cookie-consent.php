<?php
/*
Plugin Name: Cookie Consent by seojacky
Description: PageSpeed-optimized GDPR cookie consent banner with lazy loading, multilingual support, and automatic tracking script blocking. Zero render-blocking resources for maximum performance.
Version: 3.2
Author: seo_jacky
Author URI: https://t.me/big_jacky
Plugin URI: https://github.com/seojacky/cookie-consent
GitHub Plugin URI: https://github.com/seojacky/cookie-consent
Domain Path: /languages
*/

// Prohibition of direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Основной класс плагина
class CookieConsentBanner {
    
    private $version = '3.2';
    private $plugin_slug = 'cookie-consent-banner';
    private static $instance = null;
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Конструктор
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Инициализация плагина
     */
    public function init() {
        // Хуки для загрузки ресурсов
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Хук для отображения баннера
        add_action('wp_footer', [$this, 'displayBanner']);
        
        // Хуки для блокировки трекинг скриптов
        add_action('wp_enqueue_scripts', [$this, 'handleTrackingScripts'], 1);
        
        // AJAX хуки для обработки согласия
        add_action('wp_ajax_cookie_consent_action', [$this, 'handleAjaxConsent']);
        add_action('wp_ajax_nopriv_cookie_consent_action', [$this, 'handleAjaxConsent']);
        
        // Хук для очистки кеша при изменении настроек
        add_action('update_option_cookie_consent_options', [$this, 'clearCache']);
        
        // Добавляем meta теги для лучшей совместимости
        add_action('wp_head', [$this, 'addMetaTags']);
    }
    
    /**
     * Правильная загрузка ресурсов с отложенной загрузкой
     */
    public function enqueueAssets() {
        // Отладочная информация
        error_log('Cookie Consent: enqueueAssets called');
        
        // Проверяем, нужно ли показывать баннер
        if ($this->hasValidConsent()) {
            error_log('Cookie Consent: Valid consent exists, not loading');
            return;
        }
        
        error_log('Cookie Consent: No consent, preparing lazy loading');
        
        // НЕ загружаем CSS и JS сразу, только подготавливаем конфигурацию
        
        // ВАЖНО: используем wp_footer вместо wp_add_inline_script
        add_action('wp_footer', [$this, 'outputLazyScript'], 5);
    }
    
    /**
     * Вывод скрипта отложенной загрузки в футер
     */
    public function outputLazyScript() {
        error_log('Cookie Consent: outputLazyScript called');
        
        $options = $this->getCachedOptions();
        
        $config = [
            'cssUrl' => plugin_dir_url(__FILE__) . 'css/cookie-consent.css',
            'jsUrl' => plugin_dir_url(__FILE__) . 'js/cookie-consent.js',
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->plugin_slug . '_nonce'),
            'respectDNT' => $options['respect_dnt'] ?? false,
            'settings' => $this->getJSSettings()
        ];
        
        echo "\n<!-- Cookie Consent Lazy Loading Script -->\n";
        echo '<script>';
        echo 'console.log("Cookie Consent: Configuration loaded");';
        echo 'window.cookieConsent = ' . wp_json_encode($config) . ';';
        echo "\n" . $this->getLazyLoadScriptContent();
        echo '</script>';
        echo "\n<!-- End Cookie Consent Lazy Loading Script -->\n";
    }
    
    /**
     * Получение содержимого скрипта отложенной загрузки из файла
     */
    private function getLazyLoadScriptContent() {
        // Кешируем содержимое файла
        $cache_key = $this->plugin_slug . '_lazy_script_content_' . $this->version;
        $script_content = wp_cache_get($cache_key, $this->plugin_slug);
        
        if ($script_content === false) {
            $script_path = plugin_dir_path(__FILE__) . 'js/cookie-consent-lazy.js';
            
            if (file_exists($script_path)) {
                $script_content = file_get_contents($script_path);
                error_log('Cookie Consent: Loaded lazy script from file');
            } else {
                // Fallback если файл не найден
                $script_content = $this->getFallbackLazyScript();
                error_log('Cookie Consent: cookie-consent-lazy.js file not found, using fallback');
            }
            
            wp_cache_set($cache_key, $script_content, $this->plugin_slug, DAY_IN_SECONDS);
        }
        
        return $script_content;
    }
    
    /**
     * Fallback скрипт если основной файл не найден
     */
    private function getFallbackLazyScript() {
        return '(function(){"use strict";function a(a){for(var b=a+"=",c=document.cookie.split(";"),d=0;d<c.length;d++){for(var e=c[d];" "===e.charAt(0);)e=e.substring(1,e.length);if(0===e.indexOf(b))return decodeURIComponent(e.substring(b.length,e.length))}return null}if(a("cookie_consent"))return;if(window.cookieConsent&&window.cookieConsent.respectDNT&&("1"===navigator.doNotTrack||"1"===window.doNotTrack))return;var b=!1,c;function d(a){a&&a.type&&console.log("Cookie Consent: Loading triggered by",a.type),b||(b=!0,clearTimeout(c),window.removeEventListener("scroll",d,{passive:!0}),window.removeEventListener("touchstart",d,{passive:!0}),document.removeEventListener("mouseenter",d),document.removeEventListener("click",d),document.removeEventListener("DOMContentLoaded",e),setTimeout(function(){if(!document.getElementById("cookie-consent-banner-style")){var a=document.createElement("link");a.id="cookie-consent-banner-style",a.rel="stylesheet",a.href=window.cookieConsent&&window.cookieConsent.cssUrl||"",a.media="all",document.head.appendChild(a)}var b=document.createElement("script");b.id="cookie-consent-main-script",b.src=window.cookieConsent&&window.cookieConsent.jsUrl||"",b.async=!0,b.onload=function(){console.log("Cookie Consent: Banner loaded")},document.head.appendChild(b)},100))}function e(){c=setTimeout(d,3e3)}navigator.userAgent.indexOf("bot")>-1||navigator.userAgent.indexOf("Bot")>-1?d():(window.addEventListener("scroll",d,{passive:!0}),window.addEventListener("touchstart",d,{passive:!0}),document.addEventListener("mouseenter",d,{passive:!0}),document.addEventListener("click",d,{passive:!0}),document.addEventListener("DOMContentLoaded",e,{passive:!0}))})();';
    }
    
    /**
     * Получение критического CSS для inline вставки
     */
    private function getCriticalCSS() {
        return '
        #cookie-consent-banner{position:fixed;bottom:0;left:0;width:100%;background-color:#f8f9fa;border-top:1px solid #dee2e6;padding:15px;z-index:1000;font-family:Arial,sans-serif;transform:translateY(100%);transition:transform 0.3s ease-in-out}
        .cookie-consent-content{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
        .cookie-consent-content p{flex:1;margin:0 20px;font-size:14px;color:#333}
        .accept-button,.deny-button{border:none;padding:10px 20px;border-radius:5px;cursor:pointer;font-size:14px;margin-left:10px}
        .accept-button{background-color:#28a745;color:white}
        .deny-button{background-color:#dc3545;color:white}
        ';
    }
    
    /**
     * Настройки для передачи в JavaScript
     */
    private function getJSSettings() {
        $options = $this->getCachedOptions();
        
        return [
            'position' => $options['position'] ?? 'bottom',
            'respectDNT' => $options['respect_dnt'] ?? false,
            'autoHideDelay' => $options['auto_hide_delay'] ?? 0,
            'animation' => $options['animation'] ?? 'slide'
        ];
    }
    
    /**
     * Добавление defer атрибута к скрипту
     */
    public function addDeferAttribute($tag, $handle, $src) {
        if ($this->plugin_slug . '-script' === $handle) {
            $tag = str_replace(' src', ' defer="defer" src', $tag);
        }
        return $tag;
    }
    
    /**
     * Блокировка трекинг скриптов при отказе от cookies
     */
    public function handleTrackingScripts() {
        if (!$this->isTrackingAllowed()) {
            // Блокируем Google Analytics
            add_filter('wp_enqueue_scripts', [$this, 'blockGoogleAnalytics'], 99);
            
            // Блокируем Facebook Pixel
            add_filter('wp_enqueue_scripts', [$this, 'blockFacebookPixel'], 99);
            
            // Блокируем Яндекс.Метрику
            add_filter('wp_enqueue_scripts', [$this, 'blockYandexMetrica'], 99);
            
            // Общий фильтр для блокировки трекинг скриптов
            add_filter('script_loader_src', [$this, 'blockTrackingScripts'], 10, 2);
        }
    }
    
    /**
     * Блокировка Google Analytics
     */
    public function blockGoogleAnalytics() {
        wp_dequeue_script('google-analytics');
        wp_dequeue_script('gtag');
        wp_dequeue_script('ga');
        
        // Блокируем по паттернам URL
        add_filter('script_loader_src', function($src) {
            if (strpos($src, 'googletagmanager.com') !== false || 
                strpos($src, 'google-analytics.com') !== false) {
                return '';
            }
            return $src;
        });
    }
    
    /**
     * Блокировка Facebook Pixel
     */
    public function blockFacebookPixel() {
        wp_dequeue_script('facebook-pixel');
        wp_dequeue_script('fbq');
        
        add_filter('script_loader_src', function($src) {
            if (strpos($src, 'connect.facebook.net') !== false) {
                return '';
            }
            return $src;
        });
    }
    
    /**
     * Блокировка Яндекс.Метрики
     */
    public function blockYandexMetrica() {
        wp_dequeue_script('yandex-metrica');
        wp_dequeue_script('ym');
        
        add_filter('script_loader_src', function($src) {
            if (strpos($src, 'mc.yandex.ru') !== false) {
                return '';
            }
            return $src;
        });
    }
    
    /**
     * Общая блокировка трекинг скриптов
     */
    public function blockTrackingScripts($src, $handle) {
        $blocked_patterns = [
            'googletagmanager.com',
            'google-analytics.com',
            'doubleclick.net',
            'connect.facebook.net',
            'mc.yandex.ru',
            'hotjar.com',
            'crazyegg.com'
        ];
        
        foreach ($blocked_patterns as $pattern) {
            if (strpos($src, $pattern) !== false) {
                error_log("Cookie Consent: Blocked script {$handle} with src {$src}");
                return '';
            }
        }
        
        return $src;
    }
    
    /**
     * Обработка AJAX запросов согласия
     */
    public function handleAjaxConsent() {
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', $this->plugin_slug . '_nonce')) {
            wp_die('Security check failed', 'Error', ['response' => 403]);
        }
        
        $action = sanitize_text_field($_POST['consent_action'] ?? '');
        $categories = array_map('sanitize_text_field', $_POST['categories'] ?? []);
        
        if ($action === 'accept') {
            $this->setConsentCookie('accepted', $categories);
        } elseif ($action === 'deny') {
            $this->setConsentCookie('denied', []);
        }
        
        wp_send_json_success([
            'message' => 'Consent updated',
            'action' => $action,
            'categories' => $categories
        ]);
    }
    
    /**
     * Установка cookie согласия
     */
    private function setConsentCookie($status, $categories = []) {
        $data = [
            'status' => $status,
            'categories' => $categories,
            'timestamp' => time(),
            'version' => $this->version
        ];
        
        $expires = ($status === 'accepted') ? time() + (365 * 24 * 60 * 60) : time() + (30 * 24 * 60 * 60);
        
        setcookie(
            'cookie_consent',
            wp_json_encode($data),
            $expires,
            '/',
            '',
            is_ssl(),
            true // HttpOnly
        );
    }
    
    /**
     * Проверка наличия действительного согласия
     */
    private function hasValidConsent() {
        return isset($_COOKIE['cookie_consent']) && !empty($_COOKIE['cookie_consent']);
    }
    
    /**
     * Проверка разрешения трекинга
     */
    private function isTrackingAllowed() {
        if (!isset($_COOKIE['cookie_consent'])) {
            return false;
        }
        
        $consent_data = json_decode(stripslashes($_COOKIE['cookie_consent']), true);
        
        if (!$consent_data || !isset($consent_data['status'])) {
            return false;
        }
        
        return $consent_data['status'] === 'accepted';
    }
    
    /**
     * Отображение баннера - НЕ показываем HTML сразу
     */
    public function displayBanner() {
        // Не показываем в админке
        if (is_admin()) {
            return;
        }
        
        // Не показываем если согласие уже дано
        if ($this->hasValidConsent()) {
            return;
        }
        
        // Проверяем Do Not Track
        if ($this->shouldRespectDNT()) {
            return;
        }
        
        // ИЗМЕНЕНИЕ: НЕ выводим HTML баннера сразу
        // Вместо этого добавляем данные для отложенной загрузки
        $options = $this->getCachedOptions();
        $banner_config = [
            'privacy_url' => $this->getPrivacyPolicyURL($options),
            'consent_text' => $this->getLocalizedText($options['consent_text'] ?? '', $this->getPrivacyPolicyURL($options)),
            'accept_text' => $this->getLocalizedText($options['button_text'] ?? 'Accept'),
            'deny_text' => $this->getLocalizedText($options['deny_text'] ?? 'Deny'),
            'show_deny_button' => $options['show_deny_button'] ?? true,
            'position' => $options['position'] ?? 'bottom',
            'theme' => $options['theme'] ?? 'light',
            'animation' => $options['animation'] ?? 'slide'
        ];
        
        // Добавляем конфигурацию баннера в window для использования JS
        echo '<script>window.cookieConsentBannerConfig = ' . wp_json_encode($banner_config) . ';</script>';
    }
    
    /**
     * Получение HTML баннера для AJAX
     */
    public function getBannerHTMLForAjax() {
        $options = $this->getCachedOptions();
        return $this->getBannerHTML($options);
    }
    
    /**
     * Генерация HTML баннера
     */
    private function getBannerHTML($options) {
        $privacy_url = $this->getPrivacyPolicyURL($options);
        $consent_text = $this->getLocalizedText($options['consent_text'] ?? '', $privacy_url);
        $accept_text = $this->getLocalizedText($options['button_text'] ?? 'Accept');
        $deny_text = $this->getLocalizedText($options['deny_text'] ?? 'Deny');
        
        ob_start();
        ?>
        <div id="cookie-consent-banner" role="dialog" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-desc">
            <div class="cookie-consent-content">
                <h2 id="cookie-consent-title" class="sr-only"><?php esc_html_e('Cookie Consent', $this->plugin_slug); ?></h2>
                <p id="cookie-consent-desc"><?php echo wp_kses_post($consent_text); ?></p>
                <div class="cookie-consent-buttons">
                    <button id="cookie-consent-accept" class="accept-button" type="button" aria-describedby="cookie-consent-desc">
                        <?php echo esc_html($accept_text); ?>
                    </button>
                    <?php if ($options['show_deny_button'] ?? true): ?>
                    <button id="cookie-consent-deny" class="deny-button" type="button" aria-describedby="cookie-consent-desc">
                        <?php echo esc_html($deny_text); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <style>
        .sr-only {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0,0,0,0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Получение URL политики конфиденциальности
     */
    private function getPrivacyPolicyURL($options) {
        $privacy_mode = $options['privacy_mode'] ?? 'auto';
        
        if ($privacy_mode === 'manual' && !empty($options['manual_link'])) {
            return esc_url($options['manual_link']);
        }
        
        return esc_url(get_privacy_policy_url()) ?: '#';
    }
    
    /**
     * Получение локализованного текста
     */
    private function getLocalizedText($text, $privacy_url = '') {
        if (empty($text)) {
            return '';
        }
        
        // Заменяем плейсхолдер URL
        $text = str_replace('{privacy_url}', $privacy_url, $text);
        
        // Простая мультиязычность
        $locale = get_locale();
        $lang_code = substr($locale, 0, 2);
        
        $pattern = '/{:' . preg_quote($lang_code, '/') . '}(.*?){:}/s';
        if (preg_match($pattern, $text, $matches)) {
            return $matches[1];
        }
        
        // Fallback - удаляем все языковые теги и возвращаем основной текст
        $text = preg_replace('/{:[a-z]{2}}.*?{:}/s', '', $text);
        
        return $text ?: __('We use cookies to improve your experience.', $this->plugin_slug);
    }
    
    /**
     * Проверка необходимости уважать Do Not Track
     */
    private function shouldRespectDNT() {
        $options = $this->getCachedOptions();
        
        if (empty($options['respect_dnt'])) {
            return false;
        }
        
        return isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] === '1';
    }
    
    /**
     * Получение кешированных настроек
     */
    private function getCachedOptions() {
        $cache_key = $this->plugin_slug . '_options_' . get_locale();
        $options = wp_cache_get($cache_key, $this->plugin_slug);
        
        if ($options === false) {
            $options = get_option('cookie_consent_options', $this->getDefaultOptions());
            wp_cache_set($cache_key, $options, $this->plugin_slug, HOUR_IN_SECONDS);
        }
        
        return $options;
    }
    
    /**
     * Настройки по умолчанию
     */
    private function getDefaultOptions() {
        return [
            'privacy_mode' => 'auto',
            'manual_link' => '',
            'consent_text' => __('We use cookies to improve your experience. By continuing to use this site, you agree to our cookie policy.', $this->plugin_slug),
            'button_text' => __('Accept', $this->plugin_slug),
            'deny_text' => __('Deny', $this->plugin_slug),
            'show_deny_button' => true,
            'respect_dnt' => false,
            'position' => 'bottom',
            'animation' => 'slide'
        ];
    }
    
    /**
     * Очистка кеша
     */
    public function clearCache() {
        $locales = ['en_US', 'ru_RU', 'uk_UA']; // Добавьте нужные локали
        
        foreach ($locales as $locale) {
            wp_cache_delete($this->plugin_slug . '_options_' . $locale, $this->plugin_slug);
        }
        
        // Очищаем кеш скрипта lazy loading
        wp_cache_delete($this->plugin_slug . '_lazy_script_content_' . $this->version, $this->plugin_slug);
    }
    
    /**
     * Добавление meta тегов
     */
    public function addMetaTags() {
        if (!$this->hasValidConsent()) {
            echo '<meta name="cookie-consent-required" content="true">' . "\n";
        }
    }
}

// Подключаем файл настроек
require_once plugin_dir_path(__FILE__) . 'settings.php';

// Инициализация плагина
add_action('plugins_loaded', function() {
    CookieConsentBanner::getInstance();
});

// Инициализация настроек - ИСПРАВЛЕНИЕ
add_action('init', function() {
    if (is_admin()) {
        new CookieConsentSettings();
    }
});

// Хук активации плагина
register_activation_hook(__FILE__, function() {
    // Создаем таблицу для логов согласия (опционально)
    flush_rewrite_rules();
});

// Хук деактивации плагина
register_deactivation_hook(__FILE__, function() {
    // Очищаем кеш
    wp_cache_flush();
    flush_rewrite_rules();
});

// Добавляем ссылку "Settings" в список плагинов
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=cookie-consent-settings') . '">' . __('Settings', 'cookie-consent-banner') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
