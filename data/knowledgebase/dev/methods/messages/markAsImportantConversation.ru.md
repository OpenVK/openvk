OpenVK-KB-Heading: messages.markAsImportantConversation

# messages.markAsImportantConversation

Помечает беседу или диалог как важный или снимает отметку.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`) или диалога. **Обязательный параметр.** |
| `important` | integer | `1` — пометить как важную, `0` — снять отметку. По умолчанию: `1`. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного выполнения.

### Пример запроса
```http
POST /method/messages.markAsImportantConversation HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&important=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
