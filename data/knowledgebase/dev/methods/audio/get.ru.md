OpenVK-KB-Heading: audio.get

# audio.get

Возвращает список аудиозаписей пользователя или сообщества с возможностью фильтрации, выборки по альбомам или списку идентификаторов, а также поддержкой воспроизведения в случайном порядке (Knuth shuffle) с сохранением состояния перемешивания (`shuffle_seed`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца аудиозаписей (положительное число — пользователь, отрицательное — сообщество). По умолчанию: `0` (текущий пользователь). |
| `album_id` | integer | Идентификатор альбома (плейлиста), из которого необходимо получить аудиозаписи. Если передан, возвращаются треки указанного альбома. |
| `audio_ids` | string | Список идентификаторов аудиозаписей через запятую (например, `1_1,1_2` или `1,2`). Если передан, возвращает только указанные треки. |
| `need_user` | integer | `1` — возвращать объект `user` с информацией о владельце/загрузчике аудиозаписи, `0` — не возвращать. По умолчанию: `1`. |
| `offset` | integer | Смещение относительно начала списка аудиозаписей. По умолчанию: `0`. |
| `count` | integer | Количество аудиозаписей, которое необходимо вернуть. По умолчанию: `100`. |
| `uploaded_only` | integer | `1` — возвращать только аудиозаписи, непосредственно загруженные данным пользователем (не добавленные из чужих страниц). Доступно только для `owner_id > 0` и текущего пользователя. По умолчанию: `0`. |
| `shuffle` | integer | `1` — включить алгоритмическое перемешивание аудиозаписей, `0` — вернуть в обычном порядке. По умолчанию: `0`. |
| `need_seed` | integer | `1` — сгенерировать криптографически стойкий случайный сид перемешивания при `shuffle=1`. По умолчанию: `0`. |
| `shuffle_seed` | string | Base64-строка сида для детерминированного воспроизведения ранее перемешанного списка. |
| `hash` | string | Хэш авторизации потока (опционально). |

### Результат

В API версии 5.0 и выше возвращает объект со следующими полями:
* `count` (integer) — общее число аудиозаписей в выборке;
* `items` (array) — массив объектов аудиозаписей;
* `shuffle_seed` (string, опционально) — сид перемешивания в формате Base64, если был передан параметр `shuffle=1`.

В API версии ниже 5.0 возвращается прямой массив объектов аудиозаписей.

Каждый объект аудиозаписи содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Виртуальный идентификатор аудиозаписи на странице владельца. |
| `owner_id` | integer | Идентификатор владельца аудиозаписи. |
| `artist` | string | Исполнитель аудиозаписи. |
| `title` | string | Название трека. |
| `duration` | integer | Длительность аудиозаписи в секундах. |
| `url` | string | Прямая ссылка на MP3-файл трека. |
| `manifest` | string | Ссылка на манифест MPEG-DASH (`.mpd`) для потокового воспроизведения. |
| `keys` | object | Ключи DRM ClearKey (если настроены). |
| `genre_id` | integer | Идентификатор жанра по классификации ВКонтакте (1..22, 1001; по умолчанию `18` — Other). |
| `genre_str` | string | Название жанра в системе OpenVK (например, `Rock`, `Electronic`, `Pop`). |
| `global_id` | integer | Глобальный целочисленный идентификатор в базе данных OpenVK. |
| `unique_id` | string | Глобальный уникальный идентификатор в Base64. |
| `lyrics_id` | integer | Идентификатор текста песни (если текст добавлен). |
| `album` | object | Объект альбома/плейлиста, к которому привязан трек (если привязан). |
| `album_id` | string | Идентификатор альбома в формате `owner_id_playlist_id`. |
| `added` | boolean | Добавлен ли данный трек в библиотеку текущего авторизованного пользователя. |
| `editable` | boolean | Имеет ли текущий пользователь право редактировать аудиозапись. |
| `searchable` | boolean | Доступен ли трек для глобального поиска. |
| `explicit` | boolean | Помечен ли трек как ненормативный (Explicit / NSFW). |
| `withdrawn` | boolean | Изъят ли трек по требованию правообладателя. |
| `ready` | boolean | Готов ли трек к воспроизведению (завершена ли обработка медиафайла). |
| `listens` | integer | Количество прослушиваний трека (возвращается, если `editable = true`). |
| `user` | object | Объект с информацией о владельце (`id`, `photo`, `name`, `name_gen`), если передан `need_user=1`. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `uploaded_only can only be used with owner_id > 0` или неверный синтаксис `audio_ids`. |
| `15` | `Access denied: this user chose to hide his audios` — Доступ к аудиозаписям ограничен настройками приватности. |
| `50` | `Invalid user` — Указанный пользователь не найден. |
| `404` | `album_id invalid` — Указанный альбом не найден. |
| `600` | `Can't open this album for reading` — Альбом скрыт или нет прав на просмотр. |

### Пример запроса
```http
POST /method/audio.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=2&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
                "listens": 42
            },
            {
                "id": 2,
                "owner_id": 1,
                "artist": "Darude",
                "title": "Sandstorm",
                "duration": 225,
                "url": "https://openvk.instance/storage/02/hash2.mp3",
                "manifest": "https://openvk.instance/storage/02/hash2.mpd",
                "keys": {},
                "genre_id": 5,
                "genre_str": "Electronic",
                "global_id": 106,
                "unique_id": "MTA2",
                "added": true,
                "editable": true,
                "searchable": true,
                "explicit": false,
                "withdrawn": false,
                "ready": true,
                "listens": 18
            }
        ]
    }
}
```
