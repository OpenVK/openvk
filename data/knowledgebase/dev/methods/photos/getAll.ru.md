OpenVK-KB-Heading: photos.getAll

# photos.getAll

Возвращает все фотографии пользователя или сообщества в обратном хронологическом порядке.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | **Обязательный параметр.** Идентификатор владельца фотографий (положительное число — ID пользователя, отрицательное — ID сообщества). |
| `extended` | boolean | `1` — возвращать дополнительные поля (`likes`, `comments`, `can_comment`, `can_repost`), `0` — нет. По умолчанию: `0`. |
| `offset` | integer | Смещение относительно начала списка фотографий. По умолчанию: `0`. |
| `count` | integer | Количество фотографий, которое необходимо вернуть. По умолчанию: `100`. |
| `photo_sizes` | boolean | `1` — возвращать массив размеров `sizes`, `0` — нет. По умолчанию: `0`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — общее количество фотографий владельца;
* `items` (array) — массив объектов фотографий.

В API версии ниже 5.0 возвращается массив, где первый элемент — количество фотографий, а далее следуют объекты фотографий (`[count, photo1, photo2, ...]`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к фотографиям ограничен приватностью. |

### Пример запроса
```http
POST /method/photos.getAll HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 2,
        "items": [
            {
                "id": 2,
                "pid": 2,
                "owner_id": 1,
                "user_id": 1,
                "album_id": 1,
                "aid": 1,
                "width": 1024,
                "height": 768,
                "text": "Новая фотография",
                "date": 1609460000,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_2_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_2_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_2_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_2.jpeg"
            }
        ]
    }
}
```
