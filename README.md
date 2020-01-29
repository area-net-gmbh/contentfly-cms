![Contentfly CMS](https://www.contentfly-cms.de/file/get/7d937604-23e2-11e8-b76e-00ac10d52400)

# Contentfly
- **Lizenz**: Duale Lizenz MIT/ Properitär
- **Webseite**: http://www.contentfly-cms.de

## Die Contentfly Plattform

- **Server**: https://github.com/area-net-gmbh/contentfly-cms
- **Ionic SDK**: https://github.com/area-net-gmbh/contentfly-ionic
- **Download + Dokumentation**: http://www.contentfly-cms.de

## Einführung

Mit der Contentfly Plattform können Geschäftsprozesse digitalisiert und mobile Apps für iOS und Android unter dem Einsatz von webbasierten Technologien (Ionic Framework) inklusive Synchronisations-Anbindung an einen Server entwickelt werden.

Der Server basiert dabei auf PHP und MySQL und kann auf nahezu jedem Standard-Hosting-Provider eingesetzt werden. Das Ionic SDK unterstützen Entwickler bei der Datenhaltung und Synchronisation (Offline-Apps) mit dem Contentfly CMS.

Für die Entwicklung von Apps mit dem Contentfly Framework sind folgende Kenntnisse erforderlich:

- **Server**: PHP und optimalerweise Doctrine ORM und MySQL
- **Ionic**: Typescript/Javascript und Kenntnisse im Ionic Framework

# Das Contentfly CMS

Mit dem CMS können serverseitig beliebige Inhalte gespeichert und verwaltet werden. Das CMS kann letztendlich auch losgelöst von mobilen Apps betrieben werden. Über eine Schnittstelle kann auf alle im CMS gespeicherten Daten zugegriffen werden. Damit kann das CMS auch zum Beispiel als PIM (Product Information Managament) für eine Webseite in TYPO3 oder Wordpress eingesetzt werden.

**Technologien**

- [PHP](http://www.php.net/) und [MySQL](https://www.mysql.de/)
- [Silex](http://silex.sensiolabs.org/) als Micoframework mit [Symfony Components](http://symfony.com/components)
- [Doctrine](http://www.doctrine-project.org/) als ORM für die Datenhaltung
- [AngularJS](https://angularjs.org/) für die Oberfläche

## Migration 1.5 auf 1.6

Angepasste Ordnerstruktur und Betrieb in Unterordnern z.B. www.domain.de/contentfly möglich.

### Allgemeine Änderungen

* **Webserver Document-Root muss auf "/" anstatt "appcms/public" gesetzt werden**
* *RewriteBase* wurde aus der .htaccess entfernt
* Der Webordner/-pfad wird in der Bootstrap-Datei automatisch in die Konfigurationsvariable *WEB_ROOT* geschrieben
* Die Variable *WEB-ROOT* wird automatisch in den Twig-Templates als base/href gesetzt

### Geänderte Ordner- und Dateistruktur

* *appcms/areanet/PIM* => *lib/contentfly*
* *appcms/areanet/PIM-UI* => *lib/contentfly-ui*
* *appcms/vendor* => *vendor*
* *appcms/bootstrap.php* => *lib/contentfly/bootstrap.php*
* *appcms/bootstrap-web.php* => *lib/contentfly/bootstrap-web.php*
* *appcms/version.php* => *lib/contentfly/version.php*
* *appcms/config-sample.php* => *lib/contentfly/config-sample.php*
* *appcms/console.php* => *bin/console.php*
* *appcms/cli-config.php* => *bin/cli-config.php*
* *data* => *data*
* *custom* => *custom*

**Hinweis:** Die PHP-Namespaces z.B. "Areanet/PIM/Entity" wurden nicht geändert.

### Dateien und Bilder

* Der Dateiabruf "file/get" wurde intern auf HTTP-Redirects (301) auf "/data/files/..." umgestellt
* Alternativ kann der Abruf über die Konfigurationsvariable APP_FILE_MODE auf "readfile" oder "xsendfile" gestellt werden

### Migration

* Folgende Ordner/Dateien aus der Version 1.6 in das Root-Verzeichnis kopieren
    * *lib*
    * *vendor*
    * *bin*
    * *index.php*
    * *.htaccess*
* Document-Root des Webservers auf das Root-Verzeichnis stellen
* Ordner *appcms* löschen


## Installation

### Systemvoraussetzungen

* Apache 2.x
* PHP 7.1 oder höher
* PHP-Module (benötigt)
    * open_ssl
    * gd
    * pdo_mysql
* PHP-Module (empfohlen)
    * imagick
* MySQL 5.5.0 oder höher
* Konsolen-/SSH-Zugriff empfohlen

### Installation einer Release-Version

(1) Download Contentfly CMS unter http://www.contentfly-cms.de

(2) Installations-Anleitung unter http://www.contentfly-cms.de/docs/cms folgen

### Manuelle Installation aus GitHub

(1) Git-Repository laden

`git clone https://github.com/area-net-gmbh/contentfly-cms.git`

(2) Systemumgebung über [Ant](http://ant.apache.org/)-Buildskript im Root-Ordner erstellen

`ant`

(3) Datenbankzugangsdaten in _custom/config.php_ eintragen

(4) Datenbank über Doctrine im Ordner _bin_ generieren.

`php console.php orm:schema:update --force`

(5) Datenbank im Ordner _bin_ initalisieren/einrichten

`php console.php appcms:setup`

(6) URL/Host aufrufen und Standard-Login in das Contentfly CMS mit Benutzer _admin_ und Passwort _admin_

### ZIP-Version für Release-Build erstellen

`ant zip-release`

## Dokumentation

- http://www.contentfly-cms.de/docs/cms

# Lizenz

Die Contentfly Plattform ist unter eine dualen Lizenz (MIT und properitär) verfügbar. Die genauen Lizenzbedingungen sind in der Datei _LICENCE_ zu finden.

# Die Contentfly Plattform ist ein Produkt der AREA-NET GmbH

AREA-NET GmbH
Öschstrasse 33
73072 Donzdorf

**Kontakt**

- Telefon: 0 71 62 / 94 11 40
- Telefax: 0 71 62 / 94 11 18
- http://www.area-net.de
- http://www.app-agentur-bw.de
- http://www.contentfly-cms.de


**Geschäftsführer**
Gaugler Stephan, Köller Holger, Schmid Markus

**Handelsregister**
HRB 541303 Ulm
Sitz der Gesellschaft: Donzdorf
UST-ID: DE208051892




