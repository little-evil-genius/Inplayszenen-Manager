<?php
/**
 * Copyright 2014 MyBB Group, All Rights Reserved
 * @author risuena & little.evil.genius
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

	$page->add_menu_item($lang->rpg_stuff, "rpgstuff", "index.php?module=rpgstuff", 60, $sub_menu);

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
		'plugin_updates' => array('active' => 'plugin_updates', 'file' => 'plugin_updates.php')
    );
	$actions = $plugins->run_hooks("admin_rpgstuff_action_handler", $actions);

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "stylesheet_updates", "title" => $lang->stylesheet_updates, "link" => "index.php?module=rpgstuff-stylesheet_updates");
	$sub_menu['20'] = array("id" => "plugin_updates", "title" => $lang->plugin_updates, "link" => "index.php?module=rpgstuff-plugin_updates");

	$sub_menu = $plugins->run_hooks("admin_rpgstuff_menu_updates", $sub_menu);

	if(!isset($actions[$action]))
	{
		$page->active_action = $action = "stylesheet_updates";
	}

	$sidebar = new SidebarItem($lang->sidebar);
	$sidebar->add_menu_items($sub_menu, $actions[$action]['active']);

	$page->sidebar .= $sidebar->get_markup();

	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		return "stylesheet_updates.php";
	}
}

/**
 * @return array
 */
function rpgstuff_admin_permissions()
{
	global $lang, $plugins;

	$admin_permissions = array(
		"stylesheet_updates" => $lang->can_updates_stylesheet,
		"plugin_updates" => $lang->can_updates_plugin
    );

	$admin_permissions = $plugins->run_hooks("admin_rpgstuff_permissions", $admin_permissions);

	return array("name" => $lang->rpg_stuff, "permissions" => $admin_permissions, "disporder" => 60);
}
