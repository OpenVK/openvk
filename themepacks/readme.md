# OpenVK Themepacks

This folder contains all themes that can be used by any user on an instance.

## How do I create a theme?

Create a directory, the name of which should contain only Latin letters and numbers, then create a file in this directory called `theme.yml`, and fill it with the following content:

```yaml
id: vk2007
version: "0.0.1.0"
openvk_version: 0
enabled: 1
metadata:
    name:
        _: "V Kontakte 2007"
        en: "V Kontakte 2007"
        ru: "В Контакте 2007"
    author: "Veselcraft"
    description: "V Kontakte-stylized theme by 2007 era"
```

**Where:**

`id` is the name of the folder

`version` is the version of the theme

`openvk_version` is the version of OpenVK *(it is necessary to leave the value to 0)*

`metadata`:

* `name` - the name of the theme for the end user. Inside it you can leave names for different languages. `_` (underscore) is for all languages.

Next, in `stylesheet.css` you can insert any CSS code, with which you can change the elements of the site. If you need additional pictures or resources, just create a `res` folder, and access the resources via the `/themepack/{directory name}/{theme version}/resource/{resource}` path.

To support the New Year's mood, which turns on automatically from December 1st to January 15th, create the file `xmas.css` in the `res` folder, and make the necessary changes.

**After all, the directory hierarchy should look like this:**

```
vk2007:
- res
  - {resources}
- stylesheet.css
- theme.yml
```
