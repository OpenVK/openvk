OpenVK-KB-Heading: Объект Conversation

# Объект Conversation

Объект **Conversation** описывает беседу (диалог с пользователем, сообществом или групповой мультичат) в современной системе сообщений OpenVK API, начиная с версии **5.80**.

Возвращается методами `messages.getConversations`, `messages.getConversationsById`, `messages.searchConversations`.

---

## Структура объекта Conversation (v >= 5.80)

| Поле | Тип | Описание |
| --- | --- | --- |
| `peer` | object | Информация о собеседнике / назначении беседы (`id`, `type`, `local_id`). |
| `in_read` | integer | Идентификатор последнего прочитанного входящего сообщения. |
| `out_read` | integer | Идентификатор последнего прочитанного исходящего сообщения. |
| `unread_count` | integer | Количество непрочитанных сообщений в беседе. |
| `important` | boolean | Отмечена ли беседа как важная. |
| `unanswered` | boolean | Отмечена ли беседа как неотвеченная. |
| `push_settings` | object | Настройки push-уведомлений (`sound`, `disabled_until`). |
| `can_write` | object | Информация о возможности отправки сообщений (`allowed`, `reason`). |
| `chat_settings` | object\|null | *(Только для групповых бесед)* Расширенные настройки чата. |

---

### Объект `peer`
| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор назначения: `id` пользователя, `-id` сообщества или `2000000000 + chat_id` для беседы. |
| `type` | string | Тип назначения: `"user"`, `"chat"`, `"group"`, `"email"`. |
| `local_id` | integer | Локальный идентификатор (для бесед — `chat_id`, для пользователей/групп — их `id`). |

---

### Объект `chat_settings` (для чатов)
| Поле | Тип | Описание |
| --- | --- | --- |
| `title` | string | Название беседы. |
| `members_count` | integer | Количество участников беседы. |
| `state` | string | Состояние текущего пользователя в беседе: `"in"` (участник), `"left"` (вышел), `"kicked"` (исключен). |
| `owner_id` | integer | Идентификатор создателя/владельца беседы. |
| `admin_ids` | array | Список идентификаторов администраторов беседы. |
| `active_ids` | array | Список идентификаторов последних активных участников. |
| `photo` | object\|null | Объект с обложками беседы (`photo_50`, `photo_100`, `photo_200`). |
| `pinned_message` | object\|null | Объект закрепленного сообщения в беседе. |
| `acl` | object | Права текущего пользователя в беседе (`can_change_info`, `can_invite`, `can_change_pin` и др.). |
| `permissions` | object | Настройки прав для участников беседы. |

---

### Пример объекта (v >= 5.80)
```json
{
    "peer": {
        "id": 2000000001,
        "type": "chat",
        "local_id": 1
    },
    "in_read": 1050,
    "out_read": 1050,
    "unread_count": 0,
    "important": false,
    "unanswered": false,
    "push_settings": {
        "sound": 1,
        "disabled_until": 0
    },
    "can_write": {
        "allowed": true
    },
    "chat_settings": {
        "title": "Разработчики OpenVK",
        "members_count": 3,
        "state": "in",
        "owner_id": 1,
        "admin_ids": [1],
        "active_ids": [1, 2, 3],
        "photo": {
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
            "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
        }
    }
}
```
