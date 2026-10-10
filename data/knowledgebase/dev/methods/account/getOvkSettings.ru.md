OpenVK-KB-Heading: account.getOvkSettings

# account.getOvkSettings

Возвращает специфичные для платформы OpenVK настройки интерфейса и предпочтений отображения текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
Возвращает объект с настройками OpenVK:

| Поле | Тип | Описание |
| --- | --- | --- |
| `avatar_style` | integer | Стиль отображения аватаров: `0` — квадратные, `1` — круглые. |
| `style` | string | Системный стиль / тема оформления OpenVK (например `classic`, `2016`). |
| `show_rating` | boolean | Отображать ли шкалу рейтинга на странице пользователя (`true` / `false`). |
| `nsfw_tolerance` | integer | Уровень чувствительности к NSFW-контенту. |
| `post_view` | string | Режим отображения стены: `"microblog"` — микроблог, `"old"` — классическая стена. |
| `main_page` | string | Начальная страница при входе: `"my_page"` — моя страница, `"news"` — лента новостей. |

### Пример запроса
```http
POST /method/account.getOvkSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "avatar_style": 0,
        "style": "classic",
        "show_rating": true,
        "nsfw_tolerance": 0,
        "post_view": "microblog",
        "main_page": "my_page"
    }
}
```
