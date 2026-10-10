<?php declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once __DIR__ . '/classes/Runtime.php';

admin_externalpage_setup('local_base3_modules');
require_capability('moodle/site:config', context_system::instance());

// The module catalog is prepared by boot(), not by loading the local plugin.
\local_base3\Runtime::boot();
$modules = \local_base3\Runtime::catalog()->all();
$PAGE->set_title(get_string('modules', 'local_base3'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modules', 'local_base3'));
$table = new html_table();
$table->head = ['Module', 'Version', 'Namespace'];
foreach ($modules as $name => $info) {
	$table->data[] = [s($name), s($info['version']), s($info['namespace'])];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
