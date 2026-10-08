OpenVK-KB-Heading: ovk.version

# ovk.version

Возвращает строку с текущей версией движка OpenVK, на которой работает сервер.

### Авторизация
Метод является публичным и не требует авторизации.

### Параметры
Метод не принимает параметров.

### Результат

Возвращает строку с версией движка OpenVK (например, `"1.0.0"`).

### Пример запроса
```http
POST /method/ovk.version HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Пример ответа
```json
{
    "response": "1.0.0"
}
```
