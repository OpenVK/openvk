OpenVK-KB-Heading: Bug Tracker and Contributing

# Bug Tracker and Contributing to OpenVK

OpenVK is an open-source social platform developed by the community under the **GNU AGPLv3** license.

We welcome contributions: bug reports, API improvement proposals, UI enhancements, and Pull Requests with fixes or new features.

---

## Submitting a Bug Report

If you discover an error on the site, an unexpected API response, or a problem in documentation:

1. **Check Existing Issues:**
   Visit the GitHub Issues tracker: [https://github.com/openvk/openvk/issues](https://github.com/openvk/openvk/issues) and search to ensure the issue has not been reported already.

2. **Open a New Issue:**
   Click **"New issue"** and fill out the template:
   * **Problem Description** — Clear, concise summary of the issue.
   * **Steps to Reproduce** — Step-by-step instructions to trigger the bug.
   * **Expected Behavior** — What should have happened.
   * **Actual Behavior** — What actually happened (with screenshots or error text).
   * **Technical Details:**
     - API method called (e.g. `account.getProfileInfo`) and version (`v=5.80`).
     - Request payload and received JSON response.
     - Browser, OS, or client version.
     - Server logs from `/logs/` if the issue occurs on your instance.

---

## Contributing: Bug Fixes and New Features

* **Pull Request Workflow:**
  1. Fork the [openvk/openvk](https://github.com/openvk/openvk) repository.
  2. Create a feature branch: `git checkout -b fix-account-method`.
  3. Make your changes adhering to project coding standards (PSR-12 for PHP, modern ES6/CSS).
  4. Test locally on your OpenVK instance.
  5. Submit a Pull Request targeting the `master` branch with a clear summary of changes.

---

## Security and Responsible Disclosure

If you find a critical security vulnerability that could compromise user data on public instances, please do not post it publicly in GitHub Issues.

Report it directly to the core developers:
* Security team contact: `contact@openvk.org`
* Private messages to the [OpenVK Team](https://openvk.org/team)