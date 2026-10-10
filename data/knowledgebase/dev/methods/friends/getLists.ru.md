OpenVK-KB-Heading: friends.getLists

# friends.getLists

Возвращает список списков (меток) друзей текущего пользователя.

> **Примечание:** В текущей версии OpenVK метод является заглушкой совместимости и возвращает пустой список.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не требует параметров.

### Результат

Возвращает объект со структурой:
* `count` (integer) — `0`;
* `items` (array) — пустой массив `[]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/friends.getLists HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
