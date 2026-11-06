# BASE3 Moodle 0.1.0

Integration fuer die bestehende Moodle-5.3.0-Instanz unter `https://moodle.base3.de`.

## Namen und Verantwortung

- GitHub-Repository: `base3-moodle`.
- Native Moodle-Komponente: `local_base3` unter `public/local/base3/`.
- Weitere native Moodle-Komponenten: `block_base3` und `filter_base3`.
- BASE3 Hostmodul: `Base3Moodle`.
- BASE3 Projektmodul: `Base3MoodleLab`.
- Die 18 weiteren BASE3-Module stammen bitidentisch aus dem gelieferten WordPress-Paket. Ihre Quelltexte sind nicht geaendert. Git-Metadaten werden nicht ausgeliefert.

Die Installations-ZIPs sind fuer den Moodle-Hostroot ausgelegt. Nicht im Moodle-Webinstaller hochladen.

## 1. Vorbereitungen

Sicherstellen, dass es sich um Moodle 5.3 und die dokumentierte PHP-8.3-/MariaDB-11.4-Instanz handelt. Vor der Installation Datenbank und Moodle-Datenverzeichnis sichern oder einen pruefbaren Snapshot anlegen. Das erste BASE3-Bootstrap kann automatische BASE3-Datenbankmigrationen durchfuehren. Das erneute Aufspielen von ZIPs macht diese Migrationen nicht rueckgaengig.

Auf dem Host arbeiten, nicht im schreibgeschuetzten Apache-Container. Die Infrastruktur-Konfiguration liegt im Git-Repository `/srv/docker` und versioniert den Moodle-Pluginquellcode nicht automatisch.

## 2. Module und Integrationsdateien entpacken

Die beiden heruntergeladenen ZIPs auf den Server kopieren, z.B. nach `/tmp`.

```sh
cd /srv/sites/base3.de/moodle.base3.de/html
unzip -o /tmp/base3-moodle-neutral-modules-20261010.zip -d .
unzip -o /tmp/base3-moodle-integration-0.1.0.zip -d .
```

Beide ZIPs enthalten relative Pfade zum Hostroot `html/`. Alternativ kann die komplette Kombination mit dem zusaetzlich bereitgestellten Komplettpaket in einem Schritt eingespielt werden.

```sh
cd /srv/sites/base3.de/moodle.base3.de/html
unzip -o /tmp/base3-moodle-complete-0.1.0.zip -d .
```

Die beiden Varianten nicht unnoetig hintereinander ausfuehren.

## 3. Pfade und statische Assets kontrollieren

```sh
cd /srv/sites/base3.de/moodle.base3.de/html
test -f base3/modules/Base3Framework/base3.json && echo 'Framework vorhanden'
test -f base3/modules/Base3Moodle/base3.json && echo 'Hostmodul vorhanden'
test -f base3/modules/Base3MoodleLab/base3.json && echo 'Lab vorhanden'
test -f public/local/base3/version.php && echo 'Moodle-Plugin vorhanden'
readlink -f public/local/base3/assets/ClientStack
test -r public/local/base3/assets/ClientStack/assetloader/assetloader.min.js && echo 'ClientStack-Assets vorhanden'
php -l public/local/base3/version.php
```

Die Links unter `public/local/base3/assets/` verweisen auf die unveraenderten `assets/`-Verzeichnisse der BASE3-Module ausserhalb des Webroots. Dies ist notwendig fuer relative JavaScript-Imports und CSS-Assets. Die dokumentierte Apache-Konfiguration hat `Options FollowSymLinks` bereits aktiviert.

Falls spaeter ein weiteres BASE3-Modul mit eigenem `assets/`-Verzeichnis installiert wird, muss dafuer ebenfalls ein entsprechender statischer Link angelegt werden:

```sh
cd /srv/sites/base3.de/moodle.base3.de/html
mkdir -p public/local/base3/assets
# Beispiel fuer ein neues Modul mit assets/: MODULNAME ersetzen.
ln -s ../../../../base3/modules/MODULNAME/assets public/local/base3/assets/MODULNAME
```

Wenn der Link schon existiert, zuerst sein Ziel pruefen; keinen zweiten oder abweichenden Link als Ersatzpfad anlegen.

## 4. Moodle-Plugininstallation starten

```sh
cd /srv/docker/sites/base3.de/moodle.base3.de
docker compose exec -T -u www-data apache php /var/www/moodle/admin/cli/upgrade.php --non-interactive
```

Danach Cache leeren:

```sh
cd /srv/docker/sites/base3.de/moodle.base3.de
docker compose exec -T -u www-data apache php /var/www/moodle/admin/cli/purge_caches.php
```

Eine Aenderung von `docker-compose.yml` und ein Containerneustart sind fuer die neuen Quelldateien nicht vorgesehen. Apache und Cron haben die Moodle-Quelldateien als `:ro`, waehrend `/var/www/moodledata` beschreibbar bleibt.

## 5. BASE3 testen

```sh
cd /srv/docker/sites/base3.de/moodle.base3.de
docker compose exec -T -u www-data apache php /var/www/moodle/public/local/base3/cli.php --status
```

Erwartung: Die Modulnamen inklusive `Base3Framework`, `Base3Moodle`, `Base3MoodleLab`, `ClientStack`, `Chatbot` und `MissionBay` werden mit Versionen angezeigt. Beim ersten Aufruf initialisiert BASE3 seine Module und fuehrt aktive BASE3-Migrationen durch.

ClassMap explizit neu aufbauen, falls erforderlich:

```sh
cd /srv/docker/sites/base3.de/moodle.base3.de
docker compose exec -T -u www-data apache php /var/www/moodle/public/local/base3/cli.php --rebuild
```

Ein manueller Workerlauf ist moeglich:

```sh
cd /srv/docker/sites/base3.de/moodle.base3.de
docker compose exec -T -u www-data apache php /var/www/moodle/public/local/base3/cli.php --worker
```

Der normale Moodle-Cron startet die BASE3-Worker alle fuenf Minuten. Nur aktiv konfigurierte Jobs fuehren Arbeit aus.

## 6. Administration

Als Site-Administrator anmelden: `Website-Administration > Plugins > Lokale Plugins > BASE3 Moodle` beziehungsweise nach `BASE3 Moodle` suchen. Dort befinden sich die Moduluebersicht, die Werkzeuge sowie die Konfiguration der globalen Einbindung. Die Werkzeug-Tabs werden ausschliesslich von `Base3MoodleLabSettings::getAdministrationConfig()` geliefert und durch das unveraenderte `ClientStack TabControlDisplay` dargestellt.

Bei der Abnahme alle Haupttabs sowie mehrere Untertabs wechseln, Aktionen mit JSON-Daten ausfuehren, Einstellungen aendern und erneut laden. Insbesondere speichern Formulare nicht per normalem Moodle-Formsubmit, sondern ueber den BASE3-AJAX-Transport. Die bestehende Session und der Moodle-`sesskey` werden fuer POST verwendet.

## 7. BASE3-Displays in Moodle einsetzen

**Eigenstaendige Seite:**

`https://moodle.base3.de/local/base3/display.php?name=DISPLAYNAME`

Optional JSON-kodierte Daten im URL-Parameter `data`.

**Moodle-Block:** Bearbeitungsmodus einschalten, Block `BASE3 Display` hinzufuegen, technischen Displaynamen und optional JSON-Daten setzen. Ein Block kann mehrfach auf verschiedenen Seiten mit anderen Daten vorkommen.

**Inhaltsfilter:** In `Website-Administration > Plugins > Filter > Filter verwalten` den Filter `BASE3 Display Embeds` aktivieren. Danach z.B. in einer Moodle-Lernseite folgenden Platzhalter nutzen:

```text
[[base3 name="DISPLAYNAME" data='{"title":"Example"}']]
```

Die Verfuegbarkeit der eingebetteten Displays haengt weiterhin von der jeweiligen fachlichen Berechtigung ab. Als Administrations-Displays im Lab konfigurierte Ziele werden an der Moodle-Integrationsgrenze zusaetzlich auf `moodle/site:config` geprueft.

**Globale Einbindung auf Moodle-Seiten:** In den BASE3-Moodle-Einstellungen unter `Globale Einbindung` einen technischen Displaynamen und optional JSON-Daten eintragen. Leer lassen, wenn keine globale Einbindung gewuenscht ist. Moodle benutzt den nativen Hook `before_standard_footer_html_generation`. Es gibt keine zweite Runtime und keine Sondervariante einzelner Displays.

## 8. Betrieb und Git

Die Integration selbst sollte im GitHub-Repository `base3-moodle` versioniert werden. Die 18 urspruenglichen BASE3-Module bleiben in ihren bestehenden Repositories. Fuer ein reproduzierbares Deployment die Versionen der Modul-Manifeste und des Integrations-ZIPs festhalten. Verzeichnis `base3/modules/` enthaelt die zu installierenden Module, `public/local/base3/` die Moodle-Integration, `public/blocks/base3/` und `public/filter/base3/` die Einbindungsplugins.

Daten landen unter `$CFG->dataroot/base3/`, nicht im Git-Repository. Dort liegen Konfiguration, ClassMap/Cache, temporaere Daten und private Dateien. Berechtigungen dieses Verzeichnisses fuer `www-data` pruefen, insbesondere bei Erstinstallation.

Bei Fehlern die konkrete Exception, den Moodle-Debuglog und die ausgefuehrte SQL-Anweisung pruefen. Keine weiteren Datenbankverbindungen, Router, Sonder-Modi oder parallelen Plugin-Konfigurationen einfuehren. Eine fehlgeschlagene Version nicht durch Hilfsschichten kompensieren, sondern den Integrationsfehler beheben oder auf den letzten akzeptierten Stand zurueckgehen.

## 9. Teststatus und Abnahme

Im Buildsystem validiert: PHP-Syntax, 20 Modulmanifeste und Abhaengigkeiten, reale BASE3-ClassMap inklusive TabControlDisplay, SQL-Adapter mit simulierter Moodle-DB, Session/Request/Link/Asset-Vertraege, kompletter BASE3-Bootstrap und gerenderte Lab-Tabs mit simulierter Moodle-Umgebung. Die Module sind unveraendert aus `wp(1).zip` entnommen.

Nicht nachgewiesen: realer Moodle-5.3-Installationslauf auf `moodle.base3.de`, Datenbankmigrationen auf MariaDB 11.4 mit echten Tabellen, Apache-Auslieferung der Assets, alle interaktiven AJAX-Aktionen im Browser, Dateiuploads und Live-Cron. Diese Punkte sind vor Produktiveinsatz auf der Instanz zu pruefen. Die aktuelle Integration ist eine erste Beta-Auslieferung und keine bestaetigte produktive Freigabe.
