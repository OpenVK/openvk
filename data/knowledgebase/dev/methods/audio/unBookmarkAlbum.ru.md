OpenVK-KB-Heading: audio.unBookmarkAlbum

# audio.unBookmarkAlbum

Удаляет плейлист (альбом) из закладок текущего авторизованного пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор плейлиста. **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного удаления из закладок, либо `0`, если плейлист не находился в закладках.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `404` | `Not found` — Плейлист не найден. |
| `600` | `Access error` — Доступ к плейлисту ограничен. |

### Пример запроса
```http
POST /method/audio.unBookmarkAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
