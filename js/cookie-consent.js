/**
 * Cookie Consent Banner JavaScript
 * Улучшенная версия с проверками безопасности и GDPR соответствием
 */

class CookieConsent {
    constructor() {
        this.banner = null;
        this.settings = window.cookieConsentSettings || {};
        this.nonce = window.cookieConsent?.nonce || '';
        
        // Проверяем, загружена ли страница
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        // Если согласие уже дано, не показываем баннер
        if (this.getCookie('cookie_consent')) {
            return;
        }

        // Проверяем Do Not Track
        if (this.respectDNT()) {
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
        } else {
            console.warn('Cookie Consent: Accept button not found');
        }

        if (denyBtn) {
            denyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleDeny();
            });
        } else {
            console.warn('Cookie Consent: Deny button not found');
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
        if (!this.banner) return;

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

// Инициализация только если мы не в админке WordPress
if (typeof window !== 'undefined' && !document.body.classList.contains('wp-admin')) {
    // Создаем глобальный экземпляр
    window.cookieConsentInstance = new CookieConsent();
    
    // Добавляем глобальные методы для совместимости
    window.cookieConsent = {
        accept: () => window.cookieConsentInstance.acceptConsent(),
        deny: () => window.cookieConsentInstance.denyConsent(),
        reset: () => window.cookieConsentInstance.resetConsent(),
        getStatus: () => window.cookieConsentInstance.getConsentStatus()
    };
}

// Экспорт для модульных систем
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CookieConsent;
}