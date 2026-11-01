OpenVK-KB-Heading: About OpenVK Engine

# About OpenVK Engine

**OpenVK** is an attempt to create a simple CMS that ~~cosplays~~ imitates old VKontakte. Code provided here is not stable yet.

> **Warning:** **OpenVK is a fan project, not affiliated in any way with VKontakte and its company VK LLC.** \
> **OpenVK является любительской разработкой и никак не связан с ВКонтакте и компанией ООО "ВК".**

To be honest, we don't know whether it even works. However, this version is maintained and we will be happy to accept your bug reports [in our bug tracker](https://github.com/openvk/openvk/issues). You should also be able to submit them using the [ticketing system](/support?act=new) (you will need an OpenVK account for this).

---

## When's the release?

We will release OpenVK as soon as it's ready. As for now, you can:
* `git clone` this repo's master branch (use `git pull` to update)
* Grab a prebuilt OpenVK distro from [GitHub artifacts](https://nightly.link/openvk/archive/workflows/nightly/master/OpenVK%20Archive.zip)

---

## Instances

A list of instances can be found in [our wiki of this repository](https://github.com/openvk/openvk/wiki/Instances).

---

## Can I create my own OpenVK instance?

Yes! And you are very welcome to.

However, OVK requires extensions that may not always be available on shared web hostings (namely, `sodium` and `yaml`; these extensions are available on most ISPmanager hostings). That's why it is recommended to host your instances on VPS/VDS or dedicated servers.

If you want, you can add your instance to the list above so that people can register there.

### System requirements

Here is our minimum hardware recommendation:

* **CPU:** Any dual-core 1GHz+ CPU or more powerful
* **RAM:** At least 2GB RAM (we recommend 6GB or 8GB for OpenVK with Redis)
* **Minimum database space:** 10GB

### OS and PHP 8.4 Availability

OpenVK requires **PHP 8.4 or later**. PHP 8.4 is available on:
* **Ubuntu:** 24.10+ (natively in official repositories) or 22.04 / 24.04 LTS (via `ppa:ondrej/php`)
* **Debian:** 13 (Trixie) or Debian 11 / 12 (via `deb.sury.org`)
* **Alpine Linux:** 3.21+ (package `php84`)
* **RHEL / AlmaLinux / Rocky Linux:** 8 / 9 (via Remi repository `php:remi-8.4`)
* **Fedora:** 41+
* **Arch Linux:** In official extra repository
* **FreeBSD:** 14+ / 15 (`pkg install php84`)

### Looking for Docker or Kubernetes deployment?
See `install/automated/docker/README.md` and `install/automated/kubernetes/README.md` for Docker and Kubernetes deployment instructions.

### Installation procedure

1. Install PHP 8.4 or later, web-server, Composer, and NPM.

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

2. Install MySQL-compatible database.

* We recommend using MariaDB or Percona Server, but any MySQL-compatible server should work too.
* Server should be compatible with at least MySQL 5.6, MySQL 8.0+ is recommended.
* Support for MySQL 4.1+ is WIP, replace `utf8mb4` and `utf8mb4_unicode_520_ci` with `utf8` and `utf8_unicode_ci` in SQLs.

3. Clone OpenVK:

```bash
git clone https://github.com/openvk/openvk /opt/openvk
```

4. Install dependencies:

```bash
cd /opt/openvk && composer install
cd Web/static/js && npm install
```

5. Configure your database: you need 2 databases — one for the main data, another for events.

6. Copy `openvk-example.yml` to `openvk.yml` and edit to your liking.  

7. Run database migrations:

```bash
cd /opt/openvk && ./openvkctl upgrade
```

8. Point your web server to `openvk/htdocs`.

* Example config for **nginx** is available [here](https://github.com/OpenVK/chandler/blob/master/install/nginx.conf). Make sure that root is set to `/opt/openvk/htdocs` (or wherever you installed OpenVK).

Once you are done, you can login in a default system administrator account on the site itself (no registration required):

* **Login**: `admin@localhost.localdomain6`
* **Password**: `admin`
  * It is recommended to change the password of the default account or disable it.

> **Warning:** OpenVK installation procedure has been changed after [code restructurisation](https://github.com/OpenVK/openvk/pull/1718/). Now you don't need to install Chandler separately and install OpenVK as its extension. \
> If you are migrating from the old structure, make a backup of your Chandler+OpenVK installation, do a git pull, and consult the migration script: `php bin/upgrade-structure.php --help`. (When running the upgrade, it is recommended to use `--extract` flag, to move openvk dir out of Chandler's folder). \
> After the migration, do not forget to change webserver's DocumentRoot from Chandler's `htdocs` to OpenVK's `htdocs`.

### Auto-install script

You can also use auto-install script for FreeBSD 15:

```shell
pkg install wget
wget https://github.com/OpenVK/openvk/raw/refs/heads/master/install/automated/freebsd-15/install
chmod +x install
./install
```

### Real-time notifs

You can install Redis to take advantage of real-time notifications (if you enabled Event DB in config). 

1. Install Redis from your beloved package manager in your OS
2. Set `notificationsBroker` under `credentials` to `true`

It should work out of box. If not, tweak Redis and OpenVK config settings.

> **Warning:** Kafka in OpenVK has been deprecated since [this commit](https://github.com/OpenVK/openvk/commit/e99cdd1b08002dbfbd1aaef2cbc52ccbe34026c6) and is no longer used in the OpenVK codebase. If you see any mention of Kafka in source code, config or documentation, you should know that this will not work at all.

### If my website uses OpenVK, should I release its sources?

It depends. You can keep the sources to yourself if you do not plan to distribute your website binaries. If your website software must be distributed, it can stay non-OSS provided the OpenVK is not used as a primary application and is not modified. If you modified OpenVK for your needs or your work is based on it and you are planning to redistribute this, then you should license it under terms of any LGPL-compatible license (like OSL, GPL, LGPL etc).

---

## Localization

Want to translate our project to your native language? You can try either:

* [Weblate](https://hosted.weblate.org/engage/openvk/) (simple way)
* Send Pull Request to us (hard way)

Localization is located in "locales" repository. List of languages is maintained in list.yml file, and the languages itself are in iOS String format.

---

## Where can I get assistance?

You may reach out to us via:

* [Bug Tracker](https://github.com/OpenVK/openvk/issues)
* [GitHub Discussions](https://github.com/openvk/openvk/discussions)
* [Ticketing System](/support?act=new)
* [Discord Server](https://discord.gg/8TDpTeRw5k)
* Telegram Chat: Go to [our channel](https://t.me/openvkenglish) and open discussion in our channel menu.
* Matrix Chat: `#openvk:matrix.org`

> **Attention:** Bug tracker, discussions, Telegram, Discord and Matrix chat are public places, ticketing system is being served by volunteers. If you need to report something that should not be immediately disclosed to general public (for instance, a vulnerability), please contact us directly via this email: **contact [at] openvk [dot] org**