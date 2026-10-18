OpenVK-KB-Heading: messages.getById

# messages.getById

Возвращает сообщения по их глобальным идентификаторам.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `message_ids` | string | Список идентификаторов сообщений через запятую (например, `4512,4513`). **Обязательный параметр.** |
| `preview_length` | integer | Максимальная длина текста в символах для предпросмотра. По умолчанию: `0` (полный текст). |
| `extended` | integer | `1` — возвращать профили авторов сообщений и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. По умолчанию: `"photo_200,online"`. |

### Результат

#### API версии 5.0 и выше (v >= 5.0)
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
                "text": "Привет мир!",
                "out": 1,
                "attachments": [],
                "fwd_messages": []
            }
        ]
    }
}
```

#### Устаревшие версии API (v < 5.0)
Возвращает массив `[count, message1, ...]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: message_ids required` — Не передан параметр `message_ids`. |

### Пример запроса
```http
POST /method/messages.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

message_ids=4512&extended=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
