/**
 * Cookie Consent Lazy Loading Script
 * Загружает баннер согласия только при взаимодействии пользователя или через fallback
 */

(function() {
    'use strict';
    
    console.log('Cookie Consent Lazy: Script started');
    
    // Проверяем, есть ли уже согласие
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) {
                return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
        }
        return null;
    }
    
    // Если согласие уже есть, не загружаем баннер
    const existingConsent = getCookie('cookie_consent');
    if (existingConsent) {
        console.log('Cookie Consent Lazy: Consent already exists, exiting');
        return;
    }
    
    // Проверяем Do Not Track если включено в настройках
    const config = window.cookieConsent || {};
    if (config.respectDNT && (navigator.doNotTrack === '1' || window.doNotTrack === '1')) {
        console.log('Cookie Consent Lazy: Do Not Track detected, exiting');
        return;
    }
    
    let cookieBannerLoaded = false;
    let timerId;
    
    console.log('Cookie Consent Lazy: Initializing event listeners');
    
    // Инициализация отложенной загрузки
    if (navigator.userAgent.indexOf('bot') > -1 || navigator.userAgent.indexOf('Bot') > -1) {
        // Для ботов загружаем сразу
        console.log('Cookie Consent Lazy: Bot detected, loading immediately');
        loadCookieBanner('bot-detected');
    } else {
        // Для пользователей - отложенная загрузка
        console.log('Cookie Consent Lazy: Setting up user interaction listeners');
        
        window.addEventListener('scroll', loadCookieBanner, {passive: true});
        window.addEventListener('touchstart', loadCookieBanner, {passive: true});
        document.addEventListener('mouseenter', loadCookieBanner, {passive: true});
        document.addEventListener('click', loadCookieBanner, {passive: true});
        
        // Проверяем готовность DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadFallback, {passive: true});
        } else {
            // DOM уже загружен, запускаем fallback сразу
            loadFallback();
        }
    }
    
    function loadFallback() {
        console.log('Cookie Consent Lazy: Setting fallback timer (3 seconds)');
        timerId = setTimeout(() => {
            loadCookieBanner('fallback-timeout');
        }, 3000);
    }
    
    function loadCookieBanner(triggerType) {
        // Определяем тип события
        let eventType = triggerType;
        if (typeof triggerType === 'object' && triggerType.type) {
            eventType = triggerType.type;
        }
        
        console.log('Cookie Consent Lazy: Load triggered by:', eventType);
        
        if (cookieBannerLoaded) {
            console.log('Cookie Consent Lazy: Already loaded, ignoring');
            return;
        }
        
        cookieBannerLoaded = true;
        clearTimeout(timerId);
        
        // Удаляем слушатели событий
        console.log('Cookie Consent Lazy: Removing event listeners');
        window.removeEventListener('scroll', loadCookieBanner, {passive: true});
        window.removeEventListener('touchstart', loadCookieBanner, {passive: true});
        document.removeEventListener('mouseenter', loadCookieBanner);
        document.removeEventListener('click', loadCookieBanner);
        document.removeEventListener('DOMContentLoaded', loadFallback);
        
        // Небольшая задержка для плавности
        setTimeout(function() {
            console.log('Cookie Consent Lazy: Starting resource loading');
            
            // Загружаем CSS если еще не загружен
            if (!document.getElementById('cookie-consent-banner-style')) {
                console.log('Cookie Consent Lazy: Loading CSS');
                const link = document.createElement('link');
                link.id = 'cookie-consent-banner-style';
                link.rel = 'stylesheet';
                link.href = config.cssUrl || '';
                link.media = 'all';
                link.onload = function() {
                    console.log('Cookie Consent Lazy: CSS loaded successfully');
                };
                link.onerror = function() {
                    console.error('Cookie Consent Lazy: Failed to load CSS');
                };
                document.head.appendChild(link);
            } else {
                console.log('Cookie Consent Lazy: CSS already loaded');
            }
            
            // Загружаем основной JavaScript файл
            if (!document.getElementById('cookie-consent-main-script')) {
                console.log('Cookie Consent Lazy: Loading main script');
                const script = document.createElement('script');
                script.id = 'cookie-consent-main-script';
                script.src = config.jsUrl || '';
                script.async = true;
                script.onload = function() {
                    console.log('Cookie Consent Lazy: Main script loaded successfully');
                };
                script.onerror = function() {
                    console.error('Cookie Consent Lazy: Failed to load main script');
                };
                document.head.appendChild(script);
            } else {
                console.log('Cookie Consent Lazy: Main script already loaded');
            }
        }, 100);
    }
    
    // Добавляем отладочную информацию
    console.log('Cookie Consent Lazy: Configuration:', {
        respectDNT: config.respectDNT,
        cssUrl: config.cssUrl,
        jsUrl: config.jsUrl,
        cookieExists: !!existingConsent,
        userAgent: navigator.userAgent.substring(0, 50) + '...'
    });
})();