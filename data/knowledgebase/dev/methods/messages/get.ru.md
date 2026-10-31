OpenVK-KB-Heading: messages.get

# messages.get

Возвращает список входящих или исходящих сообщений текущего пользователя.

> **Примечание:** В современных приложениях (API 5.80+) рекомендуется использовать методы **[messages.getConversations](/dev/methods/messages/getConversations)** или **[messages.getHistory](/dev/methods/messages/getHistory)**.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `out` | integer | `1` — возвращать исходящие сообщения, `0` — входящие. По умолчанию: `0`. |
| `offset` | integer | Смещение относительно начала списка сообщений. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых сообщений (максимум `200`). По умолчанию: `20`. |
| `time_offset` | integer | Максимальное смещение по времени (в секундах) относительно текущего момента. |
| `filters` | integer | Маска фильтрации сообщений: `1` — непрочитанные, `2` — не из чатов, `4` — от друзей. |
| `preview_length` | integer | Количество символов для предпросмотра текста (`0` — полный текст). |
| `last_message_id` | integer | Идентификатор сообщения, начиная с которого возвращаются сообщения. |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Список дополнительных полей профилей при `extended=1`. |

---

### Результат

#### API версии 5.0 и выше (v >= 5.0)
Возвращает объект, содержащий:
* `count` (integer) — общее количество сообщений;
* `items` (array) — массив объектов **[Message](/dev/models/message)**;
* `profiles` (array, опционально) — профили пользователей (при `extended=1`);
* `groups` (array, опционально) — информация о сообществах (при `extended=1`).

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 4512,
                "date": 1696680000,
                "out": 0,
                "user_id": 1,
                "from_id": 1,
                "read_state": 1,
                "title": "Приветствие",
                "body": "Привет!",
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

#### Устаревшие версии API (v < 5.0)
Возвращает массив, где первым элементом является общее количество сообщений, за которым следуют объекты сообщений со свойствами `mid` и `uid`:
```json
{
    "response": [
        1,
        {
            "mid": 4512,
            "date": 1696680000,
            "out": 0,
            "uid": 1,
            "read_state": 1,
            "title": "Приветствие",
            "body": "Привет!",
            "attachments": []
        }
    ]
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/messages.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

out=0&count=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
