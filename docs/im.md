### Instant messages

Сообщения вынесены в отдельный репозиторий. Это микросервис, написанный на Go, к нему обращаются api-методы.

За подключение к сообщениям отвечает ветка в `openvk.yml` `credentials.im`:

`enable`,
`server_url` - URL микросервиса
`lp_server_addr` - URL LongPoll-подключения

За подключение к микросервису отвечает IMBroker.

Запустить микросервис:
`go run .`

(для запуска требуется redis)

Пересоздать базу данных:
`go run . db create`

Мигрировать старые данные:
`go run . db migrate-legacy`
