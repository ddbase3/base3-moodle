<?php declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

$tasks = [[
	'classname' => '\\local_base3\\task\\run_workers',
	'blocking' => 0,
	'minute' => '*/5',
	'hour' => '*',
	'day' => '*',
	'month' => '*',
	'dayofweek' => '*'
]];
