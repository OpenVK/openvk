UPDATE `tickets_comments` SET `text`=REGEXP_REPLACE(`text`, "(?:Здравствуйте, [^!]*!<br><\/br>|<br><\/br>С уважением,<br\/> Команда поддержки OpenVK.)", "") WHERE 1=1;
