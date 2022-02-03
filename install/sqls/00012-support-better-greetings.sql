--- if you haven't used helpdesk before nov 25, 2021 - you will not need it.

UPDATE `tickets_comments` SET `text`=REGEXP_REPLACE(`text`, "(?:Здравствуйте, [^!]*!<br><\/br>|<br><\/br>С уважением,<br\/> Команда поддержки OpenVK.)", "") WHERE 1=1;
