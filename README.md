# BÜHNER-PIM V1.1 -

## 1. Setup!

* vagrant up (Vagrant-Installation siehe unten)
* vagrant ssh
```
> cd /vagrant/source
> php vendor/bin/doctrine orm:schema:update --force
```
* http://local.dev.buehner-kalender.de/setup 
* http://local.dev.buehner-kalender.de/ (admin|admin)

## API-Doc

* Login per vagrant ssh
* in Ordner "/vagran" wechseln

```
> apidoc -i source/ -o doc/
```

## Doctrine-Schema/DB-Update

### im source-Ordner (z.B. cd /vagrant/source in der ssh-vagran-box)

* Änderungen als SQL im Ordner "sql" speichern - für Update Live-Server
```
> php vendor/bin/doctrine orm:schema:update --sql-dump
```
* Änderungen in der DEV-Umgebung durchführen
```
> php vendor/bin/doctrine orm:schema:update --force
```

## Composer
* Für Änderungen PHP-Erweiterungen im source-Ordner
```
> composer install
```

# Vagrant-Installation

Author: AREA-NET GmbH, Markus Schmid www.area-net.de | www.app-agentur-bw.de

* Ubuntu (latest stable)
* ImageMagick, Node/NPM
* Apache 2.4
* PHP 5.6 (imagick, curl, gd, intl, cli, mysql)
* MySQL (latest stable)
* Xdebug
* Composer

## System requirements

* VirtualBox www.virtualbox.org
* Vagrant www.vagrantup.com
* On Mac OSX: Ansible www.ansible.com (see next chapter)

## Mac OSX

### Install Ansible

* Install Homebrew http://brew.sh
* Install Ansible with
```
> brew install ansible
```

## Windows

### Install Ansible

* No installation needed, ansible is running on the vagrant box

### Install recommended software

* https://github.com/winnfsd/vagrant-winnfsd (NFS-Support for Windows)
```
vagrant plugin install vagrant-winnfsd 
```
* https://git-for-windows.github.io/

Use the following command line tools from the installed git bash

## Start up

* Download code or repository
* Open console (on windows with administrator privileges!) and change into the downloaded folder

```
> vagrant up
```

* Open browser and see the Shopware installation wizard on http://192.168.33.99
* Optional: Setting shopware.dev in /etc/hosts to 192.168.33.99
* SSH-Access to the vagrant box
```
> vagrant ssh
```

* Installing PHP-Dependencies in folder source (per ssh oder on development machine -> composer is needed)

```
> composer install
```

## Customizing

Change default vars in ansible/vars/all.yml
* Server hostname
* Server packages
* PHP packages
* ...

Re-provision the box after changing vars with

```
> vagrant provision
```


## MIT License

The MIT License (MIT)

Copyright (c) 06.11.2015 AREA-NET GmbH, App-Agentur BW, Markus Schmid

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
