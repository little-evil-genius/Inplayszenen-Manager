<?php
/**
 * RPGStuff admin plugin_updates page.
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item("Plugins aktualisieren", "index.php?module=rpgstuff-plugin_updates");

$page->output_header("Plugins aktualisieren");

$sub_tabs['update_plugins'] = array(
    'title' => "Plugins aktualisieren",
    'link' => "index.php?module=rpgstuff-plugin_updates",
    'description' => "In diesem Bereich kannst du nach neueren Versionen der installierten RPG-Plugins suchen."
);
$page->output_nav_tabs($sub_tabs, 'update_plugins');


$form = new Form('index.php?module=rpgstuff-plugin_updates', 'post');
$form_container = new FormContainer('Plugin aktualisieren');
$form_container->output_row_header('Plugin');
$form_container->output_row_header('Update');

$plugins->run_hooks("admin_rpgstuff_update_plugins");

if($form_container->num_rows() == 0){
    $form_container->output_cell('Aktuell hast du keine Plugins, die hier aufgelistet werden können.', array("colspan" => 2));
    $form_container->construct_row();
}

$form_container->end();
$form->end();
$page->output_footer();