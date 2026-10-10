OpenVK-KB-Heading: account.getViewerId

# account.getViewerId

Возвращает идентификатор текущего авторизованного пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Параметры отсутствуют.

### Результат
Возвращает целое число — идентификатор текущего пользователя.

### Пример запроса
```http
POST /method/account.getViewerId HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
