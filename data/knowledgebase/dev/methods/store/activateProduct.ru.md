OpenVK-KB-Heading: store.activateProduct

# store.activateProduct

Активирует товар (стикерпак) и добавляет его в панель быстрого доступа на клавиатуре стикеров.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `product_id` | integer | **Обязательный параметр.** Идентификатор активируемого товара (стикерпака). |
| `type` | string | Тип товара. По умолчанию: `stickers`. |

### Результат

Возвращает объект с полем:
| Поле | Тип | Описание |
| --- | --- | --- |
| `success` | integer | Всегда `1` при успешной активации. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Product not found` — Товар с указанным идентификатором не найден. |

### Пример запроса
```http
POST /method/store.activateProduct HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

product_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "success": 1
    }
}
```
