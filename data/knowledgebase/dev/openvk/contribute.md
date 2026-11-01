OpenVK-KB-Heading: Contributing to OpenVK

# Contributing to OpenVK

OpenVK is a community-driven open-source project developed under the **GNU AGPLv3** license. We welcome contributions from developers of all skill levels: bug fixes, performance improvements, new features, and documentation updates.

---

## 1. Getting Started & Git Workflow

All development takes place on GitHub in the [openvk/openvk](https://github.com/openvk/openvk) repository.

1. **Fork the repository:** Fork `openvk/openvk` to your personal GitHub account.
2. **Clone your fork locally:**
```bash
git clone https://github.com/<your-username>/openvk /opt/openvk
cd /opt/openvk
```
3. **Create a dedicated branch** from `master`:
```bash
git checkout -b fix/user-settings-validation
```
4. **Make and test your changes.**
5. **Commit with descriptive messages:**
```bash
git commit -m "Fix profile avatar upload validation on PHP 8.4"
```
6. **Push to your fork and submit a Pull Request** targeting the `master` branch.

---

## 2. Contribution Requirements & Coding Standards

To maintain clean and maintainable codebase, all contributions must comply with the following standards:

### PHP Standards (Backend)
* **PHP Version:** Code must be compatible with **PHP 8.4+**.
* **Strict Typing:** All PHP files must declare strict types at the very top:
```php
<?php

declare(strict_types=1);
```
* **PSR Standards:** Adhere to **PSR-12** coding style (4 spaces indentation, camelCase methods, PascalCase classes).
* **Type Hinting:** Specify parameter types, return types, and nullable annotations wherever possible.

### JavaScript & Frontend
* Write clean, vanilla **ES6+** JavaScript.
* Avoid adding large third-party dependencies unless strictly necessary.
* Place client-side scripts under `Web/static/js/` and compile/install via npm if applicable.

### Templates (Latte) & CSS
* UI templates use the **Latte** templating engine (`Web/Presenters/templates/`).
* Stylesheets are written in **Vanilla CSS** (`Web/static/css/`) following existing class naming conventions.

---

## 3. Mandatory Documentation Requirement

When adding or updating features:

* **API Methods:** If you add or modify an API method, you **must** document it in `data/knowledgebase/dev/methods/` (both English `.md` and Russian `.ru.md`).
* **Data Models:** Update corresponding data model descriptions in `data/knowledgebase/dev/models/`.
* **Localization (i18n):** Add all newly introduced UI strings to both `locales/en.strings` and `locales/ru.strings`.

---

## 4. Testing & Verification

Before submitting a Pull Request:

1. Verify PHP syntax:
```bash
php -l path/to/ModifiedFile.php
```
2. Test database migrations if your change touches schema:
```bash
./openvkctl upgrade
```
3. Test the functionality manually in a browser and via API client.
4. Ensure no warnings or errors appear in PHP error logs (`/logs/` or `/var/log/php-fpm`).

---

## 5. Review and Merge Process

Once submitted, core maintainers will review your Pull Request. Be open to feedback and suggestions. After approval, your changes will be merged into the `master` branch.
