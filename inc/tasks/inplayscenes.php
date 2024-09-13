<?php
function task_inplayscenes($task){

    global $db, $mybb, $lang, $cache;

    $lang->load('inplayscenes');

    // Aktuelles Datum
    $today = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    $current_date = $today->format('Y-m-d'); // Heutiges Datum

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $inactive_months = $mybb->settings['inplayscenes_inactive_scenes'];

    if ($inactive_months != 0) {

        // RELEVANTE FORUMS FIDs
        $relevant_forums_inplay = inplayscenes_get_relevant_forums($inplay_forum);
        $relevant_forums_sideplay = inplayscenes_get_relevant_forums($sideplays_forum);
        $relevant_forums_inplay = array_diff($relevant_forums_inplay, $relevant_forums_sideplay);
    
        $inactivdate = (new DateTime($current_date))->modify('-'.$inactive_months.' months')->format('Y-m-d');
    
        $months = array(
            '01' => ['January', 'Januar'],
            '02' => ['February', 'Februar'],
            '03' => ['March', 'März'],
            '04' => ['April'],
            '05' => ['May', 'Mai'],
            '06' => ['June', 'Juni'],
            '07' => ['July', 'Juli'],
            '08' => ['August'],
            '09' => ['September'],
            '10' => ['October', 'Oktober'],
            '11' => ['November'],
            '12' => ['December', 'Dezember'],
        );
    
        // INPLAY SZENEN
        $allinplay_query = $db->query("SELECT t.tid, t.fid, i.date FROM ".TABLE_PREFIX."threads t 
        LEFT JOIN ".TABLE_PREFIX."inplayscenes i ON i.tid = t.tid 
        WHERE t.fid IN (".implode(',', $relevant_forums_inplay).")
        AND DATE(FROM_UNIXTIME(t.lastpost)) < '".$inactivdate."'
        ");
    
        while($ilist = $db->fetch_array($allinplay_query)) {
    
            // Datum extrahieren
            $date = $ilist['date']; // Format: YYYY-MM-DD
            $year = date('Y', strtotime($date));
            $month_number = date('m', strtotime($date)); // Numerischer Monatswert (01-12)
    
            // Passenden Monatsnamen aus der Sprachliste suchen
            $possible_month_names = $months[$month_number];
    
            // Überprüfen, ob ein passendes Archiv-Unterforum existiert
            $subforum_fid = false;
            foreach ($possible_month_names as $month_name) {
                $subforum_query = $db->simple_select("forums", "fid", "name = '".$month_name." ".$year."' AND pid = ".$inplay_archive."");
                $subforum_fid = $db->fetch_field($subforum_query, 'fid');
                if ($subforum_fid) {
                    break;
                }
            }
    
            // Neue FID: Entweder das gefundene Unterforum oder das Standard-Archiv
            if ($subforum_fid) {
                $new_fid = $subforum_fid;
            } else {
                $new_fid = $inplay_archive;
            }
    
            // FID in den Threads und Posts aktualisieren
            $update_iFid = array('fid' => $new_fid);
            $db->update_query('threads', $update_iFid, "tid='".$ilist['tid']."'");
            $db->update_query('posts', $update_iFid, "tid='".$ilist['tid']."'");
    
            // Forum-Zähler aktualisieren
            require_once MYBB_ROOT . "inc/functions_rebuild.php";
            rebuild_forum_counters($ilist['fid']); // Altes Forum
            rebuild_forum_counters($new_fid);      // Neues Forum
    
            // Cache aktualisieren
            $cache->update_forums();
            $cache->update_stats();
        }
    }

    add_task_log($task, "Task erfolgreich ausgeführt.");
}
