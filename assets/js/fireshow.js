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
});
