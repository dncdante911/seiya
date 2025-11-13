# 🔗 ІНТЕГРАЦІЯ З WHMCS BILLING

## 📋 ЩО ПОТРІБНО ВІД WHMCS

Для інтеграції замовлень через WHMCS потрібні наступні дані:

### 1. **API Credentials**
```
WHMCS URL: https://ваш-домен/whmcs/
API Identifier: [отримати з WHMCS]
API Secret: [отримати з WHMCS]
```

### 2. **Як отримати API credentials:**

#### Крок 1: Зайдіть в WHMCS Admin
`Setup → Staff Management → API Credentials → Generate New API Credential`

#### Крок 2: Створіть API Credential
- **Name**: Seiya Website Integration
- **Type**: API Identifier & Secret
- **Allowed IP Address**: IP вашого сервера (або * для всіх)
- **Access Control**:
  - ✅ AddOrder
  - ✅ AddClient
  - ✅ AddInvoicePayment
  - ✅ GetClientsDetails
  - ✅ GetProducts
  - ✅ GetInvoice

#### Крок 3: Скопіюйте дані
Після створення ви отримаєте:
- **Identifier**: api-XXXXXXXXXX
- **Secret**: YYYYYYYYYYYYYYYY

---

## 🛍️ НАЛАШТУВАННЯ ПРОДУКТІВ У WHMCS

### Створіть групу продуктів:

1. **Кондитерка**
   - Setup → Products/Services → Products/Services → Create a New Group
   - Name: "Seiya Кондитерка"
   - Description: "Торти та солодощі на замовлення"

2. **Фаершоу**
   - Name: "Seiya Фаершоу"
   - Description: "Вогняні шоу для заходів"

### Створіть продукти:

#### Для кондитерки:
1. **Product Type**: Other
2. **Product Group**: Seiya Кондитерка
3. **Product Name**: "Замовлення торту/солодощів"
4. **Payment Type**: One Time
5. **Pricing**: 0.00 UAH (ціна буде передаватись динамічно)

#### Для фаершоу:
1. **Product Type**: Other
2. **Product Group**: Seiya Фаершоу
3. **Product Name**: "Замовлення фаершоу"
4. **Payment Type**: One Time
5. **Pricing**: 0.00 UAH (ціна буде передаватись динамічно)

### Запишіть Product ID:
Після створення продукту, ID буде в URL:
`/admin/configproducts.php?action=edit&id=123`

Запишіть:
- **Кондитерка Product ID**: ___
- **Фаершоу Product ID**: ___

---

## ⚙️ НАЛАШТУВАННЯ ІНТЕГРАЦІЇ НА САЙТІ

### 1. Створіть файл конфігурації:
`config/whmcs.php`

```php
<?php
/**
 * WHMCS Integration Configuration
 */

define('WHMCS_URL', 'https://ваш-домен/whmcs/');
define('WHMCS_API_IDENTIFIER', 'api-XXXXXXXXXX');
define('WHMCS_API_SECRET', 'YYYYYYYYYYYYYYYY');

// Product IDs from WHMCS
define('WHMCS_PRODUCT_CONFECTIONERY', 123); // ID продукту кондитерки
define('WHMCS_PRODUCT_FIRESHOW', 124);       // ID продукту фаершоу
?>
```

### 2. Заповніть дані:
- `WHMCS_URL` - URL вашого WHMCS
- `WHMCS_API_IDENTIFIER` - Identifier з кроку 2
- `WHMCS_API_SECRET` - Secret з кроку 2
- `WHMCS_PRODUCT_CONFECTIONERY` - ID продукту кондитерки
- `WHMCS_PRODUCT_FIRESHOW` - ID продукту фаершоу

---

## 🔄 ЯК ЦЕ ПРАЦЮВАТИМЕ

### Процес замовлення:

1. **Клієнт оформлює замовлення на сайті**
   - Вибирає торт/фаершоу
   - Заповнює форму
   - Натискає "Замовити"

2. **Сайт створює замовлення в WHMCS**
   - Перевіряє/створює клієнта в WHMCS
   - Створює замовлення (order)
   - Створює рахунок (invoice)
   - Отримує посилання на оплату

3. **Клієнт переходить до оплати**
   - Перенаправлення на WHMCS
   - Вибір способу оплати
   - Оплата рахунку

4. **WHMCS відправляє webhook**
   - При успішній оплаті
   - Сайт отримує повідомлення
   - Оновлює статус замовлення

5. **Адміністратор бачить замовлення**
   - В адмін-панелі сайту
   - В WHMCS панелі
   - Отримує email-повідомлення

---

## 📝 ДОДАТКОВІ НАЛАШТУВАННЯ WHMCS

### Email Templates:
1. **Setup → Email Templates**
2. Створіть шаблони для:
   - Нове замовлення кондитерки
   - Нове замовлення фаершоу
   - Рахунок створено
   - Оплата отримана

### Payment Gateways:
Увімкніть потрібні платіжні системи:
- Приват24
- WayForPay
- LiqPay
- Stripe
- PayPal

`Setup → Payments → Payment Gateways`

### Webhooks (для зворотного зв'язку):
`Setup → Integration Settings → Webhooks`

Додайте webhook URL:
`https://seiya.com.ua/api/whmcs_callback.php`

---

## 🚨 ВАЖЛИВО!

1. **IP Whitelist**: Додайте IP вашого сервера в WHMCS API settings
2. **SSL Certificate**: WHMCS вимагає HTTPS для API
3. **Permissions**: API credential повинен мати правильні дозволи
4. **Currency**: Встановіть UAH як валюту в WHMCS
5. **Test Mode**: Спочатку тестуйте в тестовому режимі

---

## ✅ ЧЕКЛІСТ

- [ ] Отримав API Identifier
- [ ] Отримав API Secret
- [ ] Створив групу "Seiya Кондитерка"
- [ ] Створив групу "Seiya Фаершоу"
- [ ] Створив продукт для кондитерки (отримав ID)
- [ ] Створив продукт для фаершоу (отримав ID)
- [ ] Заповнив config/whmcs.php
- [ ] Налаштував email templates
- [ ] Увімкнув payment gateways
- [ ] Налаштував webhooks
- [ ] Додав IP в whitelist
- [ ] Перевірив HTTPS

---

## 🔧 ТЕСТУВАННЯ

Після налаштування:
1. Зробіть тестове замовлення на сайті
2. Перевірте, чи з'явився клієнт в WHMCS
3. Перевірте, чи створився order
4. Перевірте, чи створився invoice
5. Спробуйте оплатити тестовий рахунок

---

## 📞 ДОПОМОГА

Якщо щось не працює:
1. Перевірте логи WHMCS: `/path/to/whmcs/logs/`
2. Перевірте логи сайту: `/logs/whmcs.log`
3. Перевірте API credentials
4. Перевірте IP whitelist
5. Переконайтесь що SSL працює

---

Після заповнення всіх даних повідомте мене, і я активую інтеграцію!
