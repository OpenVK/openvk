# <img align="right" src="/Web/static/img/logo_shadow.png" alt="openvk" title="openvk" width="15%">OpenVK

_[English](README.md)_

**OpenVK** — это попытка создать простую CMS, которая ~~косплеит~~ имитирует старый ВКонтакте. На данный момент, представленный здесь исходный код проекта пока не является стабильным.

> [!WARNING]
> **OpenVK является любительской разработкой и никак не связан с ВКонтакте и компанией ООО "ВК"**

Честно говоря, мы даже не знаем, работает ли она вообще. Однако, эта версия поддерживается, и мы будем рады принять ваши сообщения об ошибках [в нашем баг-трекере](https://github.com/openvk/openvk/projects/1). Вы также можете отправлять их через [вкладку "Помощь"](https://openvk.org/support?act=new) (для этого вам понадобится учетная запись OpenVK).

## Когда выйдет релизная версия?

Мы выпустим OpenVK, как только он будет готов. На данный момент Вы можете:
* Склонировать master ветку репозитория командой `git clone` (используйте `git pull` для обновления)
* Взять готовую сборку OpenVK из [GitHub Actions](https://nightly.link/openvk/archive/workflows/nightly/master/OpenVK%20Archive.zip)

## Инстанции

Список инстанций находится в [нашей вики этого репозитория](https://github.com/openvk/openvk/wiki/Instances-(RU)).

## Могу ли я создать свою собственную инстанцию OpenVK?

Да! И всегда пожалуйста.

Однако, OpenVK использует Chandler Application Server. Это программное обеспечение требует расширений, которые могут быть не предоставлены вашим хостинг-провайдером (а именно, sodium и yaml. Эти расширения доступны на большинстве хостингов ISPManager).

Если хотите, вы можете добавить вашу инстанцию в список выше, чтобы люди могли зарегистрироваться там.

### Процедура установки

1. Установите PHP 8.2, веб-сервер, Composer, Node.js, NPM и [Chandler](https://github.com/openvk/chandler)

2. Установите MySQL-совместимую базу данных.

* Мы рекомендуем использовать MariaDB или Percona Server, но любая MySQL-совместимая база данных должна работать.
* Сервер должен поддерживать хотя бы MySQL 5.6, рекомендуется использовать MySQL 8.0+.
* Поддержка для MySQL 4.1+ находится в процессе, а пока замените `utf8mb4` и `utf8mb4_unicode_520_ci` на `utf8` и `utf8_unicode_ci` в SQL-файлах, соответственно.

3. Установите [commitcaptcha](https://github.com/openvk/commitcaptcha) и OpenVK в качестве расширений Chandler:

```bash
git clone https://github.com/openvk/openvk /path/to/chandler/extensions/available/openvk
git clone https://github.com/openvk/commitcaptcha /path/to/chandler/extensions/available/commitcaptcha
```

4. И включите их:

```bash
ln -s /path/to/chandler/extensions/available/commitcaptcha /path/to/chandler/extensions/enabled/
ln -s /path/to/chandler/extensions/available/openvk /path/to/chandler/extensions/enabled/
```

5. Импортируйте `install/init-static-db.sql` в **ту же базу данных**, в которую вы установили Chandler, и импортируйте все SQL файлы из папки `install/sqls` в **ту же базу данных**
6. Импортируйте `install/init-event-db.sql` в **отдельную базу данных** (Яндекс.Clickhouse также может быть использован, настоятельно рекомендуется)
7. Скопируйте `openvk-example.yml` в `openvk.yml` и измените параметры под свои нужды
8. Запустите `composer install` в директории OpenVK
9. Запустите `composer install` в директории commitcaptcha
10. Перейдите в `Web/static/js` и выполните `npm install`
11. Установите `openvk` в качестве корневого приложения в файле `chandler.yml`

После этого вы можете войти как системный администратор в саму сеть (регистрация не требуется):

* **Логин**: `admin@localhost.localdomain6`
* **Пароль**: `admin`
  * Перед использованием встроенной учетной записи рекомендуется сменить пароль или отключить её.

💡Запутались? Полное руководство по установке доступно [здесь](https://docs.openvk.org/openvk_engine/centos8_installation/) (CentOS 8 [и](https://almalinux.org/ru/) [семейство](https://yum.oracle.com/oracle-linux-isos.html)).

### Уведомления в реальном времени

Вы можете установить Redis для уведомлений в реальном времени (если вы, конечно, включили Event DB в конфиге). 

1. Установите Redis в вашу операционную систему 
2. Поставьте `notificationsBroker` внутри `credentials` на `true`

Оно должно заработать сразу же из коробки. Если нет, попробуйте отредактировать настройки Redis и OpenVK.

> [!WARNING]
> Kafka в OpenVK устарела начиная с [этого коммита](https://github.com/OpenVK/openvk/commit/e99cdd1b08002dbfbd1aaef2cbc52ccbe34026c6) и больше не используется в кодовой базе OpenVK. Если вы наткнулись на любое упоминание Kafka в исходном коде, в конфиге или в документации, мы должны вас оповестить о том, что оно не будет работать и информация о ней устарела. Совсем. 

# Установка в Docker/Kubernetes
Подробные иструкции можно найти в `install/automated/docker/README.md` и `install/automated/kubernetes/README.md` соответственно.

### Если мой сайт использует OpenVK, должен ли я публиковать его исходные тексты?

Это зависит от обстоятельств. Вы можете оставить исходные тексты при себе, если не планируете распространять бинарники вашего сайта. Если программное обеспечение вашего сайта должно распространяться, оно может оставаться не-OSS при условии, что OpenVK не используется в качестве основного приложения и не модифицируется. Если вы модифицировали OpenVK для своих нужд или ваша работа основана на нем и вы планируете ее распространять, то вы должны лицензировать ее на условиях любой совместимой с LGPL лицензии (например, OSL, GPL, LGPL и т.д.).

## Где я могу получить помощь?

Вы можете связаться с нами через:

* [Баг-трекер](https://github.com/openvk/openvk/projects/1)
* [Помощь в OVK](https://openvk.org/support?act=new)
* Telegram-чат: Перейдите на [наш канал](https://t.me/openvk) и откройте обсуждение в меню нашего канала.
* [Reddit](https://www.reddit.com/r/openvk/)
* [GitHub Discussions](https://github.com/openvk/openvk/discussions)
* Чат в Matrix: #ovk:matrix.org

**Внимание**: баг-трекер, форум, Telegram- и Matrix-чат являются публичными местами, и жалобы в OVK обслуживается волонтерами. Если вам нужно сообщить о чем-то, что не должно быть раскрыто широкой публике (например, сообщение об уязвимости), пожалуйста, свяжитесь с нами напрямую по этому адресу: **contact [собачка] openvk [точка] org**.

<a href="https://codeberg.org/OpenVK/openvk">
    <img alt="Get it on Codeberg" src="https://codeberg.org/Codeberg/GetItOnCodeberg/media/branch/main/get-it-on-blue-on-white.png" height="60">
</a>
