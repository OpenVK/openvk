OpenVK-KB-Heading: messages.markAsRead

# messages.markAsRead

Помечает сообщения в диалоге или беседе как прочитанные.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `message_ids` | string | Список идентификаторов сообщений через запятую. |
| `peer_id` | integer | Идентификатор диалога/беседы (помечает прочитанными сообщения вплоть до `start_message_id`). |
| `start_message_id` | integer | Идентификатор сообщения, начиная с которого сообщения помечаются прочитанными. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного выполнения.

### Пример запроса
```http
POST /method/messages.markAsRead HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&start_message_id=4512&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
