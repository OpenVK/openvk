OpenVK-KB-Heading: stickers.getStockItems

# stickers.getStockItems

Возвращает список товаров витрины стикеров каталога. Является псевдонимом для метода [store.getStockItems](/dev/methods/store/getStockItems).

### Авторизация
Этот метод не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `type` | string | Тип товаров. По умолчанию: `stickers`. |
| `section` | string | Секция витрины: `popular` (популярные), `free` (бесплатные), `all` или `catalog` (все). По умолчанию: `popular`. |
| `extended` | integer | `1` — возвращать подробные данные о товарах, `0` — краткие. По умолчанию: `1`. |
| `count` | integer | Количество товаров. По умолчанию: `50`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `merchant` | string | Идентификатор мерчанта (параметр совместимости). |

### Результат

Возвращает витрину товаров в формате, идентичном [store.getStockItems](/dev/methods/store/getStockItems).

### Пример запроса
```http
POST /method/stickers.getStockItems HTTP/1.1
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
                    "title": "Котик Персик",
                    "price": 0
                },
                "price": 0,
                "price_str": "Бесплатно",
                "can_purchase": 1,
                "free": 1
            }
        ]
    }
}
```
