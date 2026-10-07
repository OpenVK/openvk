OpenVK-KB-Heading: account.getPrivacySettings

# account.getPrivacySettings

Возвращает конфигурацию доступных настроек приватности профиля.

> **Примечание (заглушка совместимости):** Метод возвращает пустой шаблон структуры приватности для совместимости с интерфейсами клиентов.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
Возвращает объект с разделами и параметрами приватности:

| Поле | Тип | Описание |
| --- | --- | --- |
| `sections` | array | Разделы приватности (`[]`). |
| `settings` | array | Доступные настройки приватности (`[]`). |
| `supported_categories` | array | Поддерживаемые категории видимости (`[]`). |
| `recommended_closed_profile_settings` | array | Рекомендованные настройки закрытого профиля (`[]`). |
| `story_privacy_is_deprecated_options_disabled` | boolean | Флаг устаревших параметров историй (`false`). |

### Пример запроса
```http
POST /method/account.getPrivacySettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "sections": [],
        "settings": [],
        "supported_categories": [],
        "recommended_closed_profile_settings": [],
        "story_privacy_is_deprecated_options_disabled": false
    }
}
```
