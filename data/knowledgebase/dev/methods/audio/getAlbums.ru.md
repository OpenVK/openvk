OpenVK-KB-Heading: audio.getAlbums

# audio.getAlbums

Возвращает список плейлистов (альбомов) пользователя или сообщества.

> **Примечание:** Метод **audio.getPlaylists** является полным синонимом (alias) метода **audio.getAlbums**.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца альбомов (положительное число — ID пользователя, отрицательное — ID сообщества). По умолчанию: `0` (текущий пользователь). |
| `offset` | integer | Смещение относительно начала списка альбомов. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых альбомов (максимум 100). По умолчанию: `50`. |
| `drop_private` | integer | `1` — исключать скрытые/приватные плейлисты из выдачи, `0` — возвращать `null` для недоступных плейлистов. По умолчанию: `1`. |

### Результат

В API версии 5.0 и выше возвращает объект со следующими полями:
* `count` (integer) — количество плейлистов;
* `items` (array) — массив объектов плейлистов (альбомов).

В API версии ниже 5.0 возвращается массив формата `[count, playlist1, playlist2, ...]`.

Каждый объект плейлиста содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор плейлиста. |
| `owner_id` | integer | Идентификатор владельца плейлиста. |
| `title` | string | Название плейлиста. |
| `description` | string | Описание плейлиста. |
| `size` | integer | Количество аудиозаписей в плейлисте. |
| `length` | integer | Общая продолжительность всех треков в секундах. |
| `created` | integer | Время создания (Unix timestamp). |
| `modified` | integer | Время последнего редактирования (Unix timestamp или `null`). |
| `accessible` | boolean | Доступен ли плейлист для просмотра текущему пользователю. |
| `editable` | boolean | Доступен ли плейлист для редактирования текущему пользователю. |
| `bookmarked` | boolean | Добавлен ли плейлист в закладки текущего пользователя. |
| `listens` | integer | Общее количество прослушиваний плейлиста. |
| `cover_url` | string | Ссылка на обложку плейлиста. |
| `searchable` | boolean | Доступен ли плейлист в общем поиске. |
| `thumb` | object | Объект с размерами и ссылками на варианты обложки (если установлена фото-обложка). |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `50` | `Invalid user` или `Access to playlists denied` — Пользователь не найден или ограничил доступ к аудиозаписям. |

### Пример запроса
```http
POST /method/audio.getAlbums HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
                "title": "Любимая музыка",
                "description": "Лучшие треки всех времен",
                "size": 15,
                "length": 3420,
                "created": 1609459200,
                "modified": 1612137600,
                "accessible": true,
                "editable": true,
                "bookmarked": false,
                "listens": 120,
                "cover_url": "/storage/covers/playlist_1.jpg",
                "searchable": true
            }
        ]
    }
}
```
