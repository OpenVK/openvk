OpenVK-KB-Heading: ovk.getMirrors

# ovk.getMirrors

Возвращает список всех зарегистрированных доменных зеркал текущего инстанса OpenVK.

### Авторизация
Метод является публичным и не требует авторизации.

### Параметры
Метод не принимает параметров.

### Результат

Возвращает массив строк с доменными именами зеркал (например, `["openvk.su", "ovk.to"]`).

### Пример запроса
```http
POST /method/ovk.getMirrors HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

v=5.138
```

### Пример ответа
```json
{
    "response": [
        "openvk.su",
        "ovk.to"
    ]
}
```
