<?php declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/classes/Runtime.php';

// The endpoint itself only transports BASE3 outputs. Outputs own their
// domain permissions. A Moodle sesskey is mandatory for POST mutations.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') require_sesskey();

$name = isset($_GET['name']) && is_scalar($_GET['name']) ? (string) $_GET['name'] : '';
$out = isset($_GET['out']) && is_scalar($_GET['out']) ? (string) $_GET['out'] : 'html';
if (!preg_match('/^[a-z0-9_-]+$/i', $name) || !preg_match('/^[a-z0-9]+$/i', $out)) {
	http_response_code(400);
	exit('Invalid BASE3 output request.');
}
$encoded = $_GET['base3_display_data'] ?? $_POST['base3_display_data'] ?? null;
$data = null;
if ($encoded !== null) {
	if (!is_string($encoded) || strlen($encoded) > 1048576 || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/', $encoded)) {
		http_response_code(400);
		exit('Invalid BASE3 display data.');
	}
	$json = base64_decode(strtr(str_pad($encoded, strlen($encoded) + (4 - strlen($encoded) % 4) % 4, '='), '-_', '+/'), true);
	try {
		if (!is_string($json)) throw new RuntimeException('Invalid BASE3 display encoding.');
		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	} catch (Throwable) {
		http_response_code(400);
		exit('Invalid BASE3 display data.');
	}
}

try {
	\local_base3\Runtime::boot();
	if ($name === 'chatbotdisplay' && is_array($data)
		&& is_string($data['config_group'] ?? null)
		&& trim($data['config_group']) === \Base3MoodleLab\Service\Base3MoodleLabSettings::GLOBAL_CHATBOT_GROUP
		&& is_string($data['config_name'] ?? null)
		&& trim($data['config_name']) === \Base3MoodleLab\Service\Base3MoodleLabSettings::GLOBAL_CHATBOT_NAME) {
		require_once __DIR__ . '/classes/ChatbotBubble.php';
		if (!\local_base3\ChatbotBubble::visible()) {
			http_response_code(403);
			exit('Global chatbot access denied.');
		}
	}
	$types = [
		'html' => 'text/html', 'php' => 'text/html', 'json' => 'application/json',
		'xml' => 'application/xml', 'txt' => 'text/plain', 'css' => 'text/css', 'js' => 'application/javascript'
	];
	header('Content-Type: ' . ($types[$out] ?? 'text/html') . '; charset=utf-8');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: no-store');
	echo \local_base3\Runtime::render($name, $data, $out, true);
	\local_base3\Runtime::finish();
} catch (Throwable $error) {
	error_log('[BASE3][AJAX] ' . $error->getMessage());
	if ($error instanceof \required_capability_exception || $error instanceof \require_login_exception) {
		http_response_code(403);
	} elseif ($error instanceof InvalidArgumentException) {
		http_response_code(400);
	} elseif ($error instanceof RuntimeException && str_starts_with($error->getMessage(), 'BASE3 output is not installed:')) {
		http_response_code(404);
	} else {
		http_response_code(500);
	}
	exit('BASE3 output failed.');
}
