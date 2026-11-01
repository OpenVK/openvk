OpenVK-KB-Heading: newsfeed.getLists

# newsfeed.getLists

Возвращает пользовательские списки новостей текущего пользователя.

> **Примечание:** В текущей версии OpenVK метод является заглушкой для совместимости с официальными клиентами и возвращает пустой список.

### Авторизация
Для вызова этого метода не требуется обязательная авторизация.

### Параметры
Метод не принимает параметров.

### Результат

Возвращает объект со следующими полями:
* `count` (integer) — количество списков (всегда `0`);
* `items` (array) — пустой массив списков новостей `[]`.

### Пример запроса
```http
POST /method/newsfeed.getLists HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
