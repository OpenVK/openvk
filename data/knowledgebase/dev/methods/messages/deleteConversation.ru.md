OpenVK-KB-Heading: messages.deleteConversation

# messages.deleteConversation

Удаляет всю переписку в беседе или диалоге для текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор назначения (пользователь `id`, сообщество `-id` или беседа `2000000000 + chat_id`). |
| `user_id` | integer | Идентификатор пользователя при удалении личного диалога. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного удаления.

### Пример запроса
```http
POST /method/messages.deleteConversation HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
