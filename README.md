# APP-CMS
- **Lizenz**: Duale Lizenz AGPL v3 / Properitär
- **Webseite**: http://www.das-app-cms.de

## Die APP-CMS Plattform

- **Backend**: https://github.com/appcms/backend
- **iOS SDK**: https://github.com/appcms/ios-sdk
- **Android SDK**: _coming soon_

## Tools und mehr

- Vagrant-Umgebung für Backend: https://github.com/appcms/vagrant
- Download + Dokumentation: http://www.das-app-cms.de

## Einführung

Mit der APP-CMS Plattform können mobile Apps für iOS und Android unter dem Einsatz von nativen und webbasierten Technologien inklusive Synchronisations-Anbindung an ein Backend-System entwickelt werden. 

Das Backend basiert dabei auf PHP und MySQL und kann auf nahezu jedem Standard-Hosting-Provider eingesetzt werden. Die SDKs für iOS und Android unterstützen Entwickler bei der Datenhaltung, Synchronisation und Darstellung von Inhalten. Zudem enhalten die SDKs eine Template-Engine, mit der Oberflächen einfach und plattformübergreifen in HTML erstellt werden können.

Es ist dem Entwickler völlig freigestellt, ob Apps komplett nativ oder in Kombination mit webbasierten Bereichen umgesetzt werden.

Die APP-CMS Plattform ist kein Baukasten-System, mit dem sich Apps ohne Vorkenntnisse zusammenklicken lassen - sondern bietet ein technisches Rahmenwerk, mit sich mobile Apps effizient, kosten-nutzenoptimiert, aber dennoch frei von Zwängen anderer (hybrider) Frameworks wie Titanium Mobile oder PhoneCap.

Für die Entwicklung von Apps mit dem APP-CMS Framework sind folgende Kenntnisse erforderlich:

- **Backend**: PHP und optimalerweise Doctrine und MySQL
- **iOS**: Swift oder Objective-C und gegebenenfalls HTML5
- **Android**: Java und gegebenenfalls HTML5

# Das APP-CMS Backend

Mit dem Backend können serverseitig beliebige Inhalte gespeichert und verwaltet werden. Das Backend kann letztendlich auch losgelöst von mobilen Apps betrieben werden. Über eine Schnittstelle kann auf alle im Backend gespeicherten Daten zugegriffen werden. Damit kann das Backend auch zum Beispiel als zusätzliches PIM (Product Information Managament) für eine Webseite in TYPO3 oder Wordpress eingesetzt werden.

**Technologien**

- [PHP](http://www.php.net/) und [MySQL](https://www.mysql.de/)
- [Silex](http://silex.sensiolabs.org/) als Micoframework mit [Symfony Components](http://symfony.com/components)
- [Doctrine](http://www.doctrine-project.org/) als ORM für die Datenhaltung
- [AngularJS](https://angularjs.org/) für die Oberfläche

## Installation

### Systemvoraussetzungen

* Apache 2.x
* PHP 5.6 oder höher
* PHP-Module (benötigt)
    * open_ssl
    * gd
    * pdo_mysql
* PHP-Module (empfohlene)
    * imagick
* MySQL 5.5.0 oder höher
* Konsolen-/SSH-Zugriff empfohlen

### Installation einer Release-Version

(1) Download APP-CMS unter http://www.das-app-cms.de

(2) Installations-Anleitung unter http://www.app-cms.de/docs/backend

### Manuelle Installation aus GitHub

(1) Git-Repository laden

`git clone https://github.com/appcms/backend.git`

(2) Systemumgebung über [Ant](http://ant.apache.org/)-Buildskript im Root-Ordner erstellen

`ant`

(3) Datenbankzugangsdaten in _custom/config.php_ eintragen

(4) Datenbank über Doctrine im Ordner _appcms_ generieren.

`php vendor/bin/doctrine orm:schema:update --force`

(4) Datenbank im Ordner _appcms_ initalisieren/einrichten

`php console.php appcms:setup`

(4) Webserver (Virtual Host) DocumentRoot auf _appcms/public_ stellen

(5) URL/Host aufrufen und Standard-Login in das APP-CMS mit Benutzer _admin_ und Passwort _admin_

## Erste Schritte

_TODO_

## Dokumentation

- Benutzung: _TODO_
- Administration: _TODO_
- Entwicklung: _TODO_

# Lizenz

Die APP-CMS-Plattform ist unter eine dualen Lizenz (AGPL v3 und properitär) verfügbar. Die genauen Lizenzbedingungen sind in der Datei _licence.txt_ zu finden.

# Die APP-CMS Plattform ist ein Produkt der AREA-NET GmbH

AREA-NET GmbH
Öschstrasse 33
73072 Donzdorf

**Kontakt**

- Telefon: 0 71 62 / 94 11 40
- Telefax: 0 71 62 / 94 11 18
- http://www.area-net.de
- http://www.app-agentur-bw.de
- http://www.das-app-cms.de


**Geschäftsführer**
Gaugler Stephan, Köller Holger, Schmid Markus

**Handelsregister**
HRB 541303 Ulm
Sitz der Gesellschaft: Donzdorf
UST-ID: DE208051892




