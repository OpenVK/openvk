OpenVK-KB-Heading: photos.getById

# photos.getById

Возвращает информацию о фотографиях по их идентификаторам.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `photos` | string | **Обязательный параметр.** Список идентификаторов фотографий через запятую в формате `owner_id_photo_id` или `owner_id_photo_id_access_key` (максимум 78). |
| `extended` | boolean | `1` — возвращать дополнительные поля (`likes`, `comments`, `can_comment`, `can_repost`), `0` — нет. По умолчанию: `0`. |
| `photo_sizes` | boolean | `1` — возвращать массив размеров `sizes`, `0` — нет. По умолчанию: `0`. |

### Результат

Возвращает массив объектов фотографий (`[photo1, photo2, ...]`). Недоступные или удаленные фотографии пропускаются.

Каждый объект фотографии содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор фотографии. |
| `pid` | integer | Идентификатор фотографии (для совместимости). |
| `owner_id` | integer | Идентификатор владельца фотографии. |
| `user_id` | integer | Идентификатор владельца фотографии (для совместимости). |
| `album_id` | integer | Идентификатор альбома. |
| `aid` | integer | Идентификатор альбома (для совместимости). |
| `width` | integer | Ширина изображения в пикселях. |
| `height` | integer | Высота изображения в пикселях. |
| `text` | string | Описание (текст) фотографии. |
| `date` | integer | Дата загрузки (Unix timestamp). |
| `access_key` | string | Ключ доступа к фотографии. |
| `photo_75` | string | URL копии изображения 75x75 px. |
| `photo_130` | string | URL копии изображения 130x130 px. |
| `photo_604` | string | URL копии изображения до 604 px. |
| `photo_807` | string | URL копии изображения до 807 px. |
| `photo_1280` | string | URL копии изображения до 1280 px. |
| `photo_2560` | string | URL копии изображения до 2560 px. |
| `url` | string | Прямой URL оригинального файла. |
| `orig_photo` | object | Информация об исходном файле. |
| `sizes` | array | Массив размеров копий (если передан `photo_sizes=1`). |
| `likes` | object | Информация о лайках (при `extended=1` или в версиях ниже 5.0). |
| `comments` | object | Информация о комментариях (при `extended=1` или в версиях ниже 5.0). |
| `can_comment` | integer | `1`, если текущий пользователь может оставлять комментарии. |
| `can_repost` | integer | `1`, если текущий пользователь может поделиться фотографией. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `-78` | `Photos count must not exceed limit` — Количество переданных идентификаторов превышает лимит (78). |

### Пример запроса
```http
POST /method/photos.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

photos=1_1,1_2&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "pid": 1,
            "owner_id": 1,
            "user_id": 1,
            "album_id": 1,
            "aid": 1,
            "width": 1280,
            "height": 720,
            "text": "Фото с прогулки",
            "date": 1609459200,
            "access_key": "",
            "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_1.jpeg",
            "likes": {
                "count": 5,
                "user_likes": 0,
                "can_like": 1,
                "can_publish": 1
            },
            "comments": {
                "count": 0,
                "can_post": 1
            },
            "can_comment": 1,
            "can_repost": 1
        }
    ]
}
```
