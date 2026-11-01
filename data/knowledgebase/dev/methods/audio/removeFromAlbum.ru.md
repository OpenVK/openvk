OpenVK-KB-Heading: audio.removeFromAlbum

# audio.removeFromAlbum

Удаляет указанные аудиозаписи из плейлиста (альбома).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `album_id` | integer | Идентификатор плейлиста. **Обязательный параметр.** |
| `audio_ids` | string | Список идентификаторов аудиозаписей через запятую (от 1 до 1000). **Обязательный параметр.** |

### Результат

Возвращает `1` в случае успешного удаления треков из альбома.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `audio_ids must contain at least 1 audio and at most 1000` — Количество треков вне допустимого диапазона. |
| `404` | `Album not found` — Плейлист не найден. |
| `600` | `Insufficient rights to this album` — Недостаточно прав для управления данным плейлистом. |

### Пример запроса
```http
POST /method/audio.removeFromAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=42&audio_ids=1_1,1_2&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
