OpenVK-KB-Heading: messages.createChat

# messages.createChat

Создает новую групповую беседу (чат) с указанными участниками.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_ids` | string | Список идентификаторов пользователей через запятую для добавления в беседу. **Обязательный параметр.** |
| `title` | string | Название беседы. **Обязательный параметр.** |
| `group_id` | integer | Идентификатор сообщества (если чат создается от имени группы). |

### Результат

Возвращает идентификатор созданной беседы (`integer`, число `1...N`).

### Пример запроса
```http
POST /method/messages.createChat HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_ids=2,3&title=Разработчики+OpenVK&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
