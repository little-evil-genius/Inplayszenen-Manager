# Inplayszenen-Manager
Das Inplayszenen-Manager Plugin bietet ein umfassendes Tool zur Verwaltung von Inplayszenen. Es hilft dabei, Szenen übersichtlich zu strukturieren, um so den Überblick über (laufende) Szenen zu behalten. Durch die Nutzung dieses Plugins wird das Erstellen und Verwalten von Szenen wesentlich vereinfacht, indem wichtige Informationen wie Datum, teilnehmende Charaktere und individuelle Felder, welche im ACP nach Bedarf erstellt werden können, erfasst werden. Das Plugin unterstützt und unterscheidet sowohl den normalen Inplaybereich sowie den Bereich für alternative Universen (AU) Szenen. Es besteht die Möglichkeit, Szenen in verschiedene Typen zu unterteilen: private Szenen, in denen nur eingetragene Charaktere teilnehmen dürfen, Szenen mit Absprache, bei denen eine Teilnahme angefragt werden dürfen, sowie offene Szenen, in die sich jeder Charakter mit einem Klick hinzufügen kann. Zusätzlich kann für jede Szene festgelegt werden, ob es sich um eine Szene mit fester Reihenfolge, bei denen die Teilnehmer in einer definierten Reihenfolge posten müssen, oder solchen ohne feste Reihenfolge, bei denen das Posten freier erfolgt, handelt. <br>
<br>
Im Profil jedes Charakters werden alle bisherigen Szenen chronologisch aufgelistet. Hierbei wird zwischen Inplay- und AU-Szenen unterschieden, um eine klare Trennung zu schaffen. Szenen, in denen nach einer Charakterlöschung nur noch ein bestehender Charakter vorhanden ist, können als "nicht relevant" markiert werden, um die Übersichtlichkeit zu wahren, ohne die Szene komplett löschen zu müssen.<br>
<br>
Das Plugin bietet zudem eine persönliche Übersicht über die aktiven Szenen. Diese Übersicht zeigt auf, wer in welchen Szenen als nächstes posten muss.<br>
Mitglieder:innen können eine individuelle Posting-Erinnerung festlegen, die sie nach einer selbst festgelegten Anzahl von Tagen darauf hinweisen, welche Szenen schon länger unbeantwortet warten. Diese Erinnerung ist zusätzlich pro Charakter aktivierbar bzw. deaktivierbar. Genauso können Mitglieder:innen individuell entscheiden, wie sie über Inplayereignisse informiert werden wollen. Entweder per Private Nachricht oder MyAlerts, wenn das Forum diese Möglichkeit unterstützt.<br>
<br>
Bei Account-Löschung werden betroffene Inplayszenen entsprechend in das Archiv verschoben. Zusätzlich kann das automatische Archivieren von inaktiven Szenen eingestellt werden. Szenen, die über eine definierte Zeitspanne hinweg keine Aktivität zeigen (z.B. nach X Monaten ohne neuen Post), werden automatisch ins Archiv verschoben (deaktiverbar). Das Plugin archiviert Szenen nur in diesen zwei Fällen, es bietet keine Funktion für die allgemeine Archivierung von Threads/Szenen.

# Wichtige Funktionen im Überblick
- <b>Individuelle Szenenfelder:</b> Im Admin-CP können benutzerdefinierte Felder für die Szenen erstellt werden (z.B. für Ort, Tageszeit).
- <b>Inplay- und AU-Kategorisierung:</b> Das Plugin trennt Szenen klar nach Inplay- und AU-Bereichen. Zusätzlich können einzelne Foren innerhalb des Inplaybereichs ausgeschlossen werden.
- <b>Szenenarten:</b> Szenen können privat, nach Absprache oder offen sein, was unterschiedliche Teilnahmebedingungen für die Charaktere ermöglicht (In den Einstellungen aktivierbar). Bei offenen Szenen können sich Mitglieder:innen per Klick mit ihren Charakteren in die Szene hinzufügen. 
- <b>Szeneninformationen im Forumdisplay und Thread:</b> Die Informationen zur Szene werden im Forumdisplay, Showthread und Postbit angezeigt, sofern diese Option aktiviert ist. Es gibt jeweils eine kompakte Variable oder die Felder können jeweils einzeln angesprochen werden.
- <b>Bearbeitungsmöglichkeiten:</b> Alle in einer Szene eingetragenen Charaktere können die Szeneninformationen nachträglich bearbeiten.
- <b>Individuelle Szenenübersicht:</b> Jedes Mitglied hat Zugriff auf eine eigene Übersicht der eigenen aktiven Szenen von den einzelnen Charaktere und kann leicht nachverfolgen, wer als nächstes posten muss.
- <b>Übersicht aller Inplayszenen:</b> Das Plugin bietet eine zentrale Übersicht aller Inplayszenen des Forums, die mit verschiedenen Filtern (z.B. nach Bereich, Szenenstatus, Charakteren, Spieler:innen) durchsucht und gefiltert werden können.
- <b>Posting-Erinnerungen:</b> Mitglieder:innen können individuelle Erinnerungen einstellen, die nach einer bestimmten Zeitspanne Benachrichtigungen auslösen, falls sie in einer Szene posten müssen.
- <b>Relevanzstatus:</b> Szenen, in denen nur noch ein aktiver Charakter existiert, können als "nicht relevant" markiert werden, wenn sie nicht mehr fortgesetzt werden (z.B. bei Löschung von Postpartner:innen oder Szenenabbruch), ohne dass sie gelöscht werden müssen.
- <b>PDF-Export:</b> Szenen oder einzelne Inplay-Posts können als PDF-Dateien heruntergeladen und archiviert werden.
- <b>Automatische Archivierung bei Account-Löschung:</b> Szenen, bei den ein teilnehmender Charakter gelöscht wird, werden automatisch ins Archiv verschoben. 
- <b>Automatische Archivierung von inaktiven Szenen:</b> Szenen, die länger keine Aktivität zeigen, werden automatisch ins Archiv verschoben. Diese Funktion kann vom Team deaktiviert werden.
- <b>Benachrichtigungen:</b> Mitglieder:innen können wählen, ob sie Benachrichtigungen über MyAlerts oder eine private Nachricht erhalten möchten.

# Vorrausetzung
- Der <a href="https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/enhanced-account-switcher/" target="_blank">Accountswitcher</a> von doylecc <b>muss</b> installiert sein.

# Individuelle Szenenfelder
Dieses Plugin bietet Flexibilität, indem nur die Felder für Charaktere und Datum sowie optional für Triggerwarnungen fest vordefiniert sind. Alle weiteren Felder können vom Team im ACP individuell erstellt werden, um den Anforderungen des Forums gerecht zu werden. Jedes Szenenfeld erhält dabei einen eindeutigen Identifikator, der keine Sonderzeichen oder Leerzeichen enthalten darf, um maschinenlesbar zu sein.<br>
Die Feldtypen können frei gewählt werden (ähnlich wie bei den Profilfeldern), sodass verschiedene Eingabeformate (z.B. Textfelder, Auswahlfelder) möglich sind. Zusätzlich lässt sich festlegen, ob ein Feld verpflichtend ausgefüllt werden muss und ob es nach der Erstellung noch bearbeitet werden kann.

# Inplay- und AU-Kategorisierung
Der Inplaybereich umfasst die Foren, in denen Mitglieder:innen Szenen eröffnen können, die im aktuellen Inplayzeitraum spielen. Viele Foren bieten darüber hinaus Bereiche für sogenannte Nebenplays an, in denen Szenen aus der Vergangenheit, Zukunft oder sogar alternativen Universen (AU)/"was wäre, wenn..." erstellt werden können. Während Szenen aus Vergangenheit und Zukunft oft noch zum normalen Inplayverlauf des Charakters zählen, sind AU-Szenen eigenständig und nicht Teil des offiziellen Verlaufs.<br>
Um dies klar zu trennen, bietet das Plugin eine Unterteilung in normalen Inplaybereich und AU-Bereich in den Einstellungen. So kann das Team genau festlegen, welche Foren als Inplay und welche als AU gewertet werden. Selbst wenn der AU-Bereich innerhalb der ausgewählten Inplaykategorie liegt, stellt dies kein Problem dar. Das Plugin erkennt diese Bereiche korrekt und berücksichtigt die Unterscheidung entsprechend.<br>
Zusätzlich können einzelne Foren innerhalb der ausgewählten Inplaykategorien ausgeschlossen werden. Ein Beispiel wäre der Ausschluss eines Bereichs für SMS oder andere Kommunikationsmittel, die nicht als vollwertige Inplayszenen gelten sollen.

# Szeneninformationen im Forumdisplay und Thread
Das Plugin bietet die Möglichkeit, Szeneninformationen in verschiedenen Bereichen des Forums anzuzeigen: im Forumdisplay (Template "forumdisplay_thread"), im Showthread (Template "showthread") sowie im Postbit (Templates "postbit" und "postbit_classic"). Während die Anzeige im Forumdisplay standardmäßig aktiviert ist, müssen die Optionen für Showthread und Postbit über die Einstellungen separat aktiviert werden.<br>
<br>
Für alle drei Templates gibt es eine kompakte Variable, die eine schlichte Ausgabe der Szeneninformationen ermöglicht. Das Team kann die entsprechenden Templates für die kompakte Variable anpassen, um die gewünschten Informationen darzustellen. Die Szenenfelder können jedoch auch direkt angesprochen werden und entweder in das Template für die kompakte Variable eingefügt werden oder direkt in die entsprechenden Templates "forumdisplay_thread" (Forumdisplay), "showthread" (Showthread) sowie "postbit" und "postbit_classic" (Postbit), um die Szeneninformationen dort individuell anzuzeigen.<br>
- {$inplayscene['scenedate']}: Datum der Szene
- {$inplayscene['partnerusers']}: Teilnehmende Charaktere
- {$inplayscene['openscene']}: Szenenart
- {$inplayscene['postorder']}: Postingreihenfolge
- {$inplayscene['trigger']}: Triggerwarnung (falls aktiviert)
- {$inplayscene['Identifikator']}: Individuelles Szenenfeld<br>
<br>
Für die Anzeige in den Postbit-Templates muss $inplayscene['Inhalt'] durch $post['Inhalt'] ersetzt werden, um die Szeneninformationen korrekt in die einzelnen Posts zu integrieren.<br>
<br>
Für maximale Flexibilität im Design stellt das Plugin zwei Variablen bereit, die direkt in die entsprechenden Templates Forumdisplay ("forumdisplay_thread"), Showthread ("showthread") sowie Postbit ("postbit" und "postbit_classic") eingefügt werden können. Um zu verhindern, dass Texte oder Icons, die zur besseren Verständlichkeit oder Gestaltung hinzugefügt wurden, im Offplay-Bereich das Design stören, können diese Variablen genutzt werden. Um dies zu vermeiden, gibt es die Variable <b>{$display_onlyinplay}</b>, die mit einem <a href="https://wiki.selfhtml.org/wiki/HTML/Attribute/style">style-Tag</a> (style="display:none;") arbeitet, um den Inhalt im Offplay-Bereich unsichtbar zu machen. <br>
<i>display: none</i> unterdrückt die Anzeige eines Elements vollständig und das Element verbraucht keinen Platz im Layout. Diese Variable sollte entsprechend eingefügt werden, um eine saubere Darstellung zu gewährleisten.<br>
Ebenso kann das gleiche Prinzip auf Informationen angewendet werden, die im Offplay angezeigt, jedoch im Inplay ausgeblendet werden sollen. Dafür kann die Variable <b>{$display_offplay}</b> verwendet werden.

# Automatische Archivierung
Diese Funktion greift in zwei Fällen: bei der Löschung eines Accounts und bei Inaktivität einer Szene.<br>
1. <b>Archivierung bei Account-Löschung:</b><br>
Damit die Szenen eines gelöschten Accounts automatisch ins Archiv verschoben werden, muss der Account über das Popup "Optionen" im ACP gelöscht werden. Gehe dazu im ACP auf den Reiter Benutzer & Gruppen > Benutzer, wo alle Benutzer aufgelistet sind. Rechts neben jedem Account befindet sich ein Optionen-Button. Nach dem Drücken dieses Buttons erscheint eine Auswahl an Möglichkeiten. Wählst du die Option zur Löschung des Accounts, werden automatisch alle Szenen des gelöschten Charakters in das entsprechende Archiv verschoben.<br>
2. <b>Archivierung bei Inaktivität:</b><br>
Szenen gelten als inaktiv, wenn der letzte Post eine festgelegte Anzahl an Monaten überschritten hat. Diese Funktion kann in den Einstellungen individuell konfiguriert werden. Wird der Wert auf 0 gesetzt, ist die automatische Archivierung wegen Inaktivität deaktiviert.<br>
<br>
<b>Hinweis zur Archivierung nach Monaten:</b><br>
Einige Foren nutzen im Archiv Unterforen nach dem Format "Monatsname Jahr". Diese Struktur wird vom Plugin unterstützt. Aktuell sind deutsche und englische Monatsnamen vorgesehen, aber die Monatsnamen können problemlos erweitert werden. In der Datei inc/plugins und inc/task findest du folgendes Array:<br>
<br>
$months = array(<br>
    '01' => ['January', 'Januar'],<br>
    '02' => ['February', 'Februar'],<br>
    '03' => ['March', 'März'],<br>
    '04' => ['April'],<br>
    '05' => ['May', 'Mai'],<br>
    '06' => ['June', 'Juni'],<br>
    '07' => ['July', 'Juli'],<br>
    '08' => ['August'],<br>
    '09' => ['September'],<br>
    '10' => ['October', 'Oktober'],<br>
    '11' => ['November'],<br>
    '12' => ['December', 'Dezember'],<br>
);<br>
<br>
Möchtest du dieses Array erweitern oder ändern, kannst du entweder den Inhalt der Klammern [] entsprechend ersetzen oder weitere Monatsnamen durch Hinzufügen von ", 'Monatsname'" vor der schließenden Klammer ergänzen.<br>
<br>
<b>Wichtiger Hinweis:</b><br>
Das Plugin archiviert Szenen nur in diesen zwei Fällen (Accountlöschung und Inaktivität). Für die allgemeine Archivierung von Threads muss weiterhin händisch vorgegangen werden oder das <a href="https://github.com/aheartforspinach/Archivierung">Archivierungsplugin</a> von aheartforspinach installiert sein. Eine Anpassung dieses Plugins befindet sich weiter unten in der Dokumentation.

# PDF-Export
Mit dieser Funktion können alle Mitglieder:innen des Forums einzelne Inplaybeiträge oder ganze Szenen als PDF-Datei abspeichern. Der Titel der Szene wird dabei als Hauptüberschrift im PDF-Dokument verwendet. Die kleinere Überschrift, die unter dem Titel erscheint, kann jedes Team im Template "inplayscenes_pdf_fields" individuell anpassen.<br>
Standardmäßig werden im PDF nur die Charakternamen und das Szenendatum angezeigt. Das Team kann jedoch zusätzliche Informationen wie individuelle Szenenfelder mit {$inplayscene['Identifikator']}, die Postingreihenfolge mit {$inplayscene['postorder']} oder den Szenentyp mit {$inplayscene['openscene']} integrieren.<br>
<br>
<b>Wichtig:</b> Für die PDF-Exportfunktion verwende ich die TCPDF-Bibliothek, die viele HTML-Befehle unterstützt, jedoch nur eingeschränkt CSS-Befehle. Daher sollten nur eher auf einfache HTML-Tags wie ```<br>``` oder ```<b>``` zurückgegriffen werden, um die Ausgabe korrekt zu gestalten.

# Datenbank-Änderungen
hinzugefügte Tabelle:
- inplayscenes
- inplayscenes_fields

hinzugefügte Spalten in der Tabelle <b>users</b>:
- inplayscenes_notification
- inplayscenes_reminder_days
- inplayscenes_reminder_status

# Neue Sprachdateien
- deutsch_du/admin/inplayscenes.lang.php
- deutsch_du/inplayscenes.lang.php

# Einstellungen
- Inplay-Bereich
- Inplay-Archiv
- ausgeschlossene Foren
- AU-Szenen-Bereich
- AU-Szenen-Archiv
- Szenenarten
- Triggerwarnungen
- Szeneninformationen: Showthread
- Szeneninformationen: Postbit
- Übersicht aller Inplayszenen
- Anzeige vom nächster Poster
- Spielername
- inaktive Szenen<br>
<br>
<b>HINWEIS:</b><br>
Das Plugin ist kompatibel mit den klassischen Profilfeldern von MyBB und/oder dem <a href="https://github.com/katjalennartz/application_ucp">Steckbrief-Plugin von Risuena</a>.

# Neue Template-Gruppe innerhalb der Design-Templates
- Inplayszenen-Manager

# Neue Templates (nicht global!)
- inplayscenes_counter
- inplayscenes_editscene
- inplayscenes_editscene_fields
- inplayscenes_forumdisplay
- inplayscenes_forumdisplay_fields
- inplayscenes_memberprofile
- inplayscenes_memberprofile_none
- inplayscenes_memberprofile_scenes
- inplayscenes_memberprofile_year
- inplayscenes_newthread
- inplayscenes_newthread_fields
- inplayscenes_overview
- inplayscenes_overview_openscene_filter
- inplayscenes_overview_scene
- inplayscenes_overview_scene_fields
- inplayscenes_overview_scene_none
- inplayscenes_overview_scene_sort
- inplayscenes_pdf_fields
- inplayscenes_postbit
- inplayscenes_postbit_fields
- inplayscenes_postbit_pdf
- inplayscenes_postingreminder
- inplayscenes_postingreminder_banner
- inplayscenes_postingreminder_bit
- inplayscenes_postingreminder_none
- inplayscenes_postingreminder_scene
- inplayscenes_postingreminder_scene_fields
- inplayscenes_showthread
- inplayscenes_showthread_add
- inplayscenes_showthread_edit
- inplayscenes_showthread_fields
- inplayscenes_showthread_pdf
- inplayscenes_showthread_relevant
- inplayscenes_user
- inplayscenes_usersettings
- inplayscenes_usersettings_notification
- inplayscenes_user_character
- inplayscenes_user_none
- inplayscenes_user_scene
- inplayscenes_user_scene_fields
- inplayscenes_user_scene_infos
- inplayscenes_user_scene_last<br><br>
<b>HINWEIS:</b><br>
Alle Templates wurden größtenteils ohne Tabellen-Struktur gecodet. Das Layout wurde auf ein MyBB Default Design angepasst.

# Neue Variablen
- editpost: {$edit_inplayscenes}
- forumdisplay_thread: {$inplayscenes_forumdisplay}
- header: {$inplayscenes_postingreminder}
- header_welcomeblock_member: {$inplayscenes_headercount}
- newthread: {$newthread_inplayscenes}
- postbit & postbit_classic: {$post['inplayscenes_postbit']} & {$post['inplayscenes_pdf']}
- showthread: {$inplayscenes_pdf} & {$inplayscenes_relevant} & {$inplayscenes_add} & {$inplayscenes_edit} & {$inplayscenes_showthread}

# Neues CSS - inplayqscenes.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern - auch im Default. Sonst kann es passieren, dass es bei einem Update von MyBB entfernt wird.
<blockquote>.inplayscenes-formular_input-row {
        display: flex;
        flex-wrap: nowrap;
        justify-content: flex-start;
        align-items: center;
        margin: 5px 10px;
        gap: 5px;
        }

        .inplayscenes-formular_input-desc {
        width: 30%;
        }

        .inplayscenes-formular_input-input {
        width: 70%;
        }

        .inplayscenes-formular_button {
        text-align: center;
        margin: 5px 0;
        }

        .inplayscenes_memberprofile {
        display: flex;
        flex-wrap: nowrap;
        gap: 10px;
        background-color: #f5f5f5;
        }

        .inplayscenes_memberprofile-mainplays {
        width: 60%;
        padding: 10px;
        }

        .inplayscenes_memberprofile-sideplays {
        width: 37%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 10px;
        gap: 10px;
        }

        .inplayscenes_memberprofile-scenes {
        margin-bottom: 5px;
        }

        .inplayscenes_overview-filter-table {
        display: flex;
        flex-wrap: wrap;
        gap: 0 5px;
        justify-content: space-around;
        margin: 10px;
        }

        .inplayscenes_overview-filter-row {
        width: 49%;
        }

        .inplayscenes_overview-filter-input {
        text-align: center;
        margin: 5px 0;
        }

        .inplayscenes_overview-button {
        text-align: center;
        margin: 10px 0;
        }

        .inplayscenes_overview-sort {
        text-align: center;
        margin: 10px 0;
        }

        .inplayscenes_overview-scene-table {
        display: flex;
        flex-direction: column;
        }

        .inplayscenes_overview-scene-row {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid #ddd;
        padding: 10px;
        align-items: center;
        }

        .inplayscenes_overview-scene-column {
        flex: 1;
        }

        .inplayscenes_overview-scene-column:last-child {
        text-align: right;
        }

        .inplayscenes_overview-none {
        text-align: center;
        margin: 10px 0;
        }

        .inplayscenes-postbit {
        text-align:center; 
        margin-bottom: 10px;
        }

        .inplayscenes_postingreminder-desc {
        padding: 20px 40px;
        text-align: justify;
        line-height: 180%;
        }

        .inplayscenes_postingreminder-scene-table {
        display: flex;
        flex-direction: column;
        width: 100%;
        }

        .inplayscenes_postingreminder-scene-row {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid #ddd;
        padding: 10px;
        align-items: center;
        }

        .inplayscenes_postingreminder-scene-column {
        flex: 1;
        padding: 0 10px;
        }

        .inplayscenes_postingreminder-none {
        text-align: center;
        margin: 10px 0;
        }

        .inplayscenes_showthread-bit {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
        }

        .inplayscenes_showthread-bit:last-child {
        border-bottom: none;
        }

        .inplayscenes_showthread-label {
        width: 20%;
        font-weight: bold;
        }

        .inplayscenes_showthread-value {
        flex-grow: 1;
        }

        .inplayscenes_user-settings {
        display: flex;
        flex-wrap: wrap;
        gap: 20px 0px;
        align-items: flex-start;
        justify-content: space-evenly;
        padding: 20px 0;
        text-align: center;
        }

        .inplayscenes_user-scene-sort {
        text-align: center;
        }

        .inplayscenes_user-character-scenes {
        width: 80%;
        margin: 20px auto;
        }

        .inplayscenes_user-button {
        width: 100%;
        text-align: center;
        }

        .inplayscenes_user-scene-header span {
        float: right;
        font-style: italic;
        }

        .inplayscenes_user-scene-table {
        border: 1px solid #ddd;
        }

        .inplayscenes_user-scene-row {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        border-bottom: 1px solid #ddd;
        align-items: center;
        }

        .inplayscenes_user-scene-row:last-child {
        border-bottom: none;
        }

        .inplayscenes_user-scene-col {
        width: 33%;
        padding: 5px;
        }

        .inplayscenes_user-scene-none {
        text-align: center;
        margin: 10px 0;
        }
        
        .inplayscene_next_none {
        color: #a91717;
        }

        .inplayscene_next_you {
        color: #127b12;
        }
</blockquote>

# Benutzergruppen-Berechtigungen setzen
Damit alle Admin-Accounts Zugriff auf die Verwaltung der Inplayszenenfeler haben im ACP, müssen unter dem Reiter Benutzer & Gruppen » Administrator-Berechtigungen » Benutzergruppen-Berechtigungen die Berechtigungen einmal angepasst werden. Die Berechtigungen für die Inplayzitate befinden sich im Tab 'RPG Erweiterungen'.

# Links
<b>ACP</b><br>
index.php?module=rpgstuff-inplayscenes<br>
<br>
<b>Übersicht der eigenen Szenen</b><br>
misc.php?action=inplayscenes<br>
<br>
<b>Übersicht der aller Inplayszenen</b><br>
misc.php?action=all_inplayscenes<br>
<br>
<b>Szeneninformationen bearbeiten</b><br>
misc.php?action=inplayscenes_edit<br>
<br>
<b>Posting-Erinnerung</b><br>
misc.php?action=postingreminder

# Demo
### ACP
<img src="https://stormborn.at/plugins/inplayscenes_acp_overview.png">
<img src="https://stormborn.at/plugins/inplayscenes_acp_add.png">
<img src="https://stormborn.at/plugins/inplayscenes_acp_select.png">

### Individuelle Szenenübersicht
<img src="https://stormborn.at/plugins/inplayscenes_user.png">

### Übersicht aller Inplayszenen
<img src="https://stormborn.at/plugins/inplayscenes_all.png">

### Übersicht der Posting-Erinnerung
<img src="https://stormborn.at/plugins/inplayscenes_reminder.png">

### Profile
<img src="https://stormborn.at/plugins/inplayscenes_profile.png">

# PDF-Export Credits:
- https://tcpdf.org/
- https://www.php-einfach.de/experte/php-codebeispiele/pdf-per-php-erstellen-pdf-rechnung/ 

# Anpassung andere Plugins
### <a href="https://github.com/aheartforspinach/Archivierung">Themenarchivierung</a> von <a href="https://github.com/aheartforspinach">aheartforspinach</a>
suche nach folgender Stelle:
```php
if ($db->table_exists("ipt_scenes")) {
			$ipdate = $db->fetch_field($db->simple_select('ipt_scenes', 'date', 'tid = ' . $tid), 'date');
		} elseif ($db->table_exists("scenetracker")) {
			$ipdate = $db->fetch_field($db->simple_select('threads', 'scenetracker_date', 'tid = ' . $tid), 'scenetracker_date');
			$ipdate = strtotime($ipdate);
		}
```
ersetze es durch:
```php
if ($db->table_exists("ipt_scenes")) {
			$ipdate = $db->fetch_field($db->simple_select('ipt_scenes', 'date', 'tid = ' . $tid), 'date');
		} elseif ($db->table_exists("scenetracker")) {
			$ipdate = $db->fetch_field($db->simple_select('threads', 'scenetracker_date', 'tid = ' . $tid), 'scenetracker_date');
			$ipdate = strtotime($ipdate);
		} elseif ($db->table_exists("inplayscenes")) {
			$ipdate = $db->fetch_field($db->simple_select('inplayscenes', 'date', 'tid = ' . $tid), 'date');
			$ipdate = strtotime($ipdate);
		}
```

suche nach folgender Stelle:
```php
if ($db->table_exists('ipt_scenes_partners')) {
		$query = $db->simple_select('ipt_scenes_partners', 'uid', 'tid = ' . $thread['tid']);
	} elseif ($db->table_exists('scenetracker')) {
		$query = $db->fetch_field($db->simple_select('threads', 'scenetracker_user', 'tid = ' . $thread['tid']), "scenetracker_user");
	}
```
ersetze es durch:
```php
if ($db->table_exists('ipt_scenes_partners')) {
		$query = $db->simple_select('ipt_scenes_partners', 'uid', 'tid = ' . $thread['tid']);
	} elseif ($db->table_exists('scenetracker')) {
		$query = $db->fetch_field($db->simple_select('threads', 'scenetracker_user', 'tid = ' . $thread['tid']), "scenetracker_user");
	} elseif ($db->table_exists('inplayscenes')) {
		$query = $db->fetch_field($db->simple_select('inplayscenes', 'partners', 'tid = ' . $thread['tid']), "partners");
	}
```

suche nach folgender Stelle:
```php
if ($db->table_exists('ipt_scenes_partners')) {
		while ($row = $db->fetch_array($query)) {
			$partners[] = $row['uid'];
		}
	} elseif ($db->table_exists('scenetracker')) {
		$partnerNames = explode(",", $query);
		
		foreach ($partnerNames as $partnerName) {
			$partner = get_user_by_username($partnerName);
			$partners[] = $partner['uid'];
		}
	}
```
ersetze es durch:
```php
if ($db->table_exists('ipt_scenes_partners')) {
		while ($row = $db->fetch_array($query)) {
			$partners[] = $row['uid'];
		}
	} elseif ($db->table_exists('scenetracker')) {
		$partnerNames = explode(",", $query);
		
		foreach ($partnerNames as $partnerName) {
			$partner = get_user_by_username($partnerName);
			$partners[] = $partner['uid'];
		}
	} elseif ($db->table_exists('inplayscenes')) {
		$partnerNames = explode(",", $query);
		
		foreach ($partnerNames as $partnerName) {
			$partners[] = $partner['uid'];
		}
	}
```
### <a href="https://github.com/aheartforspinach/Whitelist">Whitelist</a> von <a href="https://github.com/aheartforspinach">aheartforspinach</a>
suche nach folgender Stelle in inc/datahandlers/whitelist.php:
```php
$query = $db->simple_select(
            'ipt_scenes ips join '.  TABLE_PREFIX .'posts p on ips.tid = p.tid join '.  TABLE_PREFIX .'ipt_scenes_partners ipp on ips.tid = ipp.tid', 
            'ipp.uid',
            'p.uid in ('. implode(',', array_keys($this->characters)) .') and dateline > '. $UnixFirstXMonthAgo,
            ['order_by' => 'dateline', 'order_dir' => 'desc']
        );
        while ($row = $db->fetch_array($query)) {
            if (!in_array($row['uid'], $allowedCharacters)) {
                $allowedCharacters[] = (int)$row['uid'];
            }
        }
```
ersetze es durch:
```php
$query = $db->simple_select(
        'inplayscenes ips join '.  TABLE_PREFIX .'posts p on ips.tid = p.tid', 
        'ips.partners',
        'p.uid in ('. implode(',', array_keys($this->characters)) .') and p.dateline > '. $UnixFirstXMonthAgo,
        ['order_by' => 'p.dateline', 'order_dir' => 'desc']
    );

    while ($row = $db->fetch_array($query)) {
        $partners = explode(',', $row['partners']);
        foreach ($partners as $partnerUid) {
            $partnerUid = intval($partnerUid);

            if (!in_array($partnerUid, $allowedCharacters)) {
                $allowedCharacters[] = $partnerUid;
            }
        }
    }
```

### <a href="https://github.com/ItsSparksFly/mybb-inplaykalender">Inplaykalender</a> von <a href="https://github.com/ItsSparksFly">ItsSparksFly</a>
suche nach folgender Stelle in inc/plugins/inplaykalender.php & inplaykalender.php
```php
if($db->table_exists("ipt_scenes")) {
                $query = $db->query("SELECT * FROM ".TABLE_PREFIX."ipt_scenes WHERE date = '$date'");
                if(mysqli_num_rows($query) > 0) {
                        $threadlist = "";
                        while($szenenliste = $db->fetch_array($query)) {
                            $thread = get_thread($szenenliste['tid']);
                            if($thread) {
                                $szenen = true;
                                $threadlist .= "&bull; <a href=\"showthread.php?tid={$thread['tid']}\" target=\"_blank\">{$thread['subject']}</a><br />{$szenenliste['shortdesc']}<br />";
                            } else {  }
                    } 
                } else { $threadlist = ""; }
            }
```
ersetze es durch:
```php
if($db->table_exists("ipt_scenes")) {
    $query = $db->query("SELECT * FROM ".TABLE_PREFIX."ipt_scenes WHERE date = '$date'");
    if(mysqli_num_rows($query) > 0) {
            $threadlist = "";
            while($szenenliste = $db->fetch_array($query)) {
                $thread = get_thread($szenenliste['tid']);
                if($thread) {
                    $szenen = true;
                    $threadlist .= "&bull; <a href=\"showthread.php?tid={$thread['tid']}\" target=\"_blank\">{$thread['subject']}</a><br />{$szenenliste['shortdesc']}<br />";
                } else {  }
        } 
    } else { $threadlist = ""; }
} elseif ($db->table_exists('inplayscenes')) {
    $date_db = date('Y-m-d', $date);
    $query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes WHERE date = '$date_db'");
    $szenen = false;
    if(mysqli_num_rows($query) > 0) {
            $threadlist = "";
            while($szenenliste = $db->fetch_array($query)) {
                $thread = get_thread($szenenliste['tid']);
                if($thread) {
                    $szenen = true;
                    $threadlist .= "&bull; <a href=\"showthread.php?tid={$thread['tid']}\" target=\"_blank\">{$thread['subject']}</a><br />";
                } else {  }
        } 
    } else { $threadlist = ""; }
}
```

### <a href="https://github.com/ItsSparksFly/mybb-plottracker/tree/inplaytracker30">Plottracker</a> von <a href="https://github.com/ItsSparksFly">ItsSparksFly</a>
suche nach folgender Stelle in inc/plugins/plottracker.php:
```php
$selectedforums = explode(",", $mybb->settings['ipt_inplay']);
```
ersetze es durch:
```php
$selectedforums = explode(",", $mybb->settings['inplayscenes_inplayarea'].",".$mybb->settings['inplayscenes_sideplays']);
$excludedareas = explode(",", $mybb->settings['inplayscenes_excludedarea']);

foreach ($excludedareas as $excludedarea) {
	$key = array_search($excludedarea, $selectedforums);
	if ($key !== false) {
		unset($selectedforums[$key]);
	}
}
```

suche nach folgender Stelle in plottracker.php:
```php
$query_2 = $db->simple_select("ipt_scenes_partners", "uid", "tid='{$thread['tid']}'");
            while($userlist = $db->fetch_array($query_2)) {
                $user = get_user($userlist['uid']);
                $username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
                $formattedname = build_profile_link($username, $userlist['uid']);
                $usernames .= "&nbsp; &nbsp; {$formattedname}";
            }
```
ersetze es durch:
```php
$partners_uids = $db->fetch_field($db->simple_select("inplayscenes", "partners", "tid = '".$thread['tid']."'"), 'partners');
$partners_usernames = $db->fetch_field($db->simple_select("inplayscenes", "partners_username", "tid = '".$thread['tid']."'"), 'partners_username');

$characters_uids = explode(",", $partners_uids);
$characters_uids = array_map("trim", $characters_uids);

$characters_usernames = explode(",", $partners_usernames);
$characters_usernames = array_map("trim", $characters_usernames);

$characters = array();
foreach ($characters_uids as $key => $uid) {
	$user = get_user($uid);
	if (!empty($user)) {
		$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$formattedname = build_profile_link($username, $uid);
		$characters[] = $formattedname;
	} else {
		$characters = $usernames[$key];
	}
}
$usernames = implode(" &nbsp; &nbsp; ", $characters);
```
