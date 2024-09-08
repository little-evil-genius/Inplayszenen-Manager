# Inplayszenen-Manager
Das Inplayszenen-Manager Plugin bietet ein umfassendes Tool zur Verwaltung von Inplayszenen. Es hilft dabei, Szenen übersichtlich zu strukturieren, um so den Überblick über (laufende) Szenen zu behalten. Durch die Nutzung dieses Plugins wird das Erstellen und Verwalten von Szenen wesentlich vereinfacht, indem wichtige Informationen wie Datum, teilnehmende Charaktere und individuelle Felder, welche im ACP erstellt werden können, erfasst werden. Das Plugin unterstützt und unterscheidet sowohl den normalen Inplaybereich sowie den Bereich für alternative Universen (AU) Szenen. Es besteht die Möglichkeit, Szenen in verschiedene Typen zu unterteilen: private Szenen, in denen nur bestimmte Charaktere teilnehmen dürfen, Szenen mit Absprache, bei denen eine Teilnahme nach Genehmigung erfolgt, sowie offene Szenen, in die sich jeder Charakter mit einem Klick hinzufügen kann. Zusätzlich kann für jede Szene eine Postingreihenfolge festgelegt werden. Es wird unterschieden zwischen Szenen mit fester Reihenfolge, bei denen die Teilnehmer in einer definierten Reihenfolge posten müssen, und solchen ohne feste Reihenfolge, bei denen das Posten freier erfolgt.<br>
<br>
Im Profil jedes Charakters werden alle bisherigen Szenen chronologisch aufgelistet. Hierbei wird zwischen Inplay- und AU-Szenen unterschieden, um eine klare Trennung zu erzielen. Szenen, in denen nur noch ein bestehender Charakter vorhanden ist, können als "nicht relevant" markiert werden, um die Übersichtlichkeit zu wahren, ohne die Szene komplett löschen zu müssen.<br>
<br>
Das Plugin beitet zudem eine persönliche Übersicht über die aktiven Szenen. Diese Übersicht zeigt auf, in welchen Szenen welcher Charakter als nächstes posten muss.<br>
Mitglieder:innen können eine individuelle Posting-Erinnerung festlegen, die sie nach einer selbst festgelegten Anzahl von Tagen darauf hinweist, welchen Szenen schon länger unbeantwortet wartet. Diese Erinnerung ist zusätzlich pro Charakter einstellbar. Genauso können Mitglieder:innen individuell entscheiden, wie sie über Inplayereignisse informiert werden wollen. Entweder per Private Nachricht oder MyAlerts, wenn das Forum diese Möglichkeit unterstützt.<br>
<br>
Bei Account Löschung werden betroffene Inplayszenen entsprechend in das Archiv verschoben. Zusätzlich kann das das automatische Archivieren von inaktiven Szenen eingestellt werden. Szenen, die über eine definierte Zeitspanne hinweg keine Aktivität zeigen (z.B. nach X Monaten ohne neuen Post), werden automatisch ins Archiv verschoben. 

# Wichtige Funktionen im Überblick
- <b>Individuelle Szenenfelder:</b> Im Admin-CP können benutzerdefinierte Felder für Szenen erstellt werden (z.B. für Ort, Tageszeit).
- <b>Inplay- und AU-Kategorisierung:</b> Das Plugin trennt Szenen klar nach Inplay- und AU-Bereichen. Zusätzlich können einzelne Foren innerhalb des Inplaybereichs ausgeschlossen werden.
- <b>Szenenarten:</b> Szenen können privat, nach Absprache oder offen sein, was unterschiedliche Teilnahmebedingungen für die Charaktere ermöglicht. (In den Einstellungen aktivierbar.) Bei offenen Szenen können sich Mitglieder:innen per Klick mit ihren Charakteren der Szene hinzufügen. 
- <b>Szeneninformationen im Forumdisplay und Thread:</b> Die Informationen zur Szene werden im Forumdisplay, Showthread und Postbit angezeigt, sofern diese Option aktiviert ist. Es gibt jeweils eine kompakte Variable oder können jeweils einzeln angesprochen werden.
- <b>Bearbeitungsmöglichkeiten:</b> Alle in einer Szene eingetragenen Charaktere können die Szeneninformationen nachträglich bearbeiten.
- <b>Individuelle Szenenübersicht:</b> Jedes Mitglied hat Zugriff auf eine eigene Übersicht der eigenen aktiven Szenen und kann leicht nachverfolgen, in welchen Szenen wer als Nächstes posten muss.
- <b>Übersicht aller Inplayszenen:</b> Das Plugin bietet eine zentrale Übersicht aller Inplayszenen des Forums, die mit verschiedenen Filtern (z.B. nach Bereich, Szenenstatus, Charakteren, Spieler:innen) durchsucht und gefiltert werden kann.
- <b>Posting-Erinnerungen:</b> Mitglied:innern können individuelle Erinnerungen einstellen, die nach einer bestimmten Zeitspanne Benachrichtigungen auslösen, falls sie in einer Szene posten müssen.
- <b>Relevanzstatus:</b> Szenen, in denen nur noch ein aktiver Charakter existiert, können als "nicht relevant" markiert werden, wenn sie nicht mehr fortgesetzt werden (z.B. bei Löschung von Postpartner:innen oder Szenenabbruch), ohne dass sie gelöscht werden müssen.
- <b>PDF-Export:</b> Szenen oder einzelne Inplay-Posts können als PDF-Dateien heruntergeladen und archiviert werden.
- <b>Automatische Archivierung bei Account löschung:</b> Szenen, bei den ein teilnehmdender Charakter gelöscht wird, werden automatisch ins Archiv verschoben. 
- <b>Automatische Archivierung von inaktiven Szenen:</b> Szenen, die länger keine Aktivität zeigen, werden automatisch ins Archiv verschoben. Funktion kann vom Team deaktiviert werden.
- <b>Benachrichtigungen:</b> Mitglieder:innen können wählen, ob sie Benachrichtigungen über MyAlerts oder private Nachrichten erhalten möchten.

# Vorrausetzung
- Der <a href="https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/enhanced-account-switcher/" target="_blank">Accountswitcher</a> von doylecc <b>muss</b> installiert sein.

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

# Neues Templates (nicht global!)
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

# Neue Variable
- editpost: {$edit_inplayscenes}
- forumdisplay_thread: {$inplayscenes_forumdisplay}
- header: {$inplayscenes_postingreminder}
- header_welcomeblock_member: {$inplayscenes_headercount}
- newthread: {$newthread_inplayscenes}
- postbit & postbit_classic: {$post['inplayscenes_postbit']} & {$post['inplayscenes_pdf']}
- showthread: {$inplayscenes_pdf} & {$inplayscenes_relevant} & {$inplayscenes_add} & {$inplayscenes_edit} & {$inplayscenes_showthread}

# Neues CSS - inplayquotes.css
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

# Individuelle Szenenfelder

# Szeneninformationen im Forumdisplay und Thread

# Automatische Archivierung

# PDF-Export

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
