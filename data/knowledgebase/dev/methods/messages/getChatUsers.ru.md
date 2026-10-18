OpenVK-KB-Heading: messages.getChatUsers

# messages.getChatUsers

Возвращает список идентификаторов или профилей участников групповой беседы.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | Идентификатор беседы (`1...N`). **Обязательный параметр.** |
| `fields` | string | Дополнительные поля профилей участников. Если параметр не указан, возвращается массив чисел (ID пользователей). |
| `name_case` | string | Падеж для склонения имени и фамилии. |

### Результат

Возвращает массив идентификаторов пользователей (`integer`) или массив объектов пользователей (при указании `fields`).

### Пример запроса
```http
POST /method/messages.getChatUsers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [1, 2, 3]
}
```
