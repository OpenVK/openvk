OpenVK-KB-Heading: messages.getByConversationMessageId

# messages.getByConversationMessageId

Возвращает сообщения по их локальным идентификаторам (`conversation_message_ids`) внутри конкретной беседы/диалога.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор назначения (пользователь `id`, сообщество `-id` или беседа `2000000000 + chat_id`). **Обязательный параметр.** |
| `conversation_message_ids` | string | Список локальных номеров сообщений в беседе через запятую. **Обязательный параметр.** |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. По умолчанию: `"photo_200,online"`. |
| `group_id` | integer | Идентификатор сообщества (если вызов от имени группы). |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — количество найденных сообщений;
* `items` (array) — массив объектов **[Message](/dev/models/message)**;
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — информация о сообществах.

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 4512,
                "conversation_message_id": 42,
                "date": 1696680000,
                "peer_id": 2000000001,
                "from_id": 1,
                "text": "Привет!",
                "out": 1,
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: peer_id and conversation_message_ids required` — Не переданы обязательные параметры. |
| `15` | `Access denied` — Доступ к переписке ограничен. |

### Пример запроса
```http
POST /method/messages.getByConversationMessageId HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&conversation_message_ids=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
