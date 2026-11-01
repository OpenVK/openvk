OpenVK-KB-Heading: stickers.getProducts

# stickers.getProducts

Возвращает список товаров-стикерпаков с поддержкой фильтрации. Является псевдонимом для метода [store.getProducts](/dev/methods/store/getProducts).

### Авторизация
Этот метод не требует обязательной авторизации, кроме случаев фильтрации по `purchased` или `active`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `type` | string | Тип товаров. По умолчанию: `stickers`. |
| `filters` | string | Фильтры через запятую: `active` (активные наборы), `purchased` (все купленные/полученные наборы). |
| `extended` | integer | `1` — возвращать расширенную информацию о товарах, `0` — краткую. По умолчанию: `1`. |
| `count` | integer | Количество товаров. По умолчанию: `50`. |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `product_ids` | string/array | Список идентификаторов товаров через запятую. |
| `user_id` | integer | Идентификатор пользователя, для которого запрашиваются товары. |

### Результат

Возвращает список товаров в формате, идентичном [store.getProducts](/dev/methods/store/getProducts).

### Пример запроса
```http
POST /method/stickers.getProducts HTTP/1.1
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
                "price": 0,
                "purchased": 1,
                "active": 1
            }
        ]
    }
}
```
