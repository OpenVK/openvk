OpenVK-KB-Heading: pay.verifyOrder

# pay.verifyOrder

Проверяет цифровую подпись (HMAC Whirlpool) и параметры платежного заказа приложения. Вызывать метод может только владелец указанного приложения.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`). Пользователь должен являться владельцем приложения.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `app_id` | integer | **Обязательный параметр**. Идентификатор приложения. |
| `amount` | float | **Обязательный параметр**. Сумма платежа. |
| `signature` | string | **Обязательный параметр**. Подпись заказа в формате `time,signature`. |

### Результат

Возвращает `true` в случае успешной валидации заказа.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `4` | `Invalid order` — Недействительная цифровая подпись заказа. |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access error` — Текущий пользователь не является владельцем приложения. |
| `26` | `No app found with this id` — Приложение с указанным идентификатором не найдено. |

### Пример запроса
```http
POST /method/pay.verifyOrder HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

app_id=1&amount=100.50&signature=1700000000%2Cwhirlpool_hash&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": true
}
```
