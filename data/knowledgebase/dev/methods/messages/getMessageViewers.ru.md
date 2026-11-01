OpenVK-KB-Heading: messages.getMessageViewers

# messages.getMessageViewers

Возвращает список участников беседы, прочитавших конкретное сообщение.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор беседы (`2000000000 + chat_id`). **Обязательный параметр.** |
| `conversation_message_id` | integer | Локальный номер сообщения в беседе. |
| `message_id` | integer | Глобальный идентификатор сообщения. |
| `extended` | integer | `1` — возвращать профили пользователей. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |

### Результат

Возвращает объект, содержащий:
* `user_ids` (array) — массив идентификаторов пользователей, прочитавших сообщение;
* `profiles` (array, опционально) — массив профилей пользователей (при `extended=1`).

### Пример запроса
```http
POST /method/messages.getMessageViewers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&conversation_message_id=42&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
