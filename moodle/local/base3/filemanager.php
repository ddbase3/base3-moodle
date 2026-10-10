<?php declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/classes/Runtime.php';

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

\local_base3\Runtime::boot();
$container = \local_base3\Runtime::container();
$settings = $container->get(\Base3Moodle\Api\IBase3MoodleSettings::class);
if (!$settings instanceof \Base3Moodle\Api\IBase3MoodleSettings) {
	throw new RuntimeException('BASE3 Moodle administration settings unavailable.');
}
$config = null;
foreach ($settings->getAdministrationConfig() as $tab) {
	foreach (($tab['displays'] ?? []) as $entry) {
		if (($entry['name'] ?? '') !== 'chatbotconfigdisplay') continue;
		if ($config !== null) throw new RuntimeException('Duplicate chatbot settings display.');
		$config = $entry['data'] ?? null;
	}
}
$group = is_array($config) ? (string) ($config['group'] ?? '') : '';
$name = is_array($config) ? (string) ($config['name'] ?? '') : '';
if ($group === '' || $name === '') throw new RuntimeException('Missing configured file manager owner.');
$action = optional_param('fm_action', '', PARAM_ALPHANUMEXT);
$storage = $container->get(\ResourceFoundation\Api\IManagedFileStorageService::class);
$handler = new \Base3Moodle\Adapter\MoodleFileManagerHttpService($storage, new \Base3Moodle\Adapter\MoodleFileUploadService($storage));
$handler->handle($action, $group, $name, (int) $USER->id, 50 * 1024 * 1024);
