OpenVK-KB-Heading: places.getCitiesById

# places.getCitiesById

Возвращает информацию о городах по переданным идентификаторам. Метод является полным псевдонимом (алиасом) метода [places.getCityById](/dev/methods/places/getCityById).

### Авторизация
Метод является публичным и не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `cids` | string / integer / array | Идентификаторы городов (число, массив чисел или строка с идентификаторами через запятую). |

### Результат

Возвращает массив объектов городов (`id`, `cid`, `title`, `name`).

### Пример запроса
```http
POST /method/places.getCitiesById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

cids=1,2&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "cid": 1,
            "title": "Москва",
            "name": "Москва"
        }
    ]
}
```
