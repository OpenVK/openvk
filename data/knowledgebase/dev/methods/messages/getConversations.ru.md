OpenVK-KB-Heading: messages.getConversations

# messages.getConversations

Возвращает список бесед и диалогов текущего пользователя в современном формате (v >= 5.80).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `offset` | integer | Смещение относительно начала списка бесед. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых бесед (максимум `200`). По умолчанию: `20`. |
| `filter` | string | Фильтр: `"all"` — все беседы, `"unread"` — только с непрочитанными сообщениями, `"important"` — помеченные важными, `"unanswered"` — неотвеченные. По умолчанию: `"all"`. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. По умолчанию: `"photo_200,online"`. |
| `group_id` | integer | Идентификатор сообщества (если вызов от имени группы). |

### Результат

Возвращает объект, содержащий:
* `count` (integer) — общее количество бесед, соответствующих фильтру;
* `unread_count` (integer, опционально) — общее число непрочитанных бесед;
* `items` (array) — массив объектов, каждый из которых содержит:
  * `conversation` (object) — объект беседы **[Conversation](/dev/models/conversation)**;
  * `last_message` (object) — последнее сообщение **[Message](/dev/models/message)** в беседе;
* `profiles` (array, опционально) — профили пользователей (при `extended=1`);
* `groups` (array, опционально) — профили сообществ (при `extended=1`).

### Пример запроса
```http
POST /method/messages.getConversations HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=all&count=20&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "unread_count": 0,
        "items": [
            {
                "conversation": {
                    "peer": {
                        "id": 2000000001,
                        "type": "chat",
                        "local_id": 1
                    },
                    "in_read": 4512,
                    "out_read": 4512,
                    "unread_count": 0,
                    "important": false,
                    "chat_settings": {
                        "title": "OpenVK Developers",
                        "members_count": 3,
                        "state": "in"
                    }
                },
                "last_message": {
                    "id": 4512,
                    "conversation_message_id": 42,
                    "date": 1696680000,
                    "peer_id": 2000000001,
                    "from_id": 1,
                    "text": "Привет разработчикам!",
                    "out": 1,
                    "attachments": []
                }
            }
        ]
    }
}
```
