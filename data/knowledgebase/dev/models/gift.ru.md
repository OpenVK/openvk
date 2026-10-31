OpenVK-KB-Heading: Объект Gift

# Объект Gift

Объект **Gift** описывает отправленный подарок пользователю ВКонтакте / OpenVK.

---

## Объект Sent Gift

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор записи о подарке. |
| `from_id` | integer | Идентификатор отправителя подарка (если подарок не анонимный). |
| `message` | string | Текстовое пожелание / комментарий к подарку. |
| `date` | integer | Время отправки подарка (Unix timestamp). |
| `privacy` | integer | Приватность подарка: `0` — публичный (виден всем), `1` — имя отправителя скрыто (видно только получателю), `2` — полностью анонимный. |
| `gift` | object | Объект самого подарка (`id`, `thumb_48`, `thumb_96`, `thumb_256`). |

### Пример объекта Gift
```json
{
    "id": 1,
    "from_id": 2,
    "message": "С днём рождения!",
    "date": 1696680000,
    "privacy": 0,
    "gift": {
        "id": 5,
        "thumb_48": "https://openvk.instance/images/gifts/5/48.png",
        "thumb_96": "https://openvk.instance/images/gifts/5/96.png",
        "thumb_256": "https://openvk.instance/images/gifts/5/256.png"
    }
}
```
