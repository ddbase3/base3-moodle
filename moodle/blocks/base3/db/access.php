<?php declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

$capabilities = [
	'block/base3:addinstance' => [
		'captype' => 'write', 'contextlevel' => CONTEXT_BLOCK,
		'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW]
	],
	'block/base3:myaddinstance' => [
		'captype' => 'write', 'contextlevel' => CONTEXT_SYSTEM,
		'archetypes' => ['user' => CAP_ALLOW]
	]
];
