OpenVK-KB-Heading: store.getRecentStickers

# store.getRecentStickers

Возвращает список недавно использованных стикеров текущего пользователя.

> **Примечание:** В текущей версии OpenVK метод является заглушкой для совместимости с клиентами и возвращает пустой список.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает обязательных параметров.

### Результат

Возвращает объект с полями:
* `count` (integer) — количество недавних стикеров (`0`);
* `items` (array) — массив объектов стикеров (`[]`).

### Пример запроса
```http
POST /method/store.getRecentStickers HTTP/1.1
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
