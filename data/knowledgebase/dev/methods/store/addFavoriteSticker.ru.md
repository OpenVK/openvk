OpenVK-KB-Heading: store.addFavoriteSticker

# store.addFavoriteSticker

Добавляет стикер в список избранных стикеров пользователя.

> **Примечание:** В OpenVK метод является заглушкой для совместимости с мобильными клиентами и возвращает `{"success": 1}`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `sticker_id` | integer | **Обязательный параметр.** Идентификатор добавляемого стикера. |

### Результат

Возвращает объект с полем:
| Поле | Тип | Описание |
| --- | --- | --- |
| `success` | integer | Всегда `1`. |

### Пример запроса
```http
POST /method/store.addFavoriteSticker HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

sticker_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "success": 1
    }
}
```
