OpenVK-KB-Heading: notifications.markAsViewed

# notifications.markAsViewed

Сбрасывает счетчик непросмотренных уведомлений текущего пользователя, отмечая все поступившие уведомления как просмотренные.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`). Метод выполняет действие записи.

### Параметры
Метод не принимает параметров.

### Результат

Возвращает `1` в случае успешного обновления смещения уведомлений, либо `0` в случае ошибки.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/notifications.markAsViewed HTTP/1.1
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
