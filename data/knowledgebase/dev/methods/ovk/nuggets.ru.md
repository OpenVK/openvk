OpenVK-KB-Heading: ovk.nuggets

# ovk.nuggets

Пасхальный метод API движка OpenVK.

### Авторизация
Метод является публичным и не требует авторизации.

### Параметры
Метод не принимает параметров.

### Результат

Возвращает строку `"котлетки"`.

### Пример запроса
```http
POST /method/ovk.nuggets HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Пример ответа
```json
{
    "response": "котлетки"
}
```
