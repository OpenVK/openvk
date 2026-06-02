# <img align="right" src="/Web/static/img/logo_shadow.png" alt="openvk" title="openvk" width="15%">OpenVK

_[Русский](README_RU.md)_

**OpenVK** is an attempt to create a simple CMS that ~~cosplays~~ imitates old VKontakte. Code provided here is not stable yet. 

> [!WARNING]
> **OpenVK it is fan project, not affiliated in any way with VKontakte and it's company VK LLC. Below is the same message in Russian.** \
> **OpenVK является любительской разработкой и никак не связан с ВКонтакте и компанией ООО "ВК".**

To be honest, we don't know whether if it even works. However, this version is maintained and we will be happy to accept your bugreports [in our bug tracker](https://github.com/openvk/openvk/projects/1). You should also be able to submit them using [ticketing system](https://openvk.org/support?act=new) (you will need an OpenVK account for this).

## When's the release?

We will release OpenVK as soon as it's ready. As for now, you can:
* `git clone` this repo's master branch (use `git pull` to update)
* Grab a prebuilt OpenVK distro from [GitHub artifacts](https://nightly.link/openvk/archive/workflows/nightly/master/OpenVK%20Archive.zip)

## Instances

A list of instances can be found in [our wiki of this repository](https://github.com/openvk/openvk/wiki/Instances).

## Can I create my own OpenVK instance?

Yes! And you are very welcome to.

However, OVK makes use of Chandler Application Server. This software requires extensions, that may not be provided by your hosting provider (namely, sodium and yaml. these extensions are available on most of ISPManager hostings).

If you want, you can add your instance to the list above so that people can register there.

### System requirements

Here is our minimum hardware recommendation:

* **CPU:** Any dual-core 1GHz+ CPU or more powerful
* **RAM:** At least 2GB RAM (we recommend 6GB or 8GB for OpenVK with Redis)
* **Minimum database space:** 10GB

### Installation procedure

1. Install PHP 8.2, web-server, Composer, Node.js, NPM and [Chandler](https://github.com/openvk/chandler)

2. Install MySQL-compatible database.

* We recommend using MariaDB or Percona Server, but any MySQL-compatible server should work too.
* Server should be compatible with at least MySQL 5.6, MySQL 8.0+ is recommended.
* Support for MySQL 4.1+ is WIP, replace `utf8mb4` and `utf8mb4_unicode_520_ci` with `utf8` and `utf8_unicode_ci` in SQLs.

3. Install [commitcaptcha](https://github.com/openvk/commitcaptcha) and OpenVK as Chandler extensions like this:

```bash
git clone https://github.com/openvk/openvk /path/to/chandler/extensions/available/openvk
git clone https://github.com/openvk/commitcaptcha /path/to/chandler/extensions/available/commitcaptcha
```

4. And enable them:

```bash
ln -s /path/to/chandler/extensions/available/commitcaptcha /path/to/chandler/extensions/enabled/
ln -s /path/to/chandler/extensions/available/openvk /path/to/chandler/extensions/enabled/
```

5. You need to set up 2 databases: one for main data (it is be configured in `chandler.yml`), and another one for events (it is configured in `openvk.yml`)
6. Copy `openvk-example.yml` to `openvk.yml` and change options to your liking
7. Run `composer install` in OpenVK directory
8. Run `composer install` in commitcaptcha directory
9. Move to `Web/static/js` and execute `npm install`
10. Set `openvk` as your root app in `chandler.yml`
11. Run database migrations by executing `./openvkctl upgrade`

Once you are done, you can login as a system administrator on the network itself (no registration required):

* **Login**: `admin@localhost.localdomain6`
* **Password**: `admin`
  * It is recommended to change the password of the built-in account or disable it.

💡 Confused? Full installation walkthrough is available [here](https://docs.openvk.org/openvk_engine/centos8_installation/) (CentOS 8 [and](https://almalinux.org/) [family](https://yum.oracle.com/oracle-linux-isos.html)).

### Real-time notifs

You can install Redis to take advantage of real-time notifications (if you enabled Event DB in config). 

1. Install Redis from your beloved package manager in your OS
2. Set `notificationsBroker` under `credentials` to `true`

It should work out of box. If not, tweak Redis and OpenVK config settings

> [!WARNING]
> Kafka in OpenVK was been deprecated since [this commit](https://github.com/OpenVK/openvk/commit/e99cdd1b08002dbfbd1aaef2cbc52ccbe34026c6) and no longer used in OpenVK codebase. If you see any mention of Kafka in source code, config or documentation, you should know that this will not work at all. 

### Looking for Docker or Kubernetes deployment?
See `install/automated/docker/README.md` and `install/automated/kubernetes/README.md` for Docker and Kubernetes deployment instructions.

### If my website uses OpenVK, should I release its sources?

It depends. You can keep the sources to yourself if you do not plan to distribute your website binaries. If your website software must be distributed, it can stay non-OSS provided the OpenVK is not used as a primary application and is not modified. If you modified OpenVK for your needs or your work is based on it and you are planning to redistribute this, then you should license it under terms of any LGPL-compatible license (like OSL, GPL, LGPL etc).

## Where can I get assistance?

You may reach out to us via:

* [Bug Tracker](https://github.com/OpenVK/openvk/issues)
* [GitHub Discussions](https://github.com/openvk/openvk/discussions)
* [Ticketing System](https://openvk.org/support?act=new)
* [Discord Server](https://discord.gg/8TDpTeRw5k)
* Telegram Chat: Go to [our channel](https://t.me/openvkenglish) and open discussion in our channel menu.
* Matrix Chat: #openvk:matrix.org

**Attention**: bug tracker, board, Telegram, Discord and Matrix chat are public places, ticketing system is being served by volunteers. If you need to report something that should not be immediately disclosed to general public (for instance, a vulnerability), please contact us directly via this email: **contact [at] openvk [dot] org**

<a href="https://codeberg.org/OpenVK/openvk">
    <img alt="Get it on Codeberg" src="https://codeberg.org/Codeberg/GetItOnCodeberg/media/branch/main/get-it-on-blue-on-white.png" height="60">
</a>
