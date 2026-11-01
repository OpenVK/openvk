OpenVK-KB-Heading: store.buy

# store.buy

Приобретает товар (стикерпак) в магазине за монеты с баланса текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `product_id` | integer | Идентификатор товара (стикерпака). |
| `stickerpack_id` | integer | Альтернативный параметр идентификатора набора (для совместимости). |

> **Примечание:** Необходимо передать хотя бы один из параметров: `product_id` или `stickerpack_id`.

### Результат

Возвращает объект с полями:
| Поле | Тип | Описание |
| --- | --- | --- |
| `success` | integer | Всегда `1` при успешной покупке/активации. |
| `product_id` | integer | Идентификатор приобретенного товара. |
| `pack_id` | integer | Идентификатор приобретенного набора стикеров. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Sticker pack not found` / `Sticker not available` — Товар не найден или недоступен для покупки. |
| `15` | `Cannot purchase this pack` — Недостаточно монет на балансе или товар не может быть куплен. |

### Пример запроса
```http
POST /method/store.buy HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

product_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "success": 1,
        "product_id": 1,
        "pack_id": 1
    }
}
```
