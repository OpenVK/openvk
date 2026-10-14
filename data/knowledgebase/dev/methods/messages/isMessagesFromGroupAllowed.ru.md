OpenVK-KB-Heading: messages.isMessagesFromGroupAllowed

# messages.isMessagesFromGroupAllowed

Проверяет, разрешена ли отправка сообщений от сообщества указанному пользователю.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества. **Обязательный параметр.** |
| `user_id` | integer | Идентификатор проверяемого пользователя. По умолчанию: текущий пользователь. |

### Результат

Возвращает объект с полем:
* `is_allowed` (integer) — `1`, если сообщения разрешены, `0` — запрещены.

### Пример запроса
```http
POST /method/messages.isMessagesFromGroupAllowed HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "is_allowed": 1
    }
}
```
