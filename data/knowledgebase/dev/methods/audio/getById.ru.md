OpenVK-KB-Heading: audio.getById

# audio.getById

Возвращает информацию об одной или нескольких аудиозаписях по их идентификаторам. Метод поддерживает получение до 6000 аудиозаписей за один вызов.

Идентификаторы могут быть указаны в различных форматах:
* `owner_id_vid` — связка ID владельца и виртуального ID (например, `1_42`);
* `id` — глобальный числовой идентификатор трека в базе данных (например, `105`);
* `unique_id` — Base64-представление глобального ID (например, `MTA1`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `audios` | string | Список идентификаторов аудиозаписей через запятую (например, `1_1,1_2,105`). Максимальное количество: 6000. **Обязательный параметр.** |
| `need_user` | integer | `1` — возвращать объект `user` с информацией о владельце аудиозаписи (`id`, `photo`, `name`, `name_gen`), `0` — не возвращать. По умолчанию: `0`. |
| `hash` | string | Хэш авторизации потока (опционально). |

### Результат

* В версиях API **5.138 и выше** возвращает объект с полями:
  * `count` (integer) — количество найденных аудиозаписей;
  * `items` (array) — массив объектов аудиозаписей.
* В версиях API **до 5.138** возвращает прямой массив объектов аудиозаписей.

Каждый объект аудиозаписи содержит подробные поля: `id`, `owner_id`, `artist`, `title`, `duration`, `url`, `manifest`, `keys`, `genre_id`, `genre_str`, `global_id`, `unique_id`, `lyrics_id`, `album`, `added`, `editable`, `searchable`, `explicit`, `withdrawn`, `ready`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `Invalid audio {id}` — Некорректный синтаксис идентификатора аудиозаписи. |
| `201` | `Access denied to audio({id})` — Доступ к аудиозаписи ограничен настройками приватности владельца. |
| `404` | `Audio not found` — Аудиозапись не найдена. |
| `1980` | `Can't get more than 6000 audios at once` — Превышен лимит в 6000 треков. |

### Пример запроса
```http
POST /method/audio.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audios=1_1,1_2&need_user=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 2,
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
                "editable": true,
                "searchable": true,
                "explicit": false,
                "withdrawn": false,
                "ready": true,
                "user": {
                    "id": 1,
                    "photo": "/storage/avatars/1.jpg",
                    "name": "Павел Дуров",
                    "name_gen": "Павла Дурова"
                }
            }
        ]
    }
}
```
