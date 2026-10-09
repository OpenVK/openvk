OpenVK-KB-Heading: Quick Start Tutorial

# Quick Start Tutorial

This guide provides a step-by-step walkthrough of setting up a local OpenVK development environment and executing your first API requests.

---

## 1. Setting Up Local Environment

Ensure you have installed PHP 8.4+, Composer, MySQL/MariaDB, and Node.js.

```bash
git clone https://github.com/openvk/openvk /opt/openvk
cd /opt/openvk
composer install
cd Web/static/js && npm install && cd ../../..
cp openvk-example.yml openvk.yml
```

---

## 2. Initializing Database

Create your database and run migrations:

```bash
./openvkctl upgrade
```

---

## 3. Launching Built-in PHP Development Server

For quick local testing without configuring Nginx:

```bash
php -S 127.0.0.1:8000 -t htdocs/
```

Navigate to `http://127.0.0.1:8000` in your web browser. You can log in using default credentials `admin@localhost.localdomain6` / `admin`.
