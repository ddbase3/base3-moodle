<?php declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/classes/Runtime.php';

$name = required_param('name', PARAM_ALPHANUMEXT);
$datatext = optional_param('data', '', PARAM_RAW);
if (!preg_match('/^[a-z0-9_-]+$/i', $name)) throw new invalid_parameter_exception('Invalid BASE3 display name.');
$data = null;
if ($datatext !== '') {
	if (strlen($datatext) > 1048576) throw new invalid_parameter_exception('BASE3 display data exceeds size limit.');
	try { $data = json_decode($datatext, true, 512, JSON_THROW_ON_ERROR); }
	catch (JsonException) { throw new invalid_parameter_exception('Invalid BASE3 display data.'); }
}
$PAGE->set_url(new moodle_url('/local/base3/display.php', ['name' => $name]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('BASE3: ' . $name);
$PAGE->set_heading('BASE3: ' . $name);

// Load the normal ClientStack loader for dynamically embedded BASE3 displays.
\local_base3\Runtime::boot();
$resolver = \local_base3\Runtime::container()->get(\Base3\Api\IAssetResolver::class);
$asset = 'plugin/ClientStack/assets/assetloader/assetloader.min.js';
$url = $resolver->resolve($asset);
if ($url !== $asset) $PAGE->requires->js(new moodle_url($url), false);

echo $OUTPUT->header();
echo \local_base3\Runtime::render($name, $data);
echo $OUTPUT->footer();
