OpenVK-KB-Heading: account.getCounters

# account.getCounters

Возвращает счетчики непрочитанных сообщений, уведомлений и входящих заявок в друзья.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `filter` | string | Список счетчиков через запятую (`friends`, `notifications`, `messages`). Если параметр не передан, возвращаются все доступные счетчики. |

### Результат
Возвращает объект со счетчиками:

| Поле | Тип | Описание |
| --- | --- | --- |
| `friends` | integer | Количество входящих заявок в друзья. |
| `notifications` | integer | Количество непросмотренных уведомлений. |
| `messages` | integer | Количество диалогов с непрочитанными сообщениями. |

### Пример запроса
```http
POST /method/account.getCounters HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=messages,notifications&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "notifications": 3,
        "messages": 5
    }
}
```
