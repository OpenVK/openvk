OpenVK-KB-Heading: store.getProducts

# store.getProducts

Возвращает список товаров цифрового магазина (стикерпаков) с поддержкой фильтрации по статусу приобретения или активности.

### Авторизация
Этот метод не требует обязательной авторизации, кроме случаев фильтрации по `purchased` или `active` без указания `user_id`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `type` | string | Тип запрашиваемых товаров. По умолчанию: `stickers`. |
| `filters` | string | Список фильтров через запятую: `purchased` (все купленные/полученные наборы), `active` (активные наборы в клавиатуре). |
| `extended` | integer | `1` — возвращать подробную информацию о товарах (включая превью и список стикеров), `0` — базовую информацию. По умолчанию: `1`. |
| `count` | integer | Количество товаров для возврата. По умолчанию: `50`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `product_ids` | string | Идентификаторы товаров через запятую. Если указаны, возвращаются только эти товары. |
| `user_id` | integer | Идентификатор пользователя, для которого запрашиваются списки. |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее количество товаров;
* `items` (array) — массив объектов товаров.

Каждый объект товара содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор товара (стикерпака). |
| `type` | string | Тип товара (`stickers`). |
| `title` | string | Название набора. |
| `description` | string | Описание набора. |
| `author` | string | Автор или создатель набора. |
| `purchased` | integer | `1` — товар куплен или получен пользователем, `0` — нет. |
| `active` | integer | `1` — товар активен в быстром доступе, `0` — скрыт. |
| `price` | integer | Цена в монетах (`0` — бесплатный). |
| `price_str` | string | Форматированная строка цены («Бесплатно» или «N монет»). |
| `photo_128` | string | URL обложки размером 128x128 px. |
| `photo_256` | string | URL обложки размером 256x256 px. |
| `is_animated` | boolean | Анимированный ли стикерпак. |
| `animation_url` | string/null | URL файла анимации (Lottie/TGS JSON). |
| `stickers_count` | integer | Количество стикеров в наборе. |
| `sticker_ids` | array | Массив числовых идентификаторов стикеров. |
| `previews` | array | Превью первых стикеров набора. |

### Пример запроса
```http
POST /method/store.getProducts HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filters=purchased&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "type": "stickers",
                "title": "Котик Персик",
                "name": "Котик Персик",
                "description": "Милый кот",
                "author": "OpenVK",
                "purchased": 1,
                "active": 1,
                "price": 0,
                "price_str": "Бесплатно",
                "photo_128": "https://openvk.instance/images/stickers/1/128b.png",
                "photo_256": "https://openvk.instance/images/stickers/1/256b.png",
                "is_animated": false,
                "stickers_count": 24,
                "sticker_ids": [1, 2, 3]
            }
        ]
    }
}
```
