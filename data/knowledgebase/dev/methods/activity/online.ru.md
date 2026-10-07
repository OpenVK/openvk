OpenVK-KB-Heading: activity.online

# activity.online

Помечает текущего пользователя как online (на 5 минут) с обновлением используемой платформы клиента.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
После успешного выполнения возвращает `1`.

### Пример запроса
```http
POST /method/activity.online HTTP/1.1
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
