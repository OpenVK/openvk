OpenVK-KB-Heading: photos.createAlbum

# photos.createAlbum

Создает новый пустой фотоальбом у текущего пользователя или в управляемом сообществе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `title` | string | **Обязательный параметр.** Название фотоальбома. |
| `group_id` | integer | Идентификатор сообщества, в котором создается альбом. Если не указан или равен `0`, альбом создается у текущего пользователя. |
| `description` | string | Описание фотоальбома. По умолчанию пусто. |

### Результат

Возвращает объект созданного фотоальбома:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор созданного альбома. |
| `aid` | integer | Идентификатор альбома (для совместимости). |
| `thumb_id` | integer | Идентификатор фотографии-обложки альбома (`0`, если альбом пуст). |
| `owner_id` | integer | Идентификатор владельца альбома (положительное число — пользователь, отрицательное — сообщество). |
| `title` | string | Название альбома. |
| `description` | string | Описание альбома. |
| `created` | integer | Дата создания альбома (Unix timestamp). |
| `updated` | integer | Дата последнего обновления альбома (Unix timestamp). |
| `size` | integer | Количество фотографий в альбоме (при создании `0`). |
| `privacy` | integer | Уровень приватности альбома. |
| `privacy_comment` | integer | Уровень приватности комментирования альбома. |
| `upload_by_admins_only` | integer | `1` — загрузка доступна только администраторам. |
| `comments_disabled` | integer | `0` — комментарии включены, `1` — отключены. |
| `can_upload` | integer | `1`, если текущий пользователь может загружать фото в альбом. |
| `thumb_src` | string | URL обложки альбома по умолчанию. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Нет прав на создание альбома в указанном сообществе. |

### Пример запроса
```http
POST /method/photos.createAlbum HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

title=Мой%20новый%20альбом&description=Фотографии%20с%20отпуска&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 1,
        "aid": 1,
        "thumb_id": 0,
        "owner_id": 1,
        "title": "Мой новый альбом",
        "description": "Фотографии с отпуска",
        "created": 1609459200,
        "updated": 1609459200,
        "size": 0,
        "privacy": 0,
        "privacy_comment": 1,
        "upload_by_admins_only": 1,
        "comments_disabled": 0,
        "can_upload": 1,
        "thumb_src": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
    }
}
```
