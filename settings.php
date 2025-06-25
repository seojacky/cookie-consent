<?php
// Prohibition of direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для управления настройками Cookie Consent
 */
class CookieConsentSettings {
    
    private $plugin_slug = 'cookie-consent-banner';
    private $options_group = 'cookie_consent_options';
    private $page_slug = 'cookie-consent-settings';
    
    public function __construct() {
        $this->init();
    }
    
    /**
     * Инициализация хуков
     */
    public function init() {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_ajax_cookie_consent_preview', [$this, 'handlePreview']);
    }
    
    /**
     * Добавление страницы настроек в админ-панель
     */
    public function addSettingsPage() {
        add_options_page(
            __('Cookie Consent Settings', $this->plugin_slug),
            __('Cookie Consent', $this->plugin_slug),
            'manage_options',
            $this->page_slug,
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Регистрация настроек и полей
     */
    public function registerSettings() {
        // Регистрируем группу настроек
        register_setting(
            $this->options_group,
            $this->options_group,
            [
                'sanitize_callback' => [$this, 'sanitizeOptions'],
                'default' => $this->getDefaultOptions()
            ]
        );
        
        // Основные настройки
        add_settings_section(
            'cookie_consent_main_section',
            __('Main Settings', $this->plugin_slug),
            [$this, 'renderMainSectionCallback'],
            $this->page_slug
        );
        
        // Дизайн и позиционирование
        add_settings_section(
            'cookie_consent_design_section',
            __('Design & Position', $this->plugin_slug),
            [$this, 'renderDesignSectionCallback'],
            $this->page_slug
        );
        
        // GDPR настройки
        add_settings_section(
            'cookie_consent_gdpr_section',
            __('GDPR Compliance', $this->plugin_slug),
            [$this, 'renderGdprSectionCallback'],
            $this->page_slug
        );
        
        // Добавляем поля настроек
        $this->addSettingsFields();
    }
    
    /**
     * Добавление полей настроек
     */
    private function addSettingsFields() {
        $fields = [
            // Основные настройки
            'privacy_mode' => [
                'section' => 'cookie_consent_main_section',
                'title' => __('Privacy Policy Link Mode', $this->plugin_slug),
                'callback' => 'renderSelectField',
                'args' => [
                    'field' => 'privacy_mode',
                    'options' => [
                        'auto' => __('Automatic (from WordPress settings)', $this->plugin_slug),
                        'manual' => __('Manual URL', $this->plugin_slug)
                    ],
                    'description' => __('Choose how to specify the privacy policy link.', $this->plugin_slug)
                ]
            ],
            'manual_link' => [
                'section' => 'cookie_consent_main_section',
                'title' => __('Manual Privacy Policy URL', $this->plugin_slug),
                'callback' => 'renderUrlField',
                'args' => [
                    'field' => 'manual_link',
                    'description' => __('Enter URL manually if "Manual URL" mode is selected.', $this->plugin_slug),
                    'placeholder' => 'https://example.com/privacy-policy'
                ]
            ],
            'consent_text' => [
                'section' => 'cookie_consent_main_section',
                'title' => __('Cookie Consent Text', $this->plugin_slug),
                'callback' => 'renderTextareaField',
                'args' => [
                    'field' => 'consent_text',
                    'rows' => 5,
                    'description' => __('Enter banner text. Use format {:en}English{:}{:ru}Russian{:} for multilingual. Use {privacy_url} placeholder for privacy policy link.', $this->plugin_slug)
                ]
            ],
            'button_text' => [
                'section' => 'cookie_consent_main_section',
                'title' => __('Accept Button Text', $this->plugin_slug),
                'callback' => 'renderTextareaField',
                'args' => [
                    'field' => 'button_text',
                    'rows' => 2,
                    'description' => __('Text for "Accept" button. Use {:en}Accept{:}{:ru}Принять{:} for multilingual.', $this->plugin_slug)
                ]
            ],
            'deny_text' => [
                'section' => 'cookie_consent_main_section',
                'title' => __('Deny Button Text', $this->plugin_slug),
                'callback' => 'renderTextareaField',
                'args' => [
                    'field' => 'deny_text',
                    'rows' => 2,
                    'description' => __('Text for "Deny" button. Use {:en}Deny{:}{:ru}Отказаться{:} for multilingual.', $this->plugin_slug)
                ]
            ],
            
            // Дизайн настройки
            'position' => [
                'section' => 'cookie_consent_design_section',
                'title' => __('Banner Position', $this->plugin_slug),
                'callback' => 'renderSelectField',
                'args' => [
                    'field' => 'position',
                    'options' => [
                        'bottom' => __('Bottom', $this->plugin_slug),
                        'top' => __('Top', $this->plugin_slug),
                        'modal' => __('Modal (Center)', $this->plugin_slug)
                    ],
                    'description' => __('Choose where to display the banner.', $this->plugin_slug)
                ]
            ],
            'theme' => [
                'section' => 'cookie_consent_design_section',
                'title' => __('Theme', $this->plugin_slug),
                'callback' => 'renderSelectField',
                'args' => [
                    'field' => 'theme',
                    'options' => [
                        'light' => __('Light', $this->plugin_slug),
                        'dark' => __('Dark', $this->plugin_slug),
                        'custom' => __('Custom (use CSS)', $this->plugin_slug)
                    ],
                    'description' => __('Choose banner theme.', $this->plugin_slug)
                ]
            ],
            'animation' => [
                'section' => 'cookie_consent_design_section',
                'title' => __('Animation', $this->plugin_slug),
                'callback' => 'renderSelectField',
                'args' => [
                    'field' => 'animation',
                    'options' => [
                        'slide' => __('Slide', $this->plugin_slug),
                        'fade' => __('Fade', $this->plugin_slug),
                        'none' => __('None', $this->plugin_slug)
                    ],
                    'description' => __('Choose banner animation.', $this->plugin_slug)
                ]
            ],
            
            // GDPR настройки
            'show_deny_button' => [
                'section' => 'cookie_consent_gdpr_section',
                'title' => __('Show Deny Button', $this->plugin_slug),
                'callback' => 'renderCheckboxField',
                'args' => [
                    'field' => 'show_deny_button',
                    'description' => __('Show "Deny" button for GDPR compliance.', $this->plugin_slug)
                ]
            ],
            'respect_dnt' => [
                'section' => 'cookie_consent_gdpr_section',
                'title' => __('Respect Do Not Track', $this->plugin_slug),
                'callback' => 'renderCheckboxField',
                'args' => [
                    'field' => 'respect_dnt',
                    'description' => __('Hide banner if user has "Do Not Track" enabled.', $this->plugin_slug)
                ]
            ],
            'cookie_expiry_accept' => [
                'section' => 'cookie_consent_gdpr_section',
                'title' => __('Accept Cookie Expiry (days)', $this->plugin_slug),
                'callback' => 'renderNumberField',
                'args' => [
                    'field' => 'cookie_expiry_accept',
                    'min' => 1,
                    'max' => 3650,
                    'description' => __('How long to remember "accept" choice (1-3650 days).', $this->plugin_slug)
                ]
            ],
            'cookie_expiry_deny' => [
                'section' => 'cookie_consent_gdpr_section',
                'title' => __('Deny Cookie Expiry (days)', $this->plugin_slug),
                'callback' => 'renderNumberField',
                'args' => [
                    'field' => 'cookie_expiry_deny',
                    'min' => 1,
                    'max' => 365,
                    'description' => __('How long to remember "deny" choice (1-365 days).', $this->plugin_slug)
                ]
            ]
        ];
        
        foreach ($fields as $field_id => $field) {
            add_settings_field(
                'cookie_consent_' . $field_id,
                $field['title'],
                [$this, $field['callback']],
                $this->page_slug,
                $field['section'],
                $field['args']
            );
        }
    }
    
    /**
     * Валидация и санитизация настроек
     */
    public function sanitizeOptions($input) {
        if (!is_array($input)) {
            return $this->getDefaultOptions();
        }
        
        $output = [];
        $errors = [];
        
        // Санитизация privacy_mode
        $output['privacy_mode'] = isset($input['privacy_mode']) && 
                                 in_array($input['privacy_mode'], ['auto', 'manual']) ? 
                                 $input['privacy_mode'] : 'auto';
        
        // Валидация manual_link
        if (!empty($input['manual_link'])) {
            $url = esc_url_raw($input['manual_link']);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $output['manual_link'] = $url;
            } else {
                $errors[] = __('Invalid privacy policy URL provided.', $this->plugin_slug);
                $output['manual_link'] = '';
            }
        } else {
            $output['manual_link'] = '';
        }
        
        // Санитизация текстовых полей
        $text_fields = ['consent_text', 'button_text', 'deny_text'];
        foreach ($text_fields as $field) {
            $output[$field] = isset($input[$field]) ? 
                            wp_kses_post(trim($input[$field])) : 
                            $this->getDefaultOptions()[$field];
        }
        
        // Санитизация селектов
        $select_fields = [
            'position' => ['bottom', 'top', 'modal'],
            'theme' => ['light', 'dark', 'custom'],
            'animation' => ['slide', 'fade', 'none']
        ];
        
        foreach ($select_fields as $field => $allowed_values) {
            $output[$field] = isset($input[$field]) && 
                            in_array($input[$field], $allowed_values) ? 
                            $input[$field] : 
                            $this->getDefaultOptions()[$field];
        }
        
        // Санитизация чекбоксов
        $checkbox_fields = ['show_deny_button', 'respect_dnt'];
        foreach ($checkbox_fields as $field) {
            $output[$field] = !empty($input[$field]);
        }
        
        // Санитизация числовых полей
        $output['cookie_expiry_accept'] = isset($input['cookie_expiry_accept']) ? 
                                        max(1, min(3650, intval($input['cookie_expiry_accept']))) : 365;
        
        $output['cookie_expiry_deny'] = isset($input['cookie_expiry_deny']) ? 
                                      max(1, min(365, intval($input['cookie_expiry_deny']))) : 30;
        
        // Добавляем ошибки
        foreach ($errors as $error) {
            add_settings_error($this->options_group, 'validation_error', $error, 'error');
        }
        
        // Очищаем кеш при сохранении
        $this->clearOptionsCache();
        
        return $output;
    }
    
    /**
     * Очистка кеша настроек
     */
    private function clearOptionsCache() {
        wp_cache_delete('cookie_consent_options_' . get_locale(), 'cookie-consent-banner');
    }
    
    /**
     * Настройки по умолчанию
     */
    private function getDefaultOptions() {
        return [
            'privacy_mode' => 'auto',
            'manual_link' => '',
            'consent_text' => __('We use cookies to improve your experience and analyze site usage. By continuing to use this site, you agree to our cookie policy in accordance with our <a href="{privacy_url}" target="_blank">Privacy Policy</a>.', $this->plugin_slug),
            'button_text' => __('Accept', $this->plugin_slug),
            'deny_text' => __('Deny', $this->plugin_slug),
            'position' => 'bottom',
            'theme' => 'light',
            'animation' => 'slide',
            'show_deny_button' => true,
            'respect_dnt' => false,
            'cookie_expiry_accept' => 365,
            'cookie_expiry_deny' => 30
        ];
    }
    
    /**
     * Загрузка ресурсов для админ-панели
     */
    public function enqueueAdminAssets($hook) {
        if ($hook !== 'settings_page_' . $this->page_slug) {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_slug . '-admin',
            plugin_dir_url(__FILE__) . 'js/admin.js',
            ['jquery'],
            '3.1',
            true
        );
        
        wp_enqueue_style(
            $this->plugin_slug . '-admin',
            plugin_dir_url(__FILE__) . 'css/admin.css',
            [],
            '3.1'
        );
        
        wp_localize_script($this->plugin_slug . '-admin', 'cookieConsentAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->plugin_slug . '_admin_nonce'),
            'strings' => [
                'preview' => __('Preview', $this->plugin_slug),
                'loading' => __('Loading...', $this->plugin_slug)
            ]
        ]);
    }
    
    /**
     * Рендеринг страницы настроек
     */
    public function renderSettingsPage() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Проверяем, была ли форма отправлена
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                $this->options_group,
                'settings_updated',
                __('Settings saved successfully!', $this->plugin_slug),
                'success'
            );
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors($this->options_group); ?>
            
            <div class="cookie-consent-admin-wrapper">
                <div class="cookie-consent-settings">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields($this->options_group);
                        do_settings_sections($this->page_slug);
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="cookie-consent-sidebar">
                    <div class="cookie-consent-preview-box">
                        <h3><?php _e('Preview', $this->plugin_slug); ?></h3>
                        <button type="button" id="cookie-consent-preview-btn" class="button button-secondary">
                            <?php _e('Show Preview', $this->plugin_slug); ?>
                        </button>
                        <div id="cookie-consent-preview-area"></div>
                    </div>
                    
                    <div class="cookie-consent-help-box">
                        <h3><?php _e('Help & Documentation', $this->plugin_slug); ?></h3>
                        <ul>
                            <li><a href="#" target="_blank"><?php _e('Plugin Documentation', $this->plugin_slug); ?></a></li>
                            <li><a href="#" target="_blank"><?php _e('GDPR Compliance Guide', $this->plugin_slug); ?></a></li>
                            <li><a href="#" target="_blank"><?php _e('Support Forum', $this->plugin_slug); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .cookie-consent-admin-wrapper {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .cookie-consent-settings {
            flex: 2;
        }
        .cookie-consent-sidebar {
            flex: 1;
            max-width: 300px;
        }
        .cookie-consent-preview-box,
        .cookie-consent-help-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 15px;
            margin-bottom: 20px;
        }
        .cookie-consent-preview-box h3,
        .cookie-consent-help-box h3 {
            margin-top: 0;
        }
        #cookie-consent-preview-area {
            margin-top: 15px;
            min-height: 100px;
            border: 1px dashed #ccd0d4;
            padding: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Колбэки для секций
     */
    public function renderMainSectionCallback() {
        echo '<p>' . __('Configure the main cookie consent banner settings.', $this->plugin_slug) . '</p>';
    }
    
    public function renderDesignSectionCallback() {
        echo '<p>' . __('Customize the appearance and position of the banner.', $this->plugin_slug) . '</p>';
    }
    
    public function renderGdprSectionCallback() {
        echo '<p>' . __('GDPR compliance settings for cookie consent.', $this->plugin_slug) . '</p>';
    }
    
    /**
     * Рендеринг полей форм
     */
    public function renderSelectField($args) {
        $options = get_option($this->options_group, $this->getDefaultOptions());
        $field = $args['field'];
        $current_value = $options[$field] ?? '';
        
        ?>
        <select name="<?php echo esc_attr($this->options_group . '[' . $field . ']'); ?>" id="<?php echo esc_attr($field); ?>">
            <?php foreach ($args['options'] as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_value, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function renderTextareaField($args) {
        $options = get_option($this->options_group, $this->getDefaultOptions());
        $field = $args['field'];
        $current_value = $options[$field] ?? '';
        $rows = $args['rows'] ?? 5;
        
        ?>
        <textarea 
            name="<?php echo esc_attr($this->options_group . '[' . $field . ']'); ?>" 
            id="<?php echo esc_attr($field); ?>"
            rows="<?php echo esc_attr($rows); ?>" 
            cols="50" 
            class="large-text code"
        ><?php echo esc_textarea($current_value); ?></textarea>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php endif;
    }
    
    public function renderUrlField($args) {
        $options = get_option($this->options_group, $this->getDefaultOptions());
        $field = $args['field'];
        $current_value = $options[$field] ?? '';
        $placeholder = $args['placeholder'] ?? '';
        
        ?>
        <input 
            type="url" 
            name="<?php echo esc_attr($this->options_group . '[' . $field . ']'); ?>" 
            id="<?php echo esc_attr($field); ?>"
            value="<?php echo esc_attr($current_value); ?>" 
            class="regular-text"
            placeholder="<?php echo esc_attr($placeholder); ?>"
        >
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function renderCheckboxField($args) {
        $options = get_option($this->options_group, $this->getDefaultOptions());
        $field = $args['field'];
        $current_value = !empty($options[$field]);
        
        ?>
        <input 
            type="checkbox" 
            name="<?php echo esc_attr($this->options_group . '[' . $field . ']'); ?>" 
            id="<?php echo esc_attr($field); ?>"
            value="1"
            <?php checked($current_value); ?>
        >
        <label for="<?php echo esc_attr($field); ?>">
            <?php echo esc_html($args['description']); ?>
        </label>
        <?php
    }
    
    public function renderNumberField($args) {
        $options = get_option($this->options_group, $this->getDefaultOptions());
        $field = $args['field'];
        $current_value = $options[$field] ?? '';
        $min = $args['min'] ?? '';
        $max = $args['max'] ?? '';
        
        ?>
        <input 
            type="number" 
            name="<?php echo esc_attr($this->options_group . '[' . $field . ']'); ?>" 
            id="<?php echo esc_attr($field); ?>"
            value="<?php echo esc_attr($current_value); ?>" 
            class="small-text"
            min="<?php echo esc_attr($min); ?>"
            max="<?php echo esc_attr($max); ?>"
        >
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    /**
     * AJAX обработчик для предпросмотра
     */
    public function handlePreview() {
        check_ajax_referer($this->plugin_slug . '_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $options = get_option($this->options_group, $this->getDefaultOptions());
        
        // Генерируем HTML предпросмотра
        $preview_html = $this->generatePreviewHTML($options);
        
        wp_send_json_success([
            'html' => $preview_html
        ]);
    }
    
    /**
     * Генерация HTML для предпросмотра
     */
    private function generatePreviewHTML($options) {
        $privacy_url = '#';
        $consent_text = str_replace('{privacy_url}', $privacy_url, $options['consent_text']);
        
        return sprintf(
            '<div style="border: 1px solid #ccc; padding: 15px; background: #f9f9f9; font-family: Arial;">
                <p style="margin: 0 0 10px 0;">%s</p>
                <button style="background: #28a745; color: white; border: none; padding: 8px 16px; margin-right: 10px;">%s</button>
                <button style="background: #dc3545; color: white; border: none; padding: 8px 16px;">%s</button>
            </div>',
            wp_kses_post($consent_text),
            esc_html($options['button_text']),
            esc_html($options['deny_text'])
        );
    }
}