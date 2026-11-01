OpenVK-KB-Heading: account.setOffline

# account.setOffline

Помечает текущего пользователя как оффлайн.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Параметры отсутствуют.

### Результат
После успешного выполнения возвращает `1`.

### Пример запроса
```http
POST /method/account.setOffline HTTP/1.1
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
