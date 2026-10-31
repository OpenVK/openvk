OpenVK-KB-Heading: messages.searchDialogs

# messages.searchDialogs

Осуществляет поиск по диалогам и беседам пользователя по имени собеседника или заголовку беседы (устаревший метод).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `q` | string | Поисковый запрос. **Обязательный параметр.** |
| `limit` | integer | Максимальное количество результатов. По умолчанию: `20`. |
| `fields` | string | Дополнительные поля профилей. |

### Результат

Возвращает массив объектов пользователей и чатов, соответствующих запросу.

### Пример запроса
```http
POST /method/messages.searchDialogs HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

q=Павел&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
