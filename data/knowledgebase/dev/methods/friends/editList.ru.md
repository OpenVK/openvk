OpenVK-KB-Heading: friends.editList

# friends.editList

Редактирует существующий список (метку) друзей.

> **Примечание:** В текущей версии OpenVK метод является заглушкой совместимости и возвращает `1`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не требует обязательных параметров.

### Результат

Возвращает число `1` в случае успеха.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/friends.editList HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

list_id=1&name=Коллеги&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
