OpenVK-KB-Heading: О движке OpenVK

# О движке OpenVK

**OpenVK** — это попытка создать простую CMS, которая ~~косплеит~~ имитирует старый ВКонтакте. В данный момент код нестабилен.

> **Предупреждение:** **OpenVK является любительской разработкой и никак не связан с ВКонтакте и компанией ООО "ВК".**

Честно говоря, мы и сами не уверены в стабильности нашей платформы. Тем не менее, проект постоянно развивается, и мы будем рады принять ваши багрепорты [в нашем баг-трекере](https://github.com/openvk/openvk/issues). Вы также можете отправлять их через [систему тикетов](/support?act=new), если у вас есть аккаунт OpenVK.

---

## Когда релиз?

Мы выпустим OpenVK, как только он будет готов. На данный момент вы можете:
* Сделать `git clone` ветки master этого репозитория (используйте `git pull` для обновления)
* Сделать форк и там сделать отдельную ветку для своих наработок. В будущем мы будем рады видеть Pull Requests, которые исправляют ошибки или добавляют новые функции (обязательно с документацией).

---

## Инстансы

Список инстансов можно найти в [Wiki репозитория](https://github.com/openvk/openvk/wiki/Instances).

---

## Могу ли я создать свой собственный инстанс OpenVK?

Да! И мы это только приветствуем.

Однако OVK требует расширений, которые могут быть доступны не на всех веб-хостингах (в частности, `sodium` и `yaml`, эти расширения есть на большинстве хостингов с ISPmanager). Поэтому рекомендуется размещать инстансы на VPS/VDS или выделенных серверах.

При желании вы можете добавить свой инстанс в список выше, чтобы пользователи могли там регистрироваться.

### Системные требования

Рекомендуемые минимальные характеристики:

* **CPU:** Любой двухъядерный процессор 1 ГГц+ или мощнее
* **RAM:** Минимум 2 ГБ RAM (мы рекомендуем 6 ГБ или 8 ГБ для OpenVK с Redis)
* **Минимальное место на диске:** 10 ГБ

### Поддержка ОС и PHP 8.4

Для работы OpenVK требуется **PHP 8.4 или новее**. Доступность PHP 8.4:
* **Ubuntu:** 24.10+ (штатно в официальных репозиториях) или 22.04 / 24.04 LTS (через PPA `ppa:ondrej/php`)
* **Debian:** 13 (Trixie) или Debian 11 / 12 (через репозиторий `deb.sury.org`)
* **Alpine Linux:** 3.21+ (пакет `php84`)
* **RHEL / AlmaLinux / Rocky Linux:** 8 / 9 (через репозиторий Remi `php:remi-8.4`)
* **Fedora:** 41+
* **Arch Linux:** В официальном репозитории extra
* **FreeBSD:** 14+ / 15 (`pkg install php84`)

### Развертывание в Docker или Kubernetes
Инструкции по развертыванию в Docker и Kubernetes доступны в `install/automated/docker/README.md` и `install/automated/kubernetes/README.md`.

### Процесс установки

1. Установите PHP 8.4 или новее, веб-сервер, Composer и NPM.

**Ubuntu / Debian (LTS):**

```bash
sudo add-apt-repository ppa:ondrej/php && sudo apt update
sudo apt install php8.4 php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-curl php8.4-yaml php8.4-gd php8.4-zip php8.4-xml php8.4-redis
```

**RHEL / AlmaLinux / Rocky Linux (8 / 9):**

```bash
sudo dnf install epel-release https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %rhel).rpm
sudo dnf module reset php && sudo dnf module enable php:remi-8.4
sudo dnf install php php-fpm php-mysqlnd php-mbstring php-yaml php-gd php-pecl-redis5
```

**Alpine Linux (3.21+):**

```bash
apk add php84 php84-fpm php84-pdo_mysql php84-mbstring php84-curl php84-yaml php84-gd php84-pecl-redis
```

**FreeBSD (14+ / 15):**

```bash
pkg install php84 php84-composer php84-pdo_mysql php84-mbstring php84-curl php84-yaml php84-gd php84-pecl-redis
```

2. Установите MySQL-совместимую базу данных.

* Рекомендуется использовать MariaDB или Percona Server, но подойдет любой MySQL-совместимый сервер.
* Сервер должен быть совместим как минимум с MySQL 5.6, рекомендуется MySQL 8.0+.
* Поддержка MySQL 4.1+ в разработке: замените `utf8mb4` и `utf8mb4_unicode_520_ci` на `utf8` и `utf8_unicode_ci` в SQL-дампах.

3. Клонируйте OpenVK:

```bash
git clone https://github.com/openvk/openvk /opt/openvk
```

4. Установите зависимости:

```bash
cd /opt/openvk && composer install
cd Web/static/js && npm install
```

5. Настройте базу данных: вам понадобятся 2 базы данных — одна для основных данных, вторая для событий.

6. Скопируйте `openvk-example.yml` в `openvk.yml` и отредактируйте под свои нужды.  

7. Запустите миграции базы данных:

```bash
cd /opt/openvk && ./openvkctl upgrade
```

8. Направьте ваш веб-сервер на `openvk/htdocs`.

* Пример конфигурации для **nginx** доступен [здесь](https://github.com/OpenVK/chandler/blob/master/install/nginx.conf). Убедитесь, что root указывает на `/opt/openvk/htdocs` (или путь, куда вы установили OpenVK).

После завершения вы можете войти под учетной записью системного администратора по умолчанию на самом сайте (регистрация не требуется):

* **Логин**: `admin@localhost.localdomain6`
* **Пароль**: `admin`
  * Рекомендуется сменить пароль учетной записи по умолчанию или отключить её.

> **Предупреждение:** Процедура установки OpenVK изменилась после [реструктуризации кода](https://github.com/OpenVK/openvk/pull/1718/). Теперь не нужно отдельно устанавливать Chandler и ставить OpenVK как расширение. \
> Если вы мигрируете со старой структуры, сделайте бэкап вашей связки Chandler+OpenVK, выполните git pull и запустите скрипт миграции: `php bin/upgrade-structure.php --help`. (При запуске обновления рекомендуется использовать флаг `--extract`, чтобы вынести папку openvk из папки Chandler). \
> После миграции не забудьте изменить DocumentRoot веб-сервера с `htdocs` Chandler на `htdocs` OpenVK.

### Скрипт автоустановки

Вы также можете использовать скрипт автоустановки для FreeBSD 15:

```shell
pkg install wget
wget https://github.com/OpenVK/openvk/raw/refs/heads/master/install/automated/freebsd-15/install
chmod +x install
./install
```

### Уведомления в реальном времени

Вы можете установить Redis для работы уведомлений в реальном времени (если включена база данных событий в конфигурации).

1. Установите Redis через пакетный менеджер вашей ОС
2. Установите `notificationsBroker` в секции `credentials` в значение `true`

Все должно заработать из коробки. Если нет — проверьте настройки Redis и OpenVK.

> **Предупреждение:** Поддержка Kafka в OpenVK была объявлена устаревшей начиная с [этого коммита](https://github.com/OpenVK/openvk/commit/e99cdd1b08002dbfbd1aaef2cbc52ccbe34026c6) и больше не используется в кодовой базе OpenVK. Если вы видите упоминания Kafka в исходном коде, конфигурации или документации, имейте в виду, что это больше не поддерживается.

### Если мой сайт использует OpenVK, обязан ли я открывать его исходный код?

Это зависит от ситуации. Вы можете не публиковать исходный код, если не планируете распространять бинарные/исполняемые сборки вашего сайта. Если программное обеспечение вашего сайта распространяется, оно может оставаться закрытым при условии, что OpenVK не используется как основное приложение и не модифицируется. Если вы изменили OpenVK под свои нужды или ваша работа основана на нем, и вы планируете распространять её, вы должны лицензировать её на условиях любой LGPL-совместимой лицензии (OSL, GPL, LGPL и т.д.).

---

## Локализация

Хотите перевести наш проект на свой язык? Вы можете выбрать любой вариант:

* [Weblate](https://hosted.weblate.org/engage/openvk/) (простой способ)
* Отправить нам Pull Request (сложный способ)

Локализация находится в репозитории "locales". Список языков поддерживается в файле list.yml, а сами переводы хранятся в формате iOS String.

---

## Где получить помощь?

Вы можете связаться с нами через:

* [Баг-трекер](https://github.com/OpenVK/openvk/issues)
* [GitHub Discussions](https://github.com/openvk/openvk/discussions)
* [Систему тикетов](/support?act=new)
* [Discord-сервер](https://discord.gg/8TDpTeRw5k)
* Telegram-чат: перейдите в [наш канал](https://t.me/openvkenglish) и откройте обсуждение в меню канала.
* Matrix-чат: `#openvk:matrix.org`

> **Внимание:** Баг-трекер, обсуждения, Telegram, Discord и Matrix-чат являются публичными местами, а систему тикетов обслуживают волонтеры. Если вам необходимо сообщить о чем-то, что не должно сразу раскрываться публично (например, об уязвимости), пожалуйста, свяжитесь с нами напрямую по электронной почте: **contact [at] openvk [dot] org**
