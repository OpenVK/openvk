OpenVK-KB-Heading: messages.denyMessagesFromGroup

# messages.denyMessagesFromGroup

Запрещает указанному сообществу отправку сообщений текущему пользователю.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества (положительное число). **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного выполнения.

### Пример запроса
```http
POST /method/messages.denyMessagesFromGroup HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
