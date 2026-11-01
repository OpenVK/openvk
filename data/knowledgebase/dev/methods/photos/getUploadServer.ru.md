OpenVK-KB-Heading: photos.getUploadServer

# photos.getUploadServer

Возвращает адрес сервера для загрузки фотографий в фотоальбом пользователя или сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `album_id` | integer | Идентификатор фотоальбома, в который необходимо загрузить фотографии. |

### Результат

Возвращает объект с полями:
| Поле | Тип | Описание |
| --- | --- | --- |
| `upload_url` | string | URL-адрес для загрузки фотографии (HTTP POST multipart/form-data с полем `photo`). |
| `album_id` | integer | Идентификатор альбома (если был передан). |
| `user_id` | integer | Идентификатор текущего пользователя. |

> **Примечание:** После отправки файла на полученный `upload_url` сервер возвращает JSON-ответ, содержащий поля `photos_list`, `album_id` и `hash`. Эти данные необходимо передать в метод [photos.save](/dev/methods/photos/save) для сохранения фотографий.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/photos.getUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/a1b2c3d4e5f6?abc123pack...",
        "album_id": 1,
        "user_id": 1
    }
}
```
