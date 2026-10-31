OpenVK-KB-Heading: internal.giveMeException

# internal.giveMeException

Искусственно вызывает исключение времени выполнения (runtime exception) на стороне сервера. Используется для тестирования систем перехвата серверных сбоев, логирования и форматирования ответов с ошибками.

### Авторизация
Для вызова этого метода авторизация не требуется.

### Параметры
Метод не принимает параметров.

### Результат
Метод не возвращает успешного ответа, так как всегда намеренно генерирует исключение `RuntimeException` (`"Test exception for server error interception"`).

### Пример запроса
```http
POST /method/internal.giveMeException HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Пример ответа
```json
{
  "error_code": 10,
  "error_msg": "Internal server error: could not process request",
}
```
