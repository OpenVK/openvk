OpenVK-KB-Heading: Объект Chat

# Объект Chat

Объект **Chat** описывает групповой диалог (мультипользовательский чат / беседу) в OpenVK API.

> **Важно:** Начиная с версии API **5.80**, вместо старых методов работы с чатами (`messages.getDialogs`, `messages.getChat`, `messages.getChatUsers`) введена унифицированная модель **[conversation](/dev/models/conversation)** (`messages.getConversations`, `messages.getConversationMembers`). Объект `Chat` сохраняется для поддержки клиентов, использующих API версий ниже 5.80.

---

## Версии API 5.0 — 5.79 (5.0 <= v < 5.80)

В версиях API 5.x до 5.80 возвращается объект чата со следующей структурой (например, в массиве `chats` при `extended=1` или в ответе `messages.getChat`):

| Поле | Тип | Описание |
| --- | --- | --- |
| `type` | string | Тип объекта (всегда `"chat"`). |
| `id` | integer | Идентификатор беседы (число `1...N`). |
| `local_id` | integer | Локальный идентификатор беседы. |
| `title` | string | Название беседы. |
| `description` | string | Описание беседы. |
| `admin_id` | integer | Идентификатор создателя (главного администратора) беседы. |
| `owner_id` | integer | Идентификатор владельца беседы. |
| `admin_ids` | array | Список идентификаторов всех администраторов беседы. |
| `members` / `users` | array | Список идентификаторов участников беседы (целые числа). |
| `members_count` | integer | Количество участников беседы. |
| `left` | integer | *(Опционально)* `1`, если текущий пользователь вышел из беседы. |
| `kicked` | integer | *(Опционально)* `1`, если текущий пользователь был исключен из беседы. |
| `photo_50` | string | *(Опционально)* URL обложки беседы размером 50x50px. |
| `photo_100` | string | *(Опционально)* URL обложки беседы размером 100x100px. |
| `photo_200` | string | *(Опционально)* URL обложки беседы размером 200x200px. |
| `avatar_max` | string | *(Опционально)* URL обложки беседы в максимальном размере. |
| `photo_id` | integer | *(Опционально)* Идентификатор фотографии обложки. |
| `push_settings` | object | Настройки push-уведомлений беседы (`sound`, `disabled_until`). |
| `acl` | object | Права текущего пользователя в чате (`can_change_info`, `can_invite`, `can_promote_users` и др.). |

### Пример объекта (5.0 <= v < 5.80)
```json
{
    "type": "chat",
    "id": 1,
    "local_id": 1,
    "title": "Разработчики OpenVK",
    "description": "Рабочий чат команды разработки",
    "admin_id": 1,
    "owner_id": 1,
    "admin_ids": [1],
    "members": [1, 2, 3],
    "users": [1, 2, 3],
    "members_count": 3,
    "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
    "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
    "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
}
```

---

## До версии API 5.0 (v < 5.0)

В ранних версиях протокола (3.x, 4.x) использовались устаревшие поля `chat_id` и `users`:

| Поле | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | Идентификатор беседы (эквивалент `id`). |
| `type` | string | Тип объекта (`"chat"`). |
| `title` | string | Название беседы. |
| `admin_id` | integer | Идентификатор создателя беседы. |
| `users` | array | Список идентификаторов участников беседы. |

### Пример объекта (v < 5.0)
```json
{
    "chat_id": 1,
    "type": "chat",
    "title": "Разработчики OpenVK",
    "admin_id": 1,
    "users": [1, 2, 3]
}
```
