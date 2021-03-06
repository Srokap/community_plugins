<?php
/**
 * Action for combining two plugin projects
 */

$old_guid = (int)get_input('old_guid');
$new_guid = (int)get_input('new_guid');

$old_project = get_entity($old_guid);
$new_project = get_entity($new_guid);

if (!($old_project instanceof PluginProject) ||
	!($new_project instanceof PluginProject)) {
	register_error(elgg_echo('plugins:action:combine:invalid_guids'));
	forward(REFERER);
}

$old_name = $old_project->title;

// move releases for the old project to the new project
$params = array(
	'types' => 'object',
	'subtypes' => 'plugin_release',
	'container_guids' => $old_project->guid,
	'limit' => 0,
);
$releases = elgg_get_entities($params);
foreach ($releases as $release) {
	$release->container_guid = $new_project->guid;
	$release->save();
}

// move download count to new project
$annotation_name = get_metastring_id('download', TRUE);
if ($annotation_name) {
	$dbprefix = elgg_get_config('dbprefix');
	$query = "UPDATE {$dbprefix}annotations
		SET entity_guid=$new_project->guid
		WHERE entity_guid=$old_project->guid AND name_id=$annotation_name";
	update_data($query);
}

$old_project->delete();

system_message(elgg_echo('plugins:action:combine:success'));
forward(REFERER);
