OpenVK-KB-Heading: notes.getComments

# notes.getComments

Возвращает список комментариев к заметке.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `note_id` | integer | **Обязательный параметр**. Идентификатор заметки. |
| `owner_id` | integer | **Обязательный параметр**. Идентификатор владельца заметки. |
| `sort` | integer | Порядок сортировки (по умолчанию: `1`). |
| `offset` | integer | Смещение относительно начала списка комментариев. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых комментариев. По умолчанию: `100`. |

### Результат

В API версии 5.0 и выше возвращает объект:
* `count` (integer) — общее число комментариев к заметке;
* `items` (array) — массив объектов комментариев (`id`, `uid` / `from_id`, `date`, `text`, `reply_to_uid`, `reply_to_cid`).

В устаревших версиях API (до версии 5.0) возвращает массив, первым элементом которого является `count`, а затем список комментариев.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Заметка не найдена, удалена или доступ к ней ограничен. |

### Пример запроса
```http
POST /method/notes.getComments HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "uid": 2,
                "date": 1700001000,
                "text": "Отличная заметка!"
            }
        ]
    }
}
```
