OpenVK-KB-Heading: account.getHelpHints

# account.getHelpHints

Возвращает контекстные подсказки справки и подсказки интерфейса для указанного раздела.

> **Примечание (заглушка совместимости):** Подсказки интерфейса не используются в OpenVK. Метод возвращает пустой список (`{"hints": [], "items": [], "count": 0}`).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `section` | string | Название раздела справки. |
| `app_id` | string | Идентификатор приложения. |
| `fields` | string | Список дополнительных полей. |

### Результат
Возвращает объект, содержащий массивы подсказок:

| Поле | Тип | Описание |
| --- | --- | --- |
| `hints` | array | Массив текстовых подсказок (`[]`). |
| `items` | array | Массив элементов подсказок (`[]`). |
| `count` | integer | Количество подсказок (`0`). |

### Пример запроса
```http
POST /method/account.getHelpHints HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

section=general&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "hints": [],
        "items": [],
        "count": 0
    }
}
```
