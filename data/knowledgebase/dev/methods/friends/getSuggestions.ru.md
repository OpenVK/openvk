OpenVK-KB-Heading: friends.getSuggestions

# friends.getSuggestions

Возвращает список рекомендуемых друзей для текущего пользователя.

> **Примечание:** В текущей версии OpenVK метод является заглушкой совместимости и возвращает пустой список.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `filter` | string | Тип фильтрации рекомендаций (например, `mutual`). По умолчанию: `mutual`. |
| `fields` | string | Список дополнительных полей профилей пользователей через запятую (например, `sex,bdate,photo_50`). |
| `offset` | integer | Смещение относительно начала списка. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых рекомендаций. По умолчанию: `100`. |

### Результат

В API версии 5.0 и выше возвращает объект с полями:
* `count` (integer) — `0`;
* `items` (array) — пустой массив `[]`.

В API версии ниже 5.0 возвращается пустой массив `[]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/friends.getSuggestions HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=mutual&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
