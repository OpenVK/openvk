OpenVK-KB-Heading: audio.searchAlbums

# audio.searchAlbums

Выполняет поиск по общедоступным плейлистам (альбомам) с возможностью фильтрации и сортировки.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `query` | string | Строка поискового запроса. По умолчанию пустая строка `""` (возвращает все плейлисты). |
| `offset` | integer | Смещение относительно начала списка результатов. По умолчанию: `0`. |
| `limit` | integer | Количество возвращаемых результатов. По умолчанию: `25`. |
| `order` | integer | Порядок сортировки: `0` — по дате создания/ID, `1` — по общей продолжительности (`length`), `2` — по количеству прослушиваний (`listens`). По умолчанию: `0`. |
| `from_me` | integer | `1` — искать только среди плейлистов, созданных текущим пользователем, `0` — искать среди всех доступных. По умолчанию: `0`. |
| `drop_private` | integer | `1` — скрывать приватные плейлисты из выборки, `0` — возвращать `null` вместо недоступных. По умолчанию: `0`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — общее число найденных плейлистов;
* `items` (array) — массив объектов плейлистов.

В API версии ниже 5.0 возвращается массив формата `[count, playlist1, playlist2, ...]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/audio.searchAlbums HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

query=Rock&order=2&limit=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 5,
                "owner_id": 1,
                "title": "Classic Rock Essentials",
                "description": "Лучший классический рок",
                "size": 24,
                "length": 5840,
                "created": 1609459200,
                "modified": 1612137600,
                "accessible": true,
                "editable": false,
                "bookmarked": true,
                "listens": 450,
                "cover_url": "/storage/covers/rock.jpg",
                "searchable": true
            }
        ]
    }
}
```
