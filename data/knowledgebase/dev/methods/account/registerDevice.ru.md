OpenVK-KB-Heading: account.registerDevice

# account.registerDevice

Регистрирует устройство для получения Push-уведомлений.

> **Примечание (заглушка совместимости):** Данный метод реализован как заглушка для совместимости с мобильными клиентами VK — параметры принимаются, но регистрация не сохраняется, метод всегда возвращает `1`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `token` | string | Идентификатор устройства для доставки Push-уведомлений (Push-токен). **Обязательный параметр.** |
| `device_model` | string | Модель устройства пользователя (например `iPhone 13` или `Pixel 7`). |
| `device_year` | string | Год выпуска устройства. |
| `system_version` | string | Версия операционной системы устройства. |
| `settings` | string | Настройки Push-уведомлений в формате сериализованной JSON-строки. |

### Результат
Возвращает `1` в случае успешного вызова.

### Пример запроса
```http
POST /method/account.registerDevice HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

token=DEVICE_PUSH_TOKEN_HERE&device_model=Pixel+7&system_version=Android+14&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
