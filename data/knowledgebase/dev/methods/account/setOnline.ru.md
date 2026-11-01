OpenVK-KB-Heading: account.setOnline

# account.setOnline

Помечает текущего пользователя как online (на 5 минут) с фиксацией платформы авторизации.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Параметры отсутствуют.

### Результат
После успешного выполнения возвращает `1`.

### Пример запроса
```http
POST /method/account.setOnline HTTP/1.1
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
