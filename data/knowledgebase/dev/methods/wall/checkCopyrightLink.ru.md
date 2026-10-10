OpenVK-KB-Heading: wall.checkCopyrightLink

# wall.checkCopyrightLink

Проверяет корректность и безопасность внешней ссылки на первоисточник (копирайт) для записи на стене.

### Авторизация
Для вызова этого метода необходим токен пользователя.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `link` | string | **Обязательный параметр.** Внешний URL-адрес источника для проверки. |

### Результат

Возвращает `1`, если ссылка прошла валидацию и допустима для указания в качестве источника.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `3102` | `Specified link is incorrect` — ссылка имеет неверный формат или недоступна. |
| `3103` | `Specified link is incorrect (too long)` — длина ссылки превышает допустимый предел. |
| `3104` | `Link is suspicious` — ссылка распознана как подозрительная или вредоносная. |

### Пример запроса
```http
POST /method/wall.checkCopyrightLink HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

link=https://example.com/article/123&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
