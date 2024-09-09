<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
 * @return bool true
 */
function rpgstuff_meta()
{
	global $page, $lang, $plugins;

	$sub_menu = array();
	$sub_menu = $plugins->run_hooks("admin_rpgstuff_menu", $sub_menu);

	$page->add_menu_item("RPG Erweiterungen", "rpgstuff", "index.php?module=rpgstuff", 60, $sub_menu);

	return true;
}

/**
 * @param string $action
 *
 * @return string
 */
function rpgstuff_action_handler($action)
{
	global $page, $lang, $plugins;

	$page->active_module = "rpgstuff";

	$actions = array(
		'stylesheet_updates' => array('active' => 'stylesheet_updates', 'file' => 'stylesheet_updates.php'),
		'template_updates' => array('active' => 'template_updates', 'file' => 'template_updates.php'),
		'plugin_updates' => array('active' => 'plugin_updates', 'file' => 'plugin_updates.php')
    );
	$actions = $plugins->run_hooks("admin_rpgstuff_action_handler", $actions);

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "stylesheet_updates", "title" => "Stylesheets überprüfen", "link" => "index.php?module=rpgstuff-stylesheet_updates");
	$sub_menu['20'] = array("id" => "template_updates", "title" => "Templates überprüfen", "link" => "index.php?module=rpgstuff-template_updates");
	$sub_menu['30'] = array("id" => "plugin_updates", "title" => "Plugins aktualisieren", "link" => "index.php?module=rpgstuff-plugin_updates");

	$sub_menu = $plugins->run_hooks("admin_rpgstuff_menu_updates", $sub_menu);

	if(!isset($actions[$action]))
	{
		$page->active_action = $action = "plugin_updates";
	}

	$sidebar = new SidebarItem("Updates");
	$sidebar->add_menu_items($sub_menu, $actions[$action]['active']);

	$page->sidebar .= $sidebar->get_markup();

	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		return "plugin_updates.php";
	}
}

/**
 * @return array
 */
function rpgstuff_admin_permissions()
{
	global $lang, $plugins;

	$admin_permissions = array(
		"stylesheet_updates" => "Kann Stylesheets von Plugins updaten?",
		"template_updates" => "Kann Templates von Plugins updaten?",
		"plugin_updates" => "Kann Plugins updaten?"
    );

	$admin_permissions = $plugins->run_hooks("admin_rpgstuff_permissions", $admin_permissions);

	return array("name" => "RPG Erweiterungen", "permissions" => $admin_permissions, "disporder" => 60);
}
