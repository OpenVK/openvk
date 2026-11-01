OpenVK-KB-Heading: photos.getWallUploadServer

# photos.getWallUploadServer

Возвращает адрес сервера для загрузки фотографии на стену пользователя или сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | Идентификатор сообщества, на стену которого планируется прикрепить фотографию. Если параметр не передан, загрузка осуществляется для стены текущего пользователя. |

### Результат

Возвращает объект с полями:
| Поле | Тип | Описание |
| --- | --- | --- |
| `upload_url` | string | URL для отправки POST-запроса с файлом фотографии (в поле `photo`). |
| `album_id` | integer | Идентификатор альбома «Фотографии на стене». |
| `user_id` | integer | Идентификатор текущего пользователя. |

> **Примечание:** После отправки файла на `upload_url` сервер возвращает параметры `photo`, `server` и `hash`, которые необходимо передать в метод [photos.saveWallPhoto](/dev/methods/photos/saveWallPhoto).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `200` | `Access: Club can't be 'written' by user` — Нет прав на публикацию на стене сообщества. |
| `404` | `Club not found` — Указанное сообщество не найдено. |

### Пример запроса
```http
POST /method/photos.getWallUploadServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "upload_url": "https://openvk.instance/upload/photo/wall_hash...?info...",
        "album_id": 2,
        "user_id": 1
    }
}
```
