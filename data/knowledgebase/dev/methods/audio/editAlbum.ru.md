OpenVK-KB-Heading: audio.editAlbum

# audio.editAlbum

Редактирует название и описание существующего плейлиста (альбома).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `album_id` | integer | Идентификатор редактируемого плейлиста. **Обязательный параметр.** |
| `title` | string | Новое название плейлиста. |
| `description` | string | Новое описание плейлиста. |

### Результат

Возвращает `1`, если хотя бы одно поле было изменено, либо `0`, если изменения отсутствовали.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `404` | `Album not found` — Плейлист не найден. |
| `600` | `Insufficient rights to this album` — Недостаточно прав для редактирования этого плейлиста. |

### Пример запроса
```http
POST /method/audio.editAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=42&title=Обновленное+название&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
