/**
 * Confectionery Animations - Легкие анимации для кондитерки
 */

const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;

/**
 * Создание летающих звездочек
 */
function createFloatingSparkles() {
    if (isMobile) return;

    const banner = document.querySelector('.confectionery-banner, .hero-banner');
    if (!banner) return;

    setInterval(() => {
        const sparkle = document.createElement('div');
        const startX = Math.random() * 100;
        const size = 8 + Math.random() * 12;

        sparkle.textContent = ['✨', '⭐', '💫'][Math.floor(Math.random() * 3)];
        sparkle.style.cssText = `
            position: absolute;
            left: ${startX}%;
            bottom: 0;
            font-size: ${size}px;
            pointer-events: none;
            z-index: 1;
            opacity: 0.6;
        `;

        banner.appendChild(sparkle);

        // Анимация вверх с легким покачиванием
        sparkle.animate([
            {
                transform: 'translateY(0) rotate(0deg) scale(1)',
                opacity: 0.6
            },
            {
                transform: `translateY(-${300 + Math.random() * 200}px) rotate(${-20 + Math.random() * 40}deg) scale(${0.5 + Math.random() * 0.5})`,
                opacity: 0
            }
        ], {
            duration: 3000 + Math.random() * 2000,
            easing: 'ease-out'
        }).onfinish = () => sparkle.remove();
    }, 1000);
}

/**
 * Эффект конфетти при клике на карточки
 */
function initConfettiEffect() {
    const cards = document.querySelectorAll('.product-card, .category-card, .feature-card');

    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            const x = e.clientX;
            const y = e.clientY;

            // Создаем конфетти
            for (let i = 0; i < 12; i++) {
                const confetti = document.createElement('div');
                const colors = ['#ff6b9d', '#ff8c42', '#ffd700', '#ff69b4', '#ffa07a'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                const angle = (360 / 12) * i;
                const distance = 40 + Math.random() * 40;

                confetti.style.cssText = `
                    position: fixed;
                    width: ${4 + Math.random() * 4}px;
                    height: ${4 + Math.random() * 4}px;
                    background: ${color};
                    border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                    z-index: 9999;
                `;

                document.body.appendChild(confetti);

                const rad = angle * Math.PI / 180;
                const endX = x + Math.cos(rad) * distance;
                const endY = y + Math.sin(rad) * distance;

                confetti.animate([
                    {
                        transform: 'translate(0, 0) rotate(0deg) scale(1)',
                        opacity: 1
                    },
                    {
                        transform: `translate(${endX - x}px, ${endY - y + 50}px) rotate(${360 * Math.random()}deg) scale(0)`,
                        opacity: 0
                    }
                ], {
                    duration: 800,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                }).onfinish = () => confetti.remove();
            }
        });
    });
}

/**
 * Анимация сердечек при hover на изображениях
 */
function initHeartAnimation() {
    if (isMobile) return;

    const productImages = document.querySelectorAll('.product-image, .category-image');

    productImages.forEach(img => {
        img.addEventListener('mouseenter', function() {
            const heart = document.createElement('div');
            heart.textContent = '💖';
            heart.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                font-size: 2rem;
                pointer-events: none;
                z-index: 10;
                transform: translate(-50%, -50%) scale(0);
            `;

            img.parentElement.style.position = 'relative';
            img.parentElement.appendChild(heart);

            heart.animate([
                { transform: 'translate(-50%, -50%) scale(0)', opacity: 0 },
                { transform: 'translate(-50%, -50%) scale(1.5)', opacity: 1 },
                { transform: 'translate(-50%, -80%) scale(1)', opacity: 0 }
            ], {
                duration: 1000,
                easing: 'ease-out'
            }).onfinish = () => heart.remove();
        });
    });
}

/**
 * Мягкое свечение кнопок
 */
function initButtonGlow() {
    const buttons = document.querySelectorAll('.btn-primary, .btn-secondary');

    buttons.forEach(btn => {
        if (!isMobile) {
            btn.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 0 20px rgba(255, 107, 157, 0.6), 0 0 40px rgba(255, 140, 66, 0.3)';
            });

            btn.addEventListener('mouseleave', function() {
                this.style.boxShadow = '';
            });
        }
    });
}

/**
 * Плавное появление карточек при скролле
 */
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.product-card, .category-card, .feature-card');

    animatedElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = `all 0.6s ease ${index * 0.1}s`;
        observer.observe(el);
    });
}

/**
 * Анимация иконок категорий
 */
function initCategoryIconAnimations() {
    const categoryCards = document.querySelectorAll('.category-card');

    categoryCards.forEach(card => {
        if (!isMobile) {
            card.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.category-icon');
                if (icon) {
                    icon.animate([
                        { transform: 'scale(1) rotate(0deg)' },
                        { transform: 'scale(1.2) rotate(10deg)' },
                        { transform: 'scale(1.15) rotate(-5deg)' },
                        { transform: 'scale(1.2) rotate(0deg)' }
                    ], {
                        duration: 500,
                        easing: 'ease-in-out'
                    });
                }
            });
        }
    });
}

/**
 * Инициализация всех анимаций
 */
function initConfectioneryAnimations() {
    setTimeout(() => {
        createFloatingSparkles();
        initConfettiEffect();
        initHeartAnimation();
        initButtonGlow();
        initScrollAnimations();
        initCategoryIconAnimations();
    }, 300);
}

// Запуск при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initConfectioneryAnimations);
} else {
    initConfectioneryAnimations();
}
