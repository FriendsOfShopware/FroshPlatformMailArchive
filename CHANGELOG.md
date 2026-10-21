# Unreleased

* Fixed deleting a customer, order or flow when an archived mail references them ([#157](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/issues/157))

# 3.7.0

* Added a "sent date from" and "sent date to" filter to the mail archive listing ([#151](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/151))
* Added a resend dialog that lets you change the recipients before an archived mail is sent again ([#153](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/153))
* Added mail archive permissions to the Administration role settings with viewer, editor (resend) and attachment permissions ([#154](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/154))
* Renamed the resend privilege from `frosh_mail_archive:update` to `frosh_mail_archive:resend`, existing roles need this permission reassigned ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Replaced the placeholder of "delete mails after days" with a help text and rejected values below 0, use 0 to disable automatic deletion ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Reworked the mail detail view with a responsive two-column metadata layout and moved the resend history below the mail content ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Sanitized the HTML and plain text preview and added a restrictive content security policy, so external content and scripts no longer load ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Raised the required `zbateson/mail-mime-parser` version to `^3.0.6` ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Fixed Microsoft Graph dropping archived mails by sending the message ID as valid `X-Frosh-Message-ID` header, the old header is still read ([#149](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/149))
* Fixed duplicate attachment entries when the same transport event is processed more than once ([#152](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/152))
* Fixed resending a mail keeping the original `To`, `Cc` and `Bcc` recipients on top of the new ones ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Fixed Shopware 6.8 deprecations by moving `services.xml` to `services.yaml` and replacing `EntitySearchResult::first()` ([#150](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/150))
* Fixed the cleanup task failing on mails without a valid `.eml` path and validate the mail ID before writing archive files ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Secured the archive, resend and attachment API endpoints with the plugin ACL permissions ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Protected archived mails and attachments with system-scope write protection, so Administration users can no longer modify or forge archive entries ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
* Hidden the resend and bulk actions in the Administration for users without the resend permission ([#155](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/155))
