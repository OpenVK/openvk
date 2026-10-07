OpenVK-KB-Heading: audio.restore

# audio.restore

Восстанавливает удаленную аудиозапись в коллекцию пользователя или сообщества и возвращает ее объект.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `audio_id` | integer | Виртуальный идентификатор аудиозаписи. **Обязательный параметр.** |
| `owner_id` | integer | Идентификатор владельца аудиозаписи. **Обязательный параметр.** |
| `group_id` | integer | Идентификатор сообщества (если восстановление выполняется в сообщество). |
| `hash` | string | Хэш авторизации потока (опционально). |

### Результат

Возвращает объект восстановленной аудиозаписи со всеми стандартными полями (`id`, `owner_id`, `artist`, `title`, `duration`, `url`, `manifest`, `keys`, `genre_id`, `genre_str`, `global_id`, `unique_id`, `added`, `editable`, `ready`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `201` | `Access denied to audio` — Доступ к аудиозаписи ограничен. |
| `203` | `Insufficient rights to this group` — Недостаточно прав для работы с сообществом. |
| `300` | `Album is full` — Превышен лимит аудиозаписей. |
| `404` | `Not found` / `Invalid group_id` — Аудиозапись или сообщество не найдены. |

### Пример запроса
```http
POST /method/audio.restore HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

audio_id=45&owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 45,
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
        "added": true,
        "editable": true,
        "searchable": true,
        "explicit": false,
        "withdrawn": false,
        "ready": true
    }
}
```
