OpenVK-KB-Heading: audio.getPopular

# audio.getPopular

Возвращает список наиболее популярных аудиозаписей платформы (отсортированных по количеству прослушиваний) с возможностью фильтрации по музыкальным жанрам.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `genre_id` | integer | Числовой идентификатор жанра по классификации ВКонтакте (`1` — Rock, `2` — Pop, `3` — Rap & Hip-Hop, `4` — Easy Listening, `5` — Dance & House, `6` — Instrumental, `7` — Metal, `8` — Dubstep, `10` — Drum & Bass, `11` — Trance, `12` — Chanson, `13` — Ethnic, `14` — Acoustic & Vocal, `15` — Reggae, `16` — Classical, `17` — Indie Pop, `18` — Other, `19` — Speech, `21` — Alternative, `22` — Disco, `1001` — Jazz & Blues). |
| `genre_str` | string | Строковое название жанра в OpenVK (например, `Rock`, `Pop`, `Electronic`, `Metal`, `Jazz` и др.). |
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
| `8` | `Invalid genre_str` или `Invalid genre ID {genre_id}` — Указан несуществующий жанр. |

### Пример запроса
```http
POST /method/audio.getPopular HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

genre_id=1&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 20,
        "items": [
            {
                "id": 1,
                "owner_id": 1,
                "artist": "Queen",
                "title": "Bohemian Rhapsody",
                "duration": 354,
                "url": "https://openvk.instance/storage/01/hash.mp3",
                "manifest": "https://openvk.instance/storage/01/hash.mpd",
                "keys": {},
                "genre_id": 1,
                "genre_str": "Rock",
                "global_id": 12,
                "unique_id": "MTI=",
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
