OpenVK-KB-Heading: messages.setActivity

# messages.setActivity

Отправляет статус о том, что текущий пользователь набирает текст или записывает голосовое сообщение в диалоге или беседе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор назначения (пользователь или беседа `2000000000 + chat_id`). |
| `user_id` | integer | Идентификатор пользователя при личном диалоге. |
| `type` | string | Тип активности: `"typing"` (набор текста) или `"audiomessage"` (запись голосового сообщения). По умолчанию: `"typing"`. |
| `group_id` | integer | Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного выполнения.

### Пример запроса
```http
POST /method/messages.setActivity HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&type=typing&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
