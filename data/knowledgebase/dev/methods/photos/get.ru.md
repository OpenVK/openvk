OpenVK-KB-Heading: photos.get

# photos.get

Возвращает список фотографий из указанного альбома или по списку идентификаторов.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца альбома (положительное число — ID пользователя, отрицательное — ID сообщества). |
| `album_id` | string | Идентификатор альбома или строковое значение `profile` (альбом с фотографиями профиля). По умолчанию: `profile`. |
| `photo_ids` | string | Список идентификаторов фотографий через запятую в формате `owner_id_photo_id` (максимум 78). Если передан, `album_id` игнорируется. |
| `extended` | boolean | `1` — возвращать дополнительные поля (`likes`, `comments`, `can_comment`, `can_repost`), `0` — нет. По умолчанию: `0`. |
| `photo_sizes` | boolean | `1` — возвращать массив различных размеров фотографии `sizes`, `0` — нет. По умолчанию: `1`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `count` | integer | Количество фотографий, которое необходимо вернуть. По умолчанию: `10`. |
| `limit` | integer | Псевдоним параметра `count`. |
| `rev` | boolean | Порядок сортировки: `1` — в антихронологическом порядке (старые в начале), `0` — новые в начале. По умолчанию: `0`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — общее количество фотографий в альбоме;
* `items` (array) — массив объектов фотографий.

В API версии ниже 5.0 возвращается массив, где первый элемент — общее число фотографий, а последующие — объекты фотографий (`[count, photo1, photo2, ...]`).

Каждый объект фотографии содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор фотографии. |
| `pid` | integer | Идентификатор фотографии (для совместимости). |
| `owner_id` | integer | Идентификатор владельца фотографии. |
| `user_id` | integer | Идентификатор владельца фотографии (для совместимости). |
| `album_id` | integer | Идентификатор альбома (`-3` для несохраненных/временных). |
| `aid` | integer | Идентификатор альбома (для совместимости). |
| `width` | integer | Ширина оригинала в пикселях. |
| `height` | integer | Высота оригинала в пикселях. |
| `text` | string | Текст описания фотографии. |
| `date` | integer | Дата загрузки (Unix timestamp). |
| `access_key` | string | Ключ доступа к фотографии. |
| `photo_75` | string | URL копии изображения размером 75x75 px. |
| `photo_130` | string | URL копии изображения размером 130x130 px. |
| `photo_604` | string | URL копии изображения размером до 604 px. |
| `photo_807` | string | URL копии изображения размером до 807 px. |
| `photo_1280` | string | URL копии изображения размером до 1280 px. |
| `photo_2560` | string | URL копии изображения размером до 2560 px. |
| `url` | string | Прямой URL оригинального загруженного файла. |
| `orig_photo` | object | Информация об оригинальном изображении (`height`, `width`, `type`, `url`). |
| `sizes` | array | Массив объектов доступных размеров копий (если передан `photo_sizes=1`). |
| `likes` | object | Объект с информацией о лайках (`count`, `user_likes`, `can_like`, `can_publish`) — при `extended=1` или в версиях ниже 5.0. |
| `comments` | object | Объект с информацией о комментариях (`count`, `can_post`) — при `extended=1` или в версиях ниже 5.0. |
| `can_comment` | integer | `1`, если текущий пользователь может комментировать фотографию. |
| `can_repost` | integer | `1`, если текущий пользователь может поделиться записью. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к фотографиям ограничен настройками приватности. |
| `-78` | `Photos count must not exceed limit` — Превышен лимит на количество идентификаторов в `photo_ids` (более 78). |

### Пример запроса
```http
POST /method/photos.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&album_id=profile&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "pid": 1,
                "owner_id": 1,
                "user_id": 1,
                "album_id": 1,
                "aid": 1,
                "width": 800,
                "height": 600,
                "text": "Моя фотография",
                "date": 1609459200,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_1.jpeg",
                "likes": {
                    "count": 12,
                    "user_likes": 0,
                    "can_like": 1,
                    "can_publish": 1
                },
                "comments": {
                    "count": 2,
                    "can_post": 1
                },
                "can_comment": 1,
                "can_repost": 1
            }
        ]
    }
}
```
