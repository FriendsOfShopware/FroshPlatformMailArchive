# 3.8.0

**Added**
- Show CC and BCC recipients on the mail detail page, read from the archived EML file ([#160](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/160))
- Added `frosh:mail-archive:cleanup` console command that queues the cleanup of old archive entries ([#119](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/119))

**Fixed**
- Bulk resend sends mails sequentially so mailers with a concurrent connection limit no longer reject the batch, and shows an error notification on failure ([#116](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/pull/116))

