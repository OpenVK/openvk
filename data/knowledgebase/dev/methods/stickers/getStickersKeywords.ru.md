OpenVK-KB-Heading: stickers.getStickersKeywords

# stickers.getStickersKeywords

Возвращает словарь подсказок стикеров по эмодзи и словам-алиасам для автодополнения в поле ввода сообщений. Является псевдонимом для метода [store.getStickersKeywords](/dev/methods/store/getStickersKeywords).

### Авторизация
Этот метод не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `aliases` | integer | `1` — использовать текстовые синонимы к эмодзи (например «привет» для «👋»), `0` — только точные эмодзи. По умолчанию: `1`. |
| `all_products` | integer | `1` — возвращать подсказки для всех доступных наборов, `0` — только для активных у пользователя. По умолчанию: `1`. |
| `need_stickers` | integer | `1` — включать полные объекты стикеров, `0` — только их ID. По умолчанию: `1`. |
| `stickers_hash` | string | Хеш словаря стикеров для кеширования на клиенте. |
| `products_hash` | string | Хеш списка продуктов для кеширования на клиенте. |
| `count` | integer | Лимит количества записей. По умолчанию: `0` (все). |
| `user_id` | integer | Идентификатор пользователя. |

### Результат

Возвращает структуру подсказок в формате, идентичном [store.getStickersKeywords](/dev/methods/store/getStickersKeywords).

### Пример запроса
```http
POST /method/stickers.getStickersKeywords HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

aliases=1&need_stickers=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "dictionary": [
            {
                "words": ["👋", "привет", "хай", "hello"],
                "user_stickers": [
                    {
                        "id": 1,
                        "sticker_id": 1
                    }
                ],
                "promoted_stickers": []
            }
        ],
        "base_url": "https://openvk.instance/images/stickers/",
        "stickers_hash": "d41d8cd98f00b204e9800998ecf8427e",
        "products_hash": "7b8b965ad4bca0e41ab51de7b31363a1"
    }
}
```
