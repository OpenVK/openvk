# <img align="right" src="https://github.com/openvk/openvk/raw/master/Web/static/img/logo_shadow.png" alt="openvk" title="openvk" width="15%">OpenVK

_[Русский](README_RU.md)_

**OpenVK** is an attempt to create a simple CMS that ~~cosplays~~ imitates old VK. Code provided here is not stable yet.

VKontakte belongs to Pavel Durov and VK Group.

To be honest, we don't even know whether it even works. However, this version is maintained and we will be happy to accept your bugreports [in our bug-tracker](https://github.com/openvk/openvk/projects/1). You should also be able to submit them using [ticketing system](https://openvk.su/support?act=new) (you will need an OVK account for this).

## When's the release?

Please use the master branch, as it has the most changes.

Updating the source code is done with this command: `git pull`

## Instances

* **[openvk.su](https://openvk.su/)**
* [social.fetbuk.ru](http://social.fetbuk.ru/)
* [openvk.zavsc.pw](https://openvk.zavsc.pw/)

## Can I create my own OpenVK instance?

Yes! And you're very welcome to.

However, OVK makes use of Chandler Application Server. This software requires extensions, that may not be provided by your hosting provider (namely, sodium and yaml. this extensions are available on most of ISPManager hostings).

If you want, you can add your instance to the list above so that people can register there.

### Installation procedure

1. Install PHP 7.4, web-server, Composer, Node.js, Yarn and [Chandler](https://github.com/openvk/chandler)

* PHP 8 has **not** yet been tested, so you should not expect it to work.

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

4. Import `install/init-static-db.sql` to **same database** you installed Chandler to
5. Import `install/init-event-db.sql` to **separate database**
6. Copy `openvk-example.yml` to `openvk.yml` and change options
7. Run `composer install` in OpenVK directory
8. Move to `Web/static/js` and execute `yarn install`
9. Set `openvk` as your root app in `chandler.yml`

Once you are done, you can login as a system administrator on the network itself (no registration required):

* **Login**: `admin@localhost.localdomain6`
* **Password**: `admin`
  * It is recommended to change the password before using the built-in account.

Full example installation instruction for CentOS 8 is also available [here](https://docs.openvk.su/openvk_engine/centos8_installation/).

### If my website uses OpenVK, should I publish it's sources?

You are encouraged to do so. We don't enforce this though. You can keep your sources to yourself (unless you distribute your OpenVK distro to other people).

You also not required to publish source texts of your themepacks and plugins.

## Where can I get assistance?

You may reach out to us via:

* [Bug-tracker](https://github.com/openvk/openvk/projects/1)
* [Ticketing system](https://openvk.su/support?act=new)
* Telegram chat: Go to [our channel](https://t.me/openvkch) and open discussion in our channel menu.
* [Reddit](https://www.reddit.com/r/openvk/)
* [Discussions](https://github.com/openvk/openvk/discussions)

**Attention**: bug tracker and telegram chat are public places. And ticketing system is being served by volunteers. If you need to report something, that shouldn't be immediately disclosed to general public (for instance, vulnerability report), please use contact us directly at this email: **openvk [at] tutanota [dot] com**

<a href="https://codeberg.org/OpenVK/openvk">
    <img alt="Get it on Codeberg" src="https://codeberg.org/Codeberg/GetItOnCodeberg/media/branch/main/get-it-on-blue-on-white.png" height="60">
</a>
