OpenVK-KB-Heading: messages.getDialogs

# messages.getDialogs

Устаревший метод для получения списка диалогов текущего пользователя.

> **Важно:** Начиная с версии API 5.80, используйте метод **[messages.getConversations](/dev/methods/messages/getConversations)**.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `offset` | integer | Смещение относительно начала списка диалогов. По умолчанию: `0`. |
| `count` | integer | Количество диалогов в ответе (максимум `200`). По умолчанию: `20`. |
| `unread` | integer | `1` — только непрочитанные диалоги, `0` — все. По умолчанию: `0`. |
| `preview_length` | integer | Ограничение на длину текста сообщения (`0` — полный текст). |
| `extended` | integer | `1` — возвращать профили пользователей и сообществ. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей при `extended=1`. |

---

### Результат

#### API версий 5.0 — 5.79 (5.0 <= v < 5.80)
Возвращает объект, содержащий:
* `count` (integer) — общее количество диалогов;
* `items` (array) — массив последних сообщений диалогов (для чатов содержит поля `chat_id`, `chat_active`, `users_count`, `admin_id`);
* `profiles` (array, опционально) — профили пользователей;
* `groups` (array, опционально) — информация о сообществах.

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 4512,
                "date": 1696680000,
                "out": 1,
                "user_id": 1,
                "read_state": 1,
                "title": "OpenVK Developers",
                "body": "Привет разработчикам!",
                "chat_id": 1,
                "chat_active": [1, 2, 3],
                "users_count": 3,
                "admin_id": 1,
                "attachments": []
            }
        ]
    }
}
```

#### Устаревшие версии API (v < 5.0)
Возвращает массив, где первым элементом является общее количество диалогов, за которым следуют объекты сообщений с полями `mid` и `uid`:
```json
{
    "response": [
        1,
        {
            "mid": 4512,
            "date": 1696680000,
            "out": 1,
            "uid": 1,
            "read_state": 1,
            "title": "OpenVK Developers",
            "body": "Привет разработчикам!",
            "chat_id": 1,
            "attachments": []
        }
    ]
}
```

### Пример запроса
```http
POST /method/messages.getDialogs HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&access_token=YOUR_ACCESS_TOKEN&v=5.78
```
