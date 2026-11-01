OpenVK-KB-Heading: Chandler Framework Architecture

# Chandler Framework Architecture

**Chandler** is the underlying PHP MVC framework and foundation powering OpenVK.

---

## 1. Core Principles

Chandler provides a lightweight, modular MVC architecture tailored for high performance:

* **Session Management:** Encapsulated in `Chandler\Session\Session`.
* **Database Layer:** Database connectivity and queries handled by `Chandler\Database\Database`.
* **Routing & Controllers:** Requests are dispatched to Presenters extending `Chandler\MVC\SimplePresenter` or `openvk\Web\Presenters\OpenVKPresenter`.
* **Templating:** Views are compiled using the Latte templating engine.

---

## 2. Request Lifecycle

1. HTTP requests arrive at `htdocs/index.php`.
2. Router parses the URL and matches it against configured routes.
3. Matching presenter is instantiated and the corresponding action method (e.g. `renderDevelopersArticle()`) executes.
4. Data is assigned to `$this->template` and rendered to HTML.
