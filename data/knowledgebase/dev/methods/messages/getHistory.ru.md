OpenVK-KB-Heading: messages.getHistory

# messages.getHistory

Возвращает историю сообщений для указанного диалога или групповой беседы (чата).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `peer_id` | integer | Идентификатор назначения (пользователь `id`, сообщество `-id` или беседа `2000000000 + chat_id`). |
| `user_id` | integer | Идентификатор пользователя при запросе личной переписки. |
| `chat_id` | integer | Идентификатор групповой беседы (`1...N`). |
| `offset` | integer | Смещение относительно начала выборки истории. По умолчанию: `0`. |
| `count` | integer | Количество сообщений в ответе (максимум `200`). По умолчанию: `20`. |
| `start_message_id` | integer | Идентификатор сообщения, начиная с которого возвращается история. |
| `rev` | integer | Порядок сортировки: `1` — хронологический (старые первыми), `0` — обратный (новые первыми). По умолчанию: `0`. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `preview_length` | integer | Количество символов текста для предпросмотра (`0` — полный текст). |
| `fields` | string | Список дополнительных полей профилей при `extended=1`. По умолчанию: `"photo_200,online"`. |

---

### Результат

#### API версии 5.80 и выше (v >= 5.80)
Возвращает объект со следующими полями:
* `count` (integer) — общее число сообщений в переписке;
* `items` (array) — массив современных объектов **[Message](/dev/models/message)**;
* `conversations` (array, опционально) — массив объектов **[Conversation](/dev/models/conversation)**;
* `profiles` (array, опционально) — профили участников (при `extended=1`);
* `groups` (array, опционально) — профили сообществ (при `extended=1`).

```json
{
    "response": {
        "count": 100,
        "items": [
            {
                "id": 4512,
                "conversation_message_id": 42,
                "date": 1696680000,
                "peer_id": 2000000001,
                "from_id": 1,
                "text": "Привет разработчикам!",
                "out": 1,
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

#### API версий 5.0 — 5.79 (5.0 <= v < 5.80)
Возвращает объект:
* `count` (integer) — общее количество сообщений;
* `items` (array) — массив объектов сообщений с полями `body`, `read_state`, `user_id` и др.

#### Устаревшие версии API (v < 5.0)
Возвращает массив, где первым элементом является общее количество сообщений, за которым следуют объекты с `mid` и `uid`:
```json
{
    "response": [
        100,
        {
            "mid": 4512,
            "date": 1696680000,
            "out": 1,
            "uid": 1,
            "read_state": 1,
            "body": "Привет разработчикам!",
            "attachments": []
        }
    ]
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: peer_id, user_id or chat_id required` — Не указан получатель/диалог. |
| `15` | `Access denied` — Доступ к переписке ограничен. |

### Пример запроса
```http
POST /method/messages.getHistory HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

peer_id=2000000001&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
