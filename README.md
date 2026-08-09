# OpenVK

<p align="center">
  <strong>An open-source social network inspired by the classic VKontakte experience.</strong>
</p>

<p align="center">
  <a href="https://github.com/OpenVK/openvk">GitHub</a> •
  <a href="https://github.com/OpenVK/openvk/wiki/Instances">Instances</a> •
  <a href="https://github.com/OpenVK/openvk/issues">Issues</a> •
  <a href="https://github.com/OpenVK/openvk/discussions">Discussions</a> •
  <a href="https://openvk.org/support?act=new">Support</a>
</p>

<p align="center">
  <a href="README_RU.md">🇷🇺 Русский</a>
</p>

---

> [!WARNING]
> **OpenVK is an independent fan project and is not affiliated with, endorsed by, or connected to VKontakte or VK LLC.**
>
> **OpenVK является любительской разработкой и никак не связан с ВКонтакте и компанией ООО «ВК».**

> [!NOTE]
> OpenVK is actively developed and may contain bugs, incomplete features, or breaking changes. Contributions, bug reports, and feedback are welcome.

## ✨ What is OpenVK?

**OpenVK** is an open-source CMS and social-network platform inspired by the classic VKontakte experience.

The project aims to preserve the look, feel, and spirit of the old VK experience while providing a modern, community-driven, and self-hostable platform.

OpenVK can be deployed on your own infrastructure, allowing you to run your own independent instance and customize it to your needs.

### Highlights

* 👤 User accounts and profiles
* 💬 Social interactions and messaging
* 🔔 Real-time notifications with Redis
* 🌍 Multi-language localization
* 🏠 Fully self-hosted instances
* 🐳 Docker deployment
* ☸️ Kubernetes deployment
* 🗄️ MySQL-compatible databases
* 🔧 Community-driven development
* 🌐 Public instance support

---

## 🚀 Quick Start

Want to try OpenVK?

### Download the latest source

```bash
git clone https://github.com/OpenVK/openvk.git
cd openvk
```

Update an existing installation:

```bash
git pull
```

### Download a prebuilt build

You can download the latest nightly OpenVK distribution from the project's build artifacts.

**Nightly builds:**
https://nightly.link/openvk/archive/workflows/nightly/master/OpenVK%20Archive.zip

> [!WARNING]
> Nightly builds may contain experimental changes and are not guaranteed to be production-ready.

---

## 🌐 Public Instances

Don't want to host OpenVK yourself?

Visit the community-maintained instance list:

**OpenVK Instances:**
https://github.com/OpenVK/openvk/wiki/Instances

If you operate an OpenVK instance, you're welcome to add it to the list so other users can discover it.

---

# 🖥️ Self-Hosting

One of OpenVK's main goals is to make running your own instance possible.

You can deploy OpenVK on a VPS, VDS, dedicated server, or other suitable Linux/Unix environment.

> [!IMPORTANT]
> Shared hosting may not provide all required PHP extensions. For the best experience, we recommend using a **VPS, VDS, or dedicated server**.

## System Requirements

| Component        |           Minimum |              Recommended |
| ---------------- | ----------------: | -----------------------: |
| CPU              | Dual-core, 1 GHz+ |                 2+ cores |
| RAM              |              2 GB |        6–8 GB with Redis |
| Database storage |             10 GB |  More depending on usage |
| PHP              |              8.2+ | Latest supported version |
| Database         |  MySQL-compatible |     MySQL 8.0+ / MariaDB |
| Node.js          |          Required | Latest supported version |
| Composer         |          Required | Latest supported version |

### Required PHP Extensions

OpenVK currently requires PHP extensions including:

* `sodium`
* `yaml`

Availability may vary between hosting providers.

---

# 🐳 Docker & Kubernetes

Prefer containerized deployment?

OpenVK includes deployment instructions for:

* 🐳 Docker
* ☸️ Kubernetes

See:

```text
install/automated/docker/README.md
install/automated/kubernetes/README.md
```

These guides contain the deployment-specific configuration and instructions.

---

# ⚙️ Installation

## 1. Install prerequisites

Install the following:

* PHP 8.2+
* Composer
* Node.js and npm
* A compatible web server
* A MySQL-compatible database

We recommend **MariaDB** or **Percona Server**, although other compatible MySQL servers should work.

The database should support at least **MySQL 5.6**.

> [!TIP]
> **MySQL 8.0+ is recommended** for new installations.

### Legacy database compatibility

MySQL 4.1+ support is still a work in progress.

If you need to use an older database version, you may need to replace:

```text
utf8mb4
utf8mb4_unicode_520_ci
```

with:

```text
utf8
utf8_unicode_ci
```

in the relevant SQL files.

---

## 2. Clone OpenVK

```bash
git clone https://github.com/OpenVK/openvk.git /opt/openvk
cd /opt/openvk
```

---

## 3. Install dependencies

Install PHP dependencies:

```bash
composer install
```

Install JavaScript dependencies:

```bash
cd Web/static/js
npm install
```

---

## 4. Configure the database

OpenVK requires **two databases**:

1. Main application database
2. Event database

Configure both databases according to your deployment requirements.

---

## 5. Configure OpenVK

Copy the example configuration:

```bash
cd /opt/openvk
cp openvk-example.yml openvk.yml
```

Then edit:

```text
openvk.yml
```

Configure your:

* Database credentials
* Domain
* Application settings
* Event database
* Notification settings
* Other instance-specific options

---

## 6. Run database migrations

Run:

```bash
cd /opt/openvk
./openvkctl upgrade
```

---

## 7. Configure your web server

Set your web server's document root to:

```text
/opt/openvk/htdocs
```

An example nginx configuration is available in the OpenVK/Chandler repository:

https://github.com/OpenVK/chandler/blob/master/install/nginx.conf

> [!IMPORTANT]
> Make sure your web server points to **OpenVK's `htdocs` directory**, not the project root.

---

# 🔐 Default Administrator Account

A default administrator account is available immediately after installation.

```text
Login:    admin@localhost.localdomain6
Password: admin
```

> [!CAUTION]
> **Change the default administrator password or disable the default account immediately after installation.**
>
> Never leave the default administrator credentials enabled on a publicly accessible production instance.

---

# 🔄 Migrating from the Old Structure

OpenVK's installation architecture changed following the restructuring introduced in:

https://github.com/OpenVK/openvk/pull/1718

You no longer need to install Chandler separately and configure OpenVK as its extension.

### Migrating an existing installation

If you're migrating from the previous Chandler + OpenVK structure:

1. **Back up your entire installation.**
2. Pull the latest OpenVK changes.
3. Review the migration utility:

```bash
php bin/upgrade-structure.php --help
```

4. Run the migration using the recommended `--extract` option when appropriate.
5. Update your web server configuration.
6. Change the web server's `DocumentRoot` from Chandler's `htdocs` to OpenVK's `htdocs`.

> [!WARNING]
> Always create a complete backup before performing a structural migration.

---

# 📦 Automatic Installation

An automated installation script is available for **FreeBSD 15**.

```bash
pkg install wget

wget https://github.com/OpenVK/openvk/raw/refs/heads/master/install/automated/freebsd-15/install

chmod +x install
./install
```

---

# 🔔 Real-Time Notifications

OpenVK can use **Redis** to provide real-time notifications when the Event DB is enabled.

## Enable Redis

### 1. Install Redis

Install Redis using your operating system's package manager.

### 2. Enable the notification broker

In `openvk.yml`, enable:

```yaml
notificationsBroker: true
```

### 3. Start Redis

Start your Redis service and restart OpenVK.

In most configurations, Redis should work without additional changes.

If you experience problems, check:

* Redis configuration
* OpenVK configuration
* Event DB configuration
* Redis connectivity and permissions

---

## Kafka

> [!WARNING]
> **Kafka is deprecated and is no longer supported by OpenVK.**

Kafka support was removed from the project.

If you encounter Kafka references in older documentation, configuration files, or source code, they should not be considered supported functionality.

Reference commit:

https://github.com/OpenVK/openvk/commit/e99cdd1b08002dbfbd1aaef2cbc52ccbe34026c6

---

# 🌍 Localization

OpenVK supports multiple languages and welcomes new translations.

Want to translate OpenVK into your language?

### Recommended: Weblate

https://hosted.weblate.org/engage/openvk/

### Pull Requests

You can also contribute translations directly through pull requests.

Localization is maintained in the `locales` repository.

Languages are listed in:

```text
list.yml
```

Translations use the **iOS Strings** format.

---

# 📜 Licensing & Source Distribution

If you use OpenVK as part of your own website or software, your obligations may depend on how OpenVK is used, modified, and distributed.

In general:

* You may keep your website's source code private if you do not distribute the website's binaries.
* Distributed software may remain closed-source when OpenVK is not the primary application and has not been modified.
* Modified OpenVK code or derivative work that is redistributed should be licensed under an **LGPL-compatible license**, such as OSL, GPL, or LGPL.

> [!IMPORTANT]
> This section is only a high-level summary and should not be treated as legal advice. Always consult the project's actual license files and applicable licensing terms before distributing modified versions of OpenVK.

---

# 🤝 Contributing

OpenVK is a community-driven open-source project, and contributions are always welcome.

There are many ways to contribute:

* 🐛 Report bugs
* 🔧 Fix existing issues
* ✨ Add features
* 📚 Improve documentation
* 🌍 Add translations
* 🧪 Test new releases
* 💡 Suggest improvements

Before contributing, check the repository's existing issues, discussions, and contribution guidelines.

Every contribution — whether it's a small documentation fix or a major feature — helps the project grow.

---

# 💬 Community & Support

Need help, want to report a problem, or simply want to talk about OpenVK?

### 🐛 Bug Reports

https://github.com/OpenVK/openvk/issues

### 💡 Discussions

https://github.com/OpenVK/openvk/discussions

### 🎫 Support Tickets

https://openvk.org/support?act=new

An OpenVK account is required to submit a ticket.

### 💬 Discord

https://discord.gg/8TDpTeRw5k

### ✈️ Telegram

https://t.me/openvkenglish

### 🌐 Matrix

```text
#openvk:matrix.org
```

> [!IMPORTANT]
> GitHub Issues, Discussions, Telegram, Discord, and Matrix are public communication channels.
>
> The ticketing system is operated by volunteers.
>
> **Do not publicly disclose security vulnerabilities or other sensitive information.**
>
> For responsible disclosure of vulnerabilities, contact:
>
> **contact [at] openvk [dot] org**

---

# ⭐ Support OpenVK

If you like OpenVK, there are several ways you can help:

* ⭐ Star the repository
* 🐛 Report bugs
* 💡 Share ideas
* 🔧 Submit pull requests
* 🌍 Help translate the project
* 📖 Improve the documentation
* 🏠 Run and maintain an instance

Your contributions help keep the project alive and make OpenVK better for everyone.

---

<p align="center">
  <strong>OpenVK — bringing back the classic social-network experience.</strong>
</p>

<p align="center">
  Made with ❤️ by the OpenVK community.
</p>
