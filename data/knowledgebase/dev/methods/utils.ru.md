OpenVK-KB-Heading: Методы utils

# Методы utils

Секция **utils** содержит служебные утилиты API: получение системного времени сервера, определение типа и идентификатора объекта по короткому адресу (screen_name), разбор вложений и расчет параметров пагинации.

## Список методов

* **[utils.getServerTime](/dev/methods/utils/getServerTime)** — возвращает текущее время сервера в формате unixtime.
* **[utils.resolveScreenName](/dev/methods/utils/resolveScreenName)** — определяет тип объекта (пользователь или сообщество) и его идентификатор по короткому имени.
* **[utils.resolveGuid](/dev/methods/utils/resolveGuid)** — возвращает информацию о пользователе по его глобальному идентификатору (GUID).
* **[utils.resolveAttachments](/dev/methods/utils/resolveAttachments)** — разбирает строку с перечислением медиавложений в структуры объектов API.
* **[utils.resolveOffset](/dev/methods/utils/resolveOffset)** — вычисляет смещение (offset) для перехода к конкретному элементу (записи, фотографии или видео).
