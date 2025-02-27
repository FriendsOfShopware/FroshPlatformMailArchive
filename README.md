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

### Setting up local docker dev
#### Prerequisites
##### Task files
Install the `task` command if not already installed following this guide:

https://taskfile.dev/installation/

##### Docker
Install Docker on your maschine for the local dev setup to start in.
This can be done for example using Docker desktop and installing it from the following link:
https://www.docker.com/get-started/

#### Task Commands

#### init
Initializes the development system and gets it ready to be used.
If everything is successful you should have a clean shopware instance, with this plugin under http://localhost
```
task init
```

#### console:
Allows you to execute shopware commands as follows:
```
task console -- <shopware command>
```

#### composer:
Allows you to execute composer commands as follows:
```
task composer -- <composer command>
```

#### start:
Starts the docker containers.
```
task start
```

#### stop:
Stops the docker containers.
```
task stop
```

#### restart:
Restarts the docker containers.
```
task restart
```

#### remove:
Stops and removes the docker containers, **therefore all database data is lost**.
```
task remove
```

#### reset:
Resets the docker container to a fresh state, **therefore all database data is lost**.
```
task reset
```

#### clean:
Cleans the shopware cache.
```
task clean
```


## Licence

This project uses the [MIT License](LICENSE.md).
