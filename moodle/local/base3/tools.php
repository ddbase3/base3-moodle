<?php declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once __DIR__ . '/classes/Runtime.php';
require_once __DIR__ . '/classes/AdminTools.php';

admin_externalpage_setup('local_base3_tools');
require_capability('moodle/site:config', context_system::instance());
\local_base3\AdminTools::requireAssets();
$PAGE->set_title(get_string('tools', 'local_base3'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('tools', 'local_base3'));
echo \local_base3\AdminTools::render();
echo $OUTPUT->footer();
