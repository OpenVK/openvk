OpenVK-KB-Heading: places.getCountriesById

# places.getCountriesById

Возвращает информацию о странах по переданным идентификаторам. Метод является полным псевдонимом (алиасом) метода [places.getCountryById](/dev/methods/places/getCountryById).

### Авторизация
Метод является публичным и не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `cids` | string / integer / array | Идентификаторы стран (число, массив чисел или строка с идентификаторами через запятую). |

### Результат

Возвращает массив объектов стран (`id`, `cid`, `title`, `name`).

### Пример запроса
```http
POST /method/places.getCountriesById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

cids=1,3&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "cid": 1,
            "title": "Россия",
            "name": "Россия"
        },
        {
            "id": 3,
            "cid": 3,
            "title": "Беларусь",
            "name": "Беларусь"
        }
    ]
}
```
