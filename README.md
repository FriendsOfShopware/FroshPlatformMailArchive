# FroshPlatformMailArchive

## Description

This plugin adds an MailArchive to your Shopware-Administration stored in Database.

### Features

* Save all outgoing mails
* Linking to customer
* Resend Mail
* Save attachments
* Download EML
* Search in List

## Zip-Installation

* Download the [latest plugin version](https://github.com/FriendsOfShopware/FroshPlatformMailArchive/releases/latest/)
* Upload and install plugin using Plugin Manager

## Contributing

Feel free to fork and send [pull requests](https://github.com/FriendsOfShopware/FroshPlatformMailArchive)!

## Administration tests

Tests use the [Vitest Shopware Admin Bridge](https://github.com/FriendsOfShopware/vitest-shopware-admin-bridge), following the FroshTools setup.
Use Node.js 22.12+, 24, or 26+ and npm 10+.
A local Shopware Administration source tree with installed npm dependencies is required.
The bridge discovers it in a Shopware checkout or Composer installation; set `SHOPWARE_ADMINISTRATION_PATH` to select another installation.

```bash
cd src/Resources/app/administration
npm ci
npm run test:unit:doctor
npm run test:unit
```

From the plugin root, `composer test:admin` runs the same suite.
Use `npm run test:unit:watch` in the Administration directory for watch mode.
CI runs the suite against Shopware 6.6, 6.7, and trunk.

## Licence

This project uses the [MIT License](LICENSE.md).
