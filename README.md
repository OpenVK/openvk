# <img align="right" src="https://github.com/openvk/openvk/raw/master/Web/static/img/logo_shadow.png" alt="openvk" title="openvk" width="15%">OpenVK

_[Русский](README_RU.md)_

**OpenVK** is an attempt to create a simple CMS that ~~cosplays~~ imitates old VK. Code provided here is not stable yet.

VKontakte belongs to Pavel Durov and VK Group.

To be honest, we don't know whether it even works. However, this version is maintained and we will be happy to accept your bugreports [in our bug-tracker](https://github.com/openvk/openvk/projects/1). You should also be able to submit them using [ticketing system](https://openvk.su/support?act=new) (you will need an OVK account for this).

## When's the release?

We will release OpenVK as soon as it's ready. As for now you can:
* `git clone` this repo's master branch (use `git pull` to update)
* Grab a prebuilt OpenVK distro from [GitHub artifacts](https://github.com/openvk/archive/actions/workflows/nightly.yml)

## Instances

* **[openvk.su](https://openvk.su/)**
* **[openvk.uk](https://openvk.uk)** - official mirror of openvk.su (<https://t.me/openvkch/1609>)
* [social.fetbuk.ru](http://social.fetbuk.ru/)

## Can I create my own OpenVK instance?

Yes! And you're very welcome to.

However, OVK makes use of Chandler Application Server. This software requires extensions, that may not be provided by your hosting provider (namely, sodium and yaml. these extensions are available on most of ISPManager hostings).

If you want, you can add your instance to the list above so that people can register there.

### Installation procedure

1. Install PHP 7.4, web-server, Composer, Node.js, Yarn and [Chandler](https://github.com/openvk/chandler)

* PHP 8 has **not** yet been tested, so you should not expect it to work. (edit: it does not work).

2. Install [commitcaptcha](https://github.com/openvk/commitcaptcha) and OpenVK as Chandler extensions like this:

```bash
git clone https://github.com/openvk/openvk /path/to/chandler/extensions/available/openvk
git clone https://github.com/openvk/commitcaptcha /path/to/chandler/extensions/available/commitcaptcha
```

3. And enable them:

```bash
ln -s /path/to/chandler/extensions/available/commitcaptcha /path/to/chandler/extensions/enabled/
ln -s /path/to/chandler/extensions/available/openvk /path/to/chandler/extensions/enabled/
```

4. Import `install/init-static-db.sql` to the **same database** you installed Chandler to and import all sqls from `install/sqls` to the **same database**
5. Import `install/init-event-db.sql` to a **separate database** (Yandex.Clickhouse can also be used, higly recommended)
6. Copy `openvk-example.yml` to `openvk.yml` and change options to your liking
7. Run `composer install` in OpenVK directory
8. Run `composer install` in commitcaptcha directory
9. Move to `Web/static/js` and execute `yarn install`
10. Set `openvk` as your root app in `chandler.yml`

Once you are done, you can login as a system administrator on the network itself (no registration required):

* **Login**: `admin@localhost.localdomain6`
* **Password**: `admin`
  * It is recommended to change the password of the built-in account or disable it.

💡Confused? Full installation walkthrough is available [here](https://docs.openvk.su/openvk_engine/centos8_installation/) (CentOS 8 [and](https://almalinux.org/) [family](https://yum.oracle.com/oracle-linux-isos.html)).

### If my website uses OpenVK, should I release it's sources?

It depends. You can keep the sources to yourself if you do not plan to distribute your website binaries. If your website software must be distributed, it can stay non-OSS provided the OpenVK is not used as a primary application and is not modified. If you modified OpenVK for your needs or your work is based on it and you're planning to redistribute this, then you should license it under terms of any LGPL-compatible license (like OSL, GPL, LGPL etc).

## Where can I get assistance?

You may reach out to us via:

* [Bug-tracker](https://github.com/openvk/openvk/projects/1)
* [Ticketing system](https://openvk.su/support?act=new)
* Telegram chat: Go to [our channel](https://t.me/openvkenglish) and open discussion in our channel menu.
* [Reddit](https://www.reddit.com/r/openvk/)
* [Discussions](https://github.com/openvk/openvk/discussions)
* Matrix chat: #openvk:matrix.org

**Attention**: bug tracker, board, telegram and matrix chat are public places. And ticketing system is being served by volunteers. If you need to report something, that shouldn't be immediately disclosed to general public (for instance, vulnerability report), please use contact us directly at this email: **openvk [at] tutanota [dot] com**

<a href="https://codeberg.org/OpenVK/openvk">
    <img alt="Get it on Codeberg" src="https://codeberg.org/Codeberg/GetItOnCodeberg/media/branch/main/get-it-on-blue-on-white.png" height="60">
</a>
