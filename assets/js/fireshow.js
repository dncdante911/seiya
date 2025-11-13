/**
 * Fireshow - JavaScript
 */

const FireshowOrder = {
    selectedProgram: null,

    init() {
        this.setupEventListeners();
    },

    setupEventListeners() {
        // Выбор программы
        document.querySelectorAll('.program-option').forEach(option => {
            option.addEventListener('click', (e) => {
                this.selectProgram(e.currentTarget);
            });
        });

        // Изменение количества артистов
        const numArtistsSelect = document.getElementById('numArtists');
        if (numArtistsSelect) {
            numArtistsSelect.addEventListener('change', () => this.calculateTotal());
        }

        // Форма заказа
        const form = document.getElementById('fireshowOrderForm');
        if (form) {
            form.addEventListener('submit', (e) => this.submitOrder(e));
        }
    },

    selectProgram(element) {
        // Убираем выделение с других
        document.querySelectorAll('.program-option').forEach(opt => {
            opt.classList.remove('selected');
        });

        // Выделяем выбранный
        element.classList.add('selected');

        // Анимация выбора
        element.style.transform = 'scale(1.05)';
        setTimeout(() => {
            element.style.transform = '';
        }, 200);

        this.selectedProgram = {
            id: element.dataset.id,
            price: parseFloat(element.dataset.price),
            min: parseInt(element.dataset.min),
            max: parseInt(element.dataset.max)
        };

        document.getElementById('programId').value = this.selectedProgram.id;

        // Обновляем список количества артистов
        this.updateArtistsSelect();
        this.calculateTotal();
    },

    updateArtistsSelect() {
        const select = document.getElementById('numArtists');
        if (!select || !this.selectedProgram) return;

        select.innerHTML = '';

        for (let i = this.selectedProgram.min; i <= this.selectedProgram.max; i++) {
            const option = document.createElement('option');
            option.value = i;
            
            let label = i + ' ';
            if (i === 1) label += (window.translations?.artists || 'артист');
            else if (i < 5) label += (window.translations?.artists || 'артиста');
            else label += (window.translations?.artists || 'артистов');
            
            option.textContent = label;
            select.appendChild(option);
        }

        select.disabled = false;
    },

    calculateTotal() {
        if (!this.selectedProgram) return;

        const numArtists = parseInt(document.getElementById('numArtists')?.value) || this.selectedProgram.min;
        const total = this.selectedProgram.price * numArtists;

        const totalElement = document.getElementById('totalPrice');
        if (totalElement) {
            totalElement.textContent = total.toLocaleString('uk-UA') + ' грн';
            
            // Анимация
            totalElement.style.transform = 'scale(1.2)';
            totalElement.style.color = '#ff4500';
            setTimeout(() => {
                totalElement.style.transform = '';
            }, 300);
        }
    },

    async submitOrder(e) {
        e.preventDefault();

        if (!this.selectedProgram) {
            window.SiteHelpers.showNotification(
                window.translations?.choose_program || 'Пожалуйста, выберите программу',
                'error'
            );
            return;
        }

        const formData = new FormData(e.target);
        const numArtists = parseInt(formData.get('num_artists'));
        const totalPrice = this.selectedProgram.price * numArtists;
        formData.append('total_price', totalPrice);

        try {
            const response = await fetch('../api/orders_fireshow.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.SiteHelpers.showNotification(
                    window.translations?.order_success || 'Заказ успешно оформлен!',
                    'success'
                );
                e.target.reset();
                this.resetForm();
            } else {
                window.SiteHelpers.showNotification(
                    result.error || 'Ошибка при оформлении заказа',
                    'error'
                );
            }
        } catch (error) {
            console.error('Order error:', error);
            window.SiteHelpers.showNotification(
                'Произошла ошибка при отправке заказа',
                'error'
            );
        }
    },

    resetForm() {
        this.selectedProgram = null;
        document.querySelectorAll('.program-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        document.getElementById('totalPrice').textContent = '0 грн';
        
        const select = document.getElementById('numArtists');
        if (select) {
            select.innerHTML = '<option value="">Сначала выберите программу</option>';
            select.disabled = true;
        }
    }
};

// Глобальные функции для совместимости
function selectProgram(element) {
    FireshowOrder.selectProgram(element);
}

function submitFireshowOrder(e) {
    FireshowOrder.submitOrder(e);
}

// Модальное окно для портфолио
function openModal(imageSrc, type) {
    if (type === 'image') {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('modalImage');
        if (modal && img) {
            img.src = imageSrc;
            modal.classList.add('active');
        }
    }
}

function closeModal() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => modal.classList.remove('active'));
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    FireshowOrder.init();
    initFireshowAnimations();
});

/**
 * ==============================================
 * FIRESHOW ANIMATIONS - Огненные и звездные анимации
 * ==============================================
 */

const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;

/**
 * Создание падающих звезд
 */
function createShootingStars() {
    if (isMobile) return;

    setInterval(() => {
        const star = document.createElement('div');
        star.className = 'shooting-star';
        star.style.cssText = `
            position: fixed;
            width: 2px;
            height: 2px;
            background: white;
            box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            top: ${Math.random() * 50}%;
            left: ${Math.random() * 100}%;
            z-index: 0;
            pointer-events: none;
        `;

        document.body.appendChild(star);

        // Анимация
        star.animate([
            { transform: 'translate(0, 0)', opacity: 1 },
            { transform: 'translate(-300px, 300px)', opacity: 0 }
        ], {
            duration: 3000,
            easing: 'linear'
        }).onfinish = () => star.remove();
    }, 5000);
}

/**
 * Создание огненных частиц
 */
function createFireParticles() {
    if (isMobile) return;

    const banner = document.querySelector('.fireshow-banner');
    if (!banner) return;

    setInterval(() => {
        const particle = document.createElement('div');
        const startX = Math.random() * 100;
        const size = 2 + Math.random() * 4;

        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: linear-gradient(135deg, #ff4500, #ff6347);
            border-radius: 50%;
            left: ${startX}%;
            bottom: 0;
            pointer-events: none;
            z-index: 1;
            box-shadow: 0 0 10px rgba(255, 69, 0, 0.8);
        `;

        banner.appendChild(particle);

        // Анимация вверх
        particle.animate([
            { transform: 'translateY(0) scale(1)', opacity: 1 },
            { transform: `translateY(-${200 + Math.random() * 200}px) translateX(${-30 + Math.random() * 60}px) scale(0.3)`, opacity: 0 }
        ], {
            duration: 2000 + Math.random() * 2000,
            easing: 'ease-out'
        }).onfinish = () => particle.remove();
    }, 500);
}

/**
 * Эффект искр при клике
 */
function initSparkEffect() {
    const cards = document.querySelectorAll('.portfolio-card, .program-card, .feature-card');

    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX;
            const y = e.clientY;

            // Создаем 8 искр
            for (let i = 0; i < 8; i++) {
                const spark = document.createElement('div');
                const angle = (360 / 8) * i;
                const distance = 30 + Math.random() * 30;

                spark.style.cssText = `
                    position: fixed;
                    width: 4px;
                    height: 4px;
                    background: #ff6347;
                    border-radius: 50%;
                    left: ${x}px;
                    top: ${y}px;
                    pointer-events: none;
                    z-index: 9999;
                    box-shadow: 0 0 5px rgba(255, 69, 0, 0.8);
                `;

                document.body.appendChild(spark);

                const rad = angle * Math.PI / 180;
                const endX = x + Math.cos(rad) * distance;
                const endY = y + Math.sin(rad) * distance;

                spark.animate([
                    { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                    { transform: `translate(${endX - x}px, ${endY - y}px) scale(0)`, opacity: 0 }
                ], {
                    duration: 500,
                    easing: 'ease-out'
                }).onfinish = () => spark.remove();
            }
        });
    });
}

/**
 * Пульсация огненных кнопок
 */
function initFireButtonEffects() {
    const fireButtons = document.querySelectorAll('.btn-primary');

    fireButtons.forEach(btn => {
        if (!isMobile) {
            btn.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 0 30px rgba(255, 69, 0, 0.8), 0 0 60px rgba(255, 140, 0, 0.4)';
            });

            btn.addEventListener('mouseleave', function() {
                this.style.boxShadow = '';
            });
        }
    });
}

/**
 * Инициализация всех анимаций
 */
function initFireshowAnimations() {
    setTimeout(() => {
        createShootingStars();
        createFireParticles();
        initSparkEffect();
        initFireButtonEffects();
    }, 500);
}
