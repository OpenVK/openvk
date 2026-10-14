OpenVK-KB-Heading: store.getStockItems

# store.getStockItems

Возвращает список товаров витрины цифрового магазина (каталога стикерпаков) с разбивкой по категориям.

### Авторизация
Этот метод не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `type` | string | Тип товаров. По умолчанию: `stickers`. |
| `section` | string | Секция витрины: `popular` (популярные), `free` (бесплатные), `all` или `catalog` (все доступные наборы). По умолчанию: `popular`. |
| `extended` | integer | `1` — возвращать подробную информацию о товарах, `0` — краткую. По умолчанию: `1`. |
| `count` | integer | Количество товаров. По умолчанию: `50`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `merchant` | string | Идентификатор мерчанта (параметр совместимости). |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее количество товаров в выбранной секции;
* `items` (array) — массив объектов витрины магазина.

Каждый элемент массива `items` содержит:
| Поле | Тип | Описание |
| --- | --- | --- |
| `product` | object | Объект товара (стикерпака). |
| `description` | string | Описание набора. |
| `author` | string | Автор набора. |
| `price` | integer | Стоимость в монетах. |
| `price_str` | string | Строковое представление цены. |
| `can_purchase` | integer | `1` — товар доступен для покупки. |
| `free` | integer | `1` — товар бесплатный, `0` — платный. |
| `is_new` | integer | `1` — новинка (добавлен менее 30 дней назад), `0` — нет. |

### Пример запроса
```http
POST /method/store.getStockItems HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

section=free&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "product": {
                    "id": 1,
                    "type": "stickers",
                    "title": "Котик Персик",
                    "name": "Котик Персик",
                    "price": 0,
                    "purchased": 1,
                    "active": 1
                },
                "description": "Милый рыжий кот",
                "author": "OpenVK",
                "price": 0,
                "price_str": "Бесплатно",
                "can_purchase": 1,
                "free": 1,
                "is_new": 0
            }
        ]
    }
}
```
