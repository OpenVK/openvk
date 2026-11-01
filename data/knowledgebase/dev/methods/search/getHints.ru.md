OpenVK-KB-Heading: search.getHints

# search.getHints

Возвращает поисковые подсказки для быстрого перехода к пользователям, друзьям и сообществам.

> **Примечание:** В текущей версии OpenVK метод является заглушкой для совместимости с клиентами и возвращает пустой массив.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает обязательных параметров.

### Результат

Возвращает пустой массив `[]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/search.getHints HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": []
}
```
