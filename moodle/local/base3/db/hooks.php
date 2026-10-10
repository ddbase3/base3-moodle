<?php declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

$callbacks = [[
	'hook' => \core\hook\output\before_standard_footer_html_generation::class,
	'callback' => \local_base3\hook_callbacks::class . '::embed_global_display',
	'priority' => 0
]];
