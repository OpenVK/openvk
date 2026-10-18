OpenVK-KB-Heading: account.saveInterestsInfo

# account.saveInterestsInfo

Редактирует информацию об интересах и увлечениях текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`). Метод проверяет ограничения частоты запросов на запись.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `interests` | string | Деятельность и интересы. |
| `fav_music` | string | Любимая музыка. |
| `fav_films` | string | Любимые фильмы. |
| `fav_shows` | string | Любимые телешоу. |
| `fav_books` | string | Любимые книги. |
| `fav_quote` | string | Любимые цитаты. |
| `fav_games` | string | Любимые игры. |
| `about` | string | О себе. |

### Результат
Возвращает объект с результатом сохранения:

| Поле | Тип | Описание |
| --- | --- | --- |
| `changed` | integer | `1` в случае успешного сохранения изменений, `0` если ничего не изменилось. |

### Пример запроса
```http
POST /method/account.saveInterestsInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

interests=Программирование%2C+музыка&fav_music=Synthwave&about=Разработчик&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "changed": 1
    }
}
```
