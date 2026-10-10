OpenVK-KB-Heading: audio.deleteAlbum

# audio.deleteAlbum

Удаляет плейлист (альбом).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `album_id` | integer | Идентификатор удаляемого плейлиста. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного удаления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `404` | `Album not found` — Плейлист не найден. |
| `600` | `Insufficient rights to this album` — Недостаточно прав для удаления этого плейлиста. |

### Пример запроса
```http
POST /method/audio.deleteAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
