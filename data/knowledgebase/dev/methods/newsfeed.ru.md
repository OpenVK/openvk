OpenVK-KB-Heading: Методы newsfeed

# Методы newsfeed

Секция **newsfeed** содержит методы для работы с лентой новостей пользователя: получение персонализированной и глобальной ленты, комментариев, поиск по новостям, а также управление списком скрытых (игнорируемых) источников.

## Список методов

* **[newsfeed.get](/dev/methods/newsfeed/get)** — возвращает список записей, фотографий и видеозаписей из ленты новостей текущего пользователя.
* **[newsfeed.getGlobal](/dev/methods/newsfeed/getGlobal)** — возвращает глобальную ленту всех публичных записей платформы или RSS-поток.
* **[newsfeed.getRecommended](/dev/methods/newsfeed/getRecommended)** — возвращает список рекомендуемых записей (псевдоним для `newsfeed.getGlobal`).
* **[newsfeed.search](/dev/methods/newsfeed/search)** — осуществляет поиск по записям в ленте новостей.
* **[newsfeed.getByType](/dev/methods/newsfeed/getByType)** — возвращает новости по указанному типу ленты (`top` или стандартная).
* **[newsfeed.getComments](/dev/methods/newsfeed/getComments)** — возвращает записи с последними комментариями в отслеживаемых обсуждениях и стенах.
* **[newsfeed.getBanned](/dev/methods/newsfeed/getBanned)** — возвращает список пользователей и сообществ, скрытых из ленты новостей.
* **[newsfeed.addBan](/dev/methods/newsfeed/addBan)** — скрывает публикации указанных пользователей или сообществ из ленты новостей.
* **[newsfeed.deleteBan](/dev/methods/newsfeed/deleteBan)** — восстанавливает отображение публикаций указанных пользователей или сообществ в ленте новостей.
* **[newsfeed.getLists](/dev/methods/newsfeed/getLists)** — возвращает пользовательские списки новостей (заглушка).
