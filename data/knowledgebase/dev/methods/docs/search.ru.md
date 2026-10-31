OpenVK-KB-Heading: docs.search

# docs.search

Осуществляет поиск по общедоступным документам платформы по названию файла и тегам.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | Поисковый запрос по названию документа (до 512 символов). По умолчанию: пустая строка `""`. |
| `search_own` | integer | `1` — искать только среди документов текущего пользователя, `-1` или `0` — искать среди всех доступных документов. По умолчанию: `-1`. |
| `type` | integer | Фильтрация по типу документа (`1` — текст, `2` — архивы, `3` — GIF, `4` — изображения, `5` — аудио, `6` — видео, `7` — книги, `8` — другое, `0` — без фильтра). По умолчанию: `0`. |
| `tags` | string | Поиск по тегам (до 512 символов). |
| `offset` | integer | Смещение относительно начала списка результатов. По умолчанию: `0`. |
| `count` | integer | Количество документов в ответе. По умолчанию: `30`. |
| `return_tags` | integer | `1` — возвращать теги для каждого документа в ответе. По умолчанию: `0`. |
| `order` | integer | Порядок сортировки. По умолчанию: `-1`. |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее количество найденных документов;
* `items` (array) — массив объектов документов.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `One of the parameters specified was missing or invalid: q should be not more 512 letters length` — Длина поискового запроса превышает 512 символов. |

### Пример запроса
```http
POST /method/docs.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=report&type=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 12,
                "owner_id": 1,
                "true_owner_id": 1,
                "title": "financial_report.xlsx",
                "size": 1048576,
                "ext": "xlsx",
                "url": "https://openvk.instance/blob_b2/hash.xlsx",
                "date": 1609459200,
                "type": 1,
                "is_hidden": 0,
                "is_licensed": 0,
                "is_unsafe": 0,
                "folder_id": 3,
                "access_key": "4f8a1c9e2b3d4f5a6b",
                "can_manage": true
            }
        ]
    }
}
```
