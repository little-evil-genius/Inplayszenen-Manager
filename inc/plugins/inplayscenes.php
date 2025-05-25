<?php
/**
 * Inplayszenen Manager  - by little.evil.genius
 * https://github.com/little-evil-genius/Inplayszenen-Manager
 * https://storming-gates.de/member.php?action=profile&uid=1712
 * 
 * Das RPG Inplay Szenen-Manager Plugin hilft dabei, Inplayszenen und alternative Universen (AU) 
 * in RPG-Foren strukturiert zu verwalten. 
 * Es bietet Funktionen wie benutzerdefinierte Szenenfelder, Szenenarten (privat, nach Absprache, offen), 
 * Postingreihenfolgen, eine zentrale Szenenübersicht und individuelle Posting-Erinnerungen für Charaktere.
 * 
 * PDF EXPORT:
 * CREDITS to https://tcpdf.org/
 * and https://www.php-einfach.de/experte/php-codebeispiele/pdf-per-php-erstellen-pdf-rechnung/ 
*/

// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB")){
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// HOOKS
$plugins->add_hook("admin_config_settings_change", "inplayscenes_settings_change");
$plugins->add_hook("admin_settings_print_peekers", "inplayscenes_settings_peek");
$plugins->add_hook("admin_rpgstuff_action_handler", "inplayscenes_admin_rpgstuff_action_handler");
$plugins->add_hook("admin_rpgstuff_permissions", "inplayscenes_admin_rpgstuff_permissions");
$plugins->add_hook("admin_rpgstuff_menu", "inplayscenes_admin_rpgstuff_menu");
$plugins->add_hook("admin_rpgstuff_menu_updates", "inplayscenes_admin_rpgstuff_menu_updates");
$plugins->add_hook("admin_load", "inplayscenes_admin_manage");
$plugins->add_hook('admin_rpgstuff_update_stylesheet', 'inplayscenes_admin_update_stylesheet');
$plugins->add_hook('admin_rpgstuff_update_plugin', 'inplayscenes_admin_update_plugin');
$plugins->add_hook('newthread_start', 'inplayscenes_newthread_start');
$plugins->add_hook("datahandler_post_validate_thread", "inplayscenes_validate_newthread");
$plugins->add_hook('newthread_do_newthread_end', 'inplayscenes_do_newthread');
$plugins->add_hook('editpost_end', 'inplayscenes_editpost');
$plugins->add_hook('datahandler_post_validate_post', 'inplayscenes_validate_editpost');
$plugins->add_hook('editpost_do_editpost_end', 'inplayscenes_do_editpost');
$plugins->add_hook("newreply_do_newreply_end", "inplayscenes_do_newreply");
$plugins->add_hook("class_moderation_delete_thread_start", "inplayscenes_delete_thread");
$plugins->add_hook("class_moderation_delete_post_start", "inplayscenes_delete_post");
$plugins->add_hook("editpost_deletepost", "inplayscenes_deletepost");
$plugins->add_hook('forumdisplay_thread_end', 'inplayscenes_forumdisplay_thread');
$plugins->add_hook("forumdisplay_before_thread", "inplayscenes_forumdisplay_before_thread");
$plugins->add_hook("build_forumbits_forum", "inplayscenes_build_forumbits_forum");
$plugins->add_hook("postbit", "inplayscenes_postbit"); // normaler Postbit
$plugins->add_hook("postbit_prev", "inplayscenes_postbit"); // Vorschau
$plugins->add_hook("postbit_pm", "inplayscenes_postvariables"); // Private Nachricht
$plugins->add_hook("postbit_announcement", "inplayscenes_postvariables"); // Ankündigungen
$plugins->add_hook('showthread_start', 'inplayscenes_showthread_start');
$plugins->add_hook("no_permission", "inplayscenes_no_permission");
$plugins->add_hook("search_do_search_process", "inplayscenes_search_process");
$plugins->add_hook("search_results_end", "inplayscenes_search_results");
$plugins->add_hook("search_results_post", "inplayscenes_search_results_post");
$plugins->add_hook('member_profile_end', 'inplayscenes_memberprofile');
$plugins->add_hook("misc_start", "inplayscenes_misc");
$plugins->add_hook('global_intermediate', 'inplayscenes_global');
$plugins->add_hook("admin_user_users_delete_commit_end", "inplayscenes_user_delete");
$plugins->add_hook("datahandler_user_update", "inplayscenes_user_update");
$plugins->add_hook("usercp_do_changename_end", "inplayscenes_update_username");
$plugins->add_hook("fetch_wol_activity_end", "inplayscenes_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "inplayscenes_online_location");
$plugins->add_hook("xmlhttp_get_users_end", "inplayscenes_playername_autocompled");
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "inplayscenes_myalerts");
}
 
// Die Informationen, die im Pluginmanager angezeigt werden
function inplayscenes_info(){
	return array(
		"name"		=> "Inplayszenen-Manager",
		"description"	=> "Bietet die Möglichkeit Inplayszenen in RPG-Foren strukturiert zu verwalten.",
		"website"	=> "https://github.com/little-evil-genius/Inplayszenen-Manager",
		"author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0.6",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function inplayscenes_install(){
    
    global $db, $cache, $lang;

    // SPRACHDATEI
    $lang->load("inplayscenes");

    // RPG Stuff Modul muss vorhanden sein
    if (!file_exists(MYBB_ADMIN_DIR."/modules/rpgstuff/module_meta.php")) {
		flash_message($lang->inplayscenes_error_rpgstuff, 'error');
		admin_redirect('index.php?module=config-plugins');
	}

    // Accountswitcher muss vorhanden sein
    if (!function_exists('accountswitcher_is_installed')) {
		flash_message($lang->inplayscenes_error_accountswitcher, 'error');
		admin_redirect('index.php?module=config-plugins');
	}

    // DATENBANKTABELLEN UND FELDER
    inplayscenes_database();

    // EINSTELLUNGEN HINZUFÜGEN
    $maxdisporder = $db->fetch_field($db->query("SELECT MAX(disporder) FROM ".TABLE_PREFIX."settinggroups"), "MAX(disporder)");
    $setting_group = array(
        'name'          => 'inplayscenes',
        'title'         => 'Inplayszenen-Manager',
        'description'   => 'Einstellungen für das verwalten der Inplayszenen',
        'disporder'     => $maxdisporder+1,
        'isdefault'     => 0
    );
    $db->insert_query("settinggroups", $setting_group);

    // Einstellungen
    inplayscenes_settings();
    rebuild_settings();

	// Task hinzufügen
    $date = new DateTime(date("d.m.Y", strtotime('+1 day')));
    $inplayscenesTask = array(
        'title' => 'Inplayszenen',
        'description' => 'archiviert inaktive Inplayszenen.',
        'file' => 'inplayscenes',
        'minute' => 0,
        'hour' => 0,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
        'nextrun' => $date->getTimestamp(),
        'logging' => 1,
        'locked' => 1
    );
    $db->insert_query('tasks', $inplayscenesTask);
    $cache->update_tasks();

    // TEMPLATES ERSTELLEN
	// Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "inplayscenes",
        "title" => $db->escape_string("Inplayszenen-Manager"),
    );
    $db->insert_query("templategroups", $templategroup);
    // Templates 
    inplayscenes_templates();
    
    // STYLESHEET HINZUFÜGEN
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    // Funktion
    $css = inplayscenes_stylesheet();
    $sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "inplayscenes.css"), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}  
}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function inplayscenes_is_installed(){

    global $db, $mybb;

    if ($db->table_exists("inplayscenes_fields")) {
        return true;
    }
    return false;

} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function inplayscenes_uninstall(){
    
	global $db, $cache;

    //DATENBANKEN LÖSCHEN
    if($db->table_exists("inplayscenes"))
    {
        $db->drop_table("inplayscenes");
    }
    if($db->table_exists("inplayscenes_fields"))
    {
        $db->drop_table("inplayscenes_fields");
    }

    // DATENBANKFELDER LÖSCHEN
    if ($db->field_exists("inplayscenes_notification", "users")) {
        $db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP inplayscenes_notification;");
    }
    if ($db->field_exists("inplayscenes_reminder_days", "users")) {
        $db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP inplayscenes_reminder_days;");
    }
    if ($db->field_exists("inplayscenes_reminder_status", "users")) {
        $db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP inplayscenes_reminder_status;");
    }

    // TEMPLATGRUPPE LÖSCHEN
    $db->delete_query("templategroups", "prefix = 'inplayscenes'");

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'inplayscenes%'");
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'inplayscenes%'");
    $db->delete_query('settinggroups', "name = 'inplayscenes'");

    rebuild_settings();

	// TASK LÖSCHEN
	$db->delete_query('tasks', "file='inplayscenes'");
	$cache->update_tasks();

    // STYLESHEET ENTFERNEN
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'inplayscenes.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function inplayscenes_activate(){
    
    global $db, $cache;
    
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    
    // VARIABLEN EINFÜGEN
    find_replace_templatesets('editpost', '#'.preg_quote('{$posticons}').'#', '{$edit_inplayscenes} {$posticons}');
	find_replace_templatesets('forumdisplay_thread', '#'.preg_quote('{$thread[\'multipage\']}').'#', '{$thread[\'multipage\']} {$inplayscenes_forumdisplay}');
	find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$inplayscenes_postingreminder} {$bbclosedwarning}');
	find_replace_templatesets('header_welcomeblock_member', '#'.preg_quote('{$buddylink}').'#', '{$inplayscenes_headercount} {$buddylink}');
	find_replace_templatesets('newthread', '#'.preg_quote('{$posticons}').'#', '{$newthread_inplayscenes} {$posticons}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'message\']}').'#', '{$post[\'inplayscenes_postbit\']} {$post[\'message\']}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'button_edit\']}').'#', '{$post[\'inplayscenes_pdf\']} {$post[\'button_edit\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'message\']}').'#', '{$post[\'inplayscenes_postbit\']} {$post[\'message\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'button_edit\']}').'#', '{$post[\'inplayscenes_pdf\']} {$post[\'button_edit\']}');
	find_replace_templatesets('showthread', '#'.preg_quote('{$newreply}').'#', '{$inplayscenes_pdf} {$newreply}');
	find_replace_templatesets('showthread', '#'.preg_quote('{$newreply}').'#', '{$inplayscenes_relevant} {$newreply}');
	find_replace_templatesets('showthread', '#'.preg_quote('{$newreply}').'#', '{$inplayscenes_add} {$newreply}');
	find_replace_templatesets('showthread', '#'.preg_quote('{$newreply}').'#', '{$inplayscenes_edit} {$newreply}');
	find_replace_templatesets('showthread', '#'.preg_quote('<tr><td id="posts_container">').'#', '{$inplayscenes_showthread} <tr><td id="posts_container">');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$bannedbit}').'#', '{$inplayscenes_memberprofile} {$bannedbit}');
	find_replace_templatesets('search_results_threads', '#'.preg_quote('{$lang->search_results}').'#', '{$lang->search_results} {$count_hidescenes}');
	find_replace_templatesets('search_results_posts', '#'.preg_quote('{$lang->search_results}').'#', '{$lang->search_results} {$count_hidescenes}');
	
    // MyALERTS STUFF
    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('inplayscenes_alert_newthread'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);
		$alertTypeManager->add($alertType);

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('inplayscenes_alert_newreply'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);
		$alertTypeManager->add($alertType);

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('inplayscenes_alert_openadd'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);
		$alertTypeManager->add($alertType);
    }
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function inplayscenes_deactivate(){

    global $db, $cache;

    require MYBB_ROOT."/inc/adminfunctions_templates.php";

    // VARIABLEN ENTFERNEN
	find_replace_templatesets("editpost", "#".preg_quote('{$edit_inplayscenes}')."#i", '', 0);
	find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$inplayscenes_forumdisplay}')."#i", '', 0);
	find_replace_templatesets("header", "#".preg_quote('{$inplayscenes_postingreminder}')."#i", '', 0);
	find_replace_templatesets("header_welcomeblock_member", "#".preg_quote('{$inplayscenes_headercount}')."#i", '', 0);
	find_replace_templatesets("newthread", "#".preg_quote('{$newthread_inplayscenes}')."#i", '', 0);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'inplayscenes_postbit\']}')."#i", '', 0);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'inplayscenes_pdf\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'inplayscenes_postbit\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'inplayscenes_pdf\']}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$inplayscenes_pdf}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$inplayscenes_relevant}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$inplayscenes_add}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$inplayscenes_edit}')."#i", '', 0);
	find_replace_templatesets("showthread", "#".preg_quote('{$inplayscenes_showthread}')."#i", '', 0);
	find_replace_templatesets("member_profile", "#".preg_quote('{$inplayscenes_memberprofile}')."#i", '', 0);
	find_replace_templatesets("search_results_threads", "#".preg_quote('{$count_hidescenes}')."#i", '', 0);
	find_replace_templatesets("search_results_posts", "#".preg_quote('{$count_hidescenes}')."#i", '', 0);

    // MyALERT STUFF
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('inplayscenes_alert_newthread');
		$alertTypeManager->deleteByCode('inplayscenes_alert_newreply');
		$alertTypeManager->deleteByCode('inplayscenes_alert_openadd');
	}
}

#####################################
### THE BIG MAGIC - THE FUNCTIONS ###
#####################################

// EINSTELLUNGEN VERSTECKEN
function inplayscenes_settings_change(){
    
    global $db, $mybb, $inplayscenes_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='inplayscenes'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $inplayscenes_settings_peeker = ($mybb->get_input('gid') == $group['gid']) && ($mybb->request_method != 'post');
}
function inplayscenes_settings_peek(&$peekers){

    global $inplayscenes_settings_peeker;

    if ($inplayscenes_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_inplayscenes_hide"), $("#row_setting_inplayscenes_hidetype, #row_setting_inplayscenes_hideprofile"),/1/,true)'; 
    }
}

// ADMIN BEREICH - KONFIGURATION //

// action handler fürs acp konfigurieren
function inplayscenes_admin_rpgstuff_action_handler(&$actions) {
	$actions['inplayscenes'] = array('active' => 'inplayscenes', 'file' => 'inplayscenes');
	$actions['inplayscenes_updates'] = array('active' => 'inplayscenes_updates', 'file' => 'inplayscenes_updates');
}

// Benutzergruppen-Berechtigungen im ACP
function inplayscenes_admin_rpgstuff_permissions(&$admin_permissions) {
	global $lang;
	
    $lang->load('inplayscenes');

	$admin_permissions['inplayscenes'] = $lang->inplayscenes_permission;

	return $admin_permissions;
}

// im Menü einfügen
function inplayscenes_admin_rpgstuff_menu(&$sub_menu) {
    
	global $lang;
	
    $lang->load('inplayscenes');

	$sub_menu[] = [
		"id" => "inplayscenes",
		"title" => $lang->inplayscenes_nav,
		"link" => "index.php?module=rpgstuff-inplayscenes"
	];
}

// im Menü einfügen [Übertragen]
function inplayscenes_admin_rpgstuff_menu_updates(&$sub_menu) {

	global $mybb, $lang, $db;

    if ($db->table_exists("scenetracker") || $db->table_exists("ipt_scenes_partners") || $db->field_exists("iport", "threads")) {
        
        $lang->load('inplayscenes');
    
        $sub_menu[] = [
            "id" => "inplayscenes_updates",
            "title" => $lang->inplayscenes_updates_nav,
            "link" => "index.php?module=rpgstuff-inplayscenes_updates"
        ];
    
    }
}

// die Seiten
function inplayscenes_admin_manage() {

	global $mybb, $db, $lang, $page, $run_module, $action_file, $cache;

    if ($page->active_action != 'inplayscenes' AND $page->active_action != 'inplayscenes_updates') {
		return false;
	}

	$lang->load('inplayscenes');

    // FELDER
	if ($run_module == 'rpgstuff' && $action_file == 'inplayscenes') {

        $select_list = array(
            "text" => $lang->inplayscenes_type_text,
            "textarea" => $lang->inplayscenes_type_textarea,
            "select" => $lang->inplayscenes_type_select,
            "multiselect" => $lang->inplayscenes_type_multiselect,
            "radio" => $lang->inplayscenes_type_radio,
            "checkbox" => $lang->inplayscenes_type_checkbox,
            "date" => $lang->inplayscenes_type_date,
            "url" => $lang->inplayscenes_type_url
        );

		// Add to page navigation
		$page->add_breadcrumb_item($lang->inplayscenes_breadcrumb_main, "index.php?module=rpgstuff-inplayscenes");

		// ÜBERSICHT
		if ($mybb->get_input('action') == "" || !$mybb->get_input('action')) {

			if ($mybb->request_method == "post" && $mybb->get_input('do') == "save_sort") {

                if(!is_array($mybb->get_input('disporder', MyBB::INPUT_ARRAY))) {
                    flash_message($lang->inplayscenes_error_sort, 'error');
                    admin_redirect("index.php?module=rpgstuff-inplayscenes");
                }

                foreach($mybb->get_input('disporder', MyBB::INPUT_ARRAY) as $field_id => $order) {
        
                    $update_sort = array(
                        "disporder" => (int)$order    
                    );

                    $db->update_query("inplayscenes_fields", $update_sort, "ifid = '".(int)$field_id."'");
                }

                flash_message($lang->inplayscenes_overview_sort_flash, 'success');
                admin_redirect("index.php?module=rpgstuff-inplayscenes");
            }

			$page->output_header($lang->inplayscenes_overview_header);

			// Tabs bilden
            // Übersichtsseite Button
			$sub_tabs['overview'] = [
				"title" => $lang->inplayscenes_tabs_overview,
				"link" => "index.php?module=rpgstuff-inplayscenes",
				"description" => $lang->inplayscenes_tabs_overview_desc
			];
            // Neue Ankündigung
            $sub_tabs['add'] = [
				"title" => $lang->inplayscenes_tabs_add,
				"link" => "index.php?module=rpgstuff-inplayscenes&amp;action=add"
			];

			$page->output_nav_tabs($sub_tabs, 'overview');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

            // Übersichtsseite
			$form = new Form("index.php?module=rpgstuff-inplayscenes", "post", "", 1);
            echo $form->generate_hidden_field("do", 'save_sort');
			$form_container = new FormContainer($lang->inplayscenes_overview_container);
            $form_container->output_row_header($lang->inplayscenes_overview_container_field, array('style' => 'text-align: left;'));
            $form_container->output_row_header($lang->inplayscenes_overview_container_require, array('style' => 'text-align: center; width: 10%;'));
            $form_container->output_row_header($lang->inplayscenes_overview_container_edit, array('style' => 'text-align: center; width: 10%;'));
            $form_container->output_row_header($lang->inplayscenes_overview_container_sort, array('style' => 'text-align: center; width: 5%;'));
            $form_container->output_row_header($lang->inplayscenes_overview_container_options, array('style' => 'text-align: center; width: 10%;'));
			
            // Alle Felder
			$query_fields = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields 
            ORDER BY disporder ASC, title ASC
            ");

            while ($field = $db->fetch_array($query_fields)) {

                // Title + Beschreibung
                $form_container->output_cell('<strong><a href="index.php?module=rpgstuff-inplayscenes&amp;action=edit&amp;ifid='.$field['ifid'].'">'.htmlspecialchars_uni($field['title']).'</a></strong> <small>'.htmlspecialchars_uni($field['identification']).'</small><br><small>'.htmlspecialchars_uni($field['description']).'</small>');
                
                // Pflichtfeld?
                if ($field['required'] == 1) {
                    $form_container->output_cell($lang->inplayscenes_overview_yes, array("class" => "align_center"));
                } else {
                    $form_container->output_cell($lang->inplayscenes_overview_no, array("class" => "align_center"));
                }

                // Bearbeitbar?
                if ($field['edit'] == 1) {
                    $form_container->output_cell($lang->inplayscenes_overview_yes, array("class" => "align_center"));
                } else {
                    $form_container->output_cell($lang->inplayscenes_overview_no, array("class" => "align_center"));
                }

                // Sortierung
                $form_container->output_cell($form->generate_numeric_field("disporder[{$field['ifid']}]", $field['disporder'], array('style' => 'width: 80%; text-align: center;', 'min' => 0)), array("class" => "align_center"));

                // Optionen
				$popup = new PopupMenu("inplayscenes_".$field['ifid'], "Optionen");	
                $popup->add_item(
                    $lang->inplayscenes_overview_options_edit,
                    "index.php?module=rpgstuff-inplayscenes&amp;action=edit&amp;ifid=".$field['ifid']
                );
                $popup->add_item(
                    $lang->inplayscenes_overview_options_delete,
                    "index.php?module=rpgstuff-inplayscenes&amp;action=delete&amp;ifid=".$field['ifid']."&amp;my_post_key={$mybb->post_code}", 
					"return AdminCP.deleteConfirmation(this, '".$lang->inplayscenes_overview_options_delete_notice."')"
                );
                $form_container->output_cell($popup->fetch(), array('style' => 'text-align: center; width: 10%;'));

                $form_container->construct_row();
            }

			// keine Felder bisher
			if($db->num_rows($query_fields) == 0){
                $form_container->output_cell($lang->inplayscenes_overview_none, array("colspan" => 5, 'style' => 'text-align: center;'));
                $form_container->construct_row();
			}

            $form_container->end();
            
            $buttons = array($form->generate_submit_button($lang->inplayscenes_overview_sort_button));
            $form->output_submit_wrapper($buttons);

            $form->end();
            $page->output_footer();
			exit;
        }

		// NEUES INPLAYTRACKERFELD
		if ($mybb->get_input('action') == "add") {

			if ($mybb->request_method == "post") {

                if(empty($mybb->get_input('identification'))){
                    $errors[] = $lang->inplayscenes_error_identification;
                }
                if(empty($mybb->get_input('title'))){
                    $errors[] = $lang->inplayscenes_error_title;
                }
                if(empty($mybb->get_input('description'))) {
                    $errors[] = $lang->inplayscenes_error_description;
                }
                if(($mybb->get_input('fieldtype') == "select" AND $mybb->get_input('fieldtype') == "multiselect" AND $mybb->get_input('fieldtype') == "radio" AND $mybb->get_input('fieldtype') == "checkbox") AND empty($mybb->get_input('selectoptions'))) {
                    $errors[] = $lang->inplayscenes_error_selectoptions;
                }

                if(empty($errors)) {

                    $options = preg_replace("#(\r\n|\r|\n)#s", "\n", trim($mybb->get_input('selectoptions')));
                    if($mybb->get_input('fieldtype') != "text" AND $mybb->get_input('fieldtype') != "textarea")
                    {
                        $selectoptions = $options;
                    } else {
                        $selectoptions = "";
                    }

                    $insert_inplayscenesfield = array(
                        "identification" => $db->escape_string($mybb->get_input('identification')),
                        "title" => $db->escape_string($mybb->get_input('title')),
                        "description" => $db->escape_string($mybb->get_input('description')),
                        "type" => $db->escape_string($mybb->get_input('fieldtype')),
                        "options" => $selectoptions,
                        "required" => (int)$mybb->get_input('required'),
                        "edit" => (int)$mybb->get_input('edit'),
                        "disporder" => (int)$mybb->get_input('disporder'),
                        "allow_html" => (int)$mybb->get_input('allowhtml'),
                        "allow_mybb" => (int)$mybb->get_input('allowmycode'),
                        "allow_img" => (int)$mybb->get_input('allowimgcode'),
                        "allow_video" => (int)$mybb->get_input('allowvideocode'),
                    );
                    $ifid = $db->insert_query("inplayscenes_fields", $insert_inplayscenesfield);

                    if ($mybb->get_input('type') == "date") {
                        $fieldtype = "DATE";
                    } else  {
                        $fieldtype = "TEXT";
                    }
        
                    $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD {$db->escape_string($mybb->get_input('identification'))} {$fieldtype}");
        
                    // Log admin action
                    log_admin_action($ifid, $mybb->input['title']);
        
                    flash_message($lang->inplayscenes_add_flash, 'success');
                    admin_redirect("index.php?module=rpgstuff-inplayscenes");
                }
            }

            $page->add_breadcrumb_item($lang->inplayscenes_breadcrumb_add);
			$page->output_header($lang->inplayscenes_add_header);

			// Tabs bilden
            // Übersichtsseite Button
			$sub_tabs['overview'] = [
				"title" => $lang->inplayscenes_tabs_overview,
				"link" => "index.php?module=rpgstuff-inplayscenes"
			];
            // Neue Ankündigung
            $sub_tabs['add'] = [
				"title" => $lang->inplayscenes_tabs_add,
				"link" => "index.php?module=rpgstuff-inplayscenes&amp;action=add",
				"description" => $lang->inplayscenes_tabs_add_desc
			];

			$page->output_nav_tabs($sub_tabs, 'add');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}
    
            // Build the form
            $form = new Form("index.php?module=rpgstuff-inplayscenes&amp;action=add", "post", "", 1);

            $form_container = new FormContainer($lang->inplayscenes_add_container);
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
    
            // Identifikator
            $form_container->output_row(
				$lang->inplayscenes_container_identification,
				$lang->inplayscenes_container_identification_desc,
				$form->generate_text_box('identification', htmlspecialchars_uni($mybb->get_input('identification')), array('id' => 'identification')), 'identification'
			);
    
            // Titel
            $form_container->output_row(
				$lang->inplayscenes_container_title,
                '',
				$form->generate_text_box('title', htmlspecialchars_uni($mybb->get_input('title')), array('id' => 'title')), 'title'
			);

            // Kurzbeschreibung
            $form_container->output_row(
				$lang->inplayscenes_container_description,
                '',
				$form->generate_text_box('description', htmlspecialchars_uni($mybb->get_input('description')), array('id' => 'description')), 'description'
			);

            // Feldtyp
            $form_container->output_row(
				$lang->inplayscenes_container_type, 
				$lang->inplayscenes_container_type_desc,
                $form->generate_select_box('fieldtype', $select_list, $mybb->get_input('fieldtype'), array('id' => 'fieldtype')), 'fieldtype'
            );    
    
            // Auswahlmöglichkeiten
            $form_container->output_row(
				$lang->inplayscenes_container_selectoptions, 
				$lang->inplayscenes_container_selectoptions_desc,
                $form->generate_text_area('selectoptions', $mybb->get_input('selectoptions')), 
                'selectoptions',
                array('id' => 'row_selectoptions')
			);

            // Sortierung
            $form_container->output_row(
				$lang->inplayscenes_container_disporder, 
				$lang->inplayscenes_container_disporder_desc,
                $form->generate_numeric_field('disporder', $mybb->get_input('disporder'), array('id' => 'disporder', 'min' => 0)), 'disporder'
			);

            // Pflichtfeld?
            $form_container->output_row(
                $lang->inplayscenes_container_require, 
                $lang->inplayscenes_container_require_desc, 
                $form->generate_yes_no_radio('required', $mybb->get_input('required'))
            );

            // Bearbeitbar?
            $form_container->output_row(
                $lang->inplayscenes_container_edit, 
                $lang->inplayscenes_container_edit_desc, 
                $form->generate_yes_no_radio('edit', $mybb->get_input('edit'))
            );

            // Parser Optionen
            $parser_options = array(
                $form->generate_check_box('allowhtml', 1, $lang->inplayscenes_container_parse_allowhtml, array('checked' => $mybb->get_input('allowhtml'), 'id' => 'allowhtml')),
                $form->generate_check_box('allowmycode', 1, $lang->inplayscenes_container_parse_allowmycode, array('checked' => $mybb->get_input('allowmycode'), 'id' => 'allowmycode')),
                $form->generate_check_box('allowimgcode', 1, $lang->inplayscenes_container_parse_allowimgcode, array('checked' => $mybb->get_input('allowimgcode'), 'id' => 'allowimgcode')),
                $form->generate_check_box('allowvideocode', 1, $lang->inplayscenes_container_parse_allowvideocode, array('checked' => $mybb->get_input('allowvideocode'), 'id' => 'allowvideocode'))
            );
            $form_container->output_row($lang->inplayscenes_container_parseroptions, '', implode('<br />', $parser_options), '', array(), array('id' => 'row_parser_options'));           
    
            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->inplayscenes_add_button);
            $form->output_submit_wrapper($buttons);

            $form->end();

            echo '<script type="text/javascript" src="./jscripts/peeker.js?ver=1821"></script>
            <script type="text/javascript">
                $(function() {
                        new Peeker($("#fieldtype"), $("#row_parser_options"), /text|textarea/, false);
                        new Peeker($("#fieldtype"), $("#row_selectoptions"), /select|multiselect|radio|checkbox/, false);
                        // Add a star to the extra row since the "extra" is required if the box is shown
                        add_star("row_selectoptions");
                });
            </script>';

            $page->output_footer();
            exit;
        }

		// INPLAYTRACKERFELD BEARBEITEN
		if ($mybb->get_input('action') == "edit") {

            // Get the data
            $ifid = $mybb->get_input('ifid', MyBB::INPUT_INT);
            $inplayscenesfield_query = $db->simple_select("inplayscenes_fields", "*", "ifid = '".$ifid."'");
            $field = $db->fetch_array($inplayscenesfield_query);

            if ($mybb->request_method == "post") {

                if(empty($mybb->get_input('title'))){
                    $errors[] = $lang->inplayscenes_error_title;
                }
                if(empty($mybb->get_input('description'))) {
                    $errors[] = $lang->inplayscenes_error_description;
                }
                if(($mybb->get_input('fieldtype') == "select" AND $mybb->get_input('fieldtype') == "multiselect" AND $mybb->get_input('fieldtype') == "radio" AND $mybb->get_input('fieldtype') == "checkbox") AND empty($mybb->get_input('selectoptions'))) {
                    $errors[] = $lang->inplayscenes_error_selectoptions;
                }

                if(empty($errors)) {

                    $options = preg_replace("#(\r\n|\r|\n)#s", "\n", trim($mybb->get_input('selectoptions')));
                    if($mybb->get_input('fieldtype') != "text" AND $mybb->get_input('fieldtype') != "textarea")
                    {
                        $selectoptions = $options;
                    } else {
                        $selectoptions = "";
                    }

                    $update_inplayscenesfield = array(
                        "title" => $db->escape_string($mybb->get_input('title')),
                        "description" => $db->escape_string($mybb->get_input('description')),
                        "type" => $db->escape_string($mybb->get_input('fieldtype')),
                        "options" => $selectoptions,
                        "required" => (int)$mybb->get_input('required'),
                        "edit" => (int)$mybb->get_input('edit'),
                        "disporder" => (int)$mybb->get_input('disporder'),
                        "allow_html" => (int)$mybb->get_input('allowhtml'),
                        "allow_mybb" => (int)$mybb->get_input('allowmycode'),
                        "allow_img" => (int)$mybb->get_input('allowimgcode'),
                        "allow_video" => (int)$mybb->get_input('allowvideocode'),
                    );
                    $db->update_query("inplayscenes_fields", $update_inplayscenesfield, "ifid='".$mybb->get_input('ifid')."'");

                    // Log admin action
                    log_admin_action($mybb->get_input('ifid'), $mybb->get_input('title'));
        
                    flash_message($lang->inplayscenes_edit_flash, 'success');
                    admin_redirect("index.php?module=rpgstuff-inplayscenes");
                }

            }

            $page->add_breadcrumb_item($lang->inplayscenes_breadcrumb_edit);
            $page->output_header($lang->inplayscenes_edit_header);

			// Tabs bilden
            // Neue Ankündigung
            $sub_tabs['edit'] = [
				"title" => $lang->inplayscenes_tabs_edit,
				"link" => "index.php?module=rpgstuff-inplayscenes&amp;action=edit&amp;ifid=".$ifid,
				"description" => $lang->inplayscenes_tabs_edit_desc
			];
			$page->output_nav_tabs($sub_tabs, 'edit');

            // Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
				$title = $mybb->get_input('title');
				$description = $mybb->get_input('description');
				$fieldtype = $mybb->get_input('fieldtype');
				$selectoptions = $mybb->get_input('selectoptions');
				$required = $mybb->get_input('required');
				$edit = $mybb->get_input('edit');
				$disporder = $mybb->get_input('disporder');
				$allow_html = $mybb->get_input('allowhtml');
				$allow_mybb = $mybb->get_input('allowmycode');
				$allow_img = $mybb->get_input('allowimgcode');
				$allow_video = $mybb->get_input('allowvideocode');
			} else {
				$title = $field['title'];
				$description = $field['description'];
				$fieldtype = $field['type'];
				$selectoptions = $field['options'];
				$required = $field['required'];
				$edit = $field['edit'];
				$disporder = $field['disporder'];
				$allow_html = $field['allow_html'];
				$allow_mybb = $field['allow_mybb'];
				$allow_img = $field['allow_img'];
				$allow_video = $field['allow_video'];
            }

            // Build the form
            $form = new Form("index.php?module=rpgstuff-inplayscenes&amp;action=edit", "post", "", 1);

            $form_container = new FormContainer($lang->sprintf($lang->inplayscenes_edit_container, $field['title']));
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
            echo $form->generate_hidden_field("ifid", $ifid);
    
            // Titel
            $form_container->output_row(
				$lang->inplayscenes_container_title,
                '',
				$form->generate_text_box('title', htmlspecialchars_uni($title), array('id' => 'title')), 'title'
			);

            // Kurzbeschreibung
            $form_container->output_row(
				$lang->inplayscenes_container_description,
                '',
				$form->generate_text_box('description', htmlspecialchars_uni($description), array('id' => 'description')), 'description'
			);

            // Feldtyp
            $form_container->output_row(
				$lang->inplayscenes_container_type, 
				$lang->inplayscenes_container_type_desc,
                $form->generate_select_box('fieldtype', $select_list, $fieldtype, array('id' => 'fieldtype')), 'fieldtype'
            );    
    
            // Auswahlmöglichkeiten
            $form_container->output_row(
				$lang->inplayscenes_container_selectoptions, 
				$lang->inplayscenes_container_selectoptions_desc,
                $form->generate_text_area('selectoptions', $selectoptions), 
                'selectoptions',
                array('id' => 'row_selectoptions')
			);

            // Sortierung
            $form_container->output_row(
				$lang->inplayscenes_container_disporder, 
				$lang->inplayscenes_container_disporder_desc,
                $form->generate_numeric_field('disporder', $disporder, array('id' => 'disporder', 'min' => 0)), 'disporder'
			);

            // Pflichtfeld?
            $form_container->output_row(
                $lang->inplayscenes_container_require, 
                $lang->inplayscenes_container_require_desc, 
                $form->generate_yes_no_radio('required', $required)
            );

            // Bearbeitbar?
            $form_container->output_row(
                $lang->inplayscenes_container_edit, 
                $lang->inplayscenes_container_edit_desc, 
                $form->generate_yes_no_radio('edit', $edit)
            );

            // Parser Optionen
            $parser_options = array(
                $form->generate_check_box('allowhtml', 1, $lang->inplayscenes_container_parse_allowhtml, array('checked' => $allow_html, 'id' => 'allowhtml')),
                $form->generate_check_box('allowmycode', 1, $lang->inplayscenes_container_parse_allowmycode, array('checked' => $allow_mybb, 'id' => 'allowmycode')),
                $form->generate_check_box('allowimgcode', 1, $lang->inplayscenes_container_parse_allowimgcode, array('checked' => $allow_img, 'id' => 'allowimgcode')),
                $form->generate_check_box('allowvideocode', 1, $lang->inplayscenes_container_parse_allowvideocode, array('checked' => $allow_video, 'id' => 'allowvideocode'))
            );
            $form_container->output_row($lang->inplayscenes_container_parseroptions, '', implode('<br />', $parser_options), '', array(), array('id' => 'row_parser_options'));           
    
            $form_container->end();
            $buttons[] = $form->generate_submit_button($lang->inplayscenes_edit_button);
            $form->output_submit_wrapper($buttons);
            $form->end();

            echo '<script type="text/javascript" src="./jscripts/peeker.js?ver=1821"></script>
            <script type="text/javascript">
                $(function() {
                        new Peeker($("#fieldtype"), $("#row_parser_options"), /text|textarea/, false);
                        new Peeker($("#fieldtype"), $("#row_selectoptions"), /select|multiselect|radio|checkbox/, false);
                        // Add a star to the extra row since the "extra" is required if the box is shown
                        add_star("row_selectoptions");
                });
            </script>';

            $page->output_footer();
            exit;
        }

        // INPLAYTRACKERFELD LÖSCHEN
		if ($mybb->get_input('action') == "delete") {
            
            // Get the data
            $ifid = $mybb->get_input('ifid', MyBB::INPUT_INT);

			// Error Handling
			if (empty($ifid)) {
				flash_message($lang->inplayscenes_error_invalid, 'error');
				admin_redirect("index.php?module=rpgstuff-inplayscenes");
			}

			// Cancel button pressed?
			if (isset($mybb->input['no']) && $mybb->input['no']) {
				admin_redirect("index.php?module=rpgstuff-inplayscenes");
			}

			if ($mybb->request_method == "post") {

                // Spalte löschen bei den Szenen löschen
                $identification = $db->fetch_field($db->simple_select("inplayscenes_fields", "identification", "ifid= '".$ifid."'"), "identification");
                if ($db->field_exists($identification, "inplayscenes")) {
                    $db->drop_column("inplayscenes", $identification);
                }

                // Feld in der Feld DB löschen
                $db->delete_query('inplayscenes_fields', "ifid = '".$ifid."'");

				flash_message($lang->inplayscenes_delete_flash, 'success');
				admin_redirect("index.php?module=rpgstuff-inplayscenes");
			} else {
				$page->output_confirm_action(
					"index.php?module=rpgstuff-inplayscenes&amp;action=delete&amp;ifid=".$ifid,
					$lang->teamheader_manage_character_delete_notice
				);
			}
			exit;
        }
    }

    // ÜBERTRAGEN
    if ($run_module == 'rpgstuff' && $action_file == 'inplayscenes_updates') {

        $trackersystem_list = array(
            "" => $lang->inplayscenes_updates_trackersystem,
            "jule2" => $lang->inplayscenes_updates_trackersystem_jule2,
            "jule3" => $lang->inplayscenes_updates_trackersystem_jule3,
            "katja" => $lang->inplayscenes_updates_trackersystem_katja
        );

        // Add to page navigation
        $page->add_breadcrumb_item($lang->inplayscenes_updates_page, "index.php?module=rpgstuff-inplayscenes_updates");
    
        if ($mybb->get_input('action') == "" || !$mybb->get_input('action')) {

            $page->output_header($lang->inplayscenes_updates_page);
    
            if ($mybb->request_method == 'post') {

                $selected_tracker = $mybb->get_input('trackersystem');

                if (empty($selected_tracker)) {
                    $errors[] = $lang->inplayscenes_updates_error;
                }

                if(empty($errors)) {

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
        
                            $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD iport TEXT NOT NULL;");
                            $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD ipdaytime TEXT NOT NULL;");
        
                            // Prüfe die Einstellung inplaytracker_timeformat
                            $timeformat = $mybb->settings['inplaytracker_timeformat'];
        
                            // Wenn timeformat auf 1 steht, hole die Monatsnamen
                            if ($timeformat == 1) {
                                $months_array = explode(',', str_replace(", ", ",", $mybb->settings['inplaytracker_months'])); // in Array umwandeln
                            }
        
                            $threads_result = $db->query("SELECT * FROM ".TABLE_PREFIX."threads
                            WHERE partners IS NOT NULL 
                            AND partners != ''
                            ");

                            $all_successful = true;
                            while ($thread = $db->fetch_array($threads_result)) {
        
                                $tid = $thread['tid'];
                                $partners = $db->escape_string($thread['partners']);
                                $postorder = $db->escape_string($thread['postorder']);
                                $iport = $db->escape_string($thread['iport']);
                                $ipdaytime = $db->escape_string($thread['ipdaytime']);
                                $trigger_warning = '';
                                $relevant = 1;
 
                                // Falls die Spalte hidescene_readable existiert -> den Wert
                                if (array_key_exists('hidescene_readable', $thread)) {
                                    // Sichtbar
                                    if ($thread['hidescene_readable'] == 1) {
                                        switch ($thread['openscene']) {
                                            case -1:
                                                $scenetype = 0;
                                                break;
                                            case 0:
                                                $scenetype = 1;
                                                break;
                                            case 1:
                                                $scenetype = 2;
                                                break;
                                            default:
                                                $scenetype = 0;
                                        }
                                        $hidetype = 0;
                                    } 
                                    // versteckt
                                    else if ($thread['hidescene_readable'] == 0) { 
                                        $scenetype = 3;
                                        switch ($thread['hidescene_type']) {
                                            case 0:
                                                $hidetype = 2;
                                                break;
                                            case 1:
                                                $hidetype = 1;
                                                break;
                                            default:
                                            $hidetype = 1;
                                        }
                                    }
                                } else {
                                    switch ($thread['openscene']) {
                                        case -1:
                                            $scenetype = 0;
                                            break;
                                        case 0:
                                            $scenetype = 1;
                                            break;
                                        case 1:
                                            $scenetype = 2;
                                            break;
                                        default:
                                            $scenetype = 0;
                                    }
                                    $hidetype = 0;
                                }
        
                                $ipdate = $thread['ipdate'];
                                if ($timeformat == 1) { // eigen Zeitrechnung

                                    if (preg_match('/^(\d+)\s+([A-Za-z\s]+)\s+(\d{4})$/', $ipdate, $matches)) {
                                        $day = $matches[1];   
                                        $monthname = trim($matches[2]); 
                                        $year = $matches[3]; 
                                    }
                                    $month = array_search($monthname, $months_array) + 1; 
        
                                    if ($month === false) {
                                        $month = 1;                          
                                    }
        
                                    $date = $year."-".$month."-".$day;
                                } elseif (is_numeric($ipdate)) { // Timestamp
                                    $date = date('Y-m-d', $ipdate);
                                } else { // Datum
                                    $date = $ipdate;                        
                                }
        
                                $partners_array = explode(',', $partners);
                                $partners_usernames = array();            

                                foreach ($partners_array as $partner_uid) {
        
                                    $partner_uid = trim($partner_uid);

                                    $user_result = $db->query("SELECT username FROM ".TABLE_PREFIX."users 
                                    WHERE uid = '".$partner_uid."'
                                    ");

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
                                    'scenetype' => (int)$scenetype,                                
                                    'postorder' => (int)$postorder,
                                    'hidetype' => (int)$hidetype,
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
                                // Log admin action           
                                log_admin_action($lang->inplayscenes_updates_page);
        
                                flash_message($lang->inplayscenes_updates_update_flash, 'success');
                                admin_redirect("index.php?module=rpgstuff-inplayscenes_updates");
                            } else {
                                flash_message($lang->inplayscenes_updates_error_flash, 'error');
                            }
                        } else {
                            flash_message($lang->inplayscenes_updates_error_flash, 'error');
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
                            'type' => $db->escape_string('textarea'),
                            'options' => '',
                            'required' => (int)0,
                            'edit' => (int)1,
                            'disporder' => (int)2,
                            'allow_html' => (int)0,
                            'allow_mybb' => (int)0,
                            'allow_img' => (int)0,
                            'allow_video' => (int)0,
                        );
        
                        if ($db->insert_query("inplayscenes_fields", $location_update) && $db->insert_query("inplayscenes_fields", $shortdesc_update)) {
                    
                            $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD location TEXT NOT NULL;");
                            $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD shortdesc TEXT NOT NULL;");

                            // Überprüfung, ob Annes versteckte Szenen Erweiterung vorhanden ist
                            $privatetype_column_exists = false;
                            $columns_result_a = $db->query("SHOW COLUMNS FROM ".TABLE_PREFIX."threads LIKE 'privatetype'");
                            if ($db->num_rows($columns_result_a) > 0) {
                                $privatetype_column_exists = true;
                            }
        
                            // Überprüfung, ob Katjas hidescenes Plugin vorhanden ist
                            $hidescene_column_exists = false;
                            $columns_result_k = $db->query("SHOW COLUMNS FROM ".TABLE_PREFIX."threads LIKE 'hidescene_readable'");
                            if ($db->num_rows($columns_result_k) > 0) {
                                $hidescene_column_exists = true;
                            }
        
                            $scenes_result = $db->query("SELECT * FROM ".TABLE_PREFIX."ipt_scenes s WHERE tid IN (SELECT tid FROM ".TABLE_PREFIX."threads t WHERE t.tid = s.tid)");
        
                            $all_successful = true;
                            while ($scene = $db->fetch_array($scenes_result)) {
                                $tid = $scene['tid'];
                                $location = $db->escape_string($scene['location']);
                                $shortdesc = $db->escape_string($scene['shortdesc']);
                                $trigger_warning = '';  
                                $postorder = 1; 
                                $relevant = 1; 
              
                                if (!empty($scene['date'])) {
                                    $date = date('Y-m-d', $scene['date']);
                                } else {
                                    $date = "1970-01-01";
                                }
              
                                // Annes versteckte Szenen
                                if ($privatetype_column_exists) {
                                    $thread_result = $db->query("SELECT privatetype FROM ".TABLE_PREFIX."threads WHERE tid = '".$tid."'");
                                    $thread = $db->fetch_array($thread_result);
                                    switch ($thread['privatetype']) {
                                        case -1:
                                            $scenetype = 0;
                                            $hidetype = 0;
                                            break;
                                        case 0:
                                            $scenetype = 1;
                                            $hidetype = 0;
                                            break;
                                        case 1:
                                            $scenetype = 2;
                                            $hidetype = 0;
                                            break;
                                        case 2:
                                            $scenetype = 3;
                                            $hidetype = 1;
                                            break;
                                        default:
                                            $scenetype = 0;
                                            $hidetype = 0;
                                    }
                                } 
                                // Katjas versteckte Szenen
                                else if ($hidescene_column_exists) {
                                    $thread_result = $db->query("SELECT hidescene_readable, hidescene_type FROM ".TABLE_PREFIX."threads WHERE tid = '".$tid."'");
                                    $thread = $db->fetch_array($thread_result);
                                    // Sichtbar
                                    if ($thread['hidescene_readable'] == 1) {
                                        switch ($thread['openscene']) {
                                            case -1:
                                                $scenetype = 0;
                                                break;
                                            case 0:
                                                $scenetype = 1;
                                                break;
                                            case 1:
                                                $scenetype = 2;
                                                break;
                                            default:
                                                $scenetype = 0;
                                        }
                                        $hidetype = 0;
                                    } 
                                    // versteckt
                                    else if ($thread['hidescene_readable'] == 0) { 
                                        $scenetype = 3;
                                        switch ($thread['hidescene_type']) {
                                            case 0:
                                                $hidetype = 2;
                                                break;
                                            case 1:
                                                $hidetype = 1;
                                                break;
                                            default:
                                            $hidetype = 1;
                                        }
                                    }
                                } else {
                                    if (array_key_exists('openscene', $scene)) {
                                        if ($scene['openscene'] == 1) {
                                            $scenetype = 2;
                                        } else {
                                            $scenetype = 0;
                                        }
                                    } else {
                                        $scenetype = 0;
                                    }
                                    $hidetype = 0;
                                }
              
                                $partners_result = $db->query("SELECT * FROM ".TABLE_PREFIX."ipt_scenes_partners 
                                WHERE tid = '".$tid."' 
                                ORDER BY spid ASC
                                ");
              
                                $partners_array = array();
                                $partners_usernames = array();
              
                                while ($partner = $db->fetch_array($partners_result)) {
                                    $uid = (int)$partner['uid'];
                                    $partners_array[] = $uid;
              
                                    $user_result = $db->query("SELECT username FROM ".TABLE_PREFIX."users 
                                    WHERE uid = '".$uid."'
                                    ");
                                    if ($db->num_rows($user_result) > 0) {
                                        $user = $db->fetch_array($user_result);
                                        $partners_usernames[] = $db->escape_string($user['username']);
                                    } else {
                                        $partners_usernames[] = 'Gast';
                                    }
                                }
                                $partners = implode(',', $partners_array);
                                $partners_username = implode(',', $partners_usernames);
        
                                $new_scene = array(
                                    'tid' => (int)$tid,
                                    'partners' => $partners,
                                    'partners_username' => $partners_username,
                                    'date' => $date,
                                    'trigger_warning' => $trigger_warning,
                                    'scenetype' => (int)$scenetype,
                                    'hidetype' => (int)$hidetype,
                                    'postorder' => (int)$postorder,
                                    'shortdesc' => $shortdesc,
                                    'location' => $location,
                                    'relevant' => $relevant,
                                );
              
                                if (!$db->insert_query("inplayscenes", $new_scene)) {
                                    $all_successful = false;  
                                    break;  
                                }
                            }
        
                            if ($all_successful) {
                                // Log admin action           
                                log_admin_action($lang->inplayscenes_updates_page);
        
                                flash_message($lang->inplayscenes_updates_update_flash, 'success');
                                admin_redirect("index.php?module=rpgstuff-inplayscenes_updates");
                            } else {
                                flash_message($lang->inplayscenes_updates_error_flash, 'error');
                            }
                        } else {
                            flash_message($lang->inplayscenes_updates_error_flash, 'error');
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
        
                            $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD time TEXT NOT NULL;");
                            $db->write_query("ALTER TABLE ".TABLE_PREFIX."inplayscenes ADD place TEXT NOT NULL;");
        
                            $scenes_result = $db->query("SELECT * FROM ".TABLE_PREFIX."threads 
                            WHERE scenetracker_user IS NOT NULL 
                            AND scenetracker_user != ''
                            ");
        
                            $all_successful = true; 
                            while ($scene = $db->fetch_array($scenes_result)) {
            
                                $tid = (int)$scene['tid'];
                                $place = $db->escape_string($scene['scenetracker_place']);
                                $trigger_warning = $db->escape_string($scene['scenetracker_trigger']);
                                $partners_username = $db->escape_string($scene['scenetracker_user']);
                                $postorder = 1; 
                                $relevant = 1;

                                $date = date('Y-m-d', strtotime($scene['scenetracker_date']));
        
                                if (!empty($scene['scenetracker_time_text'])) {
                                    $time = $db->escape_string($scene['scenetracker_time_text']);
                                } else {
                                    $time = date('H:i', strtotime($scene['scenetracker_date']));    
                                }
 
                                // Falls die Spalte hidescene_readable existiert -> den Wert
                                if (array_key_exists('hidescene_readable', $scene)) {
                                    // Sichtbar
                                    if ($scene['hidescene_readable'] == 1) {
                                        $scenetype = 0;
                                        $hidetype = 0;
                                    } 
                                    // versteckt
                                    else if ($scene['hidescene_readable'] == 0) { 
                                        $scenetype = 3;
                                        switch ($scene['hidescene_type']) {
                                            case 0:
                                                $hidetype = 2;
                                                break;
                                            case 1:
                                                $hidetype = 1;
                                                break;
                                            default:
                                            $hidetype = 1;
                                        }
                                    }
                                } else {
                                    $scenetype = 0;
                                    $hidetype = 0;
                                }
        
                                $partners_result = $db->query("SELECT * FROM ".TABLE_PREFIX."scenetracker
                                WHERE tid = '$tid' 
                                ORDER BY id ASC
                                ");
        
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
                                    'scenetype' => (int)$scenetype,
                                    'hidetype' => (int)$hidetype,
                                    'postorder' => (int)$postorder,
                                    'relevant' => (int)$relevant,
                                    'place' => $place,
                                    'time' => $time,
                                );
              
                                if (!$db->insert_query("inplayscenes", $new_scene)) {
                                    $all_successful = false; 
                                    break;  
                                }    
                            }
        
                            if ($all_successful) {
                                // Log admin action           
                                log_admin_action($lang->inplayscenes_updates_page);
        
                                flash_message($lang->inplayscenes_updates_update_flash, 'success');
                                admin_redirect("index.php?module=rpgstuff-inplayscenes_updates");
                            } else {
                                flash_message($lang->inplayscenes_updates_error_flash, 'error');
                            }   
                        } else {
                            flash_message($lang->inplayscenes_updates_error_flash, 'error');
                        }
                    }
                }
            }

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}
    
            $form = new Form("index.php?module=rpgstuff-inplayscenes_updates", "post", "", 1);
            $form_container = new FormContainer($lang->inplayscenes_updates_page);
            echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
  
            $inplayscenes = $db->fetch_field($db->query("SELECT tid FROM ".TABLE_PREFIX."inplayscenes"), "tid");

            if ($inplayscenes == 0) {
                $form_container->output_row(
                    $lang->inplayscenes_updates_select, 
                    $lang->inplayscenes_updates_select_desc,
                    $form->generate_select_box('trackersystem', $trackersystem_list, $mybb->get_input('trackersystem'), array('id' => 'trackersystem')), 'trackersystem'
                );
            
                $form_container->end();
                $buttons[] = $form->generate_submit_button($lang->inplayscenes_updates_button);
                $form->output_submit_wrapper($buttons);
            } else {
                $form_container->output_cell($lang->inplayscenes_updates_none, array("colspan" => 5, 'style' => 'text-align: center;'));
                $form_container->construct_row();

                $form_container->end();
            }

            $form->end();
            $page->output_footer();
            exit;
        }
    }
}

// Stylesheet zum Master Style hinzufügen
function inplayscenes_admin_update_stylesheet(&$table) {

    global $db, $mybb, $lang;
	
    $lang->load('rpgstuff_stylesheet_updates');

    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    // HINZUFÜGEN
    if ($mybb->input['action'] == 'add_master' AND $mybb->get_input('plugin') == "inplayscenes") {

        $css = inplayscenes_stylesheet();
        
        $sid = $db->insert_query("themestylesheets", $css);
        $db->update_query("themestylesheets", array("cachefile" => "inplayscenes.css"), "sid = '".$sid."'", 1);
    
        $tids = $db->simple_select("themes", "tid");
        while($theme = $db->fetch_array($tids)) {
            update_theme_stylesheet_list($theme['tid']);
        } 

        flash_message($lang->stylesheets_flash, "success");
        admin_redirect("index.php?module=rpgstuff-stylesheet_updates");
    }

    // Zelle mit dem Namen des Themes
    $table->construct_cell("<b>".htmlspecialchars_uni("Inplayszenen-Manager")."</b>", array('width' => '70%'));

    // Ob im Master Style vorhanden
    $master_check = $db->fetch_field($db->query("SELECT tid FROM ".TABLE_PREFIX."themestylesheets 
    WHERE name = 'inplayscenes.css' 
    AND tid = 1
    "), "tid");
    
    if (!empty($master_check)) {
        $masterstyle = true;
    } else {
        $masterstyle = false;
    }

    if (!empty($masterstyle)) {
        $table->construct_cell($lang->stylesheets_masterstyle, array('class' => 'align_center'));
    } else {
        $table->construct_cell("<a href=\"index.php?module=rpgstuff-stylesheet_updates&action=add_master&plugin=inplayscenes\">".$lang->stylesheets_add."</a>", array('class' => 'align_center'));
    }
    
    $table->construct_row();
}

// Plugin Update
function inplayscenes_admin_update_plugin(&$table) {

    global $db, $mybb, $lang;
	
    $lang->load('rpgstuff_plugin_updates');

    // UPDATE
    if ($mybb->input['action'] == 'add_update' AND $mybb->get_input('plugin') == "inplayscenes") {

        // Einstellungen überprüfen => Type = update
        inplayscenes_settings('update');
        rebuild_settings();

        // Templates 
        inplayscenes_templates('update');

        // Stylesheet
        $update_data = inplayscenes_stylesheet_update();
        $update_stylesheet = $update_data['stylesheet'];
        $update_string = $update_data['update_string'];
        if (!empty($update_string)) {

            // Ob im Master Style die Überprüfung vorhanden ist
            $masterstylesheet = $db->fetch_field($db->query("SELECT stylesheet FROM ".TABLE_PREFIX."themestylesheets WHERE tid = 1 AND name = 'inplayscenes.css'"), "stylesheet");
            $masterstylesheet = (string)($masterstylesheet ?? '');
            $update_string = (string)($update_string ?? '');
            $pos = strpos($masterstylesheet, $update_string);
            if ($pos === false) { // nicht vorhanden 
            
                $theme_query = $db->simple_select('themes', 'tid, name');
                while ($theme = $db->fetch_array($theme_query)) {
        
                    $stylesheet_query = $db->simple_select("themestylesheets", "*", "name='".$db->escape_string('inplayscenes.css')."' AND tid = ".$theme['tid']);
                    $stylesheet = $db->fetch_array($stylesheet_query);
        
                    if ($stylesheet) {

                        require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
        
                        $sid = $stylesheet['sid'];
            
                        $updated_stylesheet = array(
                            "cachefile" => $db->escape_string($stylesheet['name']),
                            "stylesheet" => $db->escape_string($stylesheet['stylesheet']."\n\n".$update_stylesheet),
                            "lastmodified" => TIME_NOW
                        );
            
                        $db->update_query("themestylesheets", $updated_stylesheet, "sid='".$sid."'");
            
                        if(!cache_stylesheet($theme['tid'], $stylesheet['name'], $updated_stylesheet['stylesheet'])) {
                            $db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet=".$sid), "sid='".$sid."'", 1);
                        }
            
                        update_theme_stylesheet_list($theme['tid']);
                    }
                }
            } 
        }

        // Datenbanktabellen & Felder
        inplayscenes_database();

        flash_message($lang->plugins_flash, "success");
        admin_redirect("index.php?module=rpgstuff-plugin_updates");
    }

    // Zelle mit dem Namen des Themes
    $table->construct_cell("<b>".htmlspecialchars_uni("Inplayszenen-Manager")."</b>", array('width' => '70%'));

    // Überprüfen, ob Update erledigt
    $update_check = inplayscenes_is_updated();

    if (!empty($update_check)) {
        $table->construct_cell($lang->plugins_actual, array('class' => 'align_center'));
    } else {
        $table->construct_cell("<a href=\"index.php?module=rpgstuff-plugin_updates&action=add_update&plugin=inplayscenes\">".$lang->plugins_update."</a>", array('class' => 'align_center'));
    }
    
    $table->construct_row();
}

// NEUES THEMA ERÖFFNEN - ANZEIGE
function inplayscenes_newthread_start() {

    global $templates, $mybb, $lang, $fid, $post_errors, $db, $newthread_inplayscenes, $own_inplayscenesfields, $code;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];
    $hideprofile_setting = $mybb->settings['inplayscenes_hideprofile'];

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$sideplays_forum;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {

        $characters = $mybb->get_input('characters');
        $postorder_value = $mybb->get_input('postorder');
        $date = $mybb->get_input('date');

        $triggerwarning = $mybb->get_input('trigger');
        $scenetype_value = $mybb->get_input('scenetype');
        $hidetype_value = $mybb->get_input('hidetype');
        $hideprofile_value = $mybb->get_input('hideprofile');

        $own_inplayscenesfields = inplayscenes_generate_fields(null, true);
        
    } else {

        // Entwurf bearbeiten
        if ($tid > 0) {

            // Infos aus der DB ziehen
            $draft = $db->fetch_array($db->simple_select('inplayscenes', '*', 'tid = '.$tid));

            $partners_username = explode(",", $draft['partners_username']);
            $usernames = [];
            foreach ($partners_username as $username) {
                if ($username != $mybb->user['username']) {
                    $usernames[] = $username;
                }
            }

            $characters = implode(",", $usernames);
            $postorder_value = $draft['postorder'];
            list($year, $month, $day) = explode('-', $draft['date']);
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day); 

            $triggerwarning = $draft['trigger_warning'];
            $scenetype_value = $draft['scenetype'];
            $hidetype_value = $draft['hidetype'];
            $hideprofile_value = $draft['hideprofile'];

            $own_inplayscenesfields = inplayscenes_generate_fields($draft);

        } else {
            $characters = "";
            $postorder_value = 1;
            $date = "";

            $triggerwarning = "";
            $scenetype_value = 0;

            if ($hidetype_setting == 1) { 
                $hidetype_value = 2;
            } else {
                $hidetype_value = 1;
            }

            if ($hideprofile_setting == 1) { 
                $hideprofile_value = 2;
            } else {
                $hideprofile_value = 1;
            }

            $own_inplayscenesfields = inplayscenes_generate_fields();
        }
    }

    $postorder_select = inplayscenes_generate_postorder_select($postorder_value);

    if ($scenetype_setting == 1 || $hide_setting == 1) {
        $scenetype_select = inplayscenes_generate_scenetype_select($scenetype_value);
    } else {
        $scenetype_select = "";
    }

    if ($hide_setting == 1) {
        if ($hidetype_setting == 2) {
            $hidetype_select = inplayscenes_generate_hidetype_select($hidetype_value);
        } else {
            if ($hidetype_setting == 0) {
                $hidetype_select = $lang->inplayscenes_fields_hidetype_info;
            } else {
                $hidetype_select = $lang->inplayscenes_fields_hidetype_all;
            }
        }
        if ($hideprofile_setting == 2) {
            $hideprofile_select = inplayscenes_generate_hideprofile_select($hideprofile_value);
        } else {
            if ($hideprofile_setting == 0) {
                $hideprofile_select = $lang->inplayscenes_fields_hideprofile_info;
            } else {
                $hideprofile_select = $lang->inplayscenes_fields_hideprofile_all;
            }
        }
        if ($hidetype_setting == 2 && $hideprofile_setting == 2) {
            $inplayscenes_fields_hide_desc = $lang->inplayscenes_fields_hide_desc;
        } else {
            $inplayscenes_fields_hide_desc = $lang->inplayscenes_fields_hide_team;
        }
    } else {
        $hidetype_select = "";
        $hideprofile_select = "";
    }

    if ($trigger_setting == 1) { 
        $title = $lang->inplayscenes_fields_trigger;
        $description = $lang->inplayscenes_fields_trigger_desc;
        $code = inplayscenes_generate_input_field('trigger', 'textarea', $triggerwarning, '');
        eval("\$trigger_warning = \"".$templates->get("inplayscenes_newthread_fields")."\";");
    } else {
        $trigger_warning = "";
    }

    eval("\$newthread_inplayscenes = \"".$templates->get("inplayscenes_newthread")."\";");
}

// NEUES THEMA ERÖFFNEN - ÜBERPRÜFEN, OB ALLES AUSGEFÜLLT IST
function inplayscenes_validate_newthread(&$dh) {

    global $mybb, $lang, $fid, $db;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    
    if (empty($inplay_forum) || empty($sideplays_forum)) return;

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$sideplays_forum;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    if (!$mybb->get_input('characters')) {
        $dh->set_error($lang->inplayscenes_validate_characters);
    }

    if (!$mybb->get_input('date')) {
        $dh->set_error($lang->inplayscenes_validate_date);
    }

    // Abfrage der Felder, die als erforderlich markiert sind
    $fields_query = $db->query("SELECT identification, title, type FROM ".TABLE_PREFIX."inplayscenes_fields WHERE required = 1");

    while ($field = $db->fetch_array($fields_query)) {
        
        if ($field['type'] == "multiselect" || $field['type'] == "checkbox") {
            $field_value = $mybb->get_input($field['identification'], MyBB::INPUT_ARRAY);
        } else {
            $field_value = $mybb->get_input($field['identification']);
        }

        if (empty($field_value)) {
            $error_message = $lang->sprintf($lang->inplayscenes_validate_field, $field['title']);
            $dh->set_error($error_message);
        }
    }

}

// NEUES THEMA ERÖFFNEN - SPEICHERN
function inplayscenes_do_newthread() {

    global $mybb, $db, $fid, $tid, $lang, $forum, $visible;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];
    $hideprofile_setting = $mybb->settings['inplayscenes_hideprofile'];

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$sideplays_forum;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    // Mögliche gespeicherte Entwürfe löschen
    $db->delete_query("inplayscenes", "tid = '".$mybb->get_input('tid', MyBB::INPUT_INT)."'");

    // SPEICHERN
    $characters = explode(",", $mybb->get_input('characters'));
    $characters = array_map("trim", $characters);	

    $characters_uids = array();
    foreach($characters as $partner) {
        $characters_uid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '".$db->escape_string($partner)."'"), "uid");
        if (!empty($characters_uid)) {
            $characters_uids[] = $characters_uid;
        } else {
            $characters_uids[] = '';
        }
    }
    $charactersUids = implode(",", $characters_uids);

    // Man selbst muss man sich nicht eintragen
    $ownuid = $mybb->user['uid'];
    $ownusername = $mybb->user['username'];

    // Nullen Abfangen für Jahre vor 1000
    $dateInput = $mybb->get_input('date');
    list($year, $month, $day) = explode('-', $dateInput);
    $year = intval($year);
    $formattedDate = $year.'-'.$month.'-'.$day;

    if ($hide_setting == 1) {
        if ($hidetype_setting == 0) {
            $hidetype = 1;
        } else if ($hidetype_setting == 1) {
            $hidetype = 2;
        } else if ($hidetype_setting == 2) {
            $hidetype = (int)$mybb->get_input('hidetype');
        }
        if ($hideprofile_setting == 0) {
            $hideprofile = 1;
        } else if ($hideprofile_setting == 1) {
            $hideprofile = 2;
        } else if ($hideprofile_setting == 2) {
            $hideprofile = (int)$mybb->get_input('hideprofile');
        }
    } else {
        $hidetype = 0;
        $hideprofile = 0;
    }

    $new_scene = array(
        'tid' => (int)$tid,
        'partners' => $ownuid.",".$charactersUids,
        'partners_username' => $db->escape_string($ownusername.",".$mybb->get_input('characters')),
        'date' => $db->escape_string($formattedDate),
        'trigger_warning' => $db->escape_string($mybb->get_input('trigger')),
        'scenetype' => (int)$mybb->get_input('scenetype'),
        'hidetype' => $hidetype,
        'hideprofile' => $hideprofile,
        'postorder' => (int)$mybb->get_input('postorder'),
    );

    // Abfrage der individuellen Felder
    $fields_query = $db->query("SELECT identification, type FROM ".TABLE_PREFIX."inplayscenes_fields");
    
    while ($field = $db->fetch_array($fields_query)) {
        $identification = $field['identification'];
        $type = $field['type'];
    
        if ($type == 'multiselect' || $type == 'checkbox') {
            $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
            $value = implode(",", array_map('trim', $value));
        } else {
            $value = $mybb->get_input($identification);
        }
    
        $new_scene[$identification] = $db->escape_string($value);
    }

    $db->insert_query("inplayscenes", $new_scene);

    // BENACHRICHTIGUNG
    if($visible == 1){

        require_once MYBB_ROOT."inc/class_parser.php";
            
        $parser = new postParser;
        $parser_array = array(
            "allow_html" => 1,
            "allow_mycode" => 1,
            "allow_smilies" => 1,
            "allow_imgcode" => 0,
            "filter_badwords" => 0,
            "nl2br" => 1,
            "allow_videocode" => 0
        );
        
        // DAMIT DIE PN SACHE FUNKTIONIERT
        require_once MYBB_ROOT."inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();

        $ownip = $db->fetch_field($db->query("SELECT ip FROM ".TABLE_PREFIX."sessions WHERE uid = '".$mybb->user['uid']."'"), "ip");

        $thread = get_thread($tid);

        $playername_setting = $mybb->settings['inplayscenes_playername'];
        if (!empty($playername_setting)) {
            if (is_numeric($playername_setting)) {
                $playername_fid = "fid".$playername_setting;
            } else {
                $playername_fid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
            }
        } else {
            $playername_fid = "";
        }

        // MyAlerts möglich
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    
            // Jedem Partner
            foreach ($characters_uids as $CharaUid) {
    
                $notification = $db->fetch_field($db->simple_select("users", "inplayscenes_notification", "uid = '".$CharaUid."'"), 'inplayscenes_notification');
    
                if ($notification == 1) { // Alert

                    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('inplayscenes_alert_newthread');
                        if ($alertType != NULL && $alertType->getEnabled()) {
                            $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$CharaUid, $alertType, (int)$mybb->user['uid']);
                            $alert->setExtraDetails([
                                'username' => $mybb->user['username'],
                                'from' => $mybb->user['uid'],
                                'tid' => $tid,
                                'pid' => $thread['firstpost'],
                                'subject' => $thread['subject'],
                            ]);
                            MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);   
                        }
                    }

                } else { // PN
                    if (!empty($playername_setting)) {
                        if (is_numeric($playername_setting)) {
                            $playername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$CharaUid."'"), $playername_fid);
                        } else {
                            $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$CharaUid."' AND fieldid = '".$playername_fid."'"), "value");
                        }
                    } else {
                        $playername = "";
                    }
                    if (!empty($playername)) {
                        $Playername = $playername;
                    } else {
                        $Playername = get_user($CharaUid)['username'];
                    }

                    $pm_message = $lang->sprintf($lang->inplayscenes_pm_newthread_message, $Playername, 'showthread.php?tid='.$tid.'&pid='.$thread['firstpost'].'#pid'.$thread['firstpost'], $thread['subject']);
                    $pm_change = array(
                        "subject" => $lang->inplayscenes_pm_newthread_subject,
                        "message" => $parser->parse_message($pm_message, $parser_array),
                        "fromid" => $mybb->user['uid'], // von wem kommt diese
                        "toid" => $CharaUid, // an wen geht diese
                        "icon" => "0",
                        "do" => "",
                        "pmid" => "",
                        "ipaddress" => $ownip
                    );
            
                    $pm_change['options'] = array(
                        'signature' => '1',
                        'disablesmilies' => '0',
                        'savecopy' => '0',
                        'readreceipt' => '0',
                    );
                    
                    $pmhandler->set_data($pm_change);
                    if (!$pmhandler->validate_pm())
                        return false;
                    else {
                        $pmhandler->insert_pm();
                    }
                }
            }
        } else { // PN ausschließlich

            // Jedem Partner
            foreach ($characters_uids as $CharaUid) {
                  
                if (!empty($playername_setting)) {
                    if (is_numeric($playername_setting)) {
                        $playername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$CharaUid."'"), $playername_fid);
                    } else {
                        $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$CharaUid."' AND fieldid = '".$playername_fid."'"), "value");
                    }
                } else {
                    $playername = "";
                }
                if (!empty($playername)) {
                    $Playername = $playername;
                } else {
                    $Playername = get_user($CharaUid)['username'];
                }

                $pm_message = $lang->sprintf($lang->inplayscenes_pm_newthread_message, $Playername, 'showthread.php?tid='.$tid.'&pid='.$thread['firstpost'].'#pid'.$thread['firstpost'], $thread['subject']);
                $pm_change = array(
                    "subject" => $lang->inplayscenes_pm_newthread_subject,
                    "message" => $parser->parse_message($pm_message, $parser_array),
                    "fromid" => $mybb->user['uid'], // von wem kommt diese
                    "toid" => $CharaUid, // an wen geht diese
                    "icon" => 0,
                    "do" => "",
                    "pmid" => "",
                    "ipaddress" => $ownip
                );
        
                $pm_change['options'] = array(
                    'signature' => '1',
                    'disablesmilies' => '0',
                    'savecopy' => '0',
                    'readreceipt' => '0',
                );
                // $pmhandler->admin_override = true;
                $pmhandler->set_data($pm_change);
                if (!$pmhandler->validate_pm())
                    return false;
                else {
                    $pmhandler->insert_pm();
                }
            }
        }
    }
}

// BEARBEITEN - ANZEIGE
function inplayscenes_editpost() {

    global $templates, $mybb, $lang, $forum, $db, $thread, $pid, $post_errors, $edit_inplayscenes;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];
    $hideprofile_setting = $mybb->settings['inplayscenes_hideprofile'];

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($thread['fid'], $relevant_forums)) return;

	// Thread ID
    $tid = $thread['tid'];

    // post isnt the first post in thread
    if ($thread['firstpost'] != $pid) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    // previewing new thread?
    if (isset($mybb->input['previewpost']) || $post_errors) {

        $characters = $mybb->get_input('characters');
        $postorder_value = $mybb->get_input('postorder');
        $date = $mybb->get_input('date');

        $triggerwarning = $mybb->get_input('trigger');
        $scenetype_value = $mybb->get_input('scenetype');
        $hidetype_value = $mybb->get_input('hidetype');
        $hideprofile_value = $mybb->get_input('hideprofile');

        $own_inplayscenesfields = inplayscenes_generate_fields(null, true, true);

    } else {

        // Infos aus der DB ziehen
        $draft = $db->fetch_array($db->simple_select('inplayscenes', '*', 'tid = '.$tid));

        $partners_username = explode(",", $draft['partners_username']);
        $usernames = [];
        foreach ($partners_username as $username) {
            $usernames[] = $username;
        }

        $characters = implode(",", $usernames);
        $postorder_value = $draft['postorder'];
        list($year, $month, $day) = explode('-', $draft['date']);
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day); 

        $triggerwarning = $draft['trigger_warning'];
        $scenetype_value = $draft['scenetype'];
        $hidetype_value = $draft['hidetype'];
        $hideprofile_value = $draft['hideprofile'];

        $own_inplayscenesfields = inplayscenes_generate_fields($draft, null, true);
    }

    $postorder_select = inplayscenes_generate_postorder_select($postorder_value);

    if ($scenetype_setting == 1 || $hide_setting == 1) {
        $scenetype_select = inplayscenes_generate_scenetype_select($scenetype_value);
    } else {
        $scenetype_select = "";
    }

    if ($hide_setting == 1) {
        if ($hidetype_setting == 2) {
            $hidetype_select = inplayscenes_generate_hidetype_select($hidetype_value);
        } else {
            if ($hidetype_setting == 0) {
                $hidetype_select = $lang->inplayscenes_fields_hidetype_info;
            } else {
                $hidetype_select = $lang->inplayscenes_fields_hidetype_all;
            }
        }
        if ($hideprofile_setting == 2) {
            $hideprofile_select = inplayscenes_generate_hideprofile_select($hideprofile_value);
        } else {
            if ($hideprofile_setting == 0) {
                $hideprofile_select = $lang->inplayscenes_fields_hideprofile_info;
            } else {
                $hideprofile_select = $lang->inplayscenes_fields_hideprofile_all;
            }
        }
        if ($hidetype_setting == 2 && $hideprofile_setting == 2) {
            $inplayscenes_fields_hide_desc = $lang->inplayscenes_fields_hide_desc;
        } else {
            $inplayscenes_fields_hide_desc = $lang->inplayscenes_fields_hide_team;
        }
    } else {
        $hidetype_select = "";
        $hideprofile_select = "";
    }

    if ($trigger_setting == 1) { 
        $title = $lang->inplayscenes_fields_trigger;
        $description = $lang->inplayscenes_fields_trigger_desc;
        $code = inplayscenes_generate_input_field('trigger', 'textarea', $triggerwarning, '');
        eval("\$trigger_warning = \"".$templates->get("inplayscenes_newthread_fields")."\";");
    } else {
        $trigger_warning = "";
    }

    $lang->inplayscenes_fields_partners_hint = "";

    eval("\$edit_inplayscenes = \"".$templates->get("inplayscenes_newthread")."\";");
}

// BEARBEITEN - ÜBERPRÜFEN, OB ALLES AUSGEFÜLLT IST
function inplayscenes_validate_editpost(&$dh) {

    global $mybb, $lang, $fid, $pid, $thread, $db;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];

    if (empty($inplay_forum) || empty($inplay_archive) || empty($sideplays_forum) || empty($sideplays_archive)) return;

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // post isnt the first post in thread
    if ($thread['firstpost'] != $pid) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    if (!$mybb->get_input('characters')) {
        $dh->set_error($lang->inplayscenes_validate_characters);
    }

    if (!$mybb->get_input('date')) {
        $dh->set_error($lang->inplayscenes_validate_date);
    }

    // Abfrage der Felder, die als erforderlich markiert sind
    $fields_query = $db->query("SELECT identification, title, type FROM ".TABLE_PREFIX."inplayscenes_fields WHERE required = 1 AND edit = 1");

    while ($field = $db->fetch_array($fields_query)) {
        
        if ($field['type'] == "multiselect" || $field['type'] == "checkbox") {
            $field_value = $mybb->get_input($field['identification'], MyBB::INPUT_ARRAY);
        } else {
            $field_value = $mybb->get_input($field['identification']);
        }

        if (empty($field_value)) {
            $error_message = $lang->sprintf($lang->inplayscenes_validate_field, $field['title']);
            $dh->set_error($error_message);
        }
    }

}

// BEARBEITEN - SPEICHERN
function inplayscenes_do_editpost() {

    global $mybb, $db, $forum, $thread, $lang, $pid, $tid;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];
    $hideprofile_setting = $mybb->settings['inplayscenes_hideprofile'];

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($forum['fid'], $relevant_forums)) return;

    // post isnt the first post in thread
    if ($thread['firstpost'] != $pid) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    // SPEICHERN
    $characters = explode(",", $mybb->get_input('characters'));
    $characters = array_map("trim", $characters);	

    $characters_uids = array();
    foreach($characters as $partner) {
        $characters_uid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '".$db->escape_string($partner)."'"), "uid");
        if (!empty($characters_uid)) {
            $characters_uids[] = $characters_uid;
        } else {
            $characters_uids[] = '';
        }
    }
    $charactersUids = implode(",", $characters_uids);

    // Nullen Abfangen für Jahre vor 1000
    $dateInput = $mybb->get_input('date');
    list($year, $month, $day) = explode('-', $dateInput);
    $year = intval($year);
    $formattedDate = $year.'-'.$month.'-'.$day;

    if ($hide_setting == 1) {
        if ($hidetype_setting == 0) {
            $hidetype = 1;
        } else if ($hidetype_setting == 1) {
            $hidetype = 2;
        } else if ($hidetype_setting == 2) {
            $hidetype = (int)$mybb->get_input('hidetype');
        }
        if ($hideprofile_setting == 0) {
            $hideprofile = 1;
        } else if ($hideprofile_setting == 1) {
            $hideprofile = 2;
        } else if ($hideprofile_setting == 2) {
            $hideprofile = (int)$mybb->get_input('hideprofile');
        }
    } else {
        $hidetype = 0;
        $hideprofile = 0;
    }

    $update_scene = array(
        'partners' => $charactersUids,
        'partners_username' => $db->escape_string($mybb->get_input('characters')),
        'date' => $db->escape_string($formattedDate),
        'trigger_warning' => $db->escape_string($mybb->get_input('trigger')),
        'scenetype' => (int)$mybb->get_input('scenetype'),
        'hidetype' => $hidetype,
        'hideprofile' => $hideprofile,
        'postorder' => (int)$mybb->get_input('postorder'),
    );

    // Abfrage der individuellen Felder
    $fields_query = $db->query("SELECT identification, type FROM ".TABLE_PREFIX."inplayscenes_fields WHERE edit = 1");
    
    while ($field = $db->fetch_array($fields_query)) {
        $identification = $field['identification'];
        $type = $field['type'];
    
        if ($type == 'multiselect' || $type == 'checkbox') {
            $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
            $value = implode(",", array_map('trim', $value));
        } else {
            $value = $mybb->get_input($identification);
        }
    
        $update_scene[$identification] = $db->escape_string($value);
    }

    $db->update_query("inplayscenes", $update_scene, "tid='".$tid."'");
}

// NEUE ANTWORT - ALERT
function inplayscenes_do_newreply() {

    global $mybb, $db, $forum, $thread, $lang, $pid, $tid, $visible;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$sideplays_forum;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($forum['fid'], $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    // BENACHRICHTIGUNG
    if($visible == 1){

        require_once MYBB_ROOT."inc/class_parser.php";
            
        $parser = new postParser;
        $parser_array = array(
            "allow_html" => 1,
            "allow_mycode" => 1,
            "allow_smilies" => 1,
            "allow_imgcode" => 0,
            "filter_badwords" => 0,
            "nl2br" => 1,
            "allow_videocode" => 0
        );
        
        // DAMIT DIE PN SACHE FUNKTIONIERT
        require_once MYBB_ROOT."inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();

        $ownip = $db->fetch_field($db->query("SELECT ip FROM ".TABLE_PREFIX."sessions WHERE uid = '".$mybb->user['uid']."'"), "ip");

        $lastpost = $db->fetch_field($db->query("SELECT pid FROM ".TABLE_PREFIX."posts WHERE tid = '".$thread['tid']."' ORDER BY pid DESC LIMIT 1"), "pid");

        $partners = $db->fetch_field($db->query("SELECT partners FROM ".TABLE_PREFIX."inplayscenes WHERE tid = '".$thread['tid']."'"), "partners");
        $characters_uids = explode(",", $partners);

        $key = array_search($mybb->user['uid'], $characters_uids);
        if ($key !== false) {
            unset($characters_uids[$key]);
        }

        $playername_setting = $mybb->settings['inplayscenes_playername'];
        if (!empty($playername_setting)) {
            if (is_numeric($playername_setting)) {
                $playername_fid = "fid".$playername_setting;
            } else {
                $playername_fid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
            }
        } else {
            $playername_fid = "";
        }

        // MyAlerts möglich
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    
            // Jedem Partner
            foreach ($characters_uids as $CharaUid) {
    
                $notification = $db->fetch_field($db->simple_select("users", "inplayscenes_notification", "uid = '".$CharaUid."'"), 'inplayscenes_notification');
    
                if ($notification == 1) { // Alert

                    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('inplayscenes_alert_newreply');
                        if ($alertType != NULL && $alertType->getEnabled()) {
                            $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$CharaUid, $alertType, (int)$mybb->user['uid']);
                            $alert->setExtraDetails([
                                'username' => $mybb->user['username'],
                                'from' => $mybb->user['uid'],
                                'tid' => $thread['tid'],
                                'pid' => $lastpost,
                                'subject' => $thread['subject'],
                            ]);
                            MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);   
                        }
                    }

                } else { // PN
                    if (!empty($playername_setting)) {
                        if (is_numeric($playername_setting)) {
                            $playername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$CharaUid."'"), $playername_fid);
                        } else {
                            $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$CharaUid."' AND fieldid = '".$playername_fid."'"), "value");
                        }
                    } else {
                        $playername = "";
                    }
                    if (!empty($playername)) {
                        $Playername = $playername;
                    } else {
                        $Playername = get_user($CharaUid)['username'];
                    }

                    $pm_message = $lang->sprintf($lang->inplayscenes_pm_newreply_message, $Playername, 'showthread.php?tid='.$tid.'&pid='.$lastpost.'#pid'.$lastpost, $thread['subject']);
                    $pm_change = array(
                        "subject" => $lang->sprintf($lang->inplayscenes_pm_newreply_subject, $thread['subject']),
                        "message" => $parser->parse_message($pm_message, $parser_array),
                        "fromid" => $mybb->user['uid'], // von wem kommt diese
                        "toid" => $CharaUid, // an wen geht diese
                        "icon" => "0",
                        "do" => "",
                        "pmid" => "",
                        "ipaddress" => $ownip
                    );
            
                    $pm_change['options'] = array(
                        'signature' => '1',
                        'disablesmilies' => '0',
                        'savecopy' => '0',
                        'readreceipt' => '0',
                    );
                    
                    $pmhandler->set_data($pm_change);
                    if (!$pmhandler->validate_pm())
                        return false;
                    else {
                        $pmhandler->insert_pm();
                    }
                }
            }
        } else { // PN ausschließlich

            // Jedem Partner
            foreach ($characters_uids as $CharaUid) {
        
                if (!empty($playername_setting)) {
                    if (is_numeric($playername_setting)) {
                        $playername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$CharaUid."'"), $playername_fid);
                    } else {
                        $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$CharaUid."' AND fieldid = '".$playername_fid."'"), "value");
                    }
                } else {
                    $playername = "";
                }
                if (!empty($playername)) {
                    $Playername = $playername;
                } else {
                    $Playername = get_user($CharaUid)['username'];
                }

                $pm_message = $lang->sprintf($lang->inplayscenes_pm_newreply_message, $Playername, 'showthread.php?tid='.$tid.'&pid='.$lastpost.'#pid'.$lastpost, $thread['subject']);
                $pm_change = array(
                    "subject" => $lang->sprintf($lang->inplayscenes_pm_newreply_subject, $thread['subject']),
                    "message" => $parser->parse_message($pm_message, $parser_array),
                    "fromid" => $mybb->user['uid'], // von wem kommt diese
                    "toid" => $CharaUid, // an wen geht diese
                    "icon" => "0",
                    "do" => "",
                    "pmid" => "",
                    "ipaddress" => $ownip
                );
        
                $pm_change['options'] = array(
                    'signature' => '1',
                    'disablesmilies' => '0',
                    'savecopy' => '0',
                    'readreceipt' => '0',
                );
                // $pmhandler->admin_override = true;
                $pmhandler->set_data($pm_change);
                if (!$pmhandler->validate_pm())
                    return false;
                else {
                    $pmhandler->insert_pm();
                }
            }
        }
    }
}

// THEMA WIRD GELÖSCHT -> MODERATIONS
function inplayscenes_delete_thread($tid) {

    global $tid, $db, $mybb;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];

    $thread = get_thread($tid);

    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);

    if(in_array($thread['fid'], $relevant_forums)) {
        $db->delete_query("inplayscenes", "tid = '".$tid."'");
    }

}

// FIRST POST WIRD GELÖSCHT
function inplayscenes_delete_post($pid) {

    global $tid, $db, $mybb, $pid;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];

    $post = get_post($pid);

    $thread = get_thread($post['tid']);

    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);

    if (($thread['firstpost'] == $pid) AND (in_array($post['fid'], $relevant_forums))) {
        $db->delete_query("inplayscenes", "tid = '".$post['tid']."'");
    }

}

// THEMA WIRD GELÖSCHT -> EDIT FIRST POST
function inplayscenes_deletepost() {

    global $tid, $db, $mybb, $pid;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];

    $thread = get_thread($tid);

    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);

    if($mybb->get_input('delete', MyBB::INPUT_INT) == 1) {

        if (($thread['firstpost'] == $pid) AND (in_array($thread['fid'], $relevant_forums))) {
            $db->delete_query("inplayscenes", "tid = '".$tid."'");
        }
    }
}

// FORUMDISPLAY
function inplayscenes_forumdisplay_thread() {

    global $templates, $mybb, $lang, $db, $thread, $partnerusers, $scenedate, $inplayscene, $inplayscenes_forumdisplay, $display_offplay, $display_onlyinplay;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $month_setting = $mybb->settings['inplayscenes_months'];
    $color_setting = $mybb->settings['inplayscenes_groupcolor'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];

    // CSS Variable zum Verstecken 
    $display_onlyinplay = "style=\"display:none;\"";
    $display_offplay = "";

    // Thread- und Foren-ID
    $tid = $thread['tid'];
    $fid = $thread['fid'];

    $inplayscene = [
        'scenedate' => '',
        'partnerusers' => '',
        'postorder' => '',
        'scenetype' => '',
        'trigger' => '',
    ];

    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $inplayscene[$spalte['identification']] = '';
    }

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    $months = array(
        '01' => $lang->inplayscenes_jan,
        '02' => $lang->inplayscenes_feb,
        '03' => $lang->inplayscenes_mar,
        '04' => $lang->inplayscenes_apr,
        '05' => $lang->inplayscenes_mai,
        '06' => $lang->inplayscenes_jun,
        '07' => $lang->inplayscenes_jul,
        '08' => $lang->inplayscenes_aug,
        '09' => $lang->inplayscenes_sep,
        '10' => $lang->inplayscenes_okt,
        '11' => $lang->inplayscenes_nov,
        '12' => $lang->inplayscenes_dez
    );

    // CSS Variable zum Verstecken
    $display_offplay = "style=\"display:none;\"";
    $display_onlyinplay = "";

    // Infos aus der DB ziehen
    $info = $db->fetch_array($db->simple_select('inplayscenes', '*', 'tid = ' . $tid));

    list($year, $month, $day) = explode('-', $info['date']);
    if ($month_setting == 0) {
        $scenedate = $day.".".$month.".".$year;
    } else {
        $scenedate = $day.". ".$months[$month]." ".$year;
    }

    $partners_username = $info['partners_username'];
    $partners = $info['partners'];

    $usernames = explode(",", $partners_username);
    $uids = explode(",", $partners);

    $partners = [];
    foreach ($uids as $key => $uid) {

        $tagged_user = get_user($uid);
        if (!empty($tagged_user)) {
            if ($color_setting == 1) {
                $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
            } else {
                $username = $tagged_user['username'];
            }
            $taguser = build_profile_link($username, $uid);
        } else {
            $taguser = $usernames[$key];
        }
        $partners[] = $taguser;
    }
    $partnerusers = implode(" &#x26; ", $partners);

    $postorder = $postorderoptions[$info['postorder']];

    // Variable für einzeln
    $inplayscene['scenedate'] = $scenedate;
    $inplayscene['partnerusers'] = $partnerusers;
    $inplayscene['postorder'] = $postorder;

    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $allow_img = "";
        $allow_video = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allow_html'];
        $allow_mybb = $field['allow_mybb'];
        $allow_img = $field['allow_img'];
        $allow_video = $field['allow_video'];

        $value = inplayscenes_parser_fields($info[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);

        // Einzelne Variabeln
        $inplayscene[$identification] = $value;

        if (!empty($value)) {
            eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_forumdisplay_fields") . "\";");
        }
    }

    if ($scenetype_setting == 1) {
        $scenetype = $sceneoptions[$info['scenetype']]." &#x26; ";
        $inplayscene['scenetype'] = $sceneoptions[$info['scenetype']];
        
        if ($hide_setting == 0 && $info['scenetype'] == 3) {
            $scenetype = $sceneoptions[0]." &#x26; ";
            $inplayscene['scenetype'] = $sceneoptions[0];
        }
    } else if ($hide_setting == 1) {
        if (!empty($sceneoptions[$info['scenetype']])) {
            $scenetype = $sceneoptions[$info['scenetype']]." &#x26; ";
        } else {
            $scenetype = "";
        }
        $inplayscene['scenetype'] = $sceneoptions[$info['scenetype']];
    } else {
        $scenetype = "";
        $inplayscene['scenetype'] = "";
    }

    if ($trigger_setting == 1) {
        $trigger = $info['trigger_warning'];
        $inplayscene['trigger'] = $trigger;

        if (!empty($trigger)) {
            $title = $lang->inplayscenes_fields_trigger;
            $value = $trigger;
            eval("\$triggerwarning = \"" . $templates->get("inplayscenes_forumdisplay_fields") . "\";");
        } else {
            $triggerwarning = "";
        }
    } else {
        $triggerwarning = "";
        $inplayscene['trigger'] = "";
    }

    // Variable für alles
    eval("\$inplayscenes_forumdisplay = \"" . $templates->get("inplayscenes_forumdisplay") . "\";");
}

// FORUMDISPLAY KOMPLETT VERSTECKEN
function inplayscenes_forumdisplay_before_thread(&$args) {

    global $db, $mybb, $threadcount, $fid;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];

    // zurück, wenn verstecken nicht gegeben ist
    if ($hide_setting != 1) return;
    // zurück, wenn nicht komplett versteckt werden darf
    if ($hidetype_setting == 0) return;

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // ModCP Berechtige (Team) können immer sehen 
    if($mybb->usergroup['canmodcp'] == '1') return;

    $threadcache = &$args['threadcache'];
    $tids = &$args['tids'];
    
    $uid = $mybb->user['uid'];

    if ($hidetype_setting == 1) { // immer versteckt
        $hidescenes_setting = "";
    } else { // individuell
        $hidescenes_setting = "forumdisplay";
    }

    $remove_tids = inplayscenes_hidescenes($uid, $hidescenes_setting);

    if (!empty($remove_tids)) {
        foreach ($remove_tids as $remove_tid) {
            // Entferne den Thread aus dem Cache
            if (isset($threadcache[$remove_tid])) {
                unset($threadcache[$remove_tid]);
            }
    
            // Entferne die TID aus dem TID-Array
            $key = array_search($remove_tid, $tids);
            if ($key !== false) {
                unset($tids[$key]);
            }
        }
    
        $threadcount = count($tids);
    }
}

// FORUMBIT ANZEIGE - VERSTECKTE SZENEN
function inplayscenes_build_forumbits_forum($forum) {

    global $mybb, $db;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];

    // zurück, wenn verstecken nicht gegeben ist
    if ($hide_setting != 1) return;
    // zurück, wenn nicht komplett versteckt werden darf
    if ($hidetype_setting == 0) return;

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($forum['fid'], $relevant_forums)) return;

    // ModCP Berechtige (Team) können immer sehen 
    if($mybb->usergroup['canmodcp'] == '1') return;

    $uid = $mybb->user['uid'];
    if ($hidetype_setting == 1) { // immer versteckt
        $hidescenes_setting = "";
    } else { // individuell
        $hidescenes_setting = "forumdisplay";
    }
    $remove_tids = inplayscenes_hidescenes($uid, $hidescenes_setting);
        
    if (!empty($remove_tids)) {
        
        $remove_tids_list = implode(',', array_map('intval', $remove_tids)); 

        // Themen und Antworten Counter bearbeiten
        $query = $db->query("SELECT tid, replies FROM ".TABLE_PREFIX."threads
        WHERE fid = ".intval($forum['fid'])."
        AND tid IN (".$remove_tids_list.")
        ");

        $total_replies = 0;
        $total_threads = 0;
        while ($thread = $db->fetch_array($query)) {
            $total_replies += $thread['replies'] + 1;
            $total_threads += 1;
        }

        $forum['posts'] = max(0, $forum['posts'] - $total_replies);
        $forum['threads'] = max(0, $forum['threads'] - $total_threads);

        // Lastpost Informationen
        if (in_array($forum['lastposttid'], $remove_tids)) {

            $query = $db->query("
                SELECT tid, lastpost, lastposter, lastposteruid, subject
                FROM ".TABLE_PREFIX."threads
                WHERE fid = ".intval($forum['fid'])."
                AND tid NOT IN (".$remove_tids_list.")
                ORDER BY lastpost DESC
                LIMIT 1
            ");

            $last_valid_thread = $db->fetch_array($query);
            if (!empty($last_valid_thread)) {
                $forum['lastpost'] = $last_valid_thread['lastpost'];
                $forum['lastpostsubject'] = $last_valid_thread['subject'];
                $forum['lastposter'] = $last_valid_thread['lastposter'];
                $forum['lastposttid'] = $last_valid_thread['tid'];
                $forum['lastposteruid'] = $last_valid_thread['lastposteruid'];
            } else {
                $forum['lastpost'] = 0;
                $forum['lastpostsubject'] = '';
                $forum['lastposter'] = '';
                $forum['lastposttid'] = 0;
                $forum['lastposteruid'] = 0;
            }
        }
    }

    return $forum;
}

// POSTBIT
function inplayscenes_postbit(&$post) {

    global $db, $mybb, $lang, $templates, $pid, $tid, $page, $thread, $post_type;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $information_setting_posts = $mybb->settings['inplayscenes_information_posts'];
    $month_setting = $mybb->settings['inplayscenes_months'];
    $color_setting = $mybb->settings['inplayscenes_groupcolor'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];

    // Sprachdatei laden
    $lang->load('inplayscenes');

    // CSS Variable zum Verstecken 
    $post['display_onlyinplay'] = "style=\"display:none;\"";
    $post['display_offplay'] = "";

    $post['scenedate'] = '';
    $post['partnerusers'] = '';
    $post['postorder'] = '';
    $post['scenetype'] = '';
    $post['trigger'] = '';
    $post['inplayscenes_pdf'] = '';
    $post['inplayscenes_postbit'] = '';

    // Hole die Felder aus der Datenbank und füge sie dem $post-Array hinzu
    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $post[$spalte['identification']] = ''; // Füge die Felder mit leerem Wert hinzu
    }

    // Vorschau
    if (THIS_SCRIPT == 'newthread.php') { 
        $fid = $mybb->get_input('fid');
    }  else {
        // Thread- und Foren-ID
        $tid = $thread['tid'];
        $fid = $thread['fid'];
    }

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // PDF Button
    if ($mybb->user['uid'] != 0) {
        eval("\$post['inplayscenes_pdf'] = \"" . $templates->get("inplayscenes_postbit_pdf") . "\";");
    } else  {
        $post['inplayscenes_pdf'] = "";
    }

    // Zurück, wenn im Postbit nichts angezeigt werden soll
    if ($information_setting_posts == 0) return;

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    $months = array(
        '01' => $lang->inplayscenes_jan,
        '02' => $lang->inplayscenes_feb,
        '03' => $lang->inplayscenes_mar,
        '04' => $lang->inplayscenes_apr,
        '05' => $lang->inplayscenes_mai,
        '06' => $lang->inplayscenes_jun,
        '07' => $lang->inplayscenes_jul,
        '08' => $lang->inplayscenes_aug,
        '09' => $lang->inplayscenes_sep,
        '10' => $lang->inplayscenes_okt,
        '11' => $lang->inplayscenes_nov,
        '12' => $lang->inplayscenes_dez
    );

    // CSS Variable zum Verstecken
    $post['display_onlyinplay'] = "";
    $post['display_offplay'] = "style=\"display:none;\"";

    // Vorschau
    if (THIS_SCRIPT == 'newthread.php') {

        $info['date'] = $mybb->get_input('date');
        $info['postorder'] = $mybb->get_input('postorder');
        $info['scenetype'] = $mybb->get_input('scenetype');
        $info['trigger_warning'] = $mybb->get_input('trigger');

        // SPEICHERN
        $characters = explode(",", $mybb->get_input('characters'));
        $characters = array_map("trim", $characters);	
    
        $characters_uids = array();
        foreach ($characters as $key => $partner) {
            $characters_uid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '".$db->escape_string($partner)."'"), "uid");
            if (!empty($characters_uid)) {
                $characters_uids[] = $characters_uid;
            } else {
                $characters_uids = '';
            }
        }
        if (!empty($characters_uids)) {
            $charactersUids = implode(",", $characters_uids);
        } else {
            $charactersUids = '';
        }
    
        // Man selbst muss man sich nicht eintragen
        $ownuid = $mybb->user['uid'];
        $ownusername = $mybb->user['username'];

        $info['partners_username'] = $db->escape_string($ownusername.",".$mybb->get_input('characters'));
        $info['partners'] = $ownuid.",".$charactersUids;

        // Hole die Felder aus der Datenbank und füge sie dem $post-Array hinzu
        $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields ORDER BY disporder ASC, title ASC");
        while ($spalte = $db->fetch_array($spalten_query)) {
            $info[$spalte['identification']] = $mybb->get_input($spalte['identification']); // Füge die Felder mit leerem Wert hinzu
        }

    } else {
        // Infos aus der DB ziehen
        $info = $db->fetch_array($db->simple_select('inplayscenes', '*', 'tid = ' . $tid));
    }

    if (!empty($info['date'])) {
        list($year, $month, $day) = explode('-', $info['date']);    
        if ($month_setting == 0) {
            $scenedate = $day.".".$month.".".$year;
        } else {
            $scenedate = $day.". ".$months[$month]." ".$year;
        }
    } else {
        $scenedate = "";
    }

    $partners_username = $info['partners_username'];
    $partners = $info['partners'];

    $usernames = explode(",", $partners_username);
    $uids = explode(",", $partners);

    $partners = [];
    foreach ($uids as $key => $uid) {

        $tagged_user = get_user($uid);
        if (!empty($tagged_user)) {
            if ($color_setting == 1) {
                $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
            } else {
                $username = $tagged_user['username'];
            }
            $taguser = build_profile_link($username, $uid);
        } else {
            $taguser = $usernames[$key];
        }
        $partners[] = $taguser;
    }
    $partnerusers = implode(" &#x26; ", $partners);

    $postorder = $postorderoptions[$info['postorder']];

    // Variable für einzeln
    $post['scenedate'] = $scenedate;
    $post['partnerusers'] = $partnerusers;
    $post['postorder'] = $postorder;

    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $allow_img = "";
        $allow_video = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allow_html'];
        $allow_mybb = $field['allow_mybb'];
        $allow_img = $field['allow_img'];
        $allow_video = $field['allow_video'];

        $value = inplayscenes_parser_fields($info[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);

        // Einzelne Variabeln
        $post[$identification] = $value;

        if (!empty($value)) {
            eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_postbit_fields") . "\";");
        }
    }

    if ($scenetype_setting == 1) {
        $scenetype = $sceneoptions[$info['scenetype']]." &#x26; ";
        $post['scenetype'] = $sceneoptions[$info['scenetype']];
        
        if ($hide_setting == 0 && $info['scenetype'] == 3) {
            $scenetype = $sceneoptions[0]." &#x26; ";
            $post['scenetype'] = $sceneoptions[0];
        }
    } else if ($hide_setting == 1) {
        if (!empty($sceneoptions[$info['scenetype']])) {
            $scenetype = $sceneoptions[$info['scenetype']]." &#x26; ";
            $post['scenetype'] = $sceneoptions[$info['scenetype']];
        } else {
            $scenetype = "";
            $post['scenetype'] = "";
        }
    } else {
        $scenetype = "";
        $post['scenetype'] = "";
    }

    if ($trigger_setting == 1) {
        $trigger = $info['trigger_warning'];
        $post['trigger'] = $trigger;

        if (!empty($trigger)) {
            $title = $lang->inplayscenes_fields_trigger;
            $value = $trigger;
            eval("\$triggerwarning = \"" . $templates->get("inplayscenes_postbit_fields") . "\";");
        } else {
            $triggerwarning = "";
        }
    } else {
        $triggerwarning = "";
        $post['trigger'] = "";
    }

    // Variable für alles
    eval("\$post['inplayscenes_postbit'] = \"" . $templates->get("inplayscenes_postbit") . "\";");
}

// POSTBIT - POST VARIABELN LEER LAUFEN
function inplayscenes_postvariables(&$post) {

    global $db;

    // CSS Variable zum Verstecken 
    $post['display_onlyinplay'] = "style=\"display:none;\"";
    $post['display_offplay'] = "";

    // Setze die Variablen auch hier, aber lasse sie leer
    $post['scenedate'] = '';
    $post['partnerusers'] = '';
    $post['postorder'] = '';
    $post['scenetype'] = '';
    $post['trigger'] = '';
    $post['inplayscenes_pdf'] = '';
    $post['inplayscenes_postbit'] = '';

    // Hole die Felder aus der Datenbank und füge sie dem $post-Array hinzu
    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $post[$spalte['identification']] = ''; // Füge die Felder mit leerem Wert hinzu
    }

    return;
}

// SHOWTHREAD
function inplayscenes_showthread_start() {
	
	global $mybb, $templates, $thread, $lang, $db, $display_offplay, $display_onlyinplay, $inplayscenes_showthread, $inplayscenes_edit, $inplayscenes_add, $inplayscenes_relevant, $inplayscenes_pdf, $inplayscene;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $information_setting_thread = $mybb->settings['inplayscenes_information_thread'];
    $month_setting = $mybb->settings['inplayscenes_months'];
    $color_setting = $mybb->settings['inplayscenes_groupcolor'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $scenesedit_setting = $mybb->settings['inplayscenes_scenesedit'];

    // CSS Variable zum Verstecken 
    $display_offplay = "";
    $display_onlyinplay = "style=\"display:none;\"";

    // Thread- und Foren-ID
    $tid = $thread['tid'];
    $fid = $thread['fid'];

    $inplayscene = [
        'scenedate' => '',
        'partnerusers' => '',
        'postorder' => '',
        'scenetype' => '',
        'trigger' => '',
    ];

    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $inplayscene[$spalte['identification']] = '';
    }

    // zurück, wenn es nicht der Inplay Bereich ist
    $inplayforums = $inplay_forum.",".$inplay_archive.",".$sideplays_forum.",".$sideplays_archive;
    $relevant_forums = inplayscenes_get_relevant_forums($inplayforums);
    if (!in_array($fid, $relevant_forums)) return;

    // Buttons
    $partnersUID = explode(",", $db->fetch_field($db->simple_select("inplayscenes", "partners", "tid = '".$tid."'"), "partners"));    
    if ($scenesedit_setting == 0) {
        if (in_array($mybb->user['uid'], $partnersUID) AND $mybb->user['uid'] != 0) {
            $inplayscenes_add = "";
            eval("\$inplayscenes_edit = \"" . $templates->get("inplayscenes_showthread_edit") . "\";");
        } else  {
            $inplayscenes_edit = "";
    
            if ($scenetype_setting == 1 AND $mybb->user['uid'] != 0) {
                $scenetype = $db->fetch_field($db->simple_select("inplayscenes", "scenetype", "tid = '".$tid."'"), "scenetype");
    
                if ($scenetype == 2) {
                    eval("\$inplayscenes_add = \"" . $templates->get("inplayscenes_showthread_add") . "\";");
                } else {
                    $inplayscenes_add = "";
                }
            } else {
                $inplayscenes_add = "";
            }
        }
    } else {
        $userids_array = inplayscenes_get_allchars($mybb->user['uid']); 
        $hasEditPermission = false;

        foreach ($userids_array as $uid => $username) {
            if (in_array($uid, $partnersUID)) {
                $hasEditPermission = true;
                break;
            }
        }

        if ($hasEditPermission && $mybb->user['uid'] != 0) {
            eval("\$inplayscenes_edit = \"" . $templates->get("inplayscenes_showthread_edit") . "\";");
            $inplayscenes_add = "";
        } else {
            $inplayscenes_edit = "";
            if ($scenetype_setting == 1 && $mybb->user['uid'] != 0) {
                $scenetype = $db->fetch_field($db->simple_select("inplayscenes", "scenetype", "tid = '".$tid."'"), "scenetype");

                if ($scenetype == 2) {
                    eval("\$inplayscenes_add = \"" . $templates->get("inplayscenes_showthread_add") . "\";");
                } else {
                    $inplayscenes_add = "";
                }
            } else {
                $inplayscenes_add = "";
            }
        }
    }

    if ($mybb->user['uid'] != 0) {
        eval("\$inplayscenes_pdf = \"" . $templates->get("inplayscenes_showthread_pdf") . "\";");
    } else {
        $inplayscenes_pdf = "";
    }

    // NICHT RELEVANT BUTTON -> Nur archiv
    $archiveforums = $inplay_archive.",".$sideplays_archive;
    $relevant_archive = inplayscenes_get_relevant_forums($archiveforums);
    if (in_array($fid, $relevant_archive)) {

        $characters_uids = array();
        foreach($partnersUID as $partner) {
            $characters_uid = get_user($partner)['uid'];
            if (!empty($characters_uid)) {
                $characters_uids[] = $characters_uid;
            }
        }

        if (count($characters_uids) == 1 AND $characters_uids[0] == $mybb->user['uid']) {
            eval("\$inplayscenes_relevant = \"" . $templates->get("inplayscenes_showthread_relevant") . "\";");
        } else {
            $inplayscenes_relevant = "";
        }
    } else {
        $inplayscenes_relevant = "";
    }

    // Versteckt Error
    if ($hide_setting == 1) {
        $remove_tids = inplayscenes_hidescenes($mybb->user['uid']);
        if (in_array($tid, $remove_tids) && $mybb->usergroup['canmodcp'] != '1') {
            error_no_permission();
        }
    }

    // Zurück, wenn im Showthread nichts angezeigt werden soll
    if ($information_setting_thread == 0) return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    $months = array(
        '01' => $lang->inplayscenes_jan,
        '02' => $lang->inplayscenes_feb,
        '03' => $lang->inplayscenes_mar,
        '04' => $lang->inplayscenes_apr,
        '05' => $lang->inplayscenes_mai,
        '06' => $lang->inplayscenes_jun,
        '07' => $lang->inplayscenes_jul,
        '08' => $lang->inplayscenes_aug,
        '09' => $lang->inplayscenes_sep,
        '10' => $lang->inplayscenes_okt,
        '11' => $lang->inplayscenes_nov,
        '12' => $lang->inplayscenes_dez
    );

    // CSS Variable zum Verstecken
    $display_offplay = "style=\"display:none;\"";
    $display_onlyinplay = "";

    // Infos aus der DB ziehen
    $info = $db->fetch_array($db->simple_select('inplayscenes', '*', 'tid = ' . $tid));

    list($year, $month, $day) = explode('-', $info['date']);
    if ($month_setting == 0) {
        $scenedate = $day.".".$month.".".$year;
    } else {
        $scenedate = $day.". ".$months[$month]." ".$year;
    }

    $partners_username = $info['partners_username'];
    $partners = $info['partners'];

    $usernames = explode(",", $partners_username);
    $uids = explode(",", $partners);

    $partners = [];
    foreach ($uids as $key => $uid) {

        $tagged_user = get_user($uid);
        if (!empty($tagged_user)) {
            if ($color_setting == 1) {
                $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
            } else {
                $username = $tagged_user['username'];
            }
            $taguser = build_profile_link($username, $uid);
        } else {
            $taguser = $usernames[$key];
        }
        $partners[] = $taguser;
    }
    $partnerusers = implode(" &#x26; ", $partners);

    $postorder = $postorderoptions[$info['postorder']];

    // Variable für einzeln
    $inplayscene = [];
    $inplayscene['scenedate'] = $scenedate;
    $inplayscene['partnerusers'] = $partnerusers;
    $inplayscene['postorder'] = $postorder;

    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $allow_img = "";
        $allow_video = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allow_html'];
        $allow_mybb = $field['allow_mybb'];
        $allow_img = $field['allow_img'];
        $allow_video = $field['allow_video'];

        $value = inplayscenes_parser_fields($info[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);

        // Einzelne Variabeln
        $inplayscene[$identification] = $value;

        if (!empty($value)) {
            eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_showthread_fields") . "\";");
        }
    }

    if ($scenetype_setting == 1) {
        $scenetype = $sceneoptions[$info['scenetype']]." &#x26; ";
        $inplayscene['scenetype'] = $sceneoptions[$info['scenetype']];
        if ($hide_setting == 0 && $info['scenetype'] == 3) {
            $scenetype = $sceneoptions[0]." &#x26; ";
            $inplayscene['scenetype'] = $sceneoptions[0];
        }
    } else if ($hide_setting == 1) {
        if (!empty($sceneoptions[$info['scenetype']])) {
            $scenetype = $sceneoptions[$info['scenetype']]." &#x26; ";
        } else {
            $scenetype = "";
        }
        $inplayscene['scenetype'] = $sceneoptions[$info['scenetype']];
    } else {
        $scenetype = "";
        $inplayscene['scenetype'] = "";
    }

    if ($trigger_setting == 1) {
        $trigger = $info['trigger_warning'];
        $inplayscene['trigger'] = $trigger;

        if (!empty($trigger)) {
            $title = $lang->inplayscenes_fields_trigger;
            $value = $trigger;
            eval("\$triggerwarning = \"" . $templates->get("inplayscenes_showthread_fields") . "\";");
        } else {
            $triggerwarning = "";
        }
    } else {
        $triggerwarning = "";
        $inplayscene['trigger'] = "";
    }

    // Variable für alles
    eval("\$inplayscenes_showthread = \"" . $templates->get("inplayscenes_showthread") . "\";");
}

// ERROR ANZEIGE - VERSTECKTE SZENEN
function inplayscenes_no_permission() {

	global $mybb, $theme, $templates, $db, $lang, $session, $errorpage;

    $hide_setting = $mybb->settings['inplayscenes_hide'];

    // zurück, wenn verstecken nicht gegeben ist
    if ($hide_setting != 1) return;

    if ($mybb->user['uid'] == 0) return;

    $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
    $remove_tids = inplayscenes_hidescenes($mybb->user['uid']);
    if (!in_array($tid, $remove_tids)) return;

    $lang->inplayscenes_hide_nopermission_4 = $lang->sprintf($lang->inplayscenes_hide_nopermission_4, htmlspecialchars_uni($mybb->user['username']));
    
    eval("\$errorpage = \"".$templates->get("inplayscenes_error_hidenscenes")."\";");

	$noperm_array = array (
		"nopermission" => '1',
		"location1" => 0,
		"location2" => 0
	);

	$db->update_query("sessions", $noperm_array, "sid='{$session->sid}'");

	if($mybb->get_input('ajax', MyBB::INPUT_INT))
	{
		// Send our headers.
		header("Content-type: application/json; charset={$lang->settings['charset']}");
		echo json_encode(array("errors" => array($lang->error_nopermission_user_ajax)));
		exit;
	}

	error($errorpage);
    return;
}

// SUCHEERGEBNISSE - VERSTECKTE SZENEN
function inplayscenes_search_process(){

    global $mybb, $db, $searcharray, $remove_tids;

    // Einstellungen
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];

    // zurück, wenn verstecken nicht gegeben ist
    if ($hide_setting != 1) return;
    // zurück, wenn nicht komplett versteckt werden darf
    if ($hidetype_setting == 0) return;

    // ModCP Berechtige (Team) können immer sehen 
    if($mybb->usergroup['canmodcp'] == '1') return;

    $uid = $mybb->user['uid'];
    if ($hidetype_setting == 1) { // immer versteckt
        $hidescenes_setting = "";
    } else { // individuell
        $hidescenes_setting = "forumdisplay";
    }
    $remove_tids = inplayscenes_hidescenes($uid, $hidescenes_setting);

    // Zusätzliche Query Bedingungen
    if (!empty($remove_tids)) {

         // Threads filtern
        if($searcharray['resulttype'] == "threads") {
            if (!empty($searcharray['querycache'])) {
                $searcharray['querycache'] = $searcharray['querycache']." AND t.tid NOT IN (".implode(',', $remove_tids).")";
            } else {
                $searcharray['querycache'] = "t.tid NOT IN (".implode(',', $remove_tids).")";
            }
        }
        // Posts filtern
        else if ($searcharray['resulttype'] == "posts") {

            $pids = explode(',', $searcharray['posts']);

            $pid_query = $db->simple_select("posts","pid","tid IN (".implode(',',$remove_tids).")");
            $remove_pids = [];
            while ($post = $db->fetch_array($pid_query)) {
                $remove_pids[] = $post['pid'];
            }

            $filtered_pids = array_diff($pids, $remove_pids);

            $searcharray['posts'] = implode(',', $filtered_pids);
        }
    }
}

// SUCHERGEBNISSE - VERSTECKTE SZENEN HINWEIS
function inplayscenes_search_results(){

    global $mybb, $count_hidescenes, $lang;

    // Einstellungen
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];

    $count_hidescenes = "";

    // zurück, wenn verstecken nicht gegeben ist
    if ($hide_setting != 1) return;
    // zurück, wenn nicht komplett versteckt werden darf
    if ($hidetype_setting == 0) return;

    // ModCP Berechtige (Team) können immer sehen 
    if($mybb->usergroup['canmodcp'] == '1') return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    $uid = $mybb->user['uid'];
    if ($hidetype_setting == 1) { // immer versteckt
        $hidescenes_setting = "";
    } else { // individuell
        $hidescenes_setting = "forumdisplay";
    }
    $remove_tids = inplayscenes_hidescenes($uid, $hidescenes_setting);

    if (!empty($remove_tids)) {
        $count_hidescenes = $lang->sprintf($lang->inplayscenes_hide_results, count($remove_tids));;
    }
}

// SUCHERGEBNISSE - POSTVORSCHAU VON VERSTECKEN SZENEN ENTFERNEN
function inplayscenes_search_results_post(){

    global $mybb, $prev, $post, $lang;

    // Einstellungen
    $hide_setting = $mybb->settings['inplayscenes_hide'];

    // zurück, wenn verstecken nicht gegeben ist
    if ($hide_setting != 1) return;

    // ModCP Berechtige (Team) können immer sehen 
    if($mybb->usergroup['canmodcp'] == '1') return;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    $uid = $mybb->user['uid'];
    $remove_tids = inplayscenes_hidescenes($uid);

    if(in_array($post['tid'], $remove_tids)) {
        $prev = $lang->inplayscenes_hide_prev;
    }
}

// PROFIL
function inplayscenes_memberprofile() {

    global $db, $mybb, $lang, $templates, $theme, $memprofile, $inplayscenes_memberprofile;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $color_setting = $mybb->settings['inplayscenes_groupcolor'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hideprofile_setting = $mybb->settings['inplayscenes_hideprofile'];

	// Sprachdatei laden
    $lang->load('inplayscenes');

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    $inplayforums = $inplay_forum.",".$inplay_archive;
    $relevant_forums_inplay = inplayscenes_get_relevant_forums($inplayforums);

    $sideplayforums = $sideplays_forum.",".$sideplays_archive;
    $relevant_forums_sideplay = inplayscenes_get_relevant_forums($sideplayforums);

    // Entfernen der Sideplay-Foren aus den Inplay-Foren
    $relevant_forums_inplay = array_diff($relevant_forums_inplay, $relevant_forums_sideplay);

    if (empty($relevant_forums_inplay)) {
        $relevant_forums_inplay = [0];
    }

    $archiveplayforums = $inplay_archive.",".$sideplays_archive;
    $relevant_forums_archive = inplayscenes_get_relevant_forums($archiveplayforums);

    // klassisch aktiv/beendet
    $active_inplay = inplayscenes_get_relevant_forums($inplay_forum);
    $relevant_forums_active_inplay = array_diff($active_inplay, $relevant_forums_sideplay);

    if (empty($relevant_forums_active_inplay)) {
        $relevant_forums_active_inplay = [0];
    }

    $archive_inplay = inplayscenes_get_relevant_forums($inplay_archive);
    $relevant_forums_archive_inplay = array_diff($archive_inplay, $relevant_forums_sideplay);

    if (empty($relevant_forums_archive_inplay)) {
        $relevant_forums_archive_inplay = [0];
    }

    if ($hide_setting == 1 && $mybb->usergroup['canmodcp'] != '1') {
        if ($hideprofile_setting != 0) {
            if ($hideprofile_setting == 1) { // immer versteckt
                $hidescenes_setting = "";
            } else { // individuell
                $hidescenes_setting = "profile";
            }
            $remove_tids = inplayscenes_hidescenes($mybb->user['uid'], $hidescenes_setting);
            if (!empty($remove_tids)) {
                $remove_sql = "AND i.tid NOT IN (".implode(',', $remove_tids).")";
            } else {
                $remove_sql = "";
            }
        } else {
            $remove_sql = "";
        }
    } else {
        $remove_sql = "";
    }

	// Profil UID
	$profileUID = $mybb->get_input('uid', MyBB::INPUT_INT);

    // Inplayszenen nach Jahren kategoriesiert
    $allinplayscenesyear_query = $db->query("SELECT i.*, t.*, 
    YEAR(i.date) AS year, 
    MONTH(i.date) AS month, 
    DAY(i.date) AS day
    FROM  ".TABLE_PREFIX."inplayscenes i
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (i.tid = t.tid) 
    WHERE (concat(',',i.partners,',') LIKE '%,".$profileUID.",%')
    AND fid IN (".implode(',', $relevant_forums_inplay).")
    AND relevant != 0
    AND visible = 1
    ".$remove_sql."
    ORDER BY i.date DESC
    ");

    $scenes_by_year_month = [];
    while($inplay = $db->fetch_array($allinplayscenesyear_query)) {

        // Leer laufen lassen
        $scenedate = "";
        $status = "";
        $partners_username = "";
        $partners = "";
        $usernames = "";
        $uids = "";
        $partnerusers = "";

        // Mit Infos füllen
        $year = intval($inplay['year']);
        $month = intval($inplay['month']); 
    
        $scenedate = intval($inplay['day']).".";
        $subject = $inplay['subject'];
        $tid = $inplay['tid'];
        $pid = $inplay['firstpost'];

        if (in_array($inplay['fid'], $relevant_forums_archive)) {
            $status = $lang->inplayscenes_memberprofile_status_close;
        } else {
            $status = $lang->inplayscenes_memberprofile_status_active;
        }

        $partners_username = $inplay['partners_username'];
        $partners = $inplay['partners'];
        $usernames = explode(",", $partners_username);
        $uids = explode(",", $partners);
    
        $partners = [];
        foreach ($uids as $key => $uid) {
            $tagged_user = get_user($uid);
            if (!empty($tagged_user)) {
                if ($color_setting == 1) {
                    $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
                } else {
                    $username = $tagged_user['username'];
                }
                $taguser = build_profile_link($username, $uid);
            } else {
                $taguser = $usernames[$key];
            }
            $partners[] = $taguser;
        }
        $partnerusers = implode(" &#x26; ", $partners);

        $postorder = $postorderoptions[$inplay['postorder']];
        
        if ($hide_setting == 1) {
            $userids_array = array_keys(inplayscenes_get_allchars($mybb->user['uid']));
            if (($inplay['hideprofile'] == 1 && $inplay['scenetype'] == 3) && empty(array_intersect($userids_array, $uids)) && $mybb->usergroup['canmodcp'] != '1') {
                $scenelink = "";
            } else {
                $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
            }
        } else {
            $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
        }

        // Variable für einzeln
        $inplayscene = [];
        $inplayscene['scenedate'] = $scenedate;
        $inplayscene['partnerusers'] = $partnerusers;
        $inplayscene['postorder'] = $postorder;
    
        $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
        while ($field = $db->fetch_array($fields_query)) {
    
            // Leer laufen lassen
            $identification = "";
            $value = "";
    
            // Mit Infos füllen
            $identification = $field['identification'];
            $value = $inplay[$identification];
    
            // Einzelne Variabeln
            $inplayscene[$identification] = $value;
        }

        if ($scenetype_setting == 1) {
            $inplayscene['scenetype'] = $sceneoptions[$inplay['scenetype']];
            if ($hide_setting == 0 && $inplay['scenetype'] == 3) {
                $inplayscene['scenetype'] = $sceneoptions[0];
            }
        } else if ($hide_setting == 1) {
            $inplayscene['scenetype'] = $sceneoptions[$inplay['scenetype']];
        } else {
            $inplayscene['scenetype'] = "";
        }

        if ($trigger_setting == 1) {
            $inplayscene['trigger'] = $inplay['trigger_warning'];
        } else {
            $inplayscene['trigger'] = "";
        }

        // Szenen nach Jahr und Monat speichern
        if (!isset($scenes_by_year_month[$year])) {
            $scenes_by_year_month[$year] = [];
        }

        if (!isset($scenes_by_year_month[$year][$month])) {
            $scenes_by_year_month[$year][$month] = '';
        }

        // Szene im Template speichern
        eval("\$scenes_by_year_month[$year][$month] .= \"" . $templates->get("inplayscenes_memberprofile_scenes") . "\";");
    }

    $months = array(
        '01' => $lang->inplayscenes_jan,
        '02' => $lang->inplayscenes_feb,
        '03' => $lang->inplayscenes_mar,
        '04' => $lang->inplayscenes_apr,
        '05' => $lang->inplayscenes_mai,
        '06' => $lang->inplayscenes_jun,
        '07' => $lang->inplayscenes_jul,
        '08' => $lang->inplayscenes_aug,
        '09' => $lang->inplayscenes_sep,
        '10' => $lang->inplayscenes_okt,
        '11' => $lang->inplayscenes_nov,
        '12' => $lang->inplayscenes_dez
    );

    $allinplayscenes_year = '';
    foreach ($scenes_by_year_month as $year => $months_data) {
        $scenes_by_month = '';
        foreach ($months_data as $month => $scenes) {
            $month_str = str_pad($month, 2, '0', STR_PAD_LEFT);
            $monthname = $months[$month_str];
        
            eval("\$scenes_by_month .= \"" . $templates->get("inplayscenes_memberprofile_month") . "\";");
        }

        // Szenen für das Jahr anzeigen
        eval("\$allinplayscenes_year .= \"" . $templates->get("inplayscenes_memberprofile_year") . "\";");
    }

    // Inplayszenen nur gelistet
    $allinplayscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (i.tid = t.tid) 
    WHERE (concat(',',i.partners,',') LIKE '%,".$profileUID.",%')
    AND fid IN (".implode(',', $relevant_forums_inplay).")
    AND relevant != 0
    AND visible = 1
    ".$remove_sql."
    ORDER BY i.date DESC
    ");

    $all_inplplayscenes = "";
    while($allinplay = $db->fetch_array($allinplayscenes_query)) {
        $all_inplplayscenes .= inplayscenes_profile_scene($allinplay, $relevant_forums_archive, 'archiv');
    }

    if (empty($all_inplplayscenes)) {
        eval("\$all_inplplayscenes = \"".$templates->get("inplayscenes_memberprofile_none")."\";");
    }

    // aktive Inplayszenen [nur gelistet]
    $activeinplplayscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (i.tid = t.tid) 
    WHERE (concat(',',i.partners,',') LIKE '%,".$profileUID.",%')
    AND fid IN (".implode(',', $relevant_forums_active_inplay).")
    AND relevant != 0
    AND visible = 1
    ".$remove_sql."
    ORDER BY i.date DESC
    ");

    $active_inplplayscenes = "";
    while($active = $db->fetch_array($activeinplplayscenes_query)) {
        $active_inplplayscenes .= inplayscenes_profile_scene($active, $relevant_forums_archive, '');
    }

    if (empty($active_inplplayscenes)) {
        eval("\$active_inplplayscenes = \"".$templates->get("inplayscenes_memberprofile_none")."\";");
    }

    // archivierte Inplayszenen [nur gelistet]
    $archiveinplplayscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (i.tid = t.tid) 
    WHERE (concat(',',i.partners,',') LIKE '%,".$profileUID.",%')
    AND fid IN (".implode(',', $relevant_forums_archive_inplay).")
    AND relevant != 0
    AND visible = 1
    ".$remove_sql."
    ORDER BY i.date DESC
    ");

    $archive_inplplayscenes = "";
    while($archive = $db->fetch_array($archiveinplplayscenes_query)) {
        $archive_inplplayscenes .= inplayscenes_profile_scene($archive, $relevant_forums_archive, '');
    }

    if (empty($archive_inplplayscenes)) {
        eval("\$archive_inplplayscenes = \"".$templates->get("inplayscenes_memberprofile_none")."\";");
    }

    $allsideplayscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (i.tid = t.tid) 
    WHERE (concat(',',i.partners,',') LIKE '%,".$profileUID.",%')
    AND fid IN (".implode(',', $relevant_forums_sideplay).")
    AND relevant != 0
    AND visible = 1
    ".$remove_sql."
    ");

    $allsideplayscenes = "";
	while($side = $db->fetch_array($allsideplayscenes_query)) {
        $allsideplayscenes .= inplayscenes_profile_scene($side, $relevant_forums_archive, 'archiv');
    }

    if (empty($allsideplayscenes)) {
        eval("\$allsideplayscenes = \"".$templates->get("inplayscenes_memberprofile_none")."\";");
    }

    $allnotrelevantscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (i.tid = t.tid) 
    WHERE (concat(',',i.partners,',') LIKE '%,".$profileUID.",%')
    AND relevant != 1
    AND visible = 1
    ".$remove_sql."
    ");

    $allnotrelevantscenes = "";
	while($not = $db->fetch_array($allnotrelevantscenes_query)) {
        $allnotrelevantscenes .= inplayscenes_profile_scene($not, $relevant_forums_archive);
    }

    if (empty($allnotrelevantscenes)) {
        eval("\$allnotrelevantscenes = \"".$templates->get("inplayscenes_memberprofile_none")."\";");
    }

    eval("\$inplayscenes_memberprofile = \"" . $templates->get("inplayscenes_memberprofile") . "\";");
}

// ÜBERSICHTEN & TEILNEHMENDE KÖNNEN BEARBEITEN & OFFENE SZENEN & PDF
function inplayscenes_misc() {

    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $page;

    // return if the action key isn't part of the input
    if ($mybb->get_input('action', MYBB::INPUT_STRING) !== 'inplayscenes' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'all_inplayscenes' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'do_editinplayscenes' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'inplayscenes_edit' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'add_openscenes' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'update_relevantstatus' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'inplayscenes_pdf' 
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'update_reminderstatus'
    AND $mybb->get_input('action', MYBB::INPUT_STRING) !== 'postingreminder') {
        return;
    }

    // USER ID
    $userID = $mybb->user['uid'];

    if ($userID == 0) {
        error_no_permission();
    }

    // SPRACHDATEI
    $lang->load('inplayscenes');

    $mybb->input['action'] = $mybb->get_input('action');

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $allscene_setting = $mybb->settings['inplayscenes_allscene'];
    $nextuser_setting = $mybb->settings['inplayscenes_nextuser'];
    $playername_setting = $mybb->settings['inplayscenes_playername'];
    $inactivescenes_setting = $mybb->settings['inplayscenes_inactive_scenes'];
    $month_setting = $mybb->settings['inplayscenes_months'];
    $color_setting = $mybb->settings['inplayscenes_groupcolor'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $hidetype_setting = $mybb->settings['inplayscenes_hidetype'];
    $hideprofile_setting = $mybb->settings['inplayscenes_hideprofile'];
    $scenesedit_setting = $mybb->settings['inplayscenes_scenesedit'];

    require_once MYBB_ROOT."inc/class_parser.php";
            
    $parser = new postParser;
    $parser_array = array(
        "allow_html" => 1,
        "allow_mycode" => 1,
        "allow_smilies" => 1,
        "allow_imgcode" => 0,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => 0
    );

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    $months = array(
        '01' => $lang->inplayscenes_jan,
        '02' => $lang->inplayscenes_feb,
        '03' => $lang->inplayscenes_mar,
        '04' => $lang->inplayscenes_apr,
        '05' => $lang->inplayscenes_mai,
        '06' => $lang->inplayscenes_jun,
        '07' => $lang->inplayscenes_jul,
        '08' => $lang->inplayscenes_aug,
        '09' => $lang->inplayscenes_sep,
        '10' => $lang->inplayscenes_okt,
        '11' => $lang->inplayscenes_nov,
        '12' => $lang->inplayscenes_dez
    );

    $allforums = $inplay_forum.",".$sideplays_forum.",".$inplay_archive.",".$sideplays_archive;
    $all_forums = inplayscenes_get_relevant_forums($allforums);
    $activeforums = $inplay_forum.",".$sideplays_forum;
    $active_forums = inplayscenes_get_relevant_forums($activeforums);
    $archiveforums = $inplay_archive.",".$sideplays_archive;
    $archive_forums = inplayscenes_get_relevant_forums($archiveforums);

    $sideplays_forums_active = inplayscenes_get_relevant_forums($sideplays_forum);

    $inplayforums = $inplay_forum.",".$inplay_archive;
    $inplay_forums = inplayscenes_get_relevant_forums($inplayforums);
    $sideplaysforums = $sideplays_archive.",".$sideplays_forum;
    $sideplays_forums = inplayscenes_get_relevant_forums($sideplaysforums);

    // Entfernen der Sideplay-Foren aus den Inplay-Foren
    $only_inplay = array_diff($inplay_forums, $sideplays_forums);

    // SPIELERNAME
    // wenn Zahl => klassisches Profilfeld
    if (!empty($playername_setting)) {
        if (is_numeric($playername_setting)) {
            $playername_fid = "fid".$playername_setting;
        } else {
            $playername_fid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
        }
    } else {
        $playername_fid = "";
    }
    
    // ACCOUNTSWITCHER
    $userids_array = inplayscenes_get_allchars($userID); 

    // ÜBERSICHT EIGENE SZENEN
    if($mybb->input['action'] == "inplayscenes"){

        add_breadcrumb($lang->inplayscenes_user, "misc.php?action=inplayscenes");
    
        // COUNTER
        $scene_all = 0;
        $scene_open = 0;
        $scene_au = 0;
    
        $character_bit = "";
        foreach ($userids_array as $uid => $username) {
    
            // LEER LAUFEN
            $charaID = "";
            $charaname = "";
    
            // MIT INFOS FÜLLEN
            $charaID = $uid;
            $charaname = $username;
            
            if (!empty($mybb->get_input('typeChara'))) {
                $typeChara = $db->escape_string($mybb->get_input('typeChara'));
            } else {
                $typeChara = "date";
            }
            if (!empty($mybb->get_input('sortChara'))) {
                $sortChara = $db->escape_string($mybb->get_input('sortChara'));
            } else {
                $sortChara = "DESC";
            }

            $order_by_clause = "";
            if ($typeChara == 'date') {
                $order_by_clause = "ORDER BY i.date ".$sortChara;
            } elseif ($typeChara == 'lastpost') {
                $order_by_clause = "ORDER BY t.lastpost ".$sortChara;
            }

            // SZENEN VOM CHARAKTER AUSGEBEN
            $character_scenes = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i 
            LEFT JOIN ".TABLE_PREFIX."threads t 
            ON (i.tid = t.tid) 
            WHERE (concat(',',i.partners,',') LIKE '%,".$charaID.",%')
            AND fid IN (".implode(',', $active_forums).")
            AND visible = 1
            ".$order_by_clause."
            ");
            
            // COUNTER
            $all_scenes_character = 0;
            $all_scenes_character_open = 0;
            $auscene_count = 0;
            $inplayscene_count = 0;
            
            $inplay_scene_bit = "";
            $au_scene_bit = "";
    
            while($scene = $db->fetch_array($character_scenes)) {
    
                $scene_all++;
                $all_scenes_character++;
    
                list($year, $month, $day) = explode('-', $scene['date']);
                if ($month_setting == 0) {
                    $scenedate = $day.".".$month.".".$year;
                } else {
                    $scenedate = $day.". ".$months[$month]." ".$year;
                }
            
                $partners_username = $scene['partners_username'];
                $partners = $scene['partners'];
            
                $partnerusernames = explode(",", $partners_username);
                $partneruids = explode(",", $partners);
            
                $partners = [];
                foreach ($partneruids as $key => $partneruid) {
            
                    $tagged_user = get_user($partneruid);
                    if (!empty($tagged_user)) {
                        if ($color_setting == 1) {
                            $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
                        } else {
                            $username = $tagged_user['username'];
                        }
                        $taguser = build_profile_link($username, $partneruid);
                    } else {
                        $taguser = $partnerusernames[$key];
                    }
                    $partners[] = $taguser;
                }
                $partnerusers = implode(" &#x26; ", $partners);
            
                $postorder = $postorderoptions[$scene['postorder']];
                $subject = $scene['subject'];
                $tid = $scene['tid'];
                $pid = $scene['firstpost'];
                $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
            
                // Variable für einzeln
                $inplayscene = [];
                $inplayscene['scenedate'] = $scenedate;
                $inplayscene['partnerusers'] = $partnerusers;
                $inplayscene['postorder'] = $postorder;
                $inplayscene['subject'] = $subject;
                $inplayscene['tid'] = $tid;
                $inplayscene['pid'] = $pid;
                $inplayscene['scenelink'] = $scenelink;
            
                $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
            
                $inplayscenesfields = "";
                while ($field = $db->fetch_array($fields_query)) {

                    // Leer laufen lassen
                    $identification = "";
                    $title = "";
                    $value = "";
                    $allow_html = "";
                    $allow_mybb = "";
                    $allow_img = "";
                    $allow_video = "";
            
                    // Mit Infos füllen
                    $identification = $field['identification'];
                    $title = $field['title'];
                    $allow_html = $field['allow_html'];
                    $allow_mybb = $field['allow_mybb'];
                    $allow_img = $field['allow_img'];
                    $allow_video = $field['allow_video'];

                    $value = inplayscenes_parser_fields($scene[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);
            
                    // Einzelne Variabeln
                    $inplayscene[$identification] = $value;
            
                    if (!empty($value)) {
                        eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_user_scene_fields") . "\";");
                    }
                }
            
                if ($scenetype_setting == 1) {
                    $scenetype = $sceneoptions[$scene['scenetype']]." &#x26; ";
                    $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
                    if ($hide_setting == 0 && $scene['scenetype'] == 3) {
                        $scenetype = $sceneoptions[0]." &#x26; ";
                        $inplayscene['scenetype'] = $sceneoptions[0];
                    }
                } else {
                    $scenetype = "";
                    $inplayscene['scenetype'] = "";
                }
            
                if ($trigger_setting == 1) {
                    $trigger = $scene['trigger_warning'];
                    $inplayscene['trigger'] = $trigger;
            
                    if (!empty($trigger)) {
                        $title = $lang->inplayscenes_fields_trigger;
                        $value = $trigger;
                        eval("\$triggerwarning = \"" . $templates->get("inplayscenes_user_scene_fields") . "\";");
                    } else {
                        $triggerwarning = "";
                    }
                } else {
                    $triggerwarning = "";
                    $inplayscene['trigger'] = "";
                }
            
                // Variable für alles
                eval("\$scene_infos = \"" . $templates->get("inplayscenes_user_scene_infos") . "\";");
    
                // WER IST DRAN
                $key = array_search($scene['lastposteruid'], $partneruids);
                $key = $key + 1;
        
                if(!isset($partneruids[$key])) {
                    $nextChara = $partneruids[0];
                } else {
                    $nextChara = $partneruids[$key];
                }
        
                $next = get_user($nextChara);
                $nextUID = $next['uid'];
                $nextUsername = $next['username'];
    
                if (!empty($playername_setting)) {
                    if (is_numeric($playername_setting)) {
                        $nextPlayername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$nextUID."'"), $playername_fid);
                    } else {
                        $nextPlayername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$nextUID."' AND fieldid = '".$playername_fid."'"), "value");
                    }
                } else {
                    $nextPlayername = $next['username'];
                }
        
                if ($nextUID == $charaID) {
                    $isnext = $lang->inplayscenes_next_you;
                    $scene_open++;
                    $all_scenes_character_open++;
                } else {
                    if ($nextuser_setting == 1) {
                        $isnext = $lang->inplayscenes_next;
                    } elseif ($nextuser_setting == 2) {
                        if (!empty($nextPlayername)) {
                            $isnext = $lang->sprintf($lang->inplayscenes_next_playername, $nextPlayername);
                        } else {
                            $isnext = $lang->sprintf($lang->inplayscenes_next_playername, $nextUsername);
                        }
                    } else {
                        $isnext = $lang->sprintf($lang->inplayscenes_next_playername, $nextUsername);
                    }
                }
    
                $lastpostdate = my_date('relative', $scene['lastpost']);
                $user = get_user($scene['lastposteruid']);
                
                if ($color_setting == 1) {
                    $lastposter = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $scene['lastposteruid']); 
                } else {
                    $lastposter = build_profile_link($user['username'], $scene['lastposteruid']);
                }

                $lastpostlink = "showthread.php?tid=".$tid."&amp;action=lastpost";
                $inplayscene['lastpostlink'] = $lastpostlink;
    
                eval("\$lastpost_bit = \"".$templates->get("inplayscenes_user_scene_last")."\";");
    
                // Szene kategorisieren: Inplay oder AU
                if (in_array($scene['fid'], $sideplays_forums_active)) {
                    $scene_au++;
                    $auscene_count++;
                    eval("\$au_scene_bit .= \"".$templates->get("inplayscenes_user_scene")."\";");
                } else {
                    $inplayscene_count++;
                    eval("\$inplay_scene_bit .= \"".$templates->get("inplayscenes_user_scene")."\";");
                }
            }
    
            // Falls keine Szenen, leere Nachricht anzeigen
            if (empty($inplay_scene_bit)) {
                eval("\$inplay_scene_bit = \"".$templates->get("inplayscenes_user_none")."\";");
            }
            if (empty($au_scene_bit)) {
                eval("\$au_scene_bit = \"".$templates->get("inplayscenes_user_none")."\";");
            }

            // COUNTER
            if ($all_scenes_character == 1) {
                $scene_counter_character = $lang->sprintf($lang->inplayscenes_user_characount, $charaname, $all_scenes_character, $all_scenes_character_open, '');    
            } else {
                $scene_counter_character = $lang->sprintf($lang->inplayscenes_user_characount, $charaname, $all_scenes_character, $all_scenes_character_open, 'n');
            }

            if ($inplayscene_count == 1) {
                $scene_counter_inplay = $lang->sprintf($lang->inplayscenes_user_count_inplay, $inplayscene_count, '');    
            } else {
                $scene_counter_inplay = $lang->sprintf($lang->inplayscenes_user_count_inplay, $inplayscene_count, 'n');  
            }

            if ($auscene_count == 1) {
                $scene_counter_sideplay = $lang->sprintf($lang->inplayscenes_user_count_sideplay, $auscene_count, '');    
            } else {
                $scene_counter_sideplay = $lang->sprintf($lang->inplayscenes_user_count_sideplay, $auscene_count, 'n');  
            }

            // Ausgabe der Szenen in den Kategorien
            eval("\$scene_bit = \"".$templates->get("inplayscenes_user_inplay")."\";");
            eval("\$scene_bit .= \"".$templates->get("inplayscenes_user_au")."\";");

            // POSTING ERINNERUNGEN
            $reminderdays = $db->fetch_field($db->simple_select("users", "inplayscenes_reminder_days", "uid = '".$charaID."'"), "inplayscenes_reminder_days");
            $reminderstatus = $db->fetch_field($db->simple_select("users", "inplayscenes_reminder_status", "uid = '".$charaID."'"), "inplayscenes_reminder_status");
    
            if ($reminderdays > 0) {

                $reminderoptions = [
                    '1' => $lang->inplayscenes_user_reminderstatus_active,
                    '0' => $lang->inplayscenes_user_reminderstatus_deactive
                ];

                $reminder_status = "<a href=\"misc.php?action=update_reminderstatus&amp;uid=".$charaID."\">".$reminderoptions[$reminderstatus]."</a>";

            } else {
                $reminder_status = "";
            }

            eval("\$character_bit .= \"".$templates->get("inplayscenes_user_character")."\";");
        }

        // POSTING ERINNERUNGEN + BENACHRICHTIGUNGS EINSTELLUNGEN
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
            $selected_type = $db->fetch_field($db->simple_select("users", "inplayscenes_notification", "uid = '".$userID."'"), "inplayscenes_notification");
            $type_radiobuttons = inplayscenes_generate_radiobuttons_notification($selected_type);
            eval("\$notification_setting = \"".$templates->get("inplayscenes_usersettings_notification")."\";");
        } else {
            $notification_setting = "";
        }
        $reminder_days = $db->fetch_field($db->simple_select("users", "inplayscenes_reminder_days", "uid = '".$userID."'"), "inplayscenes_reminder_days");
        eval("\$user_settings = \"".$templates->get("inplayscenes_usersettings")."\";");

        // USER EINSTELLUNGEN SPEICHERN
        if(!empty($mybb->input['do_userscenesettings']) && $mybb->request_method == "post"){

            $update_reminder = array(
                'inplayscenes_notification' => (int)$mybb->get_input('notification_type'),
                'inplayscenes_reminder_days' => (int)$mybb->get_input('reminder_days')    
            );

            // ACCOUNTSWITCHER
            foreach ($userids_array as $uid => $username) {
                $db->update_query("users", $update_reminder, "uid='".$uid."'");
            }
            redirect("misc.php?action=inplayscenes", $lang->inplayscenes_redirect_settings);
        }

        if ($scene_all != 1) {
            $scene_summary = $lang->sprintf($lang->inplayscenes_user_scenecount, $scene_all, $scene_au, $scene_open, 'n');
        } else {
            $scene_summary = $lang->sprintf($lang->inplayscenes_user_scenecount, $scene_all, $scene_au, $scene_open, 'n');
        }
    
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("inplayscenes_user")."\";");
        output_page($page);
        die();
    }

    // ÜBERSICHT VON ALLEN SZENEN IM BOARD
    if($mybb->input['action'] == "all_inplayscenes"){

        if ($allscene_setting == 0) {
            error_no_permission();
        }

        add_breadcrumb($lang->inplayscenes_overview, "misc.php?action=all_inplayscenes");

        // FILTER
        $where = []; 
        $charactername_placeholder = $lang->inplayscenes_search_character;
        $playername_placeholder = $lang->inplayscenes_search_player;

        if ($scenetype_setting == 1) {

            if (!empty($mybb->get_input('scenetype'))) {
                $scenetype_select = (int)$mybb->get_input('scenetype');
            } else {
                $scenetype_select = "-1";
            }
            $scenetype_multipage = "&scenetype=".$scenetype_select;

            // Szeneneinstellung-Filter
            if ($scenetype_select == '0') { // private Szene
                $where[] = "i.scenetype = 0";
            } elseif ($scenetype_select == '1') { // nach Absprache
                $where[] = "i.scenetype = 1";
            } elseif ($scenetype_select == '2') { // öffentliche Szene
                $where[] = "i.scenetype = 2";
            }

            eval("\$scenetype_filter = \"".$templates->get("inplayscenes_overview_scenetype_filter")."\";");
            $scenetype_input = "<input type=\"hidden\" name=\"scenetype\" value=\"".$scenetype_select."\">";
        } else {
            $scenetype_input = "";
            $scenetype_filter = "";
            $scenetype_multipage = "";
        }

        if (!empty($playername_setting)) {
            if (!empty($mybb->get_input('playername'))) {
                $playername = $db->escape_string($mybb->get_input('playername'));
            } else {
                $playername = "";
            }
            eval("\$player_filter = \"".$templates->get("inplayscenes_overview_player_filter")."\";");
        } else {
            $player_filter = ""; 
            $playername = "";
        }

        if (!empty($mybb->get_input('scenestatus'))) {
            $scenestatus = $db->escape_string($mybb->get_input('scenestatus'));
        } else {
            $scenestatus = "all";
        }
        if (!empty($mybb->get_input('area'))) {
            $area = $db->escape_string($mybb->get_input('area'));
        } else {
            $area = "all_area";
        }
        if (!empty($mybb->get_input('postorder'))) {
            $postorder_input = (int)$mybb->get_input('postorder');
        } else {
            $postorder_input = "-1";
        }
        if (!empty($mybb->get_input('charactername'))) {
            $charactername = $db->escape_string($mybb->get_input('charactername'));
        } else {
            $charactername = "";
        }

        // Basis-Arrays für die Kombinationen
        $active_inplay = array_intersect($active_forums, $only_inplay);
        $active_au = array_intersect($active_forums, $sideplays_forums);
        $archive_inplay = array_intersect($archive_forums, $only_inplay);
        $archive_au = array_intersect($archive_forums, $sideplays_forums);

        // Szenenstatus- und Bereichs-Filter
        $allowed_fids = [];
        if ($scenestatus == 'active' && $area == 'inplayarea') {
            $allowed_fids = $active_inplay;
        } elseif ($scenestatus == 'active' && $area == 'auarea') {
            $allowed_fids = $active_au;
        } elseif ($scenestatus == 'archive' && $area == 'inplayarea') {
            $allowed_fids = $archive_inplay;
        } elseif ($scenestatus == 'archive' && $area == 'auarea') {
            $allowed_fids = $archive_au;
        } elseif ($scenestatus == 'active') {
            $allowed_fids = $active_forums;
        } elseif ($scenestatus == 'archive') {
            $allowed_fids = $archive_forums;
        } elseif ($area == 'inplayarea') {
            $allowed_fids = $only_inplay;
        } elseif ($area == 'auarea') {
            $allowed_fids = $sideplays_forums;
        } else {
            $allowed_fids = $all_forums;
        }

        $where[] = "t.fid IN (".implode(',', $allowed_fids).")";

        // Postreihenfolge-Filter
        if ($postorder_input == '1') { // feste Reihenfolgen
            $where[] = "i.postorder = 1";
        } elseif ($postorder_input == '0') { // keine feste Reihenfolge
            $where[] = "i.postorder = 0";
        }

        // Charakter
        if (!empty($charactername)) {
            $characterUid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '".$charactername."'"), "uid");
            $where[] = "(concat(',',i.partners,',') LIKE '%,".$characterUid.",%')";
        }

        // Spieler
        $player_conditions = [];
        if (!empty($playername)) {

            if (is_numeric($playername_setting)) {
                $player_query = $db->query("SELECT * FROM ".TABLE_PREFIX."userfields uf
                WHERE ".$playername_fid." = '".$playername."'
                ORDER BY (SELECT username FROM ".TABLE_PREFIX."users u WHERE u.uid = uf.ufid)
                ");
    
                while ($player = $db->fetch_array($player_query)) {
                    $player_conditions[] = "(concat(',',i.partners,',') LIKE '%,".$player['ufid'].",%')";
                }
            } else {
                $player_query = $db->query("SELECT * FROM ".TABLE_PREFIX."application_ucp_userfields uf
                WHERE id = '".$playername_fid."'
                AND value = '".$playername."'
                ORDER BY (SELECT username FROM ".TABLE_PREFIX."users u WHERE u.uid = uf.ufid)
                ");
    
                while ($player = $db->fetch_array($player_query)) {
                    $player_conditions[] = "(concat(',',i.partners,',') LIKE '%,".$player['uid'].",%')";
                }
            }

            if (!empty($player_conditions)) {
                $where[] = "(" . implode(" OR ", $player_conditions) . ")";
            }
        }

        // Zusammenfügen der Bedingungen
        $where_sql = '';
        if (!empty($where)) {
            $where_sql = "WHERE " . implode(" AND ", $where);
        }

        // Sortierung
        if(!empty($mybb->get_input('type'))) {
            $type = $db->escape_string($mybb->get_input('type'));
        } else {
            $type = "date";
        }
        if(!empty($mybb->get_input('sort'))) {
            $sort = $db->escape_string($mybb->get_input('sort'));
        } else {
            $sort = "ASC";
        }

        $order_by_sql = "ORDER BY ";
        if ($type == 'lastpost') {
            $order_by_sql .= "t.lastpost";
        } else {
            $order_by_sql .= "i.date";
        }

        if ($sort == 'DESC') {
            $order_by_sql .= " DESC";
        } else {
            $order_by_sql .= " ASC";
        }

        if ($hide_setting == 1 && $mybb->usergroup['canmodcp'] != '1') {
            if ($hidetype_setting != 0) {
                if ($hidetype_setting == 1) { // immer versteckt
                    $hidescenes_setting = "";
                } else { // individuell
                    $hidescenes_setting = "forumdisplay";
                }

                $remove_tids = inplayscenes_hidescenes($mybb->user['uid'], $hidescenes_setting);
                if (!empty($remove_tids)) {
                    $remove_sql = "AND i.tid NOT IN (".implode(',', $remove_tids).")";
                    $hide_counter = $lang->sprintf($lang->inplayscenes_overview_counter_hide, count($remove_tids));
                } else {
                    $remove_sql = "";
                    $hide_counter = "";
                }
            } else {
                $remove_sql = "";
                $hide_counter = "";
            }
        } else {
            $remove_sql = "";
            $hide_counter = "";
        }
            
        // COUNTER
        $all_scenes = $db->num_rows($db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i 
        LEFT JOIN ".TABLE_PREFIX."threads t 
        ON (i.tid = t.tid)  
        WHERE visible = 1
        "));
        $all_scenes_filter = $db->num_rows($db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i 
        LEFT JOIN ".TABLE_PREFIX."threads t ON (i.tid = t.tid) 
        ".$where_sql."
        ".$remove_sql."
        AND visible = 1
        "));

        $scene_counter = $lang->sprintf($lang->inplayscenes_overview_counter, $all_scenes_filter, $all_scenes, $hide_counter);
    
        // MULTIPAGE
        $perpage = 20;
        $input_page = $mybb->get_input('page', MyBB::INPUT_INT);
        if($input_page) {
            $start = ($input_page-1) *$perpage;
        }
        else {
            $start = 0;
            $input_page = 1;
        }
        $end = $start + $perpage;
        $lower = $start+1;
        $upper = $end;
        if($upper > $all_scenes_filter) {
            $upper = $all_scenes_filter;
        }
        
        $scenestatus_multipage = "&scenestatus=".$scenestatus;
        $area_multipage = "&area=".$area;
        $postorder_multipage = "&postorder=".$postorder_input;
        $charactername_multipage = "&charactername=".$charactername;
        $playername_multipage = "&playername=".$playername;

        $page_url = htmlspecialchars_uni("misc.php?action=all_inplayscenes".$scenestatus_multipage.$area_multipage.$postorder_multipage.$scenetype_multipage.$charactername_multipage.$playername_multipage);

        $multipage = multipage($all_scenes_filter, $perpage, $input_page, $page_url);
        $multipage_sql = "LIMIT ".$start.", ".$perpage;
        
        $scenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i 
        LEFT JOIN ".TABLE_PREFIX."threads t ON (i.tid = t.tid) 
        ".$where_sql."
        ".$remove_sql."
        AND visible = 1
        ".$order_by_sql."
        ".$multipage_sql."
        ");
    
        $scenes_bit = "";
        while($scene = $db->fetch_array($scenes_query)) {
    
            list($year, $month, $day) = explode('-', $scene['date']);
            if ($month_setting == 0) {
                $scenedate = $day.".".$month.".".$year;
            } else {
                $scenedate = $day.". ".$months[$month]." ".$year;
            }
        
            $partners_username = $scene['partners_username'];
            $partners = $scene['partners'];
        
            $partnerusernames = explode(",", $partners_username);
            $partneruids = explode(",", $partners);
        
            $partners = [];
            foreach ($partneruids as $key => $partneruid) {
        
                $tagged_user = get_user($partneruid);
                if (!empty($tagged_user)) {
                    if ($color_setting == 1) {
                        $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
                    } else {
                        $username = $tagged_user['username'];
                    }
                    $taguser = build_profile_link($username, $partneruid);
                } else {
                    $taguser = $partnerusernames[$key];
                }
                $partners[] = $taguser;
            }
            $partnerusers = implode(" &#x26; ", $partners);
        
            $postorder = $postorderoptions[$scene['postorder']];
            $subject = $scene['subject'];
            $tid = $scene['tid'];
            $pid = $scene['firstpost'];

            if ($hide_setting == 1) {
                $userids_array = array_keys(inplayscenes_get_allchars($mybb->user['uid']));
                if (($scene['hideprofile'] == 1 && $scene['scenetype'] == 3) && empty(array_intersect($userids_array, $partneruids)) && $mybb->usergroup['canmodcp'] != '1') {
                    $scenelink = "";
                } else {
                    $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
                }
            } else {
                $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
            }
        
            // Variable für einzeln
            $inplayscene = [];
            $inplayscene['scenedate'] = $scenedate;
            $inplayscene['partnerusers'] = $partnerusers;
            $inplayscene['postorder'] = $postorder;
            $inplayscene['subject'] = $subject;
            $inplayscene['tid'] = $tid;
            $inplayscene['pid'] = $pid;
        
            $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
        
            $inplayscenesfields = "";
            while ($field = $db->fetch_array($fields_query)) {

                // Leer laufen lassen
                $identification = "";
                $title = "";
                $value = "";
                $allow_html = "";
                $allow_mybb = "";
                $allow_img = "";
                $allow_video = "";
        
                // Mit Infos füllen
                $identification = $field['identification'];
                $title = $field['title'];
                $allow_html = $field['allow_html'];
                $allow_mybb = $field['allow_mybb'];
                $allow_img = $field['allow_img'];
                $allow_video = $field['allow_video'];
        
                $value = inplayscenes_parser_fields($scene[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);
        
                // Einzelne Variabeln
                $inplayscene[$identification] = $value;
        
                if (!empty($value)) {
                    eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_overview_scene_fields") . "\";");
                }
            }
        
            if ($scenetype_setting == 1) {
                $scenetype = $sceneoptions[$scene['scenetype']]."<br>";
                $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
                if ($hide_setting == 0 && $scene['scenetype'] == 3) {
                    $scenetype = $sceneoptions[0]."<br>";
                    $inplayscene['scenetype'] = $sceneoptions[0];
                }
            } else if ($hide_setting == 1) {
                $scenetype = $sceneoptions[$scene['scenetype']]."<br>";
                $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
            } else {
                $inplayscene['scenetype'] = "";
            }
        
            if ($trigger_setting == 1) {
                $trigger = $scene['trigger_warning'];
                $inplayscene['trigger'] = $trigger;
        
                if (!empty($trigger)) {
                    $title = $lang->inplayscenes_fields_trigger;
                    $value = $trigger;
                    eval("\$triggerwarning = \"" . $templates->get("inplayscenes_overview_scene_fields") . "\";");
                } else {
                    $triggerwarning = "";
                }
            } else {
                $triggerwarning = "";
                $inplayscene['trigger'] = "";
            }
    
            $lastpostdate = my_date('relative', $scene['lastpost']);
            if ($color_setting == 1) {
                $user = get_user($scene['lastposteruid']);
                $lastposter = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $scene['lastposteruid']); 
            } else {
                $lastposter = build_profile_link($user['username'], $scene['lastposteruid']);
            }
            $lastpostlink = "showthread.php?tid=".$tid."&amp;action=lastpost";
            $inplayscene['lastpostlink'] = $lastpostlink;

            if (in_array($scene['fid'], $sideplays_forums)) {
                $AUscene = $lang->inplayscenes_overview_au."<br>";
                $inplayscene['AUscene'] = $lang->inplayscenes_overview_au;
            } else {
                $AUscene = "";
                $inplayscene['AUscene'] = "";
            }

            eval("\$scenes_bit .= \"".$templates->get("inplayscenes_overview_scene")."\";");
        }

        eval("\$sort_bit = \"".$templates->get("inplayscenes_overview_scene_sort")."\";");

        if (empty($scenes_bit)) {
            eval("\$scenes_bit = \"".$templates->get("inplayscenes_overview_scene_none")."\";");
            $sort_bit = "";
        }
    
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("inplayscenes_overview")."\";");
        output_page($page);
        die();
    }

    // SZENEN-INFOSBEARBEITEN - Speichern
    if($mybb->input['action'] == "do_editinplayscenes"){

        $tid =  $mybb->get_input('tid', MyBB::INPUT_INT);

        $inplayscenes_edit_error = array();

        if (!$mybb->get_input('characters')) {
            $inplayscenes_edit_error[] = $lang->inplayscenes_validate_characters;  
        }
    
        if (!$mybb->get_input('date')) {
            $inplayscenes_edit_error[] = $lang->inplayscenes_validate_date;
        }
    
        // Abfrage der Felder, die als erforderlich markiert sind
        $fields_query = $db->query("SELECT identification, title, type FROM ".TABLE_PREFIX."inplayscenes_fields WHERE required = 1 AND edit = 1");
    
        while ($field = $db->fetch_array($fields_query)) {
            
            if ($field['type'] == "multiselect" || $field['type'] == "checkbox") {
                $field_value = $mybb->get_input($field['identification'], MyBB::INPUT_ARRAY);
            } else {
                $field_value = $mybb->get_input($field['identification']);
            }
    
            if (empty($field_value)) {
                $error_message = $lang->sprintf($lang->inplayscenes_validate_field, $field['title']);
                $inplayscenes_edit_error[] = $error_message;
            }
        }
        
        if(empty($inplayscenes_edit_error)) {

            $characters = explode(",", $mybb->get_input('characters'));
            $characters = array_map("trim", $characters);	
        
            $characters_uids = array();
            foreach($characters as $key => $partner) {
                $characters_uid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '".$db->escape_string($partner)."'"), "uid");
                if (!empty($characters_uid)) {
                    $characters_uids[] = $characters_uid;
                } else {
                    $characters_uids[] = '';
                }
            }
            $charactersUids = implode(",", $characters_uids);

            // Nullen Abfangen für Jahre vor 1000
            $dateInput = $mybb->get_input('date');
            list($year, $month, $day) = explode('-', $dateInput);
            $year = intval($year);
            $formattedDate = $year.'-'. $month.'-'.$day;

            if ($hide_setting == 1) {
                if ($hidetype_setting == 0) {
                    $hidetype = 1;
                } else if ($hidetype_setting == 1) {
                    $hidetype = 2;
                } else if ($hidetype_setting == 2) {
                    $hidetype = (int)$mybb->get_input('hidetype');
                }
                if ($hideprofile_setting == 0) {
                    $hideprofile = 1;
                } else if ($hideprofile_setting == 1) {
                    $hideprofile = 2;
                } else if ($hideprofile_setting == 2) {
                    $hideprofile = (int)$mybb->get_input('hideprofile');
                }
            } else {
                $hidetype = 0;
                $hideprofile = 0;
            }
        
            $update_scene = array(
                'partners' => $charactersUids,
                'partners_username' => $db->escape_string($mybb->get_input('characters')),
                'date' => $db->escape_string($formattedDate),
                'trigger_warning' => $db->escape_string($mybb->get_input('trigger')),
                'scenetype' => (int)$mybb->get_input('scenetype'),
                'hidetype' => $hidetype,
                'hideprofile' => $hideprofile,
                'postorder' => (int)$mybb->get_input('postorder')
            );
        
            // Abfrage der individuellen Felder
            $fields_query = $db->query("SELECT identification, type FROM ".TABLE_PREFIX."inplayscenes_fields WHERE edit = 1");
            
            while ($field = $db->fetch_array($fields_query)) {
                $identification = $field['identification'];
                $type = $field['type'];
            
                if ($type == 'multiselect' || $type == 'checkbox') {
                    $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
                    $value = implode(",", array_map('trim', $value));
                } else {
                    $value = $mybb->get_input($identification);
                }
            
                $update_scene[$identification] = $db->escape_string($value);
            }
        
            $db->update_query("inplayscenes", $update_scene, "tid='".$tid."'");
            redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_edit);
        } else {
            $mybb->input['action'] = "inplayscenes_edit";
            $inplayscenes_edit_error = inline_error($inplayscenes_edit_error);
        }
    }

    // SZENEN-INFOS BEARBEITEN
    if($mybb->input['action'] == "inplayscenes_edit"){

        add_breadcrumb($lang->inplayscenes_editscene, "misc.php?action=inplayscenes_edit"); 

        $tid =  $mybb->get_input('tid', MyBB::INPUT_INT);

        if ($scenesedit_setting == 0) {
            $partnersUID = explode(",", $db->fetch_field($db->simple_select("inplayscenes", "partners", "tid = '".$tid."'"), "partners"));
            if (!in_array($mybb->user['uid'], $partnersUID)) {
                error_no_permission();
            }
        } else {
            $partnersUID = explode(",", $db->fetch_field($db->simple_select("inplayscenes", "partners", "tid = '".$tid."'"), "partners"));
            $hasPermission = false;

            foreach ($userids_array as $uid => $username) {
                if (in_array($uid, $partnersUID)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                error_no_permission();
            }
        }

        if(!isset($inplayscenes_edit_error)){
            $inplayscenes_edit_error = "";

            // Infos aus der DB ziehen
            $draft = $db->fetch_array($db->simple_select('inplayscenes', '*', 'tid = '.$tid));

            list($year, $month, $day) = explode('-', $draft['date']);
            $draft['date'] = sprintf('%04d-%02d-%02d', $year, $month, $day); 
        } else {
            if ($hide_setting == 1) {
                if ($hidetype_setting == 0) {
                    $hidetype = 1;
                } else if ($hidetype_setting == 1) {
                    $hidetype = 2;
                } else if ($hidetype_setting == 2) {
                    $hidetype = (int)$mybb->get_input('hidetype');
                }
                if ($hideprofile_setting == 0) {
                    $hideprofile = 1;
                } else if ($hideprofile_setting == 1) {
                    $hideprofile = 2;
                } else if ($hideprofile_setting == 2) {
                    $hideprofile = (int)$mybb->get_input('hideprofile');
                }
            } else {
                $hidetype = 0;
                $hideprofile = 0;
            }

            $draft = array(
                'partners_username' => $db->escape_string($mybb->get_input('characters')),
                'date' => $db->escape_string($mybb->get_input('date')),
                'trigger_warning' => $db->escape_string($mybb->get_input('trigger')),
                'scenetype' => (int)$mybb->get_input('scenetype'),
                'hidetype' => $hidetype,
                'hideprofile' => $hideprofile,
                'postorder' => (int)$mybb->get_input('postorder')
            );
        
            // Abfrage der individuellen Felder
            $fields_query = $db->query("SELECT identification, type FROM ".TABLE_PREFIX."inplayscenes_fields WHERE edit = 1");
            
            while ($field = $db->fetch_array($fields_query)) {
                $identification = $field['identification'];
                $type = $field['type'];
            
                if ($type == 'multiselect' || $type == 'checkbox') {
                    $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
                    $value = implode(",", array_map('trim', $value));
                } else {
                    $value = $mybb->get_input($identification);
                }
            
                $draft[$identification] = $db->escape_string($value);
            }
        }
    
        $partners_username = explode(",", $draft['partners_username']);
        $usernames = [];
        foreach ($partners_username as $username) {
            $usernames[] = $username;
        }

        $characters = implode(",", $usernames);
        $postorder_value = $draft['postorder'];
        $date = $draft['date'];

        $triggerwarning = $draft['trigger_warning'];
        $scenetype_value = $draft['scenetype'];
        $hidetype_value = $draft['hidetype'];
        $hideprofile_value = $draft['hideprofile'];

        $own_inplayscenesfields = inplayscenes_generate_fields($draft, null, true, 'editscene');

        $postorder_select = inplayscenes_generate_postorder_select($postorder_value);
    
        if ($scenetype_setting == 1 || $hide_setting == 1) {
            $scenetype_select = inplayscenes_generate_scenetype_select($scenetype_value);
        } else {
            $scenetype_select = "";
        }

        if ($hide_setting == 1) {
            if ($hidetype_setting == 2) {
                $hidetype_select = inplayscenes_generate_hidetype_select($hidetype_value);
            } else {
                if ($hidetype_setting == 0) {
                    $hidetype_select = $lang->inplayscenes_fields_hidetype_info;
                } else {
                    $hidetype_select = $lang->inplayscenes_fields_hidetype_all;
                }
            }
            if ($hideprofile_setting == 2) {
                $hideprofile_select = inplayscenes_generate_hideprofile_select($hideprofile_value);
            } else {
                if ($hideprofile_setting == 0) {
                    $hideprofile_select = $lang->inplayscenes_fields_hideprofile_info;
                } else {
                    $hideprofile_select = $lang->inplayscenes_fields_hideprofile_all;
                }
            }
            if ($hidetype_setting == 2 && $hideprofile_setting == 2) {
                $inplayscenes_fields_hide_desc = $lang->inplayscenes_fields_hide_desc;
            } else {
                $inplayscenes_fields_hide_desc = $lang->inplayscenes_fields_hide_team;
            }
        } else {
            $hidetype_select = "";
            $hideprofile_select = "";
        }
    
        if ($trigger_setting == 1) { 
            $title = $lang->inplayscenes_fields_trigger;
            $description = $lang->inplayscenes_fields_trigger_desc;
            $code = inplayscenes_generate_input_field('trigger', 'textarea', $triggerwarning, '');
            eval("\$trigger_warning = \"".$templates->get("inplayscenes_editscene_fields")."\";");
        } else {
            $trigger_warning = "";
        }

        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("inplayscenes_editscene")."\";");
        output_page($page);
        die();
    }

    // OFFENE SZENE HINZUFÜGEN
    if($mybb->input['action'] == "add_openscenes"){

        if ($scenetype_setting == 0) {
            redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_deactive);
        }

        $tid =  $mybb->get_input('tid', MyBB::INPUT_INT);

        $add = $db->fetch_array($db->simple_select('inplayscenes', 'partners, partners_username', 'tid = '.$tid));

        $partnersUID = explode(",", $add['partners']);
        if (in_array($mybb->user['uid'], $partnersUID)) {
            redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_open_take);
        }

        // Man selbst muss man sich nicht eintragen
        $ownuid = $mybb->user['uid'];
        $ownusername = $mybb->user['username'];

        $update_partner = array(
            'partners' => $add['partners'].",".$ownuid,
            'partners_username' => $add['partners_username'].",".$ownusername
        );
        
        // DAMIT DIE PN SACHE FUNKTIONIERT
        require_once MYBB_ROOT."inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();

        $ownip = $db->fetch_field($db->query("SELECT ip FROM ".TABLE_PREFIX."sessions WHERE uid = '".$mybb->user['uid']."'"), "ip");

        $thread = get_thread($tid);

        $playername_setting = $mybb->settings['inplayscenes_playername'];
        if (!empty($playername_setting)) {
            if (is_numeric($playername_setting)) {
                $playername_fid = "fid".$playername_setting;
            } else {
                $playername_fid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
            }
        } else {
            $playername_fid = "";
        }
        // MyAlerts möglich
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    
            // Jedem Partner
            foreach ($partnersUID as $CharaUid) {
    
                $notification = $db->fetch_field($db->simple_select("users", "inplayscenes_notification", "uid = '".$CharaUid."'"), 'inplayscenes_notification');
             
                if ($notification == 1) { // Alert

                    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                        $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('inplayscenes_alert_openadd');
                        if ($alertType != NULL && $alertType->getEnabled()) {
                            $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$CharaUid, $alertType, (int)$mybb->user['uid']);
                            $alert->setExtraDetails([
                                'username' => $mybb->user['username'],
                                'from' => $mybb->user['uid'],
                                'tid' => $thread['tid'],
                                'pid' => $thread['firstpost'],
                                'subject' => $thread['subject'],
                            ]);
                            MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);   
                        }
                    }

                } else { // PN      
                    if (!empty($playername_setting)) {
                        if (is_numeric($playername_setting)) {
                            $playername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$CharaUid."'"), $playername_fid);
                        } else {
                            $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$CharaUid."' AND fieldid = '".$playername_fid."'"), "value");
                        }
                    } else {
                        $playername = "";
                    }
                    if (!empty($playername)) {
                        $Playername = $playername;
                    } else {
                        $Playername = get_user($CharaUid)['username'];
                    }

                    $pm_message = $lang->sprintf($lang->inplayscenes_pm_openadd_message, $Playername, $mybb->user['username'], 'showthread.php?tid='.$tid.'&pid='.$thread['firstpost'].'#pid'.$thread['firstpost'], $thread['subject']);
                    $pm_change = array(
                        "subject" => $lang->inplayscenes_pm_openadd_subject,
                        "message" => $parser->parse_message($pm_message, $parser_array),
                        "fromid" => $mybb->user['uid'], // von wem kommt diese
                        "toid" => $CharaUid, // an wen geht diese
                        "icon" => "0",
                        "do" => "",
                        "pmid" => "",
                        "ipaddress" => $ownip
                    );
            
                    $pm_change['options'] = array(
                        'signature' => '1',
                        'disablesmilies' => '0',
                        'savecopy' => '0',
                        'readreceipt' => '0',
                    );
                    
                    $pmhandler->set_data($pm_change);
                    if (!$pmhandler->validate_pm())
                        return false;
                    else {
                        $pmhandler->insert_pm();
                    }
                }
            }
        } else { // PN ausschließlich

            // Jedem Partner
            foreach ($characters_uids as $CharaUid) { 
                if (!empty($playername_setting)) {
                    if (is_numeric($playername_setting)) {
                        $playername = $db->fetch_field($db->simple_select("userfields", $playername_fid ,"ufid = '".$CharaUid."'"), $playername_fid);
                    } else {
                        $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "uid = '".$CharaUid."' AND fieldid = '".$playername_fid."'"), "value");
                    }
                } else {
                    $playername = "";
                }
                if (!empty($playername)) {
                    $Playername = $playername;
                } else {
                    $Playername = get_user($CharaUid)['username'];
                }

                $pm_message = $lang->sprintf($lang->inplayscenes_pm_openadd_message, $Playername, $mybb->user['username'], 'showthread.php?tid='.$tid.'&pid='.$thread['firstpost'].'#pid'.$thread['firstpost'], $thread['subject']);
                $pm_change = array(
                    "subject" => $lang->inplayscenes_pm_openadd_subject,
                    "message" => $parser->parse_message($pm_message, $parser_array),
                    "fromid" => $mybb->user['uid'], // von wem kommt diese
                    "toid" => $CharaUid, // an wen geht diese
                    "icon" => "0",
                    "do" => "",
                    "pmid" => "",
                    "ipaddress" => $ownip
                );
        
                $pm_change['options'] = array(
                    'signature' => '1',
                    'disablesmilies' => '0',
                    'savecopy' => '0',
                    'readreceipt' => '0',
                );
                // $pmhandler->admin_override = true;
                $pmhandler->set_data($pm_change);
                if (!$pmhandler->validate_pm())
                    return false;
                else {
                    $pmhandler->insert_pm();
                }
            }
        }
        
        $db->update_query("inplayscenes", $update_partner, "tid='".$tid."'");
        redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_open_add);
    }

    // SZENE ALS UNRELAVANT EINSTUFEN
    if($mybb->input['action'] == "update_relevantstatus"){

        $tid = $mybb->get_input('tid', MyBB::INPUT_INT);

        $relevant = $db->fetch_array($db->simple_select('inplayscenes', 'partners', 'tid = '.$tid));

        $characters = explode(",", $relevant['partners']);
        $characters = array_map("trim", $characters);	
    
        $characters_uids = array();
        foreach($characters as $partner) {
            $characters_uid = get_user($partner)['uid'];
            if (!empty($characters_uid)) {
                $characters_uids[] = $characters_uid;
            }
        }

        if ($characters_uids[0] != $mybb->user['uid']) {
            redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_relevantstatus_permissions);
        }

        if (count($characters_uids) == 1) {

            $update_relevant = array(
                'relevant' => 0
            );
            
            $db->update_query("inplayscenes", $update_relevant, "tid='".$tid."'");
            redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_relevantstatus_update);
        } else {
            redirect("showthread.php?tid=".$tid, $lang->inplayscenes_redirect_relevantstatus_notalone);
        }
    }

    // PDF GENERIEREN
    if($mybb->input['action'] == "inplayscenes_pdf") {

        $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
        $pid = $mybb->get_input('pid', MyBB::INPUT_INT);

        $ownuid = $mybb->user['uid'];
        $ownusername = $mybb->user['username'];

        if (!empty($pid) AND empty($tid)) { // einzelner Post
            $scenetid = $db->fetch_field($db->simple_select("posts", "tid", "pid = '".$pid."'"), "tid");

            $posts_query = $db->query("SELECT message, username FROM ".TABLE_PREFIX."posts
            WHERE pid = ".$pid."
            ");
            while ($post = $db->fetch_array($posts_query)) {
                
                // Leer laufen lassen
                $username = "";
                $message = "";
                
                // Mit Infos füllen
                $username = $post['username'];
                $message = $parser->parse_message($post['message'], $parser_array);

                $inhalt = "<b>".$username.":</b><br><br><span style=\"text-align:justify\">".$message."</span><br><br>";
            }

        } else if(empty($pid) AND !empty($tid)) { // ganze Szene
            $scenetid = $tid;
            $inhalt = "";

            $posts_query = $db->query("SELECT message, username FROM ".TABLE_PREFIX."posts
            WHERE visible = 1
            AND tid = ".$tid."
            ORDER BY dateline ASC, pid ASC
            ");
            while ($post = $db->fetch_array($posts_query)) {
                
                // Leer laufen lassen
                $username = "";
                $message = "";
                
                // Mit Infos füllen
                $username = $post['username'];
                $message = $parser->parse_message($post['message'], $parser_array);

                $inhalt .= "<b>".$username.":</b><br><br><span style=\"text-align:justify\">".$message."</span><br><br>";
            }
        }

        $sceneinfo = inplayscenes_pdf_fields($scenetid);

        $pdfAuthor = $ownusername;
        $pdfName = get_thread($scenetid)['subject'].".pdf";
        $subject = get_thread($scenetid)['subject'];
        
        //////////////////////////// Inhalt des PDFs als HTML-Code \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 
        // Erstellung des HTML-Codes. Dieser HTML-Code definiert das Aussehen eures PDFs.
        // tcpdf unterstützt recht viele HTML-Befehle. Die Nutzung von CSS ist allerdings
        // stark eingeschränkt.
        $html = '<div style="text-align: center; font-weight: bold; font-size: 20pt;">'.$subject.'</div>
        <div style="text-align: center; font-size: 10pt;">
        '.$sceneinfo.'
        </div>
        <div>'.$inhalt.'</div>';
 
        //////////////////////////// Erzeugung eures PDF Dokuments \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 
        // TCPDF Library laden
        require_once('tcpdf/tcpdf.php');
        
        // Erstellung des PDF Dokuments       
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
 
        // Dokumenteninformationen
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($pdfAuthor);
        $pdf->SetTitle($subject);       
        $pdf->SetSubject($subject);

        // Header und Footer Informationen
        $pdf->setPrintHeader(false);     
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->setFooterData([0,0,0], [255,255,255]);
 
        // Auswahl des Font        
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Auswahl der MArgins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);       
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
 
        // Automatisches Autobreak der Seiten       
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
 
        // Image Scale 
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Schriftart        
        $pdf->SetFont('helvetica', '', 10);

        // Neue Seite       
        $pdf->AddPage();
 
        // Fügt den HTML Code in das PDF Dokument ein       
        $pdf->writeHTML($html, true, false, true, false, '');

        // Clean any content of the output buffer
        ob_end_clean();
 
        //Ausgabe der PDF
        //Variante 1: PDF direkt an den Benutzer senden:
        $pdf->Output($pdfName, 'I');    
    }

    // POSTINGERINNERUNG CHARAKTER UPDATEN
    if($mybb->input['action'] == "update_reminderstatus"){

        $uid = $mybb->get_input('uid', MyBB::INPUT_INT);

        $reminderstatus = $db->fetch_field($db->simple_select("users", "inplayscenes_reminder_status", "uid = '".$uid."'"), "inplayscenes_reminder_status");

        if ($reminderstatus == 0) {
            $status = 1;
        } else {
            $status = 0;
        }

        $update_status = array(
            'inplayscenes_reminder_status' => (int)$status   
        );
        $db->update_query("users", $update_status, "uid='".$uid."'");

        redirect("misc.php?action=inplayscenes", $lang->sprintf($lang->inplayscenes_redirect_reminderstatus, get_user($uid)['username']));
    }

    // POSTERINNERUNG
    if($mybb->input['action'] == "postingreminder"){

        add_breadcrumb($lang->inplayscenes_postingreminder, "misc.php?action=postingreminder"); 

        $reminder = inplayscenes_openSceneReminder($userids_array, 'list');

        if (count($reminder['relevant_tids']) > 0) {
    
            $today = new DateTime('now', new DateTimeZone('Europe/Berlin'));

            $relevant_tids = implode(',', $reminder['relevant_tids']);
    
            $query_tids = $db->query("SELECT * FROM ".TABLE_PREFIX."threads t
            LEFT JOIN ".TABLE_PREFIX."inplayscenes i
            ON (i.tid = t.tid) 
            WHERE t.tid IN (".$relevant_tids.")
            AND visible = 1
            ORDER BY t.lastpost ASC
            ");
    
            $grouped_scenes = [];
            while ($scene = $db->fetch_array($query_tids)) {

                $lastpost_date = DateTime::createFromFormat('U', $scene['lastpost']);
                $interval = $today->diff($lastpost_date);
                $days_passed = $interval->days;
    
                // Gruppierung der Szenen nach Tagen
                if (!isset($grouped_scenes[$days_passed])) {
                    $grouped_scenes[$days_passed] = [];
                }
                $grouped_scenes[$days_passed][] = $scene;
            }
    
            $reminder_bit = '';
            foreach ($grouped_scenes as $days_passed => $scenes) {
                
                if ($days_passed != 0) {
                    $countday = "offen seit ".$days_passed." Tagen";
                }

                $scene_rows = '';
                foreach ($scenes as $scene) {
    
                    list($year, $month, $day) = explode('-', $scene['date']);
                    if ($month_setting == 0) {
                        $scenedate = $day.".".$month.".".$year;
                    } else {
                        $scenedate = $day.". ".$months[$month]." ".$year;
                    }
                
                    $partners_username = $scene['partners_username'];
                    $partners = $scene['partners'];
                
                    $usernames = explode(",", $partners_username);
                    $uids = explode(",", $partners);
                
                    $partners = [];
                    foreach ($uids as $key => $uid) {
                
                        $tagged_user = get_user($uid);
                        if (!empty($tagged_user)) {
                            if ($color_setting == 1) {
                                $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
                            } else {
                                $username = $tagged_user['username'];
                            }
                            $taguser = build_profile_link($username, $uid);
                        } else {
                            $taguser = $usernames[$key];
                        }
                        $partners[] = $taguser;
                    }
                    $partnerusers = implode(" &#x26; ", $partners);
            
                    $postorder = $postorderoptions[$scene['postorder']];
                    $subject = $scene['subject'];
                    $tid = $scene['tid'];
                    $pid = $scene['firstpost'];
                    $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
                
                    // Variable für einzeln
                    $inplayscene = [];
                    $inplayscene['scenedate'] = $scenedate;
                    $inplayscene['partnerusers'] = $partnerusers;
                    $inplayscene['postorder'] = $postorder;
                    $inplayscene['subject'] = $subject;
                    $inplayscene['tid'] = $tid;
                    $inplayscene['pid'] = $pid;
                    $inplayscene['scenelink'] = $scenelink;
                
                    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
                
                    $inplayscenesfields = "";
                    while ($field = $db->fetch_array($fields_query)) {

                        // Leer laufen lassen
                        $identification = "";
                        $title = "";
                        $value = "";
                        $allow_html = "";
                        $allow_mybb = "";
                        $allow_img = "";
                        $allow_video = "";
                
                        // Mit Infos füllen
                        $identification = $field['identification'];
                        $title = $field['title'];
                        $allow_html = $field['allow_html'];
                        $allow_mybb = $field['allow_mybb'];
                        $allow_img = $field['allow_img'];
                        $allow_video = $field['allow_video'];

                        $value = inplayscenes_parser_fields($scene[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);
                
                        // Einzelne Variabeln
                        $inplayscene[$identification] = $value;         

                        if (!empty($value)) {
                            eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_postingreminder_scene_fields") . "\";");
                        }
                    }
                
                    if ($scenetype_setting == 1) {
                        $scenetype = $sceneoptions[$scene['scenetype']]." &#x26; ";
                        $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
                        if ($hide_setting == 0 && $scene['scenetype'] == 3) {
                            $scenetype = $sceneoptions[0]." &#x26; ";
                            $inplayscene['scenetype'] = $sceneoptions[0];
                        }
                    } else if ($hide_setting == 1) {
                        $scenetype = $sceneoptions[$scene['scenetype']]." &#x26; ";
                        $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
                    } else {
                        $scenetype = "";
                        $inplayscene['scenetype'] = "";
                    }
                
                    if ($trigger_setting == 1) {
                        $trigger = $scene['trigger_warning'];
                        $inplayscene['trigger'] = $trigger;
                
                        if (!empty($trigger)) {
                            $title = $lang->inplayscenes_fields_trigger;
                            $value = $trigger;
                            eval("\$triggerwarning = \"" . $templates->get("inplayscenes_postingreminder_scene_fields") . "\";");
                        } else {
                            $triggerwarning = "";
                        }
                    } else {
                        $triggerwarning = "";
                        $inplayscene['trigger'] = "";
                    }
    
                    $lastpostdate = my_date('relative', $scene['lastpost']);
                    if ($color_setting == 1) {
                        $user = get_user($scene['lastposteruid']);
                        $lastposter = build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $scene['lastposteruid']); 
                    } else {
                        $lastposter = build_profile_link($user['username'], $scene['lastposteruid']);
                    }

                    $lastpostlink = "showthread.php?tid=".$tid."&amp;action=lastpost";
                    $inplayscene['lastpostlink'] = $lastpostlink;

                    if (in_array($scene['fid'], $sideplays_forums)) {
                        $AUscene = $lang->inplayscenes_postingreminder_au;
                        $inplayscene['AUscene'] = $lang->inplayscenes_postingreminder_au;
                    } else {
                        $AUscene = "";
                        $inplayscene['AUscene'] = "";
                    }
    
                    // Nutze ein Template für jede Szene
                    eval("\$scene_rows .= \"".$templates->get("inplayscenes_postingreminder_scene")."\";");
                }
    
                // Füge die Szenen für diesen Tag hinzu
                eval("\$reminder_bit .= \"".$templates->get("inplayscenes_postingreminder_bit")."\";");
            }    
        } else {
            eval("\$reminder_bit = \"".$templates->get("inplayscenes_postingreminder_none")."\";");
        }

        $reminder_days = $db->fetch_field($db->simple_select("users", "inplayscenes_reminder_days", "uid = ".$userID.""), 'inplayscenes_reminder_days');
        if ($reminder_days > 0) {
            $reminderdesc = $lang->sprintf($lang->inplayscenes_postingreminder_desc, $reminder_days);
        } else {
            $reminderdesc = $lang->sprintf($lang->inplayscenes_postingreminder_desc_deactive);
        }

        if ($inactivescenes_setting > 0) {
            $inactivescenes_hint = $lang->sprintf($lang->inplayscenes_postingreminder_hint, $inactivescenes_setting);
        } else {
            $inactivescenes_hint = "";
        }
        $postingreminder_desc = $reminderdesc.$inactivescenes_hint;

        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("inplayscenes_postingreminder")."\";");
        output_page($page);
        die();
    }
}

// COUNTER + POSTINGERINNERUNG
function inplayscenes_global() {

    global $db, $mybb, $lang, $templates, $inplayscenes_headercount, $allinplayscenes, $allinplayscenes_open, $inplayscenes_postingreminder, $banner_text;

    // Sprachdatei laden
    $lang->load('inplayscenes');

    // USER ID
    $playerID = $mybb->user['uid'];

    if ($playerID == 0) return;

    // ACCOUNTSWITCHER
    $userids_array = inplayscenes_get_allchars($playerID);

    $counter = inplayscenes_openSceneReminder($userids_array, 'count');

    // COUNTER
    // Gesamte Szenen
    $allinplayscenes = $counter['total_scenes'];
    // Offene Szenen
    $allinplayscenes_open = $counter['open_scenes'];
    eval("\$inplayscenes_headercount = \"".$templates->get("inplayscenes_counter")."\";");

    // POSTING ERINNERUNG
    $reminder = inplayscenes_openSceneReminder($userids_array, 'list');

    if (count($reminder['relevant_tids']) > 0) {
        if (count($reminder['relevant_tids']) > 1) {
            $banner_text = $lang->sprintf($lang->inplayscenes_postingreminder_banner, count($reminder['relevant_tids']), 'en', 'sind');
        } else {
            $banner_text = $lang->sprintf($lang->inplayscenes_postingreminder_banner, 'Eine', '', 'ist');
        }
        eval("\$inplayscenes_postingreminder = \"".$templates->get("inplayscenes_postingreminder_banner")."\";");
    } else {
        $inplayscenes_postingreminder = "";   
    }
}

// WAS PASSIERT MIT EINEM GELÖSCHTEN USER
function inplayscenes_user_delete() {

    global $db, $cache, $mybb, $user;

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $inplay_archive = $mybb->settings['inplayscenes_archive'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];
    $sideplays_archive = $mybb->settings['inplayscenes_sideplays_archive'];

    // UID des gelöschten Accounts
    $deleteChara = (int)$user['uid'];

    // RELEVANTE FORUMS FIDs
    $relevant_forums_inplay = inplayscenes_get_relevant_forums($inplay_forum);
    $relevant_forums_sideplay = inplayscenes_get_relevant_forums($sideplays_forum);
    $relevant_forums_inplay = array_diff($relevant_forums_inplay, $relevant_forums_sideplay);

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
    AND (concat(',',i.partners,',') LIKE '%,".$deleteChara.",%')");

    while($ilist = $db->fetch_array($allinplay_query)) {

        list($year, $month_number, $day) = explode('-', $ilist['date']);

        $possible_month_names = $months[$month_number];

        $subforum_fid = false;
        foreach ($possible_month_names as $month_name) {
            $subforum_fid = $db->fetch_field($db->simple_select("forums", "fid", "name = '".$month_name." ".$year."' AND pid = ".$inplay_archive.""), 'fid');
            if ($subforum_fid) {
                break;
            }
        }

        if ($subforum_fid) {
            $new_fid = $subforum_fid;
        } else {
            $new_fid = $inplay_archive;
        }

        $update_iFid = array('fid' => $new_fid);
        $db->update_query('threads', $update_iFid, "tid='".$ilist['tid']."'");
        $db->update_query('posts', $update_iFid, "tid='".$ilist['tid']."'");

        require_once MYBB_ROOT . "inc/functions_rebuild.php";
        rebuild_forum_counters($ilist['fid']); // Altes Forum
        rebuild_forum_counters($new_fid);      // Neues Forum

        $cache->update_forums();
        $cache->update_stats();
    }

    // AU SZENEN
    $allsideplay_query = $db->query("SELECT t.tid, t.fid, i.date FROM ".TABLE_PREFIX."threads t 
    LEFT JOIN ".TABLE_PREFIX."inplayscenes i ON i.tid = t.tid 
    WHERE t.fid IN (".implode(',', $relevant_forums_sideplay).")
    AND (concat(',',i.partners,',') LIKE '%,".$deleteChara.",%')");

    while($slist = $db->fetch_array($allsideplay_query)) {

        list($year, $month_number, $day) = explode('-', $slist['date']);

        $possible_month_names = $months[$month_number];

        $subforum_fid = false;
        foreach ($possible_month_names as $month_name) {
            $subforum_fid = $db->fetch_field($db->simple_select("forums", "fid", "name = '".$month_name." ".$year."' AND pid = ".$sideplays_archive.""), 'fid');
            if ($subforum_fid) {
                break; 
            }
        }

        if ($subforum_fid) {
            $new_fid = $subforum_fid;
        } else {
            $new_fid = $sideplays_archive;
        }

        $update_iFid = array('fid' => $new_fid);
        $db->update_query('threads', $update_iFid, "tid='".$slist['tid']."'");
        $db->update_query('posts', $update_iFid, "tid='".$slist['tid']."'");

        require_once MYBB_ROOT . "inc/functions_rebuild.php";
        rebuild_forum_counters($slist['fid']); // Altes Forum
        rebuild_forum_counters($new_fid);      // Neues Forum

        // Cache aktualisieren
        $cache->update_forums();
        $cache->update_stats();
    }
}

// WAS PASSIERT BEI USERNAME ÄNDERUNG
// Admin CP
function inplayscenes_user_update($datahandler) {

    global $db, $user;

    if (!empty($datahandler->user_update_data['username']) && $datahandler->user_update_data['username'] != $user['username']) {

        $old_username = $user['username'];
        $new_username = $datahandler->user_update_data['username'];

        $allinplayscenes_query = $db->query("SELECT isid, partners_username FROM ".TABLE_PREFIX."inplayscenes i
        WHERE concat(',',i.partners,',') LIKE '%,".$user['uid'].",%'
        ");

        if ($db->num_rows($allinplayscenes_query) > 0) {
            while ($ilist = $db->fetch_array($allinplayscenes_query)) {
                
                $partners_usernames = $ilist['partners_username'];
                $usernames = explode(",", $partners_usernames);

                $new_usernames = array();
                foreach ($usernames as $username) {

                    if ($username == $old_username) {
                        $partnerusername = $new_username;
                    } else {
                        $partnerusername = $username;
                    }

                    $new_usernames[] = $partnerusername;
                }
                
                $partnerUsernames_new = implode(",", $new_usernames);

                $update_Usernames = array(
                    'partners_username' => $db->escape_string($partnerUsernames_new)
                );

                $db->update_query('inplayscenes', $update_Usernames, "isid='".$ilist['isid']."'");
            }
        }
    }
}
// User CP
function inplayscenes_update_username(){

    global $db, $mybb;

    // UID UND NEUER USERNAME
    $changeChara = (int)$mybb->user['uid'];
    $new_username = $db->escape_string($mybb->get_input('username'));
    $old_username = $db->escape_string($mybb->user['username']);

    $allinplayscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
    WHERE (concat(',',i.partners,',') LIKE '%,".$changeChara.",%')
    ");

    if ($db->num_rows($allinplayscenes_query) > 0) {
        while ($ilist = $db->fetch_array($allinplayscenes_query)) {
            
            $partners_usernames = $ilist['partners_username'];
            $usernames = explode(",", $partners_usernames);

            $new_usernames = array();
            foreach ($usernames as $username) {

                if ($username == $old_username) {
                    $partnerusername = $new_username;
                } else {
                    $partnerusername = $username;
                }

                $new_usernames[] = $partnerusername;
            }
            
            $partnerUsernames_new = implode(",", $new_usernames);

            $update_Usernames = array(
                'partners_username' => $db->escape_string($partnerUsernames_new)
            );

            $db->update_query('inplayscenes', $update_Usernames, "isid='".$ilist['isid']."'");
        }
    }
}

// ONLINE LOCATION
function inplayscenes_online_activity($user_activity) {

	global $parameters, $user;

	$split_loc = explode(".php", $user_activity['location']);
	if(isset($user['location']) && $split_loc[0] == $user['location']) { 
		$filename = '';
	} else {
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
	}

	switch ($filename) {
		case 'misc':
			if ($parameters['action'] == "inplayscenes") {
				$user_activity['activity'] = "inplayscenes";
			}
			if ($parameters['action'] == "all_inplayscenes") {
				$user_activity['activity'] = "all_inplayscenes";
			}
			if ($parameters['action'] == "inplayscenes_edit") {

                // Name der Unterseite
                $split_value = explode("=", $split_loc[1]);

                // tid
                $tid = $split_value[2];

				$user_activity['activity'] = "inplayscenes_edit=".$tid;
			}
			if ($parameters['action'] == "inplayscenes_pdf") {

                // Name der Unterseite
                $split_value = explode("=", $split_loc[1]);

                $pos = strpos($split_value[1], 'tid');
                if ($pos === false) {
                    $key = "pid:".$split_value[2];
                } else {
                    $key = "tid:".$split_value[2];
                }

				$user_activity['activity'] = "inplayscenes_pdf=".$key;
			}
			if ($parameters['action'] == "postingreminder") {
				$user_activity['activity'] = "postingreminder";
			}
            break;
	}

	return $user_activity;
}
function inplayscenes_online_location($plugin_array) {

	global $lang, $db, $mybb;
    
    // SPRACHDATEI LADEN
    $lang->load("inplayscenes");

    // Einstellungen
    $hide_setting = $mybb->settings['inplayscenes_hide'];

    // Seitennamen
    $split_name = explode("=", $plugin_array['user_activity']['activity']);
    $sidename = $split_name[0];

	if ($sidename == "inplayscenes") {
		$plugin_array['location_name'] = $lang->inplayscenes_online_location_inplayscenes;
	}

    if ($sidename == "all_inplayscenes") {
		$plugin_array['location_name'] = $lang->inplayscenes_online_location_all;
	}

    if ($sidename == "inplayscenes_edit") {
        $thread = get_thread($split_name[1]);
		$plugin_array['location_name'] = $lang->sprintf($lang->inplayscenes_online_location_edit, $thread['tid'], $thread['firstpost'], $thread['subject']);
	}

    if ($sidename == "inplayscenes_pdf") {
        $id = explode(":", $split_name[1]);

        if ($id[0] == "pid") {
            $scenetid = $db->fetch_field($db->simple_select("posts", "tid", "pid= '".$id[1]."'"), "tid");
            $thread = get_thread($scenetid);
            $plugin_array['location_name'] = $lang->sprintf($lang->inplayscenes_online_location_pdf_post, $thread['tid'], $thread['firstpost'], $thread['subject']);
        } else {
            $thread = get_thread($id[1]);
            $plugin_array['location_name'] = $lang->sprintf($lang->inplayscenes_online_location_pdf_topic, $thread['tid'], $thread['firstpost'], $thread['subject']);
        }
	}

    if ($sidename == "postingreminder") {
		$plugin_array['location_name'] = $lang->inplayscenes_online_location_postingreminder;
	}

    if ($hide_setting == 1) {

        $uid = $mybb->user['uid'];
        $remove_tids = inplayscenes_hidescenes($uid, 'forumdisplay');

        if (!empty($remove_tids) AND $mybb->usergroup['canmodcp'] != '1') {
            if ($plugin_array['user_activity']['activity'] == "showthread") {
                if(in_array($plugin_array['user_activity']['tid'], $remove_tids)) {
                    $plugin_array['location_name'] = "Liest eine versteckte Inplayszene.";
                } 
            }
        }
    }

	return $plugin_array;
}

// MyALERTS
function inplayscenes_myalerts() {

	global $mybb, $lang;

	$lang->load('inplayscenes');

    // Neue Szene //
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_inplayscenesNewthreadFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;

			$alertContent = $alert->getExtraDetails();
            $username = $db->escape_string($alertContent['username']);
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$username}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->inplayscenes_alert_newthread,
                $alertContent['username'],
                $alertContent['from'],
                $alertContent['tid'],
                $alertContent['pid'],
                $alertContent['subject']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        $this->lang->load('inplayscenes');
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/showthread.php?tid='.$alertContent['tid'].'&pid='.$alertContent['pid'].'#pid'.$alertContent['pid'];
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_inplayscenesNewthreadFormatter($mybb, $lang, 'inplayscenes_alert_newthread')
		);
    }

    // Neue Szene //
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_inplayscenesNewreplyFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;

			$alertContent = $alert->getExtraDetails();
            $username = $db->escape_string($alertContent['username']);
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$username}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->inplayscenes_alert_newreply,
                $alertContent['username'],
                $alertContent['from'],
                $alertContent['tid'],
                $alertContent['pid'],
                $alertContent['subject']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        $this->lang->load('inplayscenes');
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/showthread.php?tid='.$alertContent['tid'].'&pid='.$alertContent['pid'].'#pid'.$alertContent['pid'];
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_inplayscenesNewreplyFormatter($mybb, $lang, 'inplayscenes_alert_newreply')
		);
    }

    // offene Szene - neuer Charakter //
    /**
	 * Alert formatter for my custom alert type.
	 */
	class MybbStuff_MyAlerts_Formatter_inplayscenesOpenFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
	    /**
	     * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
	     *
	     * @return string The formatted alert string.
	     */
	    public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	    {
			global $db;

			$alertContent = $alert->getExtraDetails();
            $username = $db->escape_string($alertContent['username']);
            $userid = $db->fetch_field($db->simple_select("users", "uid", "username = '{$username}'"), "uid");
            $user = get_user($userid);
            $alertContent['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	        return $this->lang->sprintf(
	            $this->lang->inplayscenes_alert_openadd,
                $alertContent['username'],
                $alertContent['from'],
                $alertContent['tid'],
                $alertContent['pid'],
                $alertContent['subject']
	        );
	    }

	    /**
	     * Init function called before running formatAlert(). Used to load language files and initialize other required
	     * resources.
	     *
	     * @return void
	     */
	    public function init()
	    {
	        $this->lang->load('inplayscenes');
	    }

	    /**
	     * Build a link to an alert's content so that the system can redirect to it.
	     *
	     * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
	     *
	     * @return string The built alert, preferably an absolute link.
	     */
	    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	    {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/showthread.php?tid='.$alertContent['tid'].'&pid='.$alertContent['pid'].'#pid'.$alertContent['pid'];
	    }
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
				new MybbStuff_MyAlerts_Formatter_inplayscenesOpenFormatter($mybb, $lang, 'inplayscenes_alert_openadd')
		);
    }
}

// INPLAYSZENENFELDER AUSLESEN
function inplayscenes_generate_fields($draft = null, $input_data = null, $only_editable = false, $mode = '') {

    global $db, $mybb, $templates;

    if ($only_editable) {
        $condition = "WHERE edit = 1";
    } else {
        $condition = "";
    }

    $fields_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields 
    $condition 
    ORDER BY disporder ASC, title ASC
    ");

    $own_inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        $identification = $field['identification'];
        $title = $field['title'];
        $description = $field['description'];
        $type = $field['type'];
        $options = $field['options'];
        $required = $field['required'];

        if ($input_data) {
            if ($type == "multiselect" || $type == "checkbox") {
                $value = $mybb->get_input($identification, MyBB::INPUT_ARRAY);
            } else {
                $value = $mybb->get_input($identification);
            }
        } elseif ($draft) {
            $value = $draft[$identification];
        } else {
            $value = ""; 
        }

        // INPUTS generieren
        $code = inplayscenes_generate_input_field($identification, $type, $value, $options);

        if ($mode == 'editscene') {
            eval("\$own_inplayscenesfields .= \"".$templates->get("inplayscenes_editscene_fields")."\";");
        } else {
            eval("\$own_inplayscenesfields .= \"".$templates->get("inplayscenes_newthread_fields")."\";");
        }
    }

    return $own_inplayscenesfields;
}

// INPUT FELDER GENERIEN
function inplayscenes_generate_input_field($identification, $type, $value = '', $options = '') {

    $input = '';

    switch ($type) {
        case 'text':
            $input = '<input type="text" class="textbox" size="40" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;

        case 'textarea':
            $input = '<textarea name="'.htmlspecialchars($identification).'" rows="6" cols="42">' . htmlspecialchars($value) . '</textarea>';
            break;

        case 'date':
            $input = '<input type="date" class="textbox" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;

        case 'url':
            $input = '<input type="url" class="textbox" size="40" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;

        case 'radio':
            $expoptions = explode("\n", $options);
            foreach ($expoptions as $option) {
                $checked = ($option == $value) ? ' checked' : '';
                $input .= '<input type="radio" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($option) . '"' . $checked . '>';
                $input .= '<span class="smalltext">' . htmlspecialchars($option) . '</span><br />';
            }
            break;

        case 'select':
            $expoptions = explode("\n", $options);
            $input = '<select name="'.htmlspecialchars($identification).'">';
            foreach ($expoptions as $option) {
                $selected = ($option == $value) ? ' selected' : '';
                $input .= '<option value="' . htmlspecialchars($option) . '"' . $selected . '>' . htmlspecialchars($option) . '</option>';
            }
            $input .= '</select>';
            break;

        case 'multiselect':
            $expoptions = explode("\n", $options);
            $value = is_array($value) ? $value : explode(',', $value);
            $input = '<select name="'.htmlspecialchars($identification).'[]" multiple>';
            foreach ($expoptions as $option) {
                $selected = in_array($option, $value) ? ' selected' : '';
                $input .= '<option value="' . htmlspecialchars($option) . '"' . $selected . '>' . htmlspecialchars($option) . '</option>';
            }
            $input .= '</select>';
            break;

        case 'checkbox':
            $expoptions = explode("\n", $options);
            $value = is_array($value) ? $value : explode(',', $value);
            foreach ($expoptions as $option) {
                $checked = in_array($option, $value) ? ' checked' : '';
                $input .= '<input type="checkbox" name="'.htmlspecialchars($identification).'[]" value="' . htmlspecialchars($option) . '"' . $checked . '>';
                $input .= '<span class="smalltext">' . htmlspecialchars($option) . '</span><br />';
            }
            break;

        default:
            $input = '<input type="text" name="'.htmlspecialchars($identification).'" value="' . htmlspecialchars($value) . '">';
            break;
    }

    return $input;
}

// POSTING-REIHENFOLGE SELECT GENERIEN
function inplayscenes_generate_postorder_select($selected_value) {

	global $lang;

	$lang->load('inplayscenes');

    // Optionen für das Dropdown-Menü
    $options = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];    
    
    $select = '<select name="postorder">';

    foreach ($options as $value => $label) {
        $selected = ($value == $selected_value) ? ' selected' : '';
        $select .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }

    $select .= '</select>';

    return $select;
}

// SZENENSTATUS SELECT GENERIEN
function inplayscenes_generate_scenetype_select($selected_value) {

	global $lang, $mybb;

    $hide_setting = $mybb->settings['inplayscenes_hide'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];

	$lang->load('inplayscenes');

    // Optionen für das Dropdown-Menü
    if ($scenetype_setting == 1) {
        $options = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($options, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $options = [
                '0' => $lang->inplayscenes_scenetype_visible,
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    // Start des Select-Tags
    $select = '<select name="scenetype">';

    // Optionen durchlaufen und die entsprechende als ausgewählt markieren
    foreach ($options as $value => $label) {
        $selected = ($value == $selected_value) ? ' selected' : '';
        $select .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }

    // Ende des Select-Tags
    $select .= '</select>';

    return $select;
}

// GEHEIME SZENEN EINSTELLUNGEN SELECT GENERIEN
function inplayscenes_generate_hidetype_select($selected_value) {

	global $lang;

	$lang->load('inplayscenes');

    // Optionen für das Dropdown-Menü
    $options = [
        '1' => $lang->inplayscenes_hidetype_info,
        '2' => $lang->inplayscenes_hidetype_all
    ];    
    
    $select = '<select name="hidetype">';

    foreach ($options as $value => $label) {
        $selected = ($value == $selected_value) ? ' selected' : '';
        $select .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }

    $select .= '</select>';

    return $select;
}

// GEHEIME SZENEN PROFIL EINSTELLUNGEN SELECT GENERIEN
function inplayscenes_generate_hideprofile_select($selected_value) {

	global $lang;

	$lang->load('inplayscenes');

    // Optionen für das Dropdown-Menü
    $options = [
        '1' => $lang->inplayscenes_hideprofile_info,
        '2' => $lang->inplayscenes_hideprofile_all
    ];    
    
    $select = '<select name="hideprofile">';

    foreach ($options as $value => $label) {
        $selected = ($value == $selected_value) ? ' selected' : '';
        $select .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($label) . '</option>';
    }

    $select .= '</select>';

    return $select;
}

// ACCOUNTSWITCHER HILFSFUNKTION => Danke, Katja <3
function inplayscenes_get_allchars($user_id) {

	global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

	//für den fall nicht mit hauptaccount online
	if (isset(get_user($user_id)['as_uid'])) {
        $as_uid = intval(get_user($user_id)['as_uid']);
    } else {
        $as_uid = 0;
    }

	$charas = array();
	if ($as_uid == 0) {
	  // as_uid = 0 wenn hauptaccount oder keiner angehangen
	  $get_all_users = $db->query("SELECT uid,username FROM ".TABLE_PREFIX."users WHERE (as_uid = ".$user_id.") OR (uid = ".$user_id.") ORDER BY username");
	} else if ($as_uid != 0) {
	  //id des users holen wo alle an gehangen sind 
	  $get_all_users = $db->query("SELECT uid,username FROM ".TABLE_PREFIX."users WHERE (as_uid = ".$as_uid.") OR (uid = ".$user_id.") OR (uid = ".$as_uid.") ORDER BY username");
	}
	while ($users = $db->fetch_array($get_all_users)) {
	  $uid = $users['uid'];
	  $charas[$uid] = $users['username'];
	}
	return $charas;  
}

// INPLAYBEREICH FIDS
function inplayscenes_get_relevant_forums($relevantforums) {

    global $db, $mybb;

    // EINSTELLUNGEN
    $excludedarea = $mybb->settings['inplayscenes_excludedarea'];
    $excludedarea_array = explode(',', $excludedarea);

    if (!empty($excludedarea)) {
        $sql_exludedareas = "AND fid NOT IN (".implode(',', $excludedarea_array).")";
    } else {
        $sql_exludedareas = "";
    }

    $relevantforums = trim($relevantforums, ',');
    
    $inplayarea = array_filter(explode(',', $relevantforums), function($fid) {
        return trim($fid) !== '-1'; 
    });

    if (empty($inplayarea)) {
        return [0];
    }

    $relevant_forums = [];
    foreach ($inplayarea as $fid) {

        $fid = trim($fid); 
        if (empty($fid) || $fid == '-1') {
            continue;
        }

        $query = $db->query("SELECT fid FROM ".TABLE_PREFIX."forums 
        WHERE (concat(',',parentlist,',') LIKE '%,".$fid.",%')
        ".$sql_exludedareas."
        ");
    
        while ($forum = $db->fetch_array($query)) {
            $relevant_forums[] = $forum['fid'];
        }
    }

    $relevant_forums = array_filter(array_unique($relevant_forums));

    if (empty($relevant_forums)) {
        return [0];
    }

    return $relevant_forums;
}

// SPIELERNAME AUTOCOMPLED
function inplayscenes_playername_autocompled(){

    global $db, $mybb, $data, $search_type, $likestring;

    // $logfile = MYBB_ROOT . "my_custom_log.txt";
    // file_put_contents($logfile, 'Action: ' . $mybb->input['from_page'] .' & '. $mybb->input['selectField']);

    if (empty($mybb->settings['inplayscenes_playername']) && ($mybb->input['selectField'] !== '#playername' || $mybb->input['from_page'] !== 'all_inplayscenes')) {
        return;
    }

    // EINSTELLUNGEN
    $playername_setting = $mybb->settings['inplayscenes_playername'];
    if (!empty($playername_setting)) {
        if (is_numeric($playername_setting)) {
            $playername_fid = "fid".$playername_setting;
        } else {
            $playername_fid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
        }
    } else {
        $playername_fid = "";
    }

    if (my_strlen($mybb->input['query']) < 2) {
        return;
    }

    $likestring = $db->escape_string_like($mybb->input['query']);
    if ($search_type == 1) {
        $likestring = '%' . $likestring;
    } elseif ($search_type == 2) {
        $likestring = '%' . $likestring . '%';
    } else {
        $likestring .= '%';
    }

    $data = array();

    $seen_playername = [];
    if (is_numeric($playername_setting)) {
        $players_query = $db->query("SELECT ufid, ".$playername_fid." FROM ".TABLE_PREFIX."userfields uf
        WHERE ".$playername_fid." LIKE '".$likestring."'
        ");

        while ($player = $db->fetch_array($players_query)) {
            if (!in_array($player[$playername_fid], $seen_playername)) {
                $data[] = array('uid' => $player['ufid'], 'id' => $player[$playername_fid], 'text' => $player[$playername_fid]);
                $seen_playername[] = $player[$playername_fid];
            }
        }
    } else {
        $players_query = $db->query("SELECT * FROM ".TABLE_PREFIX."application_ucp_userfields uf
        WHERE id = '".$playername_fid."'
        AND value LIKE '".$likestring."'
        ");

        while ($player = $db->fetch_array($players_query)) {
            if (!in_array($player['value'], $seen_playername)) {
                $data[] = array('uid' => $player['uid'], 'id' => $player['value'], 'text' => $player['value']);
                $seen_playername[] = $player['value'];
            }
        }
    }
}

// PDF FELDER
function inplayscenes_pdf_fields($scenetid) {

    global $db, $mybb, $lang, $templates, $inplaysceneinfo;

    // EINSTELLUNGEN
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];

    // SPRACHDATEI
    $lang->load('inplayscenes');

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }
            
    $inplaysceneinfo = "";

    $inplayscene = $db->fetch_array($db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i WHERE tid = ".$scenetid));

    $partners_username = $inplayscene['partners_username'];
    $partners = $inplayscene['partners'];

    $partnerusernames = explode(",", $partners_username);
    $partneruids = explode(",", $partners);

    $partners = [];
    foreach ($partneruids as $key => $partneruid) {

        $tagged_user = get_user($partneruid);
        if (!empty($tagged_user)) {
            $taguser = $tagged_user['username'];
        } else {
            $taguser = $partnerusernames[$key];
        }
        $partners[] = $taguser;
    }
    $partnerusers = implode(" &#x26; ", $partners);

    list($year, $month, $day) = explode('-', $inplayscene['date']);
    $inplayscene['scenedate'] = $day.".".$month.".".$year;
    $inplayscene['partnerusers'] = $partnerusers;
    $inplayscene['postorder'] = $postorderoptions[$inplayscene['postorder']];
    $inplayscene['scenetype'] = $postorderoptions[$sceneoptions['scenetype']];

    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
    while ($field = $db->fetch_array($fields_query)) {
        
        // Leer laufen lassen
        $identification = "";
        $value = "";
        
        // Mit Infos füllen
        $identification = $field['identification'];
        $value = $inplayscene[$identification];
    
        // Einzelne Variabeln
        $inplayscene[$identification] = $value;  
    }

    eval("\$inplaysceneinfo = \"" . $templates->get("inplayscenes_pdf_fields") . "\";");

	return $inplaysceneinfo;  
}

// BENACHRICHTIGUNG RADIOBUTTONS GENERIEN
function inplayscenes_generate_radiobuttons_notification($selected_type = '') {

    global $lang;

	$lang->load('inplayscenes');

    // EINSTELLUNGEN
    $type_options = array(
        0 => $lang->inplayscenes_alert_pm,
        1 => $lang->inplayscenes_alert_alerts
    );

    $radiobuttons = "";
    foreach ($type_options as $key => $value) {
        $checked = ((int)$key === (int)$selected_type) ? ' checked' : '';
        $radiobuttons .= '<label>';
        $radiobuttons .= '<input type="radio" name="notification_type" value="' . htmlspecialchars($key) . '"' . $checked . '>';
        $radiobuttons .= htmlspecialchars($value);
        $radiobuttons .= '</label> ';
    }

    return $radiobuttons;
}

// OFFENE SZENEN + POSTING ERINNERUNG 
function inplayscenes_openSceneReminder($userids_array, $mode = 'count') {

    global $db, $lang, $mybb;

    // SPRACHDATEI
    $lang->load('inplayscenes');

    // EINSTELLUNGEN
    $inplay_forum = $mybb->settings['inplayscenes_inplayarea'];
    $sideplays_forum = $mybb->settings['inplayscenes_sideplays'];

    $activeforums = $inplay_forum.",".$sideplays_forum;
    $relevant_forums = inplayscenes_get_relevant_forums($activeforums);

    $today = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    $current_date = $today->format('Y-m-d'); // Heutiges Datum

    $result = [
        'total_scenes' => 0,
        'open_scenes' => 0,
        'relevant_tids' => []
    ];

    foreach ($userids_array as $userID => $userName) {

        // ALLE INPLAYSZENEN
        $allinplayscenes_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes i
            LEFT JOIN ".TABLE_PREFIX."threads t 
            ON (i.tid = t.tid) 
            WHERE (concat(',', i.partners, ',') LIKE '%,".$userID.",%')
            AND t.fid IN (".implode(',', $relevant_forums).")
            AND t.visible = 1
        ");

        $total_scenes_for_user = $db->num_rows($allinplayscenes_query);
        $result['total_scenes'] += $total_scenes_for_user;

        $open_scenes_for_user = 0;

        while ($sceneHeader = $db->fetch_array($allinplayscenes_query)) {

            $lastposterID = $sceneHeader['lastposteruid'];

            if ($sceneHeader['postorder'] == 1) {
                $partners = explode(",", $sceneHeader['partners']);
                $key = array_search($lastposterID, $partners);
                // Ein Schlüssel weiter - zur nächsten UID
                $key = $key + 1;

                // Falls kein weiterer Partner existiert, auf den ersten zurückspringen
                if (!isset($partners[$key])) {
                    $nextChara = $partners[0];
                } else {
                    $nextChara = $partners[$key];
                }

                // Nächsten Benutzer (nach der Reihenfolge) holen
                $next = get_user($nextChara);
                $nextUID = $next['uid'];

                if ($nextUID == $userID) {
                    $open_scenes_for_user++;

                    if ($mode == 'list') {
                        $reminder_days_query = $db->query("SELECT u.inplayscenes_reminder_days FROM ".TABLE_PREFIX."users u 
                        WHERE u.uid = ".$userID."
                        AND u.inplayscenes_reminder_status = 1
                        ");                    
                        $reminder_days_result = $db->fetch_array($reminder_days_query);

                        if ($reminder_days_result && isset($reminder_days_result['inplayscenes_reminder_days'])) {
                            $reminder_days = $reminder_days_result['inplayscenes_reminder_days'];
                        } else {
                            $reminder_days = 0;
                        }
    
                        if ($reminder_days > 0) {
                            // Berechne das Vergleichsdatum ohne Uhrzeit    
                            $interval_date = (new DateTime($current_date))->modify('-'.$reminder_days.' days');

                            // Extrahiere nur das Datum von lastpost (ohne Uhrzeit)
                            $lastpost_date = DateTime::createFromFormat('U', $sceneHeader['lastpost']);    
                            $lastpost_date->setTime(0, 0); 

                            if ($lastpost_date <= $interval_date) {
                                $result['relevant_tids'][] = $sceneHeader['tid'];
                            }
                        }
                    }
                }
            } else {
                $open_scenes_for_user++;
            }
        }

        $result['open_scenes'] += $open_scenes_for_user;
    }

    return $result;
}

// PARSER OPTIONEN INPLAYFELDR
function inplayscenes_parser_fields($fieldvalue, $allow_html, $allow_mybb, $allow_img, $allow_video) {

    global $parser, $parser_array;
                
    require_once MYBB_ROOT."inc/class_parser.php";
        
    $parser = new postParser;
    $parser_array = array(
        "allow_html" => $allow_html,
        "allow_mycode" => $allow_mybb,
        "allow_smilies" => 0,
        "allow_imgcode" => $allow_img,
        "filter_badwords" => 0,
        "nl2br" => 1,
        "allow_videocode" => $allow_video
    );

    $value = $parser->parse_message($fieldvalue, $parser_array);

    return $value;
}

// PROFIL WHILE AUSGABE
function inplayscenes_profile_scene($scene, $archive_forums, $mode = '') {

    global $db, $mybb, $lang, $templates;

    $result = "";

    // EINSTELLUNGEN
    $trigger_setting = $mybb->settings['inplayscenes_trigger'];
    $scenetype_setting = $mybb->settings['inplayscenes_scenetype'];
    $month_setting = $mybb->settings['inplayscenes_months'];
    $color_setting = $mybb->settings['inplayscenes_groupcolor'];
    $hide_setting = $mybb->settings['inplayscenes_hide'];

	// Sprachdatei laden
    $lang->load('inplayscenes');

    $postorderoptions = [
        '1' => $lang->inplayscenes_postorder_fixed,
        '0' => $lang->inplayscenes_postorder_none
    ];

    if ($scenetype_setting == 1) {
        $sceneoptions = [
            '0' => $lang->inplayscenes_scenetype_private,
            '1' => $lang->inplayscenes_scenetype_agreed,
            '2' => $lang->inplayscenes_scenetype_open
        ];
        if ($hide_setting == 1) {
            array_push($sceneoptions, $lang->inplayscenes_scenetype_hide);
        }
    } else {
        if ($hide_setting == 1) {
            $sceneoptions = [
                '0' => '',
                '3' => $lang->inplayscenes_scenetype_hide
            ];
        }
    }

    $months = array(
        '01' => $lang->inplayscenes_jan,
        '02' => $lang->inplayscenes_feb,
        '03' => $lang->inplayscenes_mar,
        '04' => $lang->inplayscenes_apr,
        '05' => $lang->inplayscenes_mai,
        '06' => $lang->inplayscenes_jun,
        '07' => $lang->inplayscenes_jul,
        '08' => $lang->inplayscenes_aug,
        '09' => $lang->inplayscenes_sep,
        '10' => $lang->inplayscenes_okt,
        '11' => $lang->inplayscenes_nov,
        '12' => $lang->inplayscenes_dez
    );

    // Leer laufen lassen
    $db_date = "";
    $scenedate = "";
    $status = "";
    $partners_username = "";
    $partners = "";
    $usernames = "";
    $uids = "";
    $partnerusers = "";
    $subject = "";
    $tid = "";
    $pid = "";
    $scenelink = "";

    // Mit Infos füllen
    list($year, $month, $day) = explode('-', $scene['date']);
    if ($month_setting == 0) {
        $scenedate = $day.".".$month.".".$year;
    } else {
        $scenedate = $day.". ".$months[$month]." ".$year;
    }

    $partners_username = $scene['partners_username'];
    $partners = $scene['partners'];

    $usernames = explode(",", $partners_username);
    $uids = explode(",", $partners);

    $partners = [];
    foreach ($uids as $key => $uid) {

        $tagged_user = get_user($uid);
        if (!empty($tagged_user)) {
            if ($color_setting == 1) {
                $username = format_name($tagged_user['username'], $tagged_user['usergroup'], $tagged_user['displaygroup']);
            } else {
                $username = $tagged_user['username'];
            }
            $taguser = build_profile_link($username, $uid);
        } else {
            $taguser = $usernames[$key];
        }
        $partners[] = $taguser;
    }
    $partnerusers = implode(" &#x26; ", $partners);

    $postorder = $postorderoptions[$scene['postorder']];
    $subject = $scene['subject'];
    $tid = $scene['tid'];
    $pid = $scene['firstpost'];

    if ($hide_setting == 1) {
        $userids_array = array_keys(inplayscenes_get_allchars($mybb->user['uid']));
        if (($scene['hideprofile'] == 1 && $scene['scenetype'] == 3) && empty(array_intersect($userids_array, $uids)) && $mybb->usergroup['canmodcp'] != '1') {
            $scenelink = "";
        } else {
            $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
        }
    } else {
        $scenelink = "showthread.php?tid=".$tid."&pid=".$pid."#pid".$pid;
    }

    // Variable für einzeln
    $inplayscene = [];
    $inplayscene['scenedate'] = $scenedate;
    $inplayscene['partnerusers'] = $partnerusers;
    $inplayscene['postorder'] = $postorder;

    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $value = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $value = $scene[$identification];

        // Einzelne Variabeln
        $inplayscene[$identification] = $value;
    }

    if ($scenetype_setting == 1) {
        $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
        if ($hide_setting == 0 && $scene['scenetype'] == 3) {
            $inplayscene['scenetype'] = $sceneoptions[0];
        }
    } else if ($hide_setting == 1) {
        $inplayscene['scenetype'] = $sceneoptions[$scene['scenetype']];
    } else {
        $inplayscene['scenetype'] = "";
    }

    if ($trigger_setting == 1) {
        $inplayscene['trigger'] = $scene['trigger_warning'];
    } else {
        $inplayscene['trigger'] = "";
    }

    if (!empty($mode)) {
        if (in_array($scene['fid'], $archive_forums)) {
            $status = $lang->inplayscenes_memberprofile_status_close;
        } else {
            $status = $lang->inplayscenes_memberprofile_status_active;
        }
    } else {
        $status = "";
    }

    eval("\$result .= \"" . $templates->get("inplayscenes_memberprofile_scenes") . "\";");

    return $result;
}

// VERSTECKTE SZENEN
function inplayscenes_hidescenes($uid, $mode = '') {

    global $db;

    if ($uid != 0) {
        // ACCOUNTSWITCHER
        $userids_array = inplayscenes_get_allchars($uid);
        $uids_to_check = array_keys($userids_array);
    
        $find_in_set_conditions = array();
        foreach ($uids_to_check as $uid_to_check) {
            $find_in_set_conditions[] = "NOT (concat(',',partners,',') LIKE '%,".$uid_to_check.",%')";
        }
        $find_in_set_sql = implode(' AND ', $find_in_set_conditions);

        $user_sql = "AND NOT (concat(',',partners,',') LIKE '%,".$uid.",%') AND ".$find_in_set_sql."";
    } else {
        $user_sql = "";
    }

    if ($mode == 'forumdisplay') {
        $type_sql = "AND hidetype = 2";
    } else if ($mode == 'profile') {
        $type_sql = "AND hideprofile = 2";
    } else {
        $type_sql = "";
    }

    $remove_query = $db->query("SELECT tid FROM ".TABLE_PREFIX."inplayscenes 
    WHERE scenetype = 3
    ".$type_sql."
    ".$user_sql."
    ");

    $remove_tids = array();
    while ($remove = $db->fetch_array($remove_query)) {
        $remove_tids[] = $remove['tid'];
    }

    return $remove_tids;
}

// DATENBANKTABELLEN + DATENBANKFELDER
function inplayscenes_database() {

    global $db;
    
    // DATENBANKEN ERSTELLEN
    // Inplayszenen
    if (!$db->table_exists("inplayscenes")) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."inplayscenes (
            `isid` int(10) NOT NULL AUTO_INCREMENT, 
            `tid` int(11) unsigned,
            `partners` VARCHAR(1500),
            `partners_username` VARCHAR(2500),
            `date` VARCHAR(12) NOT NULL DEFAULT '0000-00-00',
            `trigger_warning` VARCHAR(500),
            `scenetype` int(1) unsigned NOT NULL DEFAULT '0',
            `hidetype` int(1) unsigned NOT NULL DEFAULT '0',
            `hideprofile` int(1) unsigned NOT NULL DEFAULT '0',
            `postorder` int(1) unsigned NOT NULL DEFAULT '1',
            `relevant` int(1) unsigned NOT NULL DEFAULT '1',
            PRIMARY KEY(`isid`),
            KEY `isid` (`isid`)
            )
            ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
        ");
    }
    // Inplayszenen Felder
    if (!$db->table_exists("inplayscenes_fields")) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."inplayscenes_fields (
            `ifid` int(10) NOT NULL AUTO_INCREMENT, 
            `identification` VARCHAR(250) NOT NULL,
            `title` VARCHAR(250) NOT NULL,
            `description` VARCHAR(500),
            `type` VARCHAR(250) NOT NULL,
            `options` VARCHAR(500),
            `required` int(1) NOT NULL DEFAULT '0',
            `edit` int(1) NOT NULL DEFAULT '0',
            `disporder` int(5) NOT NULL DEFAULT '0',
            `allow_html` int(1) NOT NULL DEFAULT '0',
            `allow_mybb` int(1) NOT NULL DEFAULT '0',
            `allow_img` int(1) NOT NULL DEFAULT '0',
            `allow_video` int(1) NOT NULL DEFAULT '0',
            PRIMARY KEY(`ifid`),
            KEY `ifid` (`ifid`)
            )
            ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
        ");
    }

    // DATENBANKSPALTEN => USERS
    // Benachrichtigungssystem
    if (!$db->field_exists("inplayscenes_notification", "users")) {
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
            $db->query("ALTER TABLE ".TABLE_PREFIX."users ADD `inplayscenes_notification` int(1) unsigned NOT NULL DEFAULT '1';");
        } else {
            $db->query("ALTER TABLE ".TABLE_PREFIX."users ADD `inplayscenes_notification` int(1) unsigned NOT NULL DEFAULT '0';");
        }
    }
    // Posterinnerung Tage
    if (!$db->field_exists("inplayscenes_reminder_days", "users")) {
        $db->query("ALTER TABLE ".TABLE_PREFIX."users ADD `inplayscenes_reminder_days` int(5) unsigned NOT NULL DEFAULT '0';");
    }
    // Posterinnerung Einstellung
    if (!$db->field_exists("inplayscenes_reminder_status", "users")) {
        $db->query("ALTER TABLE ".TABLE_PREFIX."users ADD `inplayscenes_reminder_status` int(1) unsigned NOT NULL DEFAULT '1';");
    }
}

// EINSTELLUNGEN
function inplayscenes_settings($type = 'install') {

    global $db; 

    $setting_array = array(
		'inplayscenes_inplayarea' => array(
			'title' => 'Inplay-Bereich',
            'description' => 'Bei welchen Foren handelt es sich um den Inplay-Bereich? Es reicht aus, die übergeordneten Kategorien zu markieren.',
            'optionscode' => 'forumselect',
            'value' => '', // Default
            'disporder' => 1
		),
        'inplayscenes_archive' => array(
            'title' => 'Inplay-Archiv',
            'description' => 'Bei welchen Foren handelt es sich um das Inplay-Archiv? Es reicht aus, die übergeordneten Kategorien oder das übergeordnete Forum zu markieren.',
            'optionscode' => 'forumselectsingle',
            'value' => '', // Default
            'disporder' => 2
        ),
		'inplayscenes_excludedarea' => array(
			'title' => 'ausgeschlossene Foren',
            'description' => 'Gibt es Foren, die innerhalb der ausgewählten Kategorien liegen, aber nicht beachtet werden sollen (z.B. Communication)?',
            'optionscode' => 'forumselect',
            'value' => '', // Default
            'disporder' => 3
		),
		'inplayscenes_sideplays' => array(
			'title' => 'AU-Szenen-Bereich',
            'description' => 'Bei welchen Foren handelt es sich um den alternative Universum Bereich? Es reicht aus, die übergeordneten Kategorien oder das übergeordnete Forum zu markieren.',
            'optionscode' => 'forumselect',
            'value' => '', // Default
            'disporder' => 4
		),
		'inplayscenes_sideplays_archive' => array(
			'title' => 'AU-Szenen-Archiv',
            'description' => 'Bei welchen Foren handelt es sich um das Archiv für das alternative Universum? Es reicht aus, die übergeordneten Kategorien oder das übergeordnete Forum zu markieren.',
            'optionscode' => 'forumselectsingle',
            'value' => '', // Default
            'disporder' => 5
		),
        'inplayscenes_scenetype' => array(
            'title' => 'Szenenarten',
            'description' => 'Soll es die Möglichkeit geben für verschiedene Szenenarten? Zur Auswahl stehen: privat, nach Absprache oder offen.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 6
        ),
        'inplayscenes_hide' => array(
            'title' => 'Szenen verstecken',
            'description' => 'Soll es die Möglichkeit geben Inplayszenen zu verstecken? Zählt als Szenenart, wenn diese aktiviert sind.',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 7
        ),
        'inplayscenes_hidetype' => array(
            'title' => 'Art des Verstecken',
            'description' => 'Wie sollen die Szenen versteckt werden? Entweder gibt es eine einheitliche Regelung für alle Szenen oder die Mitglieder können es für jede Szene individuell entscheiden.',
            'optionscode' => 'select\n0=Szenen weiterhin anzeigen, aber nicht lesbar\n1=komplett verstecken\n2=individuell entscheiden lassen',
            'value' => '0', // Default
            'disporder' => 8
        ),
        'inplayscenes_hideprofile' => array(
            'title' => 'versteckte Szenen im Profil',
            'description' => 'Wie sollen versteckte Szenen im Profil angezeigt werden? Entweder gibt es eine einheitliche Regelung für alle Szenen oder die Mitglieder können es für jede Szene individuell entscheiden.',
            'optionscode' => 'select\n0=Szenen normal anzeigen, aber Links sind nicht anklickbar\n1=komplett verstecken\n2=individuell entscheiden lassen',
            'value' => '0', // Default
            'disporder' => 9
        ),
        'inplayscenes_trigger' => array(
            'title' => 'Triggerwarnungen',
            'description' => 'Sollen User ein Feld ausfüllen können, in welchem sie zusätzlich Triggerthemen angeben können, die in der Szene vorkommen können?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 10
        ),
        'inplayscenes_information_thread' => array(
            'title' => 'Szeneninformationen: Showthread',
            'description' => 'Sollen im Template Showthread Szeneinformation angezeigt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 11
        ),
        'inplayscenes_information_posts' => array(
            'title' => 'Szeneninformationen: Postbit',
            'description' => 'Sollen in den Templates Postbit und Postbit_Classic Szeneinformation angezeigt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 12
        ),
        'inplayscenes_allscene' => array(
            'title' => 'Übersicht aller Inplayszenen',
            'description' => 'Soll es eine zentrale Übersicht aller Inplayszenen des Forums geben, die mit verschiedenen Filtern durchsucht und gefiltert werden können?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 13
        ),
        'inplayscenes_nextuser' => array(
            'title' => 'Anzeige vom nächster Poster',
            'description' => 'Wie soll auf der Übersichtsseite gezeigt werden, dass man selbst nicht in der Szene dran ist?',
            'optionscode' => 'select\n0=Username vom nächsten Charakter\n1=Einfaches - du bist nicht dran\n2=Spitzname vom nächsten Mitglied',
            'value' => '0', // Default
            'disporder' => 14
        ),
        'inplayscenes_playername' => array(
            'title' => 'Spitzname',
            'description' => 'Wie lautet die FID / der Identifikator von dem Profilfeld/Steckbrieffeld für den Spitznamen?<br>
			<b>Hinweis:</b> Bei klassischen Profilfeldern muss eine Zahl eintragen werden. Bei dem Steckbrief-Plugin von Risuena muss der Name/Identifikator des Felds eingetragen werden.',
            'optionscode' => 'text',
            'value' => '4', // Default
            'disporder' => 15
        ),
        'inplayscenes_months' => array(
            'title' => 'Monatsanzeige',
            'description' => 'Wie sollen die Monaten bei der Ausgabe vom Szenendatum angezeigt werden? Als Zahl oder mit dem Monatsnamen? Monatsnamen können in der Sprachdatei oder im ACP unter Sprache geändert werden.',
            'optionscode' => 'select\n0=Zahl\n1=Wort',
            'value' => '0', // Default
            'disporder' => 16
        ),
        'inplayscenes_groupcolor' => array(
            'title' => 'farbige Usernamen',
            'description' => 'Sollen die Charakternamen die an der Szene teilnehmen in ihrer Gruppenfarbe dargestellt werden?',
            'optionscode' => 'yesno',
            'value' => '1', // Default
            'disporder' => 17
        ),
        'inplayscenes_scenesedit' => array(
            'title' => 'Szeneninformationen bearbeiten',
            'description' => 'Können die User die Szeneninformationen ihrer Szenen über den Button im Showthread mit allen angehängten Accounts bearbeiten?<br>Sonst können sie das nur mit dem Account, der in dieser Szene eingetragen ist.',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 18
        ),
        'inplayscenes_inactive_scenes' => array(
            'title' => 'inaktive Szenen',
            'description' => 'Ab wie vielen Monaten, ohne Post gelten Szenen als inaktiv? Inaktive Szenen werden automatisch ins Archiv verschoben. AU-Szenen sind nicht davon betroffen. (0 = schließt die Funktion aus)',
            'optionscode' => 'numeric',
            'value' => '0', // Default
            'disporder' => 19
        ),
    );

    $gid = $db->fetch_field($db->write_query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name = 'inplayscenes' LIMIT 1;"), "gid");

    if ($type == 'install') {
        foreach ($setting_array as $name => $setting) {
          $setting['name'] = $name;
          $setting['gid'] = $gid;
          $db->insert_query('settings', $setting);
        }  
    }

    if ($type == 'update') {

        // Einzeln durchgehen 
        foreach ($setting_array as $name => $setting) {
            $setting['name'] = $name;
            $check = $db->write_query("SELECT name FROM ".TABLE_PREFIX."settings WHERE name = '".$name."'"); 
            $check = $db->num_rows($check);
            $setting['gid'] = $gid;
            if ($check == 0) { 
              $db->insert_query('settings', $setting);
            } else { 
                
                $current_setting = $db->fetch_array($db->write_query("SELECT title, description, optionscode, disporder FROM ".TABLE_PREFIX."settings 
                WHERE name = '".$db->escape_string($name)."'
                "));
            
                $update_needed = false;
                $update_data = array();
            
                if ($current_setting['title'] != $setting['title']) {
                    $update_data['title'] = $setting['title'];
                    $update_needed = true;
                }
                if ($current_setting['description'] != $setting['description']) {
                    $update_data['description'] = $setting['description'];
                    $update_needed = true;
                }
                if ($current_setting['optionscode'] != $setting['optionscode']) {
                    $update_data['optionscode'] = $setting['optionscode'];
                    $update_needed = true;
                }
                if ($current_setting['disporder'] != $setting['disporder']) {
                    $update_data['disporder'] = $setting['disporder'];
                    $update_needed = true;
                }
            
                if ($update_needed) {
                    $db->update_query('settings', $update_data, "name = '".$db->escape_string($name)."'");
                }
            }
        }
    }

    rebuild_settings();
}

// TEMPLATES
function inplayscenes_templates($mode = '') {

    global $db;

    $templates[] = array(
        'title'		=> 'inplayscenes_counter',
        'template'	=> $db->escape_string('<li><a href="misc.php?action=inplayscenes">{$lang->inplayscenes}</a> ({$allinplayscenes_open}/{$allinplayscenes})</li>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_editscene',
        'template'	=> $db->escape_string('<html>
        <head>
        <title>{$mybb->settings[\'bbname\']} - {$lang->inplayscenes_editscene}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		{$inplayscenes_edit_error}
		<div class="tborder">
			<div class="thead">{$lang->inplayscenes_editscene}</div>
			<div class="trow1">
				<form action="misc.php?action=do_editinplayscenes&tid={$tid}" method="post">
					<div class="inplayscenes-formular_input-row">
						<div class="inplayscenes-formular_input-desc">
							<b>{$lang->inplayscenes_fields_partners}</b>
							<div class="smalltext">
								{$lang->inplayscenes_fields_partners_desc}
							</div>
						</div>
						<div class="inplayscenes-formular_input-input">
							<input type="text" class="textbox" name="characters" id="characters" maxlength="1155" value="{$characters}" />
						</div>
					</div>

					<div class="inplayscenes-formular_input-row">
						<div class="inplayscenes-formular_input-desc">
							<b>{$lang->inplayscenes_fields_scenesetting}</b>
							<div class="smalltext">
								{$lang->inplayscenes_fields_scenesetting_desc}
							</div>
						</div>
						<div class="inplayscenes-formular_input-input">
							{$postorder_select} {$scenetype_select}
						</div>
					</div>

					<div class="inplayscenes-formular_input-row" id="hidetype_row" style="display: none;">
						<div class="inplayscenes-formular_input-desc">
							<b>{$lang->inplayscenes_fields_hide}</b>
							<div class="smalltext">
								{$inplayscenes_fields_hide_desc}
							</div>
						</div>
						<div class="inplayscenes-formular_input-input">
							{$hidetype_select} {$hideprofile_select}
						</div>
					</div>

					<div class="inplayscenes-formular_input-row">
						<div class="inplayscenes-formular_input-desc">
							<b>{$lang->inplayscenes_fields_date}</b>
							<div class="smalltext">
								{$lang->inplayscenes_fields_date_desc}
							</div>
						</div>
						<div class="inplayscenes-formular_input-input">
							<input type="date" name="date" class="textbox" value="{$date}" />
							<div class="smalltext">{$lang->inplayscenes_fields_date_hint}</div>
						</div>
					</div>
					
					{$own_inplayscenesfields}
					{$trigger_warning}

					<div class="inplayscenes-formular_button">
						<input type="submit" name="do_editinplayscenes" value="{$lang->inplayscenes_editscene_button}" class="button" />
					</div>
				</form>
			</div>
		</div>
		{$footer}
        </body>
        </html>
        <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
        <script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
        <script type="text/javascript">
	
        document.addEventListener(\'DOMContentLoaded\', function () {
        var scenetypeSelect = document.querySelector(\'select[name="scenetype"]\');
        var hidetypeRow = document.getElementById(\'hidetype_row\');

        function toggleHidetypeRow() {
            if (scenetypeSelect.value == \'3\') {
                hidetypeRow.style.display = \'flex\';
            } else {
                hidetypeRow.style.display = \'none\';
            }
        }

        // Initial check when the page loads
        toggleHidetypeRow();

        // Check whenever the selection changes
        scenetypeSelect.addEventListener(\'change\', toggleHidetypeRow);
        });

        <!--
        if(use_xmlhttprequest == "1")
        {
        MyBB.select2();
        $("#characters").select2({
        placeholder: "{$lang->inplayscenes_search_character}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
        url: "xmlhttp.php?action=get_users",
        dataType: \'json\',
        data: function (term, page) {
        return {
        query: term, // search term
        };
        },
        results: function (data, page) { // parse the results into the format expected by Select2.
        // since we are using custom formatting functions we do not need to alter remote JSON data
        return {results: data};
        }
        },
        initSelection: function(element, callback) {
        var query = $(element).val();
        if (query !== "") {
        var newqueries = [];
        exp_queries = query.split(",");
        $.each(exp_queries, function(index, value ){
        if(value.replace(/\s/g, \'\') != "")
        {
        var newquery = {
        id: value.replace(/,\s?/g, ","),
        text: value.replace(/,\s?/g, ",")
        };
        newqueries.push(newquery);
        }
        });
        callback(newqueries);
        }
        }
        })
        }
        // -->
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_editscene_fields',
        'template'	=> $db->escape_string('<div class="inplayscenes-formular_input-row">
        <div class="inplayscenes-formular_input-desc">
		<b>{$title}</b>
		<div class="smalltext">
			{$description}
		</div>
        </div>
        <div class="inplayscenes-formular_input-input">
		{$code}
        </div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_error_hidenscenes',
        'template'	=> $db->escape_string('{$lang->inplayscenes_hide_nopermission_1}
        <ol>
        <li>{$lang->inplayscenes_hide_nopermission_2}</li>
        <li>{$lang->inplayscenes_hide_nopermission_3}</li>
        <li>{$lang->inplayscenes_hide_nopermission_4}</li>
        </ol>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_forumdisplay',
        'template'	=> $db->escape_string('<div class="smalltext">
        <b>{$lang->inplayscenes_characters}</b> {$partnerusers}<br>
        <b>{$lang->inplayscenes_scenesetting}</b> {$scenetype}{$postorder}<br>
        <b>{$lang->inplayscenes_date}</b> {$scenedate}<br>
        {$triggerwarning}
        {$inplayscenesfields}
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_forumdisplay_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_memberprofile',
        'template'	=> $db->escape_string('<fieldset>
        <div class="thead">{$lang->inplayscenes_memberprofile}</div>	
        <div class="inplayscenes_memberprofile">
		<div class="inplayscenes_memberprofile-mainplays">
			{$allinplayscenes_year}
		</div>
		<div class="inplayscenes_memberprofile-sideplays">
			<div class="inplayscenes_memberprofile-auplays">
				<h3>{$lang->inplayscenes_memberprofile_au}</h3>
				{$allsideplayscenes}
			</div>
			<div class="inplayscenes_memberprofile-out">
				<h3>{$lang->inplayscenes_memberprofile_notrelevant}</h3>
				{$allnotrelevantscenes}
			</div>
		</div>		
        </div>
        </fieldset><br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_memberprofile_month',
        'template'	=> $db->escape_string('<div class="tcat">{$monthname}</div>{$scenes}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_memberprofile_none',
        'template'	=> $db->escape_string('<div class="inplayscenes_memberprofile-scenes">{$lang->inplayscenes_memberprofile_none}</div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_memberprofile_scenes',
        'template'	=> $db->escape_string('<div class="inplayscenes_memberprofile-scenes">{$scenedate} {$status}  - <a href="{$scenelink}" class="sceneLink">{$subject}</a><br><span class="smalltext">{$partnerusers}</span></div> 
        <script>
        document.addEventListener("DOMContentLoaded", function() {
        var links = document.querySelectorAll(".sceneLink");
        links.forEach(function(link) {
            if (link.getAttribute("href") === "") {
                link.removeAttribute("href");
                link.style.pointerEvents = "none";
                link.style.textDecoration = "none";
            }
        });
        });
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_memberprofile_year',
        'template'	=> $db->escape_string('<div class="thead">{$year}</div>{$scenes_by_month}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_newthread',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1" width="20%">
		<strong>{$lang->inplayscenes_fields_partners}</strong>
		<div class="smalltext">{$lang->inplayscenes_fields_partners_desc}</div>
        </td>
        <td class="trow1">
		<span class="smalltext">
			<input type="text" class="textbox" name="characters" id="characters" size="40" maxlength="1155" value="{$characters}" style="min-width: 347px; max-width: 100%;" /> 
			<br>
			{$lang->inplayscenes_fields_partners_hint}
		</span> 
        </td>
        </tr>
        <tr>
        <td class="trow1" width="20%">
		<strong>{$lang->inplayscenes_fields_scenesetting}</strong>
		<div class="smalltext">{$lang->inplayscenes_fields_scenesetting_desc}</div>
        </td>
        <td class="trow1">
		{$postorder_select} {$scenetype_select}
        </td>
        </tr>
        <tr id="hidetype_row" style="display: none;">
        <td class="trow1" width="20%">
        <strong>{$lang->inplayscenes_fields_hide}</strong>
        <div class="smalltext">{$inplayscenes_fields_hide_desc}</div>
        </td>
        <td class="trow1">
        {$hidetype_select} {$hideprofile_select}
        </td>
        </tr>
        <tr>
        <td class="trow1" width="20%"><strong>{$lang->inplayscenes_fields_date}</strong>
		<div class="smalltext">{$lang->inplayscenes_fields_date_desc}</div>
        </td>
        <td class="trow1">
        <span class="smalltext">
			<input type="date" name="date" class="textbox" value="{$date}" \>		
			<br>
			{$lang->inplayscenes_fields_date_hint}
		</span>		
        </td>	
        </tr>

        {$own_inplayscenesfields}
        {$trigger_warning}

        <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
        <script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
        <script type="text/javascript">
        document.addEventListener(\'DOMContentLoaded\', function () {
        var scenetypeSelect = document.querySelector(\'select[name="scenetype"]\');
        var hidetypeRow = document.getElementById(\'hidetype_row\');

        function toggleHidetypeRow() {
            if (scenetypeSelect.value == \'3\') {
                hidetypeRow.style.display = \'table-row\';
            } else {
                hidetypeRow.style.display = \'none\';
            }
        }

        // Initial check when the page loads
        toggleHidetypeRow();

        // Check whenever the selection changes
        scenetypeSelect.addEventListener(\'change\', toggleHidetypeRow);
        });

        <!--
        if(use_xmlhttprequest == "1")
        {
		MyBB.select2();
		$("#characters").select2({
			placeholder: "{$lang->inplayscenes_search_character}",
			minimumInputLength: 2,
			maximumSelectionSize: \'\',
			multiple: true,
			ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
				url: "xmlhttp.php?action=get_users",
				dataType: \'json\',
				data: function (term, page) {
					return {
						query: term, // search term
					};
				},
				results: function (data, page) { // parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to alter remote JSON data
					return {results: data};
				}
			},
			initSelection: function(element, callback) {
				var query = $(element).val();
				if (query !== "") {
					var newqueries = [];
					exp_queries = query.split(",");
					$.each(exp_queries, function(index, value ){
						if(value.replace(/\s/g, \'\') != "")
						{
							var newquery = {
								id: value.replace(/,\s?/g, ","),
								text: value.replace(/,\s?/g, ",")
							};
							newqueries.push(newquery);
						}
					});
					callback(newqueries);
				}
			}
		})
        }
        // -->
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_newthread_fields',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1" width="20%"><strong>{$title}</strong>
		<div class="smalltext">{$description}</div>
        </td>
        <td class="trow1">
		<span class="smalltext">
			{$code}
		</span>		
        </td>	
        </tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->inplayscenes_overview}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<div class="tborder">
			<div class="thead"><strong>{$lang->inplayscenes_overview}</strong></div>
			<div class="trow1">
				<form id="inplayscenes_filter" method="get" action="misc.php?action=all_inplayscenes">
					<input type="hidden" name="action" value="all_inplayscenes" />
					<div>
						<div class="inplayscenes_overview-filter-table">

							<div class="inplayscenes_overview-filter-row">
								<div class="tcat">{$lang->inplayscenes_overview_filter_status}</div>
								<div class="inplayscenes_overview-filter-input">
									<select name="scenestatus" data-selected="{$scenestatus}">
										<option value="all">{$lang->inplayscenes_overview_filter_status_all}</option>
										<option value="active">{$lang->inplayscenes_overview_filter_status_active}</option>
										<option value="archive">{$lang->inplayscenes_overview_filter_status_archive}</option>
									</select>
									<select name="area" data-selected="{$area}">
										<option value="all_area">{$lang->inplayscenes_overview_filter_area_all}</option>
										<option value="inplayarea">{$lang->inplayscenes_overview_filter_area_inplayarea}</option>
										<option value="auarea">{$lang->inplayscenes_overview_filter_area_sideplay}</option>
									</select>
								</div>
							</div>

							<div class="inplayscenes_overview-filter-row">
								<div class="tcat">{$lang->inplayscenes_overview_filter_postorder}</div>
								<div class="inplayscenes_overview-filter-input">
									<select name="postorder" data-selected="{$postorder_input}">
										<option value="-1">{$lang->inplayscenes_overview_filter_postorder_all}</option>
										<option value="1">{$lang->inplayscenes_postorder_fixed}</option>
										<option value="0">{$lang->inplayscenes_postorder_none}</option>
									</select>
									{$scenetype_filter}
								</div>
							</div>

							<div class="inplayscenes_overview-filter-row">
								<div class="tcat">{$lang->inplayscenes_overview_filter_character}</div>
								<div class="inplayscenes_overview-filter-input">
								<input type="text" class="textbox" name="charactername" id="charactername" value="{$charactername}" />
								</div>
							</div>

							{$player_filter}
							
						</div>

						<div class="inplayscenes_overview-button">
							<input type="submit" value="{$lang->inplayscenes_overview_filter_button}" class="button" />
						</div>

					</div>
				</form>
			</div>
			<div class="thead">{$scene_counter}</div>
			{$sort_bit}
			<div class="inplayscenes_overview-scene-table trow1">
				{$scenes_bit}
			</div>
			<div class="trow1">
				{$multipage}
			</div>
		</div>
		{$footer}
        </body>
        </html>

        <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
        <script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
        <script type="text/javascript">
        <!--
		document.addEventListener(\'DOMContentLoaded\', function() {
		// Funktionen zur Auswahl der Werte
		var selects = document.querySelectorAll(\'select[data-selected]\');
		selects.forEach(function(select) {
			var selectedValue = select.getAttribute(\'data-selected\');
			if (selectedValue !== null) {
				select.value = selectedValue;
			}
		});
		// Initialisierung von select2
		if(use_xmlhttprequest == "1")
		{
			MyBB.select2();

			var select2Fields = {
				\'#charactername\': "{$charactername_placeholder}",
				\'#playername\': "{$playername_placeholder}"
			};

			$.each(select2Fields, function(fieldId, placeholder) {
				$(fieldId).select2({
					placeholder: placeholder,
					minimumInputLength: 2,
					multiple: false,
					allowClear: true,
					ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
						url: "xmlhttp.php?action=get_users",
						dataType: \'json\',
						data: function (term, page) {
							return {
								query: term, // search term
								from_page: "all_inplayscenes",
								selectField: fieldId
							};
						},
						results: function (data, page) { // parse the results into the format expected by Select2.
							return {results: data};
						}
					},
					initSelection: function(element, callback) {
						var value = $(element).val();
						if (value !== "") {
							callback({
								id: value,
								text: value
							});
						}
					},
					// Allow the user entered text to be selected as well
					createSearchChoice: function(term, data) {
						if ($(data).filter(function() {
							return this.text.localeCompare(term) === 0;
						}).length === 0) {
							return {id: term, text: term};
						}
					},
				});

				$(\'[for=\' + fieldId.replace(\'#\', \'\') + \']\').on(\'click\', function(){
					$(fieldId).select2(\'open\');
					return false;
				});
			});
		}
        });
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview_player_filter',
        'template'	=> $db->escape_string('<div class="inplayscenes_overview-filter-row">
        <div class="tcat">{$lang->inplayscenes_overview_filter_player}</div>
        <div class="inplayscenes_overview-filter-input">
        <input type="text" class="textbox" name="playername" id="playername" value="{$playername}" />
        </div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview_scenetype_filter',
        'template'	=> $db->escape_string('<select name="scenetype" data-selected2="{$scenetype_select}">
        <option value="-1">{$lang->inplayscenes_scenetype_all}</option>
        <option value="0">{$lang->inplayscenes_scenetype_private}</option>
        <option value="1">{$lang->inplayscenes_scenetype_agreed}</option>
        <option value="2">{$lang->inplayscenes_scenetype_open}</option>
        </select>

        <script type="text/javascript">
        document.addEventListener(\'DOMContentLoaded\', function() {
        // Überprüfen und Setzen der select-Werte
        var selects = document.querySelectorAll(\'select[data-selected2]\');
		selects.forEach(function(select) {
			var selectedValue = select.getAttribute(\'data-selected2\');
			if (selectedValue !== null && selectedValue !== "") {
				select.value = selectedValue;
			}
		});
        });
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview_scene',
        'template'	=> $db->escape_string('<div class="inplayscenes_overview-scene-row">
        <div class="inplayscenes_overview-scene-column">
		<strong>{$AUscene}{$scenetype}{$postorder}</strong>
        </div>
        <div class="inplayscenes_overview-scene-column">
		<strong><a href="{$scenelink}" target="_blank" class="sceneLink">{$subject}</a></strong><br>
		<b>{$lang->inplayscenes_characters}</b> {$partnerusers}<br>
		<b>{$lang->inplayscenes_date}</b> {$scenedate}<br>
		{$triggerwarning}
		{$inplayscenesfields}
        </div>
        <div class="inplayscenes_overview-scene-column">
		<strong><a href="{$lastpostlink}" target="_blank">{$lang->inplayscenes_lastpost}</a></strong><br>
		{$lastpostdate}<br />{$lastposter}
        </div>
        </div>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
        var links = document.querySelectorAll(".sceneLink");
        links.forEach(function(link) {
            if (link.getAttribute("href") === "") {
                link.removeAttribute("href");
                link.style.pointerEvents = "none";
                link.style.textDecoration = "none";
            }
        });
        });
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview_scene_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview_scene_none',
        'template'	=> $db->escape_string('<div class="inplayscenes_overview-none">{$lang->inplayscenes_overview_none}</div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_overview_scene_sort',
        'template'	=> $db->escape_string('<div class="trow1">
        <form id="inplayscenes_sort" method="get" action="misc.php?action=all_inplayscenes">
        <input type="hidden" name="action" value="all_inplayscenes">
		<input type="hidden" name="scenestatus" value="{$scenestatus}">
		<input type="hidden" name="area" value="{$area}">
		<input type="hidden" name="postorder" value="{$postorder_input}">
		{$scenetype_input}
		<input type="hidden" name="charactername" value="{$charactername}">
		<input type="hidden" name="playername" value="{$playername}">
		
		<div class="inplayscenes_overview-sort">
			{$lang->inplayscenes_overview_sort}
			<select name="type" data-selected="{$type}">
				<option value="date">{$lang->inplayscenes_overview_sort_date}</option>
				<option value="lastpost">{$lang->inplayscenes_overview_sort_lastpost}</option>
			</select>
			<select name="sort" data-selected="{$sort}">
				<option value="ASC">{$lang->inplayscenes_overview_sort_asc}</option>
				<option value="DESC">{$lang->inplayscenes_overview_sort_desc}</option>
			</select>
			<input type="submit" value="{$lang->inplayscenes_overview_sort_button}" class="button" />
		</div>
        </form>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_pdf_fields',
        'template'	=> $db->escape_string('{$inplayscene[\'partnerusers\']}<br>{$inplayscene[\'scenedate\']}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postbit',
        'template'	=> $db->escape_string('<div class="inplayscenes-postbit">
        <div class="thead">{$lang->inplayscenes_postbit}</div>
        <div class="smalltext">
        <b>{$lang->inplayscenes_characters}</b> {$partnerusers}<br>
        <b>{$lang->inplayscenes_scenesetting}</b> {$scenetype}{$postorder}<br>
		<b>{$lang->inplayscenes_date}</b> {$scenedate}<br>
		{$triggerwarning}
		{$inplayscenesfields}
        </div> 
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postbit_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postbit_pdf',
        'template'	=> $db->escape_string('<a href="misc.php?action=inplayscenes_pdf&amp;pid={$post[\'pid\']}" target="_blank" title="{$lang->inplayscenes_postbit_pdf}">{$lang->inplayscenes_postbit_pdf}</a>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postingreminder',
        'template'	=> $db->escape_string('<html>
        <head>
        <title>{$mybb->settings[\'bbname\']} - {$lang->inplayscenes_postingreminder}</title>
        {$headerinclude}
        </head>
        <body>
		{$header}
		<div class="tborder">
			<div class="thead"><strong>{$lang->inplayscenes_postingreminder}</strong></div>
			<div class="inplayscenes_postingreminder-desc trow1">{$postingreminder_desc}</div>
			<div class="trow1">
				{$reminder_bit}
			</div>
		</div>
		{$footer}
        </body>
        </html>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postingreminder_banner',
        'template'	=> $db->escape_string('<div class="red_alert"><a href="misc.php?action=postingreminder">{$banner_text}</a></div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postingreminder_bit',
        'template'	=> $db->escape_string('<div class="tcat">{$countday}</div>
        <div class="inplayscenes_postingreminder-scene-table">
        {$scene_rows}
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postingreminder_none',
        'template'	=> $db->escape_string('<div class="inplayscenes_postingreminder-none">{$lang->inplayscenes_postingreminder_none}</div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postingreminder_scene',
        'template'	=> $db->escape_string('<div class="inplayscenes_postingreminder-scene-row">
        <div class="inplayscenes_postingreminder-scene-column">
        <strong>{$AUscene} <a href="{$scenelink}" target="_blank">{$subject}</a></strong><br>
		<b>{$lang->inplayscenes_characters}</b> {$partnerusers}<br>
		<b>{$lang->inplayscenes_date}</b> {$scenedate}<br>
		{$triggerwarning}
		{$inplayscenesfields}
        </div>
        <div class="inplayscenes_postingreminder-scene-column">
		<strong><a href="{$lastpostlink}" target="_blank">{$lang->inplayscenes_lastpost}</a></strong><br>
		{$lastpostdate}<br />{$lastposter}
        </div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_postingreminder_scene_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_showthread',
        'template'	=> $db->escape_string('<tr>
        <td class="trow1">
		<div class="inplayscenes_showthread">
			<div class="tcat">{$lang->inplayscenes_showthread}</div>
			<div class="inplayscenes_showthread-bit">
				<div class="inplayscenes_showthread-label"><strong>{$lang->inplayscenes_showthread_characters}</strong></div>
				<div class="inplayscenes_showthread-value">{$partnerusers}</div>
			</div>
			<div class="inplayscenes_showthread-bit">
				<div class="inplayscenes_showthread-label"><strong>{$lang->inplayscenes_showthread_scenesetting}</strong></div>
				<div class="inplayscenes_showthread-value">{$scenetype}{$postorder}</div>
			</div>
			<div class="inplayscenes_showthread-bit">
				<div class="inplayscenes_showthread-label"><strong>{$lang->inplayscenes_showthread_date}</strong></div>
				<div class="inplayscenes_showthread-value">{$scenedate}</div>
			</div>
			{$triggerwarning}
			{$inplayscenesfields}
		</div>
        </td>
        </tr>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_showthread_add',
        'template'	=> $db->escape_string('<a href="misc.php?action=add_openscenes&amp;tid={$tid}" class="button new_reply_button">{$lang->inplayscenes_showthread_openscene}</a>&nbsp;'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_showthread_edit',
        'template'	=> $db->escape_string('<a href="misc.php?action=inplayscenes_edit&amp;tid={$tid}" class="button new_reply_button">{$lang->inplayscenes_showthread_edit}</a>&nbsp;'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_showthread_fields',
        'template'	=> $db->escape_string('<div class="inplayscenes_showthread-bit">
        <div class="inplayscenes_showthread-label"><strong>{$title}</strong></div>
        <div class="inplayscenes_showthread-value">{$value}</div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_showthread_pdf',
        'template'	=> $db->escape_string('<a href="misc.php?action=inplayscenes_pdf&amp;tid={$tid}" target="_blank" class="button new_reply_button">{$lang->inplayscenes_showthreadt_pdf}</a>&nbsp;'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_showthread_relevant',
        'template'	=> $db->escape_string('<a href="misc.php?action=update_relevantstatus&amp;tid={$tid}" class="button new_reply_button">{$lang->inplayscenes_showthreadt_relevant}</a>&nbsp;'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user',
        'template'	=> $db->escape_string('<html>
        <head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->inplayscenes_user}</title>
		{$headerinclude}
        </head>
        <body>
		{$header}
		<div class="tborder">
			
			{$user_settings}

			<div class="inplayscenes_user-scene-summary trow1">
				<div class="thead">{$scene_summary}</div>

				<div class="inplayscenes_user-scene-sort tcat">
					<form id="inplayscenes_sortChara" method="get" action="misc.php?action=inplayscenes">
						<input type="hidden" name="action" value="inplayscenes">
						{$lang->inplayscenes_user_sort}
						<select name="typeChara" data-selectedChara="{$typeChara}">
							<option value="date">{$lang->inplayscenes_user_sort_date}</option>
							<option value="lastpost">{$lang->inplayscenes_user_sort_lastpost}</option>
						</select>
						<select name="sortChara" data-selectedChara="{$sortChara}">
							<option value="ASC">{$lang->inplayscenes_user_sort_asc}</option>
							<option value="DESC">{$lang->inplayscenes_user_sort_desc}</option>
						</select>
						<input type="submit" value="{$lang->inplayscenes_user_sort_button}" class="button">
					</form>
				</div>
				
				{$character_bit}
			</div>

			{$footer}
		</div>
        </body>
        </html>

        <script type="text/javascript">
        document.addEventListener(\'DOMContentLoaded\', function() {
		// Überprüfen und Setzen der select-Werte
		var selects = document.querySelectorAll(\'select[data-selectedChara]\');
		selects.forEach(function(select) {
			var selectedValue = select.getAttribute(\'data-selectedChara\');
			if (selectedValue !== null && selectedValue !== "") {
				select.value = selectedValue;
			}
		});
        });
        </script>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_usersettings',
        'template'	=> $db->escape_string('<div class="thead">{$lang->inplayscenes_usersettings}</div>
        <form action="misc.php?action=inplayscenes" method="post">
        <div class="inplayscenes_user-settings trow1">
		<div class="inplayscenes_user-setting-row">
			<div>
				<strong>{$lang->inplayscenes_usersettings_reminder}</strong>
				<div class="smalltext">{$lang->inplayscenes_usersettings_reminder_desc}</div>
			</div>
			<div>
				<input type="number" id="reminder_days" class="textbox" name="reminder_days" value="{$reminder_days}" min="0">
			</div>
		</div>
		{$notification_setting}
		<div class="inplayscenes_user-button">
			<div class="smalltext">{$lang->inplayscenes_usersettings_hint}</div>
			<input type="submit" name="do_userscenesettings" value="{$lang->inplayscenes_usersettings_button}" class="button">
		</div>
        </div>
        </form>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_usersettings_notification',
        'template'	=> $db->escape_string('<div class="inplayscenes_user-setting-row">
        <div>
		<strong>{$lang->inplayscenes_usersettings_notification}</strong>
		<div class="smalltext">{$lang->inplayscenes_usersettings_notification_desc}</div>
        </div>
        <div>
		{$type_radiobuttons}
        </div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user_character',
        'template'	=> $db->escape_string('<div class="inplayscenes_user-character-scenes">
        <div class="inplayscenes_user-scene-header">
        <div class="thead">
			{$scene_counter_character}<span>{$reminder_status}</span>
		</div>
        </div>

        <!-- Inplay Szenen -->
        <div class="inplayscenes_user-scene-category">
		<div class="tcat">{$scene_counter_inplay}</div>
		<div class="inplayscenes_user-scene-table">
			<div class="inplayscenes_user-scene-row trow2">
				<div class="inplayscenes_user-scene-col"><strong>{$lang->inplayscenes_user_scene_next}</strong></div>
				<div class="inplayscenes_user-scene-col"><strong>{$lang->inplayscenes_user_scene_info}</strong></div>
				<div class="inplayscenes_user-scene-col"><strong>{$lang->inplayscenes_user_scene_last}</strong></div>
			</div>
			{$inplay_scene_bit}
		</div>
        </div>

        <!-- AU Szenen -->
        <div class="inplayscenes_user-scene-category">
		<div class="tcat">{$scene_counter_sideplay}</div>
		<div class="inplayscenes_user-scene-table">
			<div class="inplayscenes_user-scene-row trow2">
				<div class="inplayscenes_user-scene-col"><strong>{$lang->inplayscenes_user_scene_next}</strong></div>
				<div class="inplayscenes_user-scene-col"><strong>{$lang->inplayscenes_user_scene_info}</strong></div>
				<div class="inplayscenes_user-scene-col"><strong>{$lang->inplayscenes_user_scene_last}</strong></div>
			</div>
			{$au_scene_bit}
		</div>
        </div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user_none',
        'template'	=> $db->escape_string('<div class="inplayscenes_user-scene-none">{$lang->inplayscenes_user_scene_none}</div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user_scene',
        'template'	=> $db->escape_string('<div class="inplayscenes_user-scene-row">
        <div class="inplayscenes_user-scene-col">
        <strong><center>{$isnext}</center></strong>
        </div>
        <div class="inplayscenes_user-scene-col">
        {$scene_infos}
        </div>
        <div class="inplayscenes_user-scene-col">
        {$lastpost_bit}
        </div>
        </div>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user_scene_fields',
        'template'	=> $db->escape_string('<b>{$title}:</b> {$value}<br>'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user_scene_infos',
        'template'	=> $db->escape_string('<strong><a href="{$scenelink}" target="_blank">{$subject}</a></strong><br>
        <b>{$lang->inplayscenes_characters}</b> {$partnerusers}<br>
        <b>{$lang->inplayscenes_scenesetting}</b> {$scenetype}{$postorder}<br>
        <b>{$lang->inplayscenes_date}</b> {$scenedate}<br>
        {$triggerwarning}
        {$inplayscenesfields}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    $templates[] = array(
        'title'		=> 'inplayscenes_user_scene_last',
        'template'	=> $db->escape_string('<strong><a href="{$lastpostlink}" target="_blank">{$lang->inplayscenes_lastpost}</a></strong><br>{$lastpostdate}<br>{$lastposter}'),
        'sid'		=> '-2',
        'version'	=> '',
        'dateline'	=> TIME_NOW
    );

    if ($mode == "update") {

        foreach ($templates as $template) {
            $query = $db->simple_select("templates", "tid, template", "title = '".$template['title']."' AND sid = '-2'");
            $existing_template = $db->fetch_array($query);

            if($existing_template) {
                if ($existing_template['template'] !== $template['template']) {
                    $db->update_query("templates", array(
                        'template' => $template['template'],
                        'dateline' => TIME_NOW
                    ), "tid = '".$existing_template['tid']."'");
                }
            }   
            else {
                $db->insert_query("templates", $template);
            }
        }
        
	
    } else {
        foreach ($templates as $template) {
            $check = $db->num_rows($db->simple_select("templates", "title", "title = '".$template['title']."'"));
            if ($check == 0) {
                $db->insert_query("templates", $template);
            }
        }
    }
}

// STYLESHEET MASTER
function inplayscenes_stylesheet() {

    global $db;
    
    $css = array(
		'name' => 'inplayscenes.css',
		'tid' => 1,
		'attachedto' => '',
		'stylesheet' =>	'.inplayscenes-formular_input-row {
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
        max-height: 450px;
        overflow: auto;
        }

        .inplayscenes_memberprofile-sideplays {
        width: 37%;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
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
        }',
		'cachefile' => $db->escape_string(str_replace('/', '', 'inplayscenes.css')),
		'lastmodified' => TIME_NOW
	);

    return $css;
}

// STYLESHEET UPDATE
function inplayscenes_stylesheet_update() {

    // Update-Stylesheet
    // wird an bestehende Stylesheets immer ganz am ende hinzugefügt
    $update = '';

    // Definiere den  Überprüfung-String (muss spezifisch für die Überprüfung sein)
    $update_string = '';

    return array(
        'stylesheet' => $update,
        'update_string' => $update_string
    );
}

// UPDATE CHECK
function inplayscenes_is_updated(){

    global $db, $mybb;

    $template = $db->fetch_field($db->simple_select("templates", "tid", "title = 'inplayscenes_overview_player_filter'"),"tid");

    if (!$template) {
        return false;
    }
    
    return true;
}
