OpenVK-KB-Heading: messages.markAsAnsweredConversation

# messages.markAsAnsweredConversation

Помечает беседу или диалог как отвеченный или снимает отметку.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`) или диалога. **Обязательный параметр.** |
| `answered` | integer | `1` — пометить как отвеченную, `0` — пометить как неотвеченную. По умолчанию: `1`. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного выполнения.

### Пример запроса
```http
POST /method/messages.markAsAnsweredConversation HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&answered=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
