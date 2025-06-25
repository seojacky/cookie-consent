/**
 * Cookie Consent Banner JavaScript
 * Улучшенная версия с проверками безопасности и GDPR соответствием
 */

class CookieConsent {
    constructor() {
        this.banner = null;
        this.settings = window.cookieConsentSettings || {};
        this.nonce = window.cookieConsent?.nonce || '';
        
        console.log('Cookie Consent: Main class initialized');
        
        // Проверяем, загружена ли страница
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        console.log('Cookie Consent: Init called');
        
        // Если согласие уже дано, не показываем баннер
        if (this.getCookie('cookie_consent')) {
            console.log('Cookie Consent: Consent already exists, exiting');
            return;
        }

        // Проверяем Do Not Track
        if (this.respectDNT()) {
            console.log('Cookie Consent: Do Not Track detected, exiting');
            return;
        }

        // Проверяем, есть ли уже баннер (для отложенной загрузки)
        if (!document.getElementById('cookie-consent-banner')) {
            console.log('Cookie Consent: No banner element found, will be created later');
            return;
        }

        this.setupBanner();
        this.bindEvents();
        this.showBanner();
    }

    setupBanner() {
        this.banner = document.getElementById('cookie-consent-banner');
        
        if (!this.banner) {
            console.warn('Cookie Consent: Banner element not found');
            return;
        }

        console.log('Cookie Consent: Banner element found');
        
        // Добавляем ARIA атрибуты для accessibility
        this.banner.setAttribute('role', 'dialog');
        this.banner.setAttribute('aria-live', 'polite');
        this.banner.setAttribute('aria-label', 'Cookie consent banner');
    }

    bindEvents() {
        if (!this.banner) return;

        const acceptBtn = document.getElementById('cookie-consent-accept');
        const denyBtn = document.getElementById('cookie-consent-deny');

        // Проверяем существование кнопок перед добавлением событий
        if (acceptBtn) {
            acceptBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleAccept();
            });
            console.log('Cookie Consent: Accept button event bound');
        } else {
            console.warn('Cookie Consent: Accept button not found');
        }

        if (denyBtn) {
            denyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleDeny();
            });
            console.log('Cookie Consent: Deny button event bound');
        } else {
            console.log('Cookie Consent: Deny button not shown (optional)');
        }

        // Обработка клавиши Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.banner && this.banner.style.display !== 'none') {
                this.handleDeny();
            }
        });

        // Обработка клика вне баннера (только для модального режима)
        if (this.settings.position === 'modal') {
            document.addEventListener('click', (e) => {
                if (this.banner && !this.banner.contains(e.target)) {
                    this.handleDeny();
                }
            });
        }
    }

    handleAccept() {
        try {
            console.log('Cookie Consent: Accept clicked');
            
            // Устанавливаем cookie согласия
            this.setCookie('cookie_consent', 'accepted', 365);
            
            // Скрываем баннер
            this.hideBanner();
            
            // Включаем трекинг скрипты
            this.enableTracking();
            
            // Отправляем событие
            this.triggerEvent('cookieAccepted', { consent: 'accepted' });
            
            console.log('Cookie Consent: Consent accepted');
        } catch (error) {
            console.error('Cookie Consent: Error handling accept:', error);
        }
    }

    handleDeny() {
        try {
            console.log('Cookie Consent: Deny clicked');
            
            // Устанавливаем cookie отказа (на меньший срок)
            this.setCookie('cookie_consent', 'denied', 30);
            
            // Скрываем баннер
            this.hideBanner();
            
            // Отключаем трекинг скрипты
            this.disableTracking();
            
            // Отправляем событие
            this.triggerEvent('cookieDenied', { consent: 'denied' });
            
            console.log('Cookie Consent: Consent denied');
        } catch (error) {
            console.error('Cookie Consent: Error handling deny:', error);
        }
    }

    setCookie(name, value, days) {
        try {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            
            // Определяем, нужен ли Secure атрибут
            const isSecure = location.protocol === 'https:';
            const secureAttr = isSecure ? '; Secure' : '';
            
            // Формируем cookie с всеми необходимыми атрибутами
            const cookieString = `${name}=${encodeURIComponent(value)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax${secureAttr}`;
            
            document.cookie = cookieString;
            
            return true;
        } catch (error) {
            console.error('Cookie Consent: Error setting cookie:', error);
            return false;
        }
    }

    getCookie(name) {
        try {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(c.substring(nameEQ.length, c.length));
                }
            }
            return null;
        } catch (error) {
            console.error('Cookie Consent: Error reading cookie:', error);
            return null;
        }
    }

    showBanner() {
        if (!this.banner) {
            console.warn('Cookie Consent: Cannot show banner - element not found');
            return;
        }

        console.log('Cookie Consent: Showing banner');

        // Устанавливаем начальное состояние для анимации
        this.banner.style.transform = 'translateY(100%)';
        this.banner.style.transition = 'transform 0.3s ease-in-out';
        this.banner.style.display = 'block';

        // Запускаем анимацию появления
        requestAnimationFrame(() => {
            this.banner.style.transform = 'translateY(0)';
        });

        // Фокус на первой кнопке для accessibility
        const firstButton = this.banner.querySelector('button');
        if (firstButton) {
            setTimeout(() => firstButton.focus(), 300);
        }
    }

    hideBanner() {
        if (!this.banner) return;

        console.log('Cookie Consent: Hiding banner');

        // Анимация скрытия
        this.banner.style.transform = 'translateY(100%)';
        
        setTimeout(() => {
            if (this.banner) {
                this.banner.style.display = 'none';
                this.banner.remove();
            }
        }, 300);
    }

    enableTracking() {
        // Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted',
                'ad_storage': 'granted'
            });
        }

        // Google Tag Manager
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({
                'event': 'cookie_consent_granted',
                'consent_analytics': true,
                'consent_marketing': true
            });
        }

        // Facebook Pixel
        if (typeof fbq !== 'undefined') {
            fbq('consent', 'grant');
        }

        // Яндекс.Метрика
        if (typeof ym !== 'undefined') {
            // Яндекс.Метрика не требует отдельного управления согласием
        }

        console.log('Cookie Consent: Tracking enabled');
    }

    disableTracking() {
        // Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'denied',
                'ad_storage': 'denied'
            });
        }

        // Google Tag Manager
        if (typeof dataLayer !== 'undefined') {
            dataLayer.push({
                'event': 'cookie_consent_denied',
                'consent_analytics': false,
                'consent_marketing': false
            });
        }

        // Facebook Pixel
        if (typeof fbq !== 'undefined') {
            fbq('consent', 'revoke');
        }

        // Удаляем существующие трекинг cookies
        this.removeTrackingCookies();

        console.log('Cookie Consent: Tracking disabled');
    }

    removeTrackingCookies() {
        const trackingCookies = [
            '_ga', '_gid', '_gat', '_gtag_', '_gcl_au',
            'fr', '_fbp', '_fbc',
            '_ym_uid', '_ym_d', '_ym_isad', '_ym_visorc'
        ];

        trackingCookies.forEach(cookieName => {
            this.deleteCookie(cookieName);
            this.deleteCookie(cookieName, '.' + window.location.hostname);
        });
    }

    deleteCookie(name, domain = '') {
        const domainPart = domain ? '; domain=' + domain : '';
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/${domainPart}`;
    }

    respectDNT() {
        // Проверяем заголовок Do Not Track
        return this.settings.respectDNT && 
               (navigator.doNotTrack === '1' || 
                window.doNotTrack === '1' || 
                navigator.msDoNotTrack === '1');
    }

    triggerEvent(eventName, data = {}) {
        try {
            // Создаем кастомное событие
            const event = new CustomEvent('cookieConsent.' + eventName, {
                detail: {
                    ...data,
                    timestamp: Date.now(),
                    userAgent: navigator.userAgent
                },
                bubbles: true,
                cancelable: true
            });

            document.dispatchEvent(event);

            // Также отправляем в window для обратной совместимости
            if (window.cookieConsentCallback && typeof window.cookieConsentCallback === 'function') {
                window.cookieConsentCallback(eventName, data);
            }
        } catch (error) {
            console.error('Cookie Consent: Error triggering event:', error);
        }
    }

    // Публичные методы для внешнего использования
    static getInstance() {
        if (!window.cookieConsentInstance) {
            window.cookieConsentInstance = new CookieConsent();
        }
        return window.cookieConsentInstance;
    }

    // Метод для программного принятия согласия
    acceptConsent() {
        this.handleAccept();
    }

    // Метод для программного отказа от согласия
    denyConsent() {
        this.handleDeny();
    }

    // Метод для сброса согласия (показать баннер снова)
    resetConsent() {
        this.deleteCookie('cookie_consent');
        location.reload();
    }

    // Метод для получения статуса согласия
    getConsentStatus() {
        return this.getCookie('cookie_consent');
    }
}

/**
 * Модификация для поддержки отложенной загрузки
 */
class CookieConsentLazy extends CookieConsent {
    constructor() {
        console.log('Cookie Consent Lazy: CookieConsentLazy constructor called');
        
        // ВАЖНО: Сначала вызываем super()
        super();
        
        // Затем создаем баннер если нужно
        if (!document.getElementById('cookie-consent-banner')) {
            console.log('Cookie Consent Lazy: Creating banner from config');
            this.createBannerFromConfig();
            // После создания HTML нужно переинициализировать
            this.setupBanner();
            this.bindEvents();
            this.showBanner();
        }
    }

    createBannerFromConfig() {
        const config = window.cookieConsentBannerConfig;
        if (!config) {
            console.warn('Cookie Consent Lazy: No banner config found');
            return;
        }

        console.log('Cookie Consent Lazy: Banner config found, creating HTML');

        const bannerHTML = `
            <div id="cookie-consent-banner" role="dialog" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-desc" style="position:fixed;bottom:0;left:0;width:100%;background-color:#f8f9fa;border-top:1px solid #dee2e6;padding:15px;z-index:9999;font-family:Arial,sans-serif;transform:translateY(100%);transition:transform 0.3s ease-in-out;display:block;">
                <div class="cookie-consent-content" style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:15px;">
                    <h2 id="cookie-consent-title" class="sr-only">Cookie Consent</h2>
                    <p id="cookie-consent-desc" style="flex:1;margin:0;color:#333;font-size:14px;">${config.consent_text}</p>
                    <div class="cookie-consent-buttons" style="display:flex;gap:10px;flex-shrink:0;">
                        <button id="cookie-consent-accept" class="accept-button" type="button" aria-describedby="cookie-consent-desc" style="border:none;padding:10px 20px;border-radius:5px;cursor:pointer;font-size:14px;background-color:#28a745;color:white;font-weight:500;">
                            ${config.accept_text}
                        </button>
                        ${config.show_deny_button ? `
                        <button id="cookie-consent-deny" class="deny-button" type="button" aria-describedby="cookie-consent-desc" style="border:none;padding:10px 20px;border-radius:5px;cursor:pointer;font-size:14px;background-color:#dc3545;color:white;font-weight:500;">
                            ${config.deny_text}
                        </button>` : ''}
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
            @media (max-width: 768px) {
                #cookie-consent-banner .cookie-consent-content {
                    flex-direction: column !important;
                    text-align: center !important;
                    gap: 12px !important;
                }
                #cookie-consent-banner .cookie-consent-buttons {
                    width: 100% !important;
                    justify-content: center !important;
                }
            }
            </style>`;

        document.body.insertAdjacentHTML('beforeend', bannerHTML);
        console.log('Cookie Consent Lazy: Banner HTML created and inserted');
        
        // Принудительно показываем баннер сразу для тестирования
        setTimeout(() => {
            const banner = document.getElementById('cookie-consent-banner');
            if (banner) {
                banner.style.transform = 'translateY(0)';
                console.log('Cookie Consent Lazy: Banner forced to show');
            }
        }, 100);
    }
}

// Инициализация только если мы не в админке WordPress
if (typeof window !== 'undefined' && !document.body.classList.contains('wp-admin')) {
    console.log('Cookie Consent: Initializing main script');
    
    // Создаем экземпляр только если не создан отложенной загрузкой
    if (!window.cookieConsentInstance) {
        window.cookieConsentInstance = new CookieConsentLazy();
        console.log('Cookie Consent: CookieConsentLazy instance created');
    }
    
    // Добавляем глобальные методы для совместимости
    window.cookieConsent = window.cookieConsent || {};
    Object.assign(window.cookieConsent, {
        accept: () => window.cookieConsentInstance.acceptConsent(),
        deny: () => window.cookieConsentInstance.denyConsent(),
        reset: () => window.cookieConsentInstance.resetConsent(),
        getStatus: () => window.cookieConsentInstance.getConsentStatus()
    });
}

// Экспорт для модульных систем
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CookieConsent;
}