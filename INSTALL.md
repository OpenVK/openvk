# OpenVK Installation Instructions

* * *

1.  Install Composer, Node.js, Yarn and [Chandler](https://github.com/openvk/chandler)
2.  Install [commitcaptcha](https://github.com/openvk/commitcaptcha)/[chandler-recaptcha](https://github.com/openvk/chandler-recaptcha) and OpenVK as Chandler extensions and enable them like this:

```bash
ln -s /path/to/chandler/extensions/available/commitcaptcha /path/to/chandler/extensions/enabled/
ln -s /path/to/chandler/extensions/available/openvk /path/to/chandler/extensions/enabled/
```

3.  Import install/init-static-db.sql to same database you installed Chandler to
4.  Import install/init-event-db.sql to separate database
5.  Rename openvk-example.yml to openvk.yml and change options
6.  Run `composer install` in OpenVK directory
7.  Move to Web/static/js and execute `yarn install`
8.  Set `openvk` as your root app in chandler.yml

Once you are done, you can login as a system administrator on the network itself (no registration required):

-   **Login**: `admin@localhost.localdomain6`
-   **Password**: `admin`

It is recommended to change the password before using the built-in account.

Full example installation instruction for CentOS 8 is also available [here](docs/centos8_install.md).
