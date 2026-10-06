OpenVK-KB-Heading: account.getProfileInfo

# account.getProfileInfo 🔰

Возвращает расширенную информацию о профиле текущего авторизованного пользователя для отображения или редактирования в настройках.

## Требования
* **Авторизация:** Требуется (`access_token`).

## Параметры
Метод не принимает параметров.

## Результат
Возвращает объект с данными профиля:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор пользователя. |
| `first_name` | string | Имя пользователя. |
| `last_name` | string | Фамилия пользователя. |
| `nickname` | string | Никнейм (псевдоним). |
| `screen_name` | string | Короткое имя страницы (например, `id1` или `durov`). |
| `sex` | integer | Пол: `1` — женский, `2` — мужской, `0` — не указан. |
| `status` | string | Текстовый статус профиля. |
| `bdate` | string | Дата рождения в формате `D.M.YYYY` или `D.M`. |
| `bdate_visibility` | integer | Видимость даты рождения: `1` — показывать полностью, `2` — только месяц и день, `0` — скрывать. |
| `home_town` | string | Родной город. |
| `relation` | integer | Семейное положение (код статуса отношений). |
| `is_verified` | boolean | Подтвержден ли аккаунт (наличие галочки верификации). |
| `photo_200` | string | Ссылка на квадратную фотографию профиля 200x200px. |
| `audio_status` | object | Объект текущего транслируемого трека (если включена трансляция). |

## Пример ответа

```json
{
    "response": {
        "id": 1,
        "first_name": "Павел",
        "last_name": "Дуров",
        "nickname": "",
        "screen_name": "durov",
        "sex": 2,
        "status": "ВКонтакте",
        "bdate": "10.10.1984",
        "bdate_visibility": 1,
        "home_town": "Ленинград",
        "is_verified": true,
        "photo_200": "https://ovk.to/assets/packages/static/openvk/img/camera_200.png"
    }
}
```
