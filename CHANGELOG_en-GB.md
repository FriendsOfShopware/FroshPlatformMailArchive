# 2.0.2
* Fixed migration of MailArchive without sync option

# 2.0.1
* Fixed composer execution on plugin install or plugin update

# 2.0.0
* Change MailResendController to MailArchiveController
* Change storage of EML from database to private filesystem (please run command "frosh:mailarchive:migrate" to migrate)
* Change EML content to gzipped content
* Add function to download EML file
* Add info about attachments to mail detail page

# 1.0.1
* Fix dal validation

* # 1.0.0
* Shopware 6.5 compatibility

# 0.3.4
* Add Search bar
* Search in archive mail entity through a search bar 

# 0.3.3
* Remove Deprecations
* Remove duplicated loading when loading filter

# 0.3.2
* Save only mails that were indeed sent
* Support for 6.4.10

# 0.3.1
* Cleanup job added to delete older messages

# 0.3.0
* Add compatibility for Shopware 6.4

# 0.2.2
* To improve readability, the boxes for HTML and TEXT mail have been raised
* The time of sending is now also displayed in the mail details
* An error in the display of e-mail addresses has been fixed

# 0.2.1

* Sender name added
* Default sorting changed to newest entries

# 0.2.0

* Add compatibility for Shopware 6.3

# 0.1.2

* Add compatibility for Shopware 6.2

# 0.1.1

* Add sidebar for searching and filtering

# 0.1.0

* First release in Store
