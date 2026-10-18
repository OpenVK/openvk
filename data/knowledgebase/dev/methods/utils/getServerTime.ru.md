OpenVK-KB-Heading: utils.getServerTime

# utils.getServerTime

Возвращает текущее время сервера в формате unixtime (число секунд, прошедших с 1 января 1970 года).

### Авторизация
Этот метод не требует авторизации.

### Параметры
Метод не принимает параметров.

### Результат

Возвращает целое число (`integer`) — текущее время сервера в unixtime.

### Пример запроса
```http
POST /method/utils.getServerTime HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Пример ответа
```json
{
    "response": 1775656800
}
```
