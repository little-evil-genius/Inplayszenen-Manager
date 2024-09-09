<?php
define("IN_MYBB", 1);
require("global.php");
// error_reporting(-1);
// ini_set('display_errors', 1);

global $db, $mybb, $lang;

echo (
	'<title>Update Script Inplayszenen</title>
    <style type="text/css">
    body {
    background-color: #efefef;
    text-align: center;
    margin: 40px 100px;
    font-family: Verdana;
    }
    fieldset {
    width: 50%;
    margin: auto;
    margin-bottom: 20px;
    }

    legend {
    font-weight: bold;
    }
    </style>'
);

if ($mybb->usergroup['canmodcp'] == 1) {
	echo "<h1>Update Script um die Inplayszenen zu übertragen</h1>";
	echo "<p>vorher ein BackUp der Datenbank machen!</p>";

	echo '<form action="" method="post">';
	echo '<select id="trackersystem" name="trackersystem">
  <option value="">Altes Inplayszenensystem wählen</option>
  <option value="jule2">Inplaytracker 2.0 von sparks fly</option>
  <option value="jule3">Inplaytracker 3.0 von sparks fly</option>
  <option value="katja">Szenentracker von risuena</option>
  </select><br><br>';
	echo '<input type="submit" name="update_inplayscenessystem" value="Inplayszenen übertragen">';
	echo '</form>';

	if (isset($_POST['update_inplayscenessystem'])) {
		$selected_tracker = $_POST['trackersystem'];

		// Überprüfe, ob der User ein Trackersystem ausgewählt hat
		if (empty($selected_tracker)) {
			echo "<p style='color:red;'>Bitte wähle ein Trackersystem aus!</p>";
		} else {
			// Inplaytracker 2.0 von sparks fly
			if ($selected_tracker == "jule2") {

				$iport_update = array(
          'identification' => $db->escape_string('iport'),
          'title' => $db->escape_string('Ort'),
          'description' => $db->escape_string('Wo spielt deine Szene?'),
          'type' => $db->escape_string('text'),
          'options' => '',
          'required' => (int)1,
          'edit' => (int)1,
          'disporder' => (int)1,
          'allow_html' => (int)0,
          'allow_mybb' => (int)0,
          'allow_img' => (int)0,
          'allow_video' => (int)0,
        );

        $ipdaytime_update = array(
          'identification' => $db->escape_string('ipdaytime'),
          'title' => $db->escape_string('Tageszeit'),
          'description' => $db->escape_string('Zu welcher Tageszeit spielt deine Szene?'),
          'type' => $db->escape_string('text'),
          'options' => '',
          'required' => (int)1,
          'edit' => (int)1,
          'disporder' => (int)2,
          'allow_html' => (int)0,
          'allow_mybb' => (int)0,
          'allow_img' => (int)0,
          'allow_video' => (int)0,
        );

				if ($db->insert_query("inplayscenes_fields", $iport_update) && $db->insert_query("inplayscenes_fields", $ipdaytime_update)) {

					$db->write_query("ALTER TABLE `".TABLE_PREFIX."inplayscenes` ADD `iport` TEXT NOT NULL;");
					$db->write_query("ALTER TABLE `".TABLE_PREFIX."inplayscenes` ADD `ipdaytime` TEXT NOT NULL;");

					// Prüfe die Einstellung inplaytracker_timeformat
					$timeformat = $mybb->settings['inplaytracker_timeformat'];

					// Wenn timeformat auf 1 steht, hole die Monatsnamen
					if ($timeformat == 1) {
						// Hole die benutzerdefinierten Monatsnamen
						$months_query = "SELECT value FROM " . TABLE_PREFIX . "settings WHERE name = 'inplaytracker_months'";
						$months_result = $db->fetch_array($db->query($months_query));
						$months_array = explode(', ', $months_result['value']); // in Array umwandeln
					}

					// Daten von threads nach inplayscenes übertragen
					// alle Einträge bei denen das Feld "partners" nicht leer ist
					$threads_query = "SELECT * FROM `".TABLE_PREFIX."threads` WHERE `partners` IS NOT NULL AND `partners` != ''";
					$threads_result = $db->query($threads_query);

          $all_successful = true;
					while ($thread = $db->fetch_array($threads_result)) {

						// Setze die festen Felder
						$tid = $thread['tid'];
						$partners = $db->escape_string($thread['partners']);
						$postorder = $db->escape_string($thread['postorder']);
						$iport = $db->escape_string($thread['iport']);
						$ipdaytime = $db->escape_string($thread['ipdaytime']);
						$trigger_warning = ''; // fest
						$relevant = 1; // fest

						// Bereite das Feld openscene vor
						switch ($thread['openscene']) {
							case -1:
								$openscene = 0;
								break;
							case 0:
								$openscene = 1;
								break;
							case 1:
								$openscene = 2;
								break;
							default:
								$openscene = 0;
						}

							// ipdate kann Timestamp oder DATE sein
							$ipdate = $thread['ipdate'];
							if ($timeformat == 1) {
								// Benutzerdefiniertes Datumsformat - z.B. "1 Nachjul 2023"
								// Splitte ipdate und hole die Monat-Position
								list($day, $monthname, $year) = explode(' ', $ipdate);

								// Suche den Monatsnamen im Monatsarray
								$month = array_search($monthname, $months_array) + 1; // Position +1 für Monatsnummer

								// Falls der Monatsname nicht gefunden wird, nutze Standard
								if ($month === false) {
									$month = 1; // Default-Wert
								}

								// Erstelle das Datum im richtigen Format
								$date = "$year-$month-$day";

							} elseif (is_numeric($ipdate)) {
								// Ist es ein Timestamp? Dann konvertieren
								$date = date('Y-m-d', $ipdate);
							} else {
								// Ansonsten davon ausgehen, dass es im DATE-Format vorliegt
								$date = $ipdate;
							}

							$partners_array = explode(',', $partners);
							$partners_usernames = array();
							foreach ($partners_array as $partner_uid) {

								$partner_uid = trim($partner_uid);
								$user_query = "SELECT `username` FROM `".TABLE_PREFIX."users` WHERE `uid` = '$partner_uid'";

								$user_result = $db->query($user_query);
								if ($db->num_rows($user_result) > 0) {
									$user = $db->fetch_array($user_result);
									$partners_usernames[] = $db->escape_string($user['username']);
								} else {
									$partners_usernames[] = 'Gast';
								}
							}
							$partners_username = implode(',', $partners_usernames);

							$new_scene = array(
								'tid' => (int)$tid,
								'partners' => $partners,
								'partners_username' => $partners_username,
								'date' => $date,
								'trigger_warning' => $trigger_warning,
								'openscene' => (int)$openscene,
								'postorder' => (int)$postorder,
								'iport' => $iport,
								'ipdaytime' => $ipdaytime,
								'relevant' => $relevant,
							);

              if (!$db->insert_query("inplayscenes", $new_scene)) {
                $all_successful = false;
                break; 
              }
					}

          if ($all_successful) {
            echo "Alle Szenen wurden erfolgreich übertragen. Du kannst nun das alte Trackersystem entfernen.<br>";
            echo "<p style='color:red;'>Entferne umgehend auch diese Datei!</p>";
          } else {
            echo "Fehler beim Übertragen der Szenen. Bitte versuche es erneut.<br>";
          }
				} else {
				
          echo "Fehler beim Übertragen der Felder. Versuche es erneut.";
				}
			
      } 
      // Inplaytracker 3.0 von sparks fly
      else if ($selected_tracker == "jule3") {

				$location_update = array(
          'identification' => $db->escape_string('location'),
          'title' => $db->escape_string('Ort'),
          'description' => $db->escape_string('Wo spielt deine Szene?'),
          'type' => $db->escape_string('text'),
          'options' => '',
          'required' => (int)1,
          'edit' => (int)1,
          'disporder' => (int)1,
          'allow_html' => (int)0,
          'allow_mybb' => (int)0,
          'allow_img' => (int)0,
          'allow_video' => (int)0,
        );

        $shortdesc_update = array(
          'identification' => $db->escape_string('shortdesc'),
          'title' => $db->escape_string('Szenenbeschreibung (optional)'),
          'description' => $db->escape_string('Beschreibe in maximal 140 Zeichen, worum es in dieser Szene geht.'),
          'type' => $db->escape_string('text'),
          'options' => '',
          'required' => (int)1,
          'edit' => (int)1,
          'disporder' => (int)2,
          'allow_html' => (int)0,
          'allow_mybb' => (int)0,
          'allow_img' => (int)0,
          'allow_video' => (int)0,
        );

				if ($db->insert_query("inplayscenes_fields", $location_update) && $db->insert_query("inplayscenes_fields", $shortdesc_update)) {

					$db->write_query("ALTER TABLE `".TABLE_PREFIX."inplayscenes` ADD `location` TEXT NOT NULL;");
					$db->write_query("ALTER TABLE `".TABLE_PREFIX."inplayscenes` ADD `shortdesc` TEXT NOT NULL;");

          $scenes_query = "SELECT * FROM `".TABLE_PREFIX."ipt_scenes`";
          $scenes_result = $db->query($scenes_query);

          $all_successful = true;

          while ($scene = $db->fetch_array($scenes_result)) {
            $tid = $scene['tid'];
            $location = $db->escape_string($scene['location']);
            $shortdesc = $db->escape_string($scene['shortdesc']);
            $trigger_warning = '';  // fest
            $postorder = 1;  // fest
            $relevant = 1;  // fest
      
            // Wandeln des Timestamps in ein Datum
            $date = date('Y-m-d', $scene['date']);  // Timestamp in Date-Format konvertieren
      
            // openscene-Wert umwandeln
            $openscene = ($scene['openscene'] == 1) ? 2 : 0;
      
            // Partner-Daten aus ipt_scenes_partners ermitteln
            $partners_query = "SELECT * FROM `".TABLE_PREFIX."ipt_scenes_partners` WHERE `tid` = '$tid' ORDER BY `spid` ASC";
            $partners_result = $db->query($partners_query);
      
            $partners_array = array();
            $partners_usernames = array();
      
            while ($partner = $db->fetch_array($partners_result)) {
              $uid = (int)$partner['uid'];
              $partners_array[] = $uid;
      
              // Suche den Benutzernamen anhand der UID
              $user_query = "SELECT `username` FROM `".TABLE_PREFIX."users` WHERE `uid` = '$uid'";
              $user_result = $db->query($user_query);
              if ($db->num_rows($user_result) > 0) {
                $user = $db->fetch_array($user_result);
                $partners_usernames[] = $db->escape_string($user['username']);
              } else {
                // Wenn der Benutzer nicht existiert, füge "Gast" hinzu
                $partners_usernames[] = 'Gast';
              }
            }
      
            // Partner als String zusammenfügen
            $partners = implode(',', $partners_array);
            $partners_username = implode(',', $partners_usernames);

            $new_scene = array(
              'tid' => (int)$tid,
              'partners' => $partners,
              'partners_username' => $partners_username,
              'date' => $date,
              'trigger_warning' => $trigger_warning,
              'openscene' => (int)$openscene,
              'postorder' => (int)$postorder,
              'shortdesc' => $shortdesc,
              'location' => $location,
              'relevant' => $relevant,
            );
      
            // Führe die Abfrage aus und prüfe auf Fehler
            if (!$db->insert_query("inplayscenes", $new_scene)) {
              $all_successful = false;  // Setze auf false, wenn ein Fehler auftritt
              break;  // Beende die Schleife bei einem Fehler
            }
          }

          if ($all_successful) {
            echo "Alle Szenen wurden erfolgreich übertragen. Du kannst nun das alte Trackersystem entfernen.<br>";
            echo "<p style='color:red;'>Entferne umgehend auch diese Datei!</p>";
          } else {
            echo "Fehler beim Übertragen der Szenen. Bitte versuche es erneut.<br>";
          }
        } else {
          echo "Fehler beim Übertragen der Felder. Versuche es erneut.";
				}
      }
      // Szenentracker von Katja
      else if ($selected_tracker == "katja") {

				$location_update = array(
          'identification' => $db->escape_string('place'),
          'title' => $db->escape_string('Ort'),
          'description' => $db->escape_string('Hier den Ort eintragen. Wo findet die Szene statt?'),
          'type' => $db->escape_string('text'),
          'options' => '',
          'required' => (int)1,
          'edit' => (int)1,
          'disporder' => (int)1,
          'allow_html' => (int)0,
          'allow_mybb' => (int)0,
          'allow_img' => (int)0,
          'allow_video' => (int)0,
        );

        $time_update = array(
          'identification' => $db->escape_string('time'),
          'title' => $db->escape_string('Tageszeit'),
          'description' => $db->escape_string('Wann spielt die Szene?'),
          'type' => $db->escape_string('text'),
          'options' => '',
          'required' => (int)1,
          'edit' => (int)1,
          'disporder' => (int)2,
          'allow_html' => (int)0,
          'allow_mybb' => (int)0,
          'allow_img' => (int)0,
          'allow_video' => (int)0,
        );

        if ($db->insert_query("inplayscenes_fields", $location_update) && $db->insert_query("inplayscenes_fields", $time_update)) {

					$db->write_query("ALTER TABLE `".TABLE_PREFIX."inplayscenes` ADD `time` TEXT NOT NULL;");
					$db->write_query("ALTER TABLE `".TABLE_PREFIX."inplayscenes` ADD `place` TEXT NOT NULL;");

          $scenes_query = "SELECT * FROM `".TABLE_PREFIX."threads` WHERE `scenetracker_user` != ''";
          $scenes_result = $db->query($scenes_query);

          $all_successful = true; 
          while ($scene = $db->fetch_array($scenes_result)) {
    
            $tid = (int)$scene['tid'];
            $place = $db->escape_string($scene['scenetracker_place']);
            $trigger_warning = $db->escape_string($scene['scenetracker_trigger']);
            $partners_username = $db->escape_string($scene['scenetracker_user']);
            $openscene = 0; 
            $postorder = 1; 
            $relevant = 1;
   
            $date = date('Y-m-d', strtotime($scene['scenetracker_date']));

            if (!empty($scene['scenetracker_time_text'])) {
              $time = $db->escape_string($scene['scenetracker_time_text']);
            } else {
              $time = date('H:i', strtotime($scene['scenetracker_date']));    
            }

            $partners_query = "SELECT * FROM `".TABLE_PREFIX."scenetracker` WHERE `tid` = '$tid' ORDER BY `id` ASC";    
            $partners_result = $db->query($partners_query);

            $partners_array = array();
            while ($partner = $db->fetch_array($partners_result)) {
              $partners_array[] = (int)$partner['uid'];    
            }

            $partners = implode(',', $partners_array);

            $new_scene = array(
              'tid' => (int)$tid,
              'partners' => $partners,
              'partners_username' => $partners_username,
              'date' => $date,
              'trigger_warning' => $trigger_warning,
              'openscene' => (int)$openscene,
              'postorder' => (int)$postorder,
              'relevant' => (int)$relevant,
              'place' => $place,
              'time' => $time,
            );
      
            // Führe die Abfrage aus und prüfe auf Fehler
            if (!$db->insert_query("inplayscenes", $new_scene)) {
              $all_successful = false;  // Setze auf false, wenn ein Fehler auftritt
              break;  // Beende die Schleife bei einem Fehler
            }    
          }

          // Am Ende der Übertragung eine Nachricht ausgeben
          if ($all_successful) {
            echo "Alle Szenen wurden erfolgreich übertragen. Du kannst nun das alte Trackersystem entfernen.<br>";
            echo "<p style='color:red;'>Entferne umgehend auch diese Datei!</p>";
          } else {
            echo "Fehler beim Übertragen der Szenen. Bitte versuche es erneut.<br>";
          }      

        }

      }
      else {
        echo "Für dieses Trackersystem gibt es noch kein Update-Skript.";
        echo "Du kannst im SG Support Thema um Hilfe bitten.";
      }
    }
  }
} else {
  echo "<h1>Kein Zugriff</h1>";	
}


echo '<div style="width:100%; background-color: rgb(121 123 123 / 50%); display: flex; position:fixed; bottom:0;right:0; height:50px; justify-content: center; align-items:center; gap:20px;">
<div> <a href="https://github.com/little-evil-genius/Inplayszenen-Manager" target="_blank">Github Rep</a></div>
<div> <b>Kontakt:</b> little.evil.genius (Discord)</div>
<div> <b>Support:</b>  <a href="LINK">SG Thread</a> oder per Discord</div>
</div>';	