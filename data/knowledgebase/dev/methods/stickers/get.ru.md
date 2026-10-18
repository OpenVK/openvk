OpenVK-KB-Heading: stickers.get

# stickers.get

Возвращает список активных стикерпаков текущего пользователя вместе со стикерами каждого набора.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя. По умолчанию: ID текущего пользователя. |
| `count` | integer | Количество возвращаемых стикерпаков. По умолчанию: `50`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее количество активных стикерпаков;
* `items` (array) — массив объектов стикерпаков.

Каждый объект стикерпака содержит:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор стикерпака. |
| `name` | string | Название стикерпака. |
| `title` | string | Заголовок стикерпака. |
| `description` | string | Описание стикерпака. |
| `slug` | string | Символьный идентификатор (слаг) стикерпака. |
| `price` | integer | Стоимость стикерпака в монетах (`0` — бесплатный). |
| `end_time` | integer | Время окончания доступности (Unix timestamp) или `0`. |
| `purchased` | integer | `1` — набор приобретен/установлен, `0` — нет. |
| `photo_128` | string | URL обложки стикерпака размером 128x128 px. |
| `photo_256` | string | URL обложки стикерпака размером 256x256 px. |
| `is_animated` | boolean | Является ли набор анимированным. |
| `animation_url` | string/null | URL Lottie/TGS JSON-файла анимации (для анимированных стикеров). |
| `stickers_count` | integer | Количество стикеров в наборе. |
| `sticker_ids` | array | Массив числовых идентификаторов стикеров. |
| `stickers` | array | Массив объектов стикеров набора. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/stickers.get HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

count=20&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "name": "Пося",
                "title": "Пося",
                "description": "Не кусается :p",
                "slug": "hornypossum",
                "price": 0,
                "end_time": 0,
                "purchased": 1,
                "photo_128": "https://openvk.instance/images/stickers/1/128b.png",
                "photo_256": "https://openvk.instance/images/stickers/1/256b.png",
                "is_animated": false,
                "animation_url": null,
                "stickers_count": 24,
                "sticker_ids": [1, 2, 3],
                "stickers": [
                    {
                        "sticker_id": 1,
                        "is_allowed": true
                    }
                ]
            }
        ]
    }
}
```
