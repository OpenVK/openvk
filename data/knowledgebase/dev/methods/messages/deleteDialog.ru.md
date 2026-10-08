OpenVK-KB-Heading: messages.deleteDialog

# messages.deleteDialog

Удаляет все сообщения в диалоге или чате (устаревший синоним `messages.deleteConversation`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя при удалении личного диалога. |
| `peer_id` | integer | Идентификатор назначения. |
| `chat_id` | integer | Идентификатор беседы. |
| `offset` | integer | Смещение. |
| `count` | integer | Количество. |

### Результат

Возвращает `1` в случае успешного удаления.

### Пример запроса
```http
POST /method/messages.deleteDialog HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
