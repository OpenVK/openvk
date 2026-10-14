OpenVK-KB-Heading: messages.getLastActivity

# messages.getLastActivity

Возвращает информацию о текущем статусе присутствия (онлайн) и времени последней активности пользователя на сайте.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | Идентификатор пользователя. **Обязательный параметр.** |

### Результат

Возвращает объект, содержащий:
* `online` (integer) — `1`, если пользователь сейчас онлайн, `0` — офлайн;
* `time` (integer) — время последней активности (Unix timestamp).

### Пример запроса
```http
POST /method/messages.getLastActivity HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "online": 1,
        "time": 1696680000
    }
}
```
