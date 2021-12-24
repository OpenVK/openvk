# Installing OpenVK

Based on [@rem-pai](https://github.com/rem-pai)'s way to install OpenVK modified using my experience.

## SELinux

🖥Run the command:

```bash
sestatus
```

If it says `SELinux status:                 enabled` then SELinux will disturb us. Let's disable it.

_ℹNote: I know that it's not most secured solution but I don't know any proper way that will work._

📝Edit file `/etc/sysconfig/selinux` and change the line `SELinux=enforcing` to `SELinux=disabled`, then 🔌reboot your machine. `sestatus` should tell `SELinux status:                 disabled` right now.

## Dependencies

🖥Let's install EPEL and Remi repos for PHP 7.4:

```bash
dnf -y install epel-release
dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
```

🖥Then enable modules that we need:

```bash
dnf -y module enable php:remi-7.4
dnf -y module enable nodejs:14
```

🖥And install dependencies:

```bash
dnf -y install php php-cli php-common unzip php-zip php-yaml php-gd php-pdo_mysql nodejs git
```

🖥Don't forget about Yarn and Composer:

```bash
npm i -g yarn
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --filename=composer2 --install-dir=/bin --snapshot
rm composer-setup.php
```

### Database

🖥We will use Percona Server for DB:

```bash
dnf -y install https://repo.percona.com/yum/percona-release-latest.noarch.rpm
percona-release setup -y ps80
dnf -y install percona-server-server percona-toolkit
systemctl start mysql
```

🖥And then look up for temporary password:

```bash
cat /var/log/mysqld.log | grep password
```

It should look like this:

    2021-01-11T12:56:09.203991Z 6 [Note] [MY-010454] [Server] A temporary password is generated for root@localhost: >b?Q.fDXJ4fk

🖥Then run `mysql_secure_installation`, set new password and answer like this:

    Change the password for root ? ((Press y|Y for Yes, any other key for No) : n
    Remove anonymous users? (Press y|Y for Yes, any other key for No) : y
    Disallow root login remotely? (Press y|Y for Yes, any other key for No) : y
    Remove test database and access to it? (Press y|Y for Yes, any other key for No) : y
    Reload privilege tables now? (Press y|Y for Yes, any other key for No) : y

### ffmpeg

Additionally, you can install ffmpeg for processing videos.

🖥You will need to use RPMFusion repo to install it:

```bash
dnf -y localinstall --nogpgcheck https://download1.rpmfusion.org/free/el/rpmfusion-free-release-8.noarch.rpm
dnf -y install --nogpgcheck https://download1.rpmfusion.org/free/el/rpmfusion-free-release-8.noarch.rpm
```

🖥Then install SDL2 and ffmpeg:

```bash
dnf -y install http://rpmfind.net/linux/epel/7/x86_64/Packages/s/SDL2-2.0.10-1.el7.x86_64.rpm
dnf -y install ffmpeg
```

## Chandler and OpenVK installation

🖥Install Chandler in `/opt`:

```bash
cd /opt
git clone https://github.com/openvk/chandler.git
cd chandler/
composer2 install
```

🖥You will need a secret key. You can generate it using:

```bash
cat /dev/random | tr -dc 'a-z0-9' | fold -w 128 | head -n 1
```

📝Now edit config file `chandler-example.yml` like this:

```yaml
chandler:
    debug: true
    websiteUrl: null
    rootApp:    "openvk"
    
    preferences:
        appendExtension: "xhtml"
        adminUrl: "/chandlerd"
        exposeChandler: true
    
    database:
        dsn: "mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=db"
        user: "root"
        password: "DATABASE_PASSWORD"
    
    security:
        secret: "SECRET_KEY_HERE"
        csrfProtection: "permissive"
        sessionDuration: 14
```

🖥And rename it to `chandler.yml`:

```bash
mv chandler-example.yml chandler.yml
```

🖥Now let's install CommitCaptcha extension. It is mandatory for OpenVK.

```bash
cd extensions/available/
git clone https://github.com/openvk/commitcaptcha.git
cd commitcaptcha/
composer2 install
```

🖥And now download OpenVK:

```bash
cd ..
git clone https://github.com/openvk/openvk.git
cd openvk/
composer2 install
cd Web/static/js
yarn install
```

📝Now edit config file `openvk-example.yml` like this:

```yaml
openvk:
    debug: true
    appearance:
        name: "OpenVK"
        motd: "Yet another OpenVK instance"
    preferences:
        femaleGenderPriority: true
        uploads:
            disableLargeUploads: false
            mode: "basic"
        shortcodes:
            forbiddenNames:
                - "index.php"
        security:
            requireEmail: false
            requirePhone: false
            forcePhoneVerification: false
            forceEmailVerification: false
            enableSu: true
            rateLimits:
                actions: 5
                time: 20
                maxViolations: 50
                maxViolationsAge: 120
                autoban: true
        support:
            supportName: "Moderator"
            adminAccount: 1 # Change this ok
        messages:
            strict: false
        wall:
            postSizes:
                maxSize: 60000
                processingLimit: 3000
                emojiProcessingLimit: 1000
        menu:
            links:
                
        adPoster:
            enable: false
            src: "https://example.org/ad_poster.jpeg"
            caption: "Ad caption"
            link: "https://example.org/product.aspx?id=10&from=ovk"
        bellsAndWhistles:
            fartscroll: false
            testLabel: false
    credentials:
        smsc:
            enable: false
            client: ""
            secret: ""
        eventDB:
            enable: true # Better enable this
            database:
                dsn: "mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=openvk_eventdb"
                user: "root"
                password: "DATABASE_PASSWORD"
```

Please note `eventDB` section because it's better to enable event database.

🖥And rename it to `openvk.yml`:

```bash
mv openvk-example.yml openvk.yml
```

🖥Then enable CommitCaptcha and OpenVK for Chandler:

```bash
ln -s /opt/chandler/extensions/available/commitcaptcha/ /opt/chandler/extensions/enabled/commitcaptcha
ln -s /opt/chandler/extensions/available/openvk/ /opt/chandler/extensions/enabled/openvk
```

### DB configuration

_ℹNote: it's better to create another user for SQL but I won't cover that._

🖥Enter MySQL shell:

```bash
mysql -p
```

🖥And create main and event databases:

```sql
CREATE DATABASE openvk;                                                                                          
CREATE DATABASE openvk_eventdb;
exit
```

🖥Go to `/opt/chandler`:

```bash
cd /opt/chandler
```

📝We need to import Chandler database but for some reason it's not ready for use so we need to edit dump `install/init-db.sql`:
1\. Remove ` PAGE_CHECKSUM=1` everywhere.
2\. Replace `Aria` with `InnoDB` everywhere.

🖥Now database dump can be imported:

```bash
mysql -p'DATABASE_PASSWORD' openvk < install/init-db.sql
```

🖥Go to `extensions/available/openvk/`:

```bash
cd extensions/available/openvk/
```

📝We also need to import OpenVK database. Unless you use MariaDB (we are using Percona here) you should edit `install/init-static-db.sql`:
1\. Replace `utf8mb4_unicode_nopad_ci` with `utf8mb4_unicode_520_ci` everywhere.

🖥Now database dump can be imported:

```bash
mysql -p'DATABASE_PASSWORD' openvk < install/init-static-db.sql
```

🖥Also import event database:

```bash
mysql -p'DATABASE_PASSWORD' openvk_eventdb < install/init-event-db.sql
```

### Webserver configuration

Apache is already installed so we will use it.

🖥Make the user `apache` owner of the `chandler` folder:

```bash
cd /opt
chown -R apache: chandler/
```

📝Now let's create config file `/etc/httpd/conf.d/10-openvk.conf`:

```apache
<VirtualHost *:80>
    ServerName openvk.local
    DocumentRoot /opt/chandler/htdocs

    <Directory /opt/chandler/htdocs>
        AllowOverride All

        Require all granted
    </Directory>

    ErrorLog /var/log/openvk/error.log
    CustomLog /var/log/openvk/access.log combinedio
</VirtualHost>
```

📝Also enable rewrite_module by creating `/etc/httpd/conf.modules.d/02-rewrite.conf`:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

🖥Make directory for OpenVK logs and make the user `apache` owner of it:

```bash
mkdir /var/log/openvk
chown apache: /var/log/openvk/
```

🖥Make the firewall exception for port 80:

```bash
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --reload
```

🖥And start Apache:

```bash
systemctl start httpd
```

OpenVK should work right now!

Also you can raise 2MB the file limit through editing `/etc/php.ini`. And it's also better to install PHPMyAdmin but I won't cover that.
