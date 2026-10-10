OpenVK-KB-Heading: Методы notifications

# Методы notifications

Секция **notifications** содержит методы для работы с уведомлениями пользователя: получение истории уведомлений, отметка о прочтении, опрос потока событий и настройки.

## Список методов

* **[notifications.get](/dev/methods/notifications/get)** — возвращает список уведомлений текущего пользователя.
* **[notifications.markAsViewed](/dev/methods/notifications/markAsViewed)** — сбрасывает счетчик непросмотренных уведомлений.
* **[notifications.fetch](/dev/methods/notifications/fetch)** — получает порцию новых событий уведомлений через брокер событий.
* **[notifications.getSettings](/dev/methods/notifications/getSettings)** — возвращает настройки уведомлений пользователя (заглушка).
* **[notifications.getIgnoredSources](/dev/methods/notifications/getIgnoredSources)** — возвращает список источников, скрытых из уведомлений (заглушка).
