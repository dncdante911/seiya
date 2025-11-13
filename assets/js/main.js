/**
 * Главный JS файл сайта Seiya
 * Общие функции, анимации, утилиты
 */

// ============================================
// АНИМАЦИИ ПРИ СКРОЛЛЕ
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    initScrollAnimations();
    initSmoothScroll();
    initModalHandlers();
    initImageLazyLoad();

    // Дополнительные анимации для главной страницы
    if (document.querySelector('.landing-page')) {
        initLandingAnimations();
        initParticles();
        initSectionInteractions();
    }
});

// ============================================
// АНИМАЦИИ ГЛАВНОЙ СТРАНИЦЫ
// ============================================
function initLandingAnimations() {
    // Fade-in анимация для header и секций
    const fadeElements = document.querySelectorAll('.fade-in, .landing-header, .section-half');

    fadeElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 1s ease, transform 1s ease';

        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 150);
    });

    // Параллакс для декоративных элементов при движении мыши
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;

    if (!isMobile) {
        document.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX / window.innerWidth - 0.5;
            const mouseY = e.clientY / window.innerHeight - 0.5;

            // Параллакс для декоративных элементов
            const decorElements = document.querySelectorAll('.decorative-element');
            decorElements.forEach((el, index) => {
                const speed = (index + 1) * 20;
                const x = mouseX * speed;
                const y = mouseY * speed;
                el.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
    }
}

// ============================================
// ИНТЕРАКТИВНЫЕ СЕКЦИИ
// ============================================
function initSectionInteractions() {
    const sections = document.querySelectorAll('.section-half');

    sections.forEach(section => {
        // Эффект при наведении
        section.addEventListener('mouseenter', function() {
            // Увеличиваем иконку
            const icon = this.querySelector('.section-icon');
            if (icon) {
                icon.style.transform = 'scale(1.15) rotate(5deg)';
                icon.style.transition = 'transform 0.4s ease';
            }

            // Добавляем свечение
            this.style.boxShadow = 'inset 0 0 100px rgba(255, 255, 255, 0.15)';
            this.style.transition = 'box-shadow 0.5s ease';
        });

        section.addEventListener('mouseleave', function() {
            // Возвращаем иконку
            const icon = this.querySelector('.section-icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }

            // Убираем свечение
            this.style.boxShadow = 'none';
        });

        // Ripple эффект при клике
        section.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.5);
                width: 20px;
                height: 20px;
                left: ${e.clientX - this.getBoundingClientRect().left - 10}px;
                top: ${e.clientY - this.getBoundingClientRect().top - 10}px;
                pointer-events: none;
                animation: ripple-effect 0.8s ease-out;
            `;

            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 800);
        });
    });

    // Добавляем CSS для ripple анимации
    if (!document.querySelector('#ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = `
            @keyframes ripple-effect {
                to {
                    width: 400px;
                    height: 400px;
                    margin: -200px 0 0 -200px;
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// ============================================
// ЧАСТИЦЫ НА ФОНЕ
// ============================================
function initParticles() {
    const landingPage = document.querySelector('.landing-page');
    if (!landingPage || window.innerWidth <= 768) return; // Отключаем на мобильных

    const particleCount = 40;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'floating-particle';

        // Случайные параметры
        const size = Math.random() * 3 + 1;
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const duration = Math.random() * 30 + 20;
        const delay = Math.random() * 10;

        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            left: ${x}%;
            top: ${y}%;
            pointer-events: none;
            z-index: 1;
            animation: float-particle ${duration}s ${delay}s infinite ease-in-out;
        `;

        landingPage.appendChild(particle);
    }

    // CSS для анимации частиц
    if (!document.querySelector('#particle-style')) {
        const style = document.createElement('style');
        style.id = 'particle-style';
        style.textContent = `
            @keyframes float-particle {
                0%, 100% {
                    transform: translate(0, 0) scale(1);
                    opacity: 0.3;
                }
                25% {
                    transform: translate(100px, -100px) scale(1.3);
                    opacity: 0.8;
                }
                50% {
                    transform: translate(-50px, -200px) scale(0.7);
                    opacity: 0.4;
                }
                75% {
                    transform: translate(150px, -150px) scale(1.2);
                    opacity: 0.7;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// Анимации при скролле
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Элементы для анимации
    const animatedElements = document.querySelectorAll('.product-card, .portfolio-card, .program-card, .feature-card, .category-card, .section-box');
    animatedElements.forEach(el => {
        el.classList.add('animate-on-scroll');
        observer.observe(el);
    });
}

// ============================================
// ПЛАВНАЯ ПРОКРУТКА
// ============================================
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
}

// ============================================
// МОДАЛЬНЫЕ ОКНА
// ============================================
function initModalHandlers() {
    // Закрытие модальных окон по клику на фон
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    // Закрытие по ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

// ============================================
// LAZY LOADING ИЗОБРАЖЕНИЙ
// ============================================
function initImageLazyLoad() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

// ============================================
// УТИЛИТЫ
// ============================================

// Форматирование цены
function formatPrice(price) {
    return parseFloat(price).toLocaleString('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' грн';
}

// Показ уведомлений
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Валидация телефона
function validatePhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return cleaned.length >= 10;
}

// Валидация email
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ============================================
// АНИМАЦИЯ ЧИСЕЛ (СЧЕТЧИКИ)
// ============================================
function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = Math.round(target);
            clearInterval(timer);
        } else {
            element.textContent = Math.round(current);
        }
    }, 16);
}

// ============================================
// PARALLAX ЭФФЕКТ
// ============================================
function initParallax() {
    const parallaxElements = document.querySelectorAll('.parallax');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(el => {
            const speed = el.dataset.speed || 0.5;
            el.style.transform = `translateY(${scrolled * speed}px)`;
        });
    });
}

// ============================================
// ЭКСПОРТ ФУНКЦИЙ
// ============================================
window.SiteHelpers = {
    formatPrice,
    showNotification,
    validatePhone,
    validateEmail,
    animateCounter
};
