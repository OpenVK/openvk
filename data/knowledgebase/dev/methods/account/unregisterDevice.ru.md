OpenVK-KB-Heading: account.unregisterDevice

# account.unregisterDevice

Отменяет регистрацию устройства для получения Push-уведомлений.

> **Примечание (заглушка совместимости):** Данный метод реализован как заглушка для совместимости с клиентами VK и всегда возвращает `1`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `token` | string | Идентификатор устройства (Push-токен), регистрацию которого необходимо отменить. **Обязательный параметр.** |

### Результат
Возвращает `1` в случае успешного вызова.

### Пример запроса
```http
POST /method/account.unregisterDevice HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

token=DEVICE_PUSH_TOKEN_HERE&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
