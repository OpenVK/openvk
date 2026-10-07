OpenVK-KB-Heading: audio.search

# audio.search

Осуществляет полнотекстовый поиск по общедоступным аудиозаписям платформы с возможностью фильтрации по исполнителю, наличию текста песни и сортировкой.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | Поисковый запрос (название трека, имя исполнителя или текст песни). **Обязательный параметр.** |
| `performer_only` | integer | `1` — искать только по имени исполнителя (автора), `0` — искать везде. По умолчанию: `0`. |
| `lyrics` | integer | `1` — возвращать только аудиозаписи с прикрепленным текстом песни, `0` — без ограничений. По умолчанию: `0`. |
| `sort` | integer | Порядок сортировки результатов: `2` — по популярности (количеству прослушиваний), `1` — по длительности, `0` — по дате добавления. По умолчанию: `2`. |
| `offset` | integer | Смещение относительно начала списка результатов. По умолчанию: `0`. |
| `count` | integer | Количество аудиозаписей в ответе (от `1` до `300`). По умолчанию: `30`. |
| `auto_complete` | integer | *(Не поддерживается)* Должно быть `0`. При передаче `1` возвращает ошибку 10. |
| `search_own` | integer | *(Не поддерживается)* Должно быть `0`. При передаче `1` возвращает ошибку 10. |
| `hash` | string | Хэш авторизации потока (опционально). |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — количество найденных аудиозаписей;
* `items` (array) — массив объектов аудиозаписей.

В API версии ниже 5.0 возвращается массив, где первый элемент — количество результатов, а последующие — объекты аудиозаписей (`[count, audio1, audio2, ...]`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `count is invalid: {count}` — Параметр `count` меньше 1 или больше 300. |
| `10` | `auto_complete and search_own are not supported` — Переданы неподдерживаемые флаги. |

### Пример запроса
```http
POST /method/audio.search HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Astley&performer_only=1&sort=2&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "artist": "Rick Astley",
                "title": "Never Gonna Give You Up",
                "duration": 213,
                "url": "https://openvk.instance/storage/01/hash.mp3",
                "manifest": "https://openvk.instance/storage/01/hash.mpd",
                "keys": {},
                "genre_id": 2,
                "genre_str": "Pop",
                "global_id": 105,
                "unique_id": "MTA1",
                "lyrics_id": 105,
                "added": true,
                "editable": false,
                "searchable": true,
                "explicit": false,
                "withdrawn": false,
                "ready": true
            }
        ]
    }
}
```
