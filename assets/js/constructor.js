/**
 * Конструктор тортов - JavaScript
 */

const CakeConstructor = {
    selected: {
        base: null,
        filling: null,
        cream: null,
        decoration: null,
        topping: null
    },

    init() {
        this.setupEventListeners();
        this.updateSummary();
    },

    setupEventListeners() {
        // Обработчики выбора ингредиентов
        document.querySelectorAll('.ingredient-item').forEach(item => {
            item.addEventListener('click', (e) => {
                this.selectIngredient(e.currentTarget);
            });
        });

        // Форма заказа
        const form = document.getElementById('constructorOrderForm');
        if (form) {
            form.addEventListener('submit', (e) => this.submitOrder(e));
        }
    },

    selectIngredient(element) {
        const type = element.dataset.type;
        const id = parseInt(element.dataset.id);
        const name = element.dataset.name;
        const price = parseFloat(element.dataset.price);

        // Убираем выделение с других элементов этого типа
        document.querySelectorAll(`.ingredient-item[data-type="${type}"]`).forEach(item => {
            item.classList.remove('selected');
        });

        // Если клик по уже выбранному - отменяем выбор
        if (this.selected[type] && this.selected[type].id === id) {
            this.selected[type] = null;
        } else {
            // Выделяем новый элемент
            element.classList.add('selected');
            this.selected[type] = { id, name, price };

            // Анимация выбора
            element.style.transform = 'scale(1.1)';
            setTimeout(() => {
                element.style.transform = '';
            }, 200);
        }

        this.updateSummary();
    },

    updateSummary() {
        let total = 0;

        Object.keys(this.selected).forEach(type => {
            const sumElement = document.getElementById(`sum-${type}`);
            if (!sumElement) return;

            if (this.selected[type]) {
                sumElement.textContent = this.selected[type].name;
                sumElement.style.color = '#ff6b9d';
                total += this.selected[type].price;

                // Анимация обновления
                sumElement.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    sumElement.style.transform = '';
                }, 200);
            } else {
                sumElement.textContent = window.translations?.not_selected || 'Не выбрано';
                sumElement.style.color = '#999';
            }
        });

        const totalElement = document.getElementById('total-price');
        if (totalElement) {
            totalElement.textContent = total.toLocaleString('uk-UA') + ' грн';
            
            // Анимация цены
            totalElement.style.transform = 'scale(1.2)';
            totalElement.style.color = '#ff6b9d';
            setTimeout(() => {
                totalElement.style.transform = '';
            }, 300);
        }
    },

    openOrderModal() {
        if (!this.selected.base || !this.selected.filling || !this.selected.cream) {
            window.SiteHelpers.showNotification(
                window.translations?.fill_required || 'Выберите хотя бы основу, начинку и крем',
                'error'
            );
            return;
        }
        
        const modal = document.getElementById('orderModal');
        if (modal) {
            modal.classList.add('active');
            // Фокус на первое поле
            const firstInput = modal.querySelector('input');
            if (firstInput) firstInput.focus();
        }
    },

    closeOrderModal() {
        const modal = document.getElementById('orderModal');
        if (modal) modal.classList.remove('active');
    },

    async submitOrder(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('order_type', 'custom');
        formData.append('constructor_data', JSON.stringify(this.selected));

        let totalPrice = 0;
        Object.values(this.selected).forEach(item => {
            if (item) totalPrice += item.price;
        });
        formData.append('total_price', totalPrice);

        try {
            const response = await fetch('../api/orders.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.SiteHelpers.showNotification(
                    window.translations?.order_success || 'Заказ успешно оформлен!',
                    'success'
                );
                this.closeOrderModal();
                e.target.reset();
                this.resetConstructor();
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

    resetConstructor() {
        // Сброс выбора
        Object.keys(this.selected).forEach(key => this.selected[key] = null);
        
        // Убираем выделение
        document.querySelectorAll('.ingredient-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        this.updateSummary();
    }
};

// Глобальные функции для совместимости
function selectIngredient(element, type) {
    CakeConstructor.selectIngredient(element);
}

function openOrderModal() {
    CakeConstructor.openOrderModal();
}

function closeOrderModal() {
    CakeConstructor.closeOrderModal();
}

function submitConstructorOrder(e) {
    CakeConstructor.submitOrder(e);
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    CakeConstructor.init();
});
