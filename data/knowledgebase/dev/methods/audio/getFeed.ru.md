OpenVK-KB-Heading: audio.getFeed

# audio.getFeed

Возвращает ленту недавно загруженных аудиозаписей платформы (отсортированных в хронологическом порядке добавления) с поддержкой фильтрации по музыкальным жанрам.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `genre_id` | integer | Числовой идентификатор жанра по классификации ВКонтакте (1..22, 1001). |
| `genre_str` | string | Строковое название жанра в OpenVK (например, `Rock`, `Pop`, `Electronic`, `Metal` и др.). |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `count` | integer | Количество аудиозаписей, которое необходимо вернуть. По умолчанию: `100`. |
| `hash` | string | Хэш авторизации потока (опционально). |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — количество возвращенных аудиозаписей;
* `items` (array) — массив объектов аудиозаписей.

В API версии ниже 5.0 возвращается массив формата `[count, audio1, audio2, ...]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `Invalid genre_str` или `Invalid genre ID {genre_id}` — Некорректный идентификатор или название жанра. |

### Пример запроса
```http
POST /method/audio.getFeed HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

genre_str=Electronic&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 10,
        "items": [
            {
                "id": 5,
                "owner_id": 2,
                "artist": "Kraftwerk",
                "title": "The Robots",
                "duration": 372,
                "url": "https://openvk.instance/storage/03/hash3.mp3",
                "manifest": "https://openvk.instance/storage/03/hash3.mpd",
                "keys": {},
                "genre_id": 5,
                "genre_str": "Electronic",
                "global_id": 302,
                "unique_id": "MzAy",
                "added": false,
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
