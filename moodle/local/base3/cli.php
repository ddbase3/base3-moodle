<?php declare(strict_types=1);

// Moodle CLI entry points must declare CLI_SCRIPT before loading config.php.
define('CLI_SCRIPT', true);

// Administrative CLI entry point: php public/local/base3/cli.php --status|--rebuild|--worker
require_once __DIR__ . '/../../../config.php';
require_once $CFG->libdir . '/clilib.php';
require_once __DIR__ . '/classes/Runtime.php';

[$options, $unrecognized] = cli_get_params(['status' => false, 'rebuild' => false, 'worker' => false], ['s' => 'status']);
if ($unrecognized) cli_error('Unexpected arguments: ' . implode(', ', $unrecognized));
\local_base3\Runtime::boot();
if ($options['rebuild']) {
	\local_base3\Runtime::container()->get(\Base3\Api\IClassMap::class)->generate(true);
	echo "BASE3 class map rebuilt.\n";
} elseif ($options['worker']) {
	\local_base3\Runtime::runWorker();
	echo "BASE3 worker cycle executed.\n";
} else {
	foreach (\local_base3\Runtime::catalog()->all() as $name => $info) {
		echo $name . ' ' . $info['version'] . PHP_EOL;
	}
}
\local_base3\Runtime::finish();
