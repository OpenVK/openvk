OpenVK-KB-Heading: Руководство: Быстрый старт

# Руководство: Быстрый старт

Это руководство поможет вам быстро развернуть локальное окружение OpenVK для разработки и тестирования.

---

## 1. Подготовка окружения

Убедитесь, что у вас установлены PHP 8.4+, Composer, MySQL/MariaDB и Node.js.

```bash
git clone https://github.com/openvk/openvk /opt/openvk
cd /opt/openvk
composer install
cd Web/static/js && npm install && cd ../../..
cp openvk-example.yml openvk.yml
```

---

## 2. Инициализация базы данных

Создайте базы данных и выполните миграции:

```bash
./openvkctl upgrade
```

---

## 3. Запуск встроенного dev-сервера

Для быстрого локального тестирования без настройки Nginx:

```bash
php -S 127.0.0.1:8000 -t htdocs/
```

Откройте `http://127.0.0.1:8000` в браузере. Вы можете авторизоваться под учетной записью администратора `admin@localhost.localdomain6` / `admin`.
