# <img align="right" src="https://github.com/openvk/openvk/raw/master/Web/static/img/logo_shadow.png" alt="openvk" title="openvk" width="15%">OpenVK

_[English](README.md)_

**OpenVK** это попытка создать простую CMS, которая ~~косплеит~~ имитирует старый ВКонтакте. Представленный здесь код пока не стабилен.

ВКонтакте принадлежит Павлу Дурову и VK Group.

Честно говоря, мы даже не знаем, работает ли она вообще. Однако, эта версия поддерживается, и мы будем рады принять ваши сообщения об ошибках [в нашем баг-трекере](https://github.com/openvk/openvk/projects/1). Вы также можете отправлять их через [вкладку "Помощь"](https://openvk.su/support?act=new) (для этого вам понадобится учетная запись OVK).

## Когда релиз?

Пожалуйста, используйте ветку master, так как в ней больше всего изменений.

Обновление исходного кода выполняется с помощью этой команды: `git pull`.

## Инстанции

* **[openvk.su](https://openvk.su/)**
* **[openvk.uk](https://openvk.uk)** - официальное зеркало openvk.su (<https://t.me/openvkch/1609>)
* [social.fetbuk.ru](http://social.fetbuk.ru/)

## Могу ли я создать свою собственную инстанцию OpenVK?

Да! И всегда пожалуйста.

Однако, OVK использует Chandler Application Server. Это программное обеспечение требует расширений, которые могут быть не предоставлены вашим хостинг-провайдером (а именно, sodium и yaml. эти расширения доступны на большинстве хостингов ISPManager).

Если вы хотите, вы можете добавить вашу инстанцию в список выше, чтобы люди могли зарегистрироваться там.

### Процедура установки

1. Установите PHP 7.4, веб-сервер, Composer, Node.js, Yarn и [Chandler](https://github.com/openvk/chandler)

* PHP 8 еще **не** тестировался, поэтому не стоит ожидать, что он будет работать.

2. Установите [commitcaptcha](https://github.com/openvk/commitcaptcha) и OpenVK в качестве расширений Chandler следующим образом:

```bash
git clone https://github.com/openvk/openvk /path/to/chandler/extensions/available/openvk
git clone https://github.com/openvk/commitcaptcha /path/to/chandler/extensions/available/commitcaptcha
```

3. И включите их:

```bash
ln -s /path/to/chandler/extensions/available/commitcaptcha /path/to/chandler/extensions/enabled/
ln -s /path/to/chandler/extensions/available/openvk /path/to/chandler/extensions/enabled/
```

4. Импортируйте `install/init-static-db.sql` в **ту же базу данных**, в которую вы установили Chandler, и импортируйте все SQL файлы из папки `install/sqls` в **ту же базу данных**
5. Импортируйте `install/init-event-db.sql` в **отдельную базу данных**
6. Скопируйте `openvk-example.yml` в `openvk.yml` и измените параметры
7. Запустите `composer install` в директории OpenVK
8. Перейдите в `Web/static/js` и выполните `yarn install`
9. Установите `openvk` в качестве корневого приложения в файле `chandler.yml`

После этого вы можете войти как системный администратор в саму сеть (регистрация не требуется):

* **Логин**: `admin@localhost.localdomain6`
* **Пароль**: `admin`
  * Перед использованием встроенной учетной записи рекомендуется сменить пароль.

Полный пример инструкции по установке CentOS 8 также доступен [здесь](https://docs.openvk.su/openvk_engine/centos8_installation/).

### Если мой сайт использует OpenVK, должен ли я публиковать его исходные тексты?

Вам рекомендуется это делать. Однако мы не следим за этим. Вы можете держать свои исходные тексты при себе (если только вы не распространяете свой дистрибутив OpenVK среди других людей).

Вы также не обязаны публиковать исходные тексты ваших тематических пакетов и плагинов.

## Где я могу получить помощь?

Вы можете связаться с нами через:

* [Баг-трекер](https://github.com/openvk/openvk/projects/1)
* [Помощь в OVK](https://openvk.su/support?act=new)
* Telegram-чат: Перейдите на [наш канал](https://t.me/openvkch) и откройте обсуждение в меню нашего канала.
* [Reddit](https://www.reddit.com/r/openvk/)
* [Обсуждения](https://github.com/openvk/openvk/discussions)
* Чат в Matrix: #ovk:matrix.org

**Внимание**: баг-трекер, телеграм- и matrix-чат являются публичными местами, и жалобы в OVK обслуживается волонтерами. Если вам нужно сообщить о чем-то, что не должно быть раскрыто широкой публике (например, сообщение об уязвимости), пожалуйста, свяжитесь с нами напрямую по этому адресу: **openvk [собака] tutanota [точка] com**.

<a href="https://codeberg.org/OpenVK/openvk">
    <img alt="Получить на Codeberg" src="https://codeberg.org/Codeberg/GetItOnCodeberg/media/branch/main/get-it-on-blue-on-white.png" height="60">
</a>
