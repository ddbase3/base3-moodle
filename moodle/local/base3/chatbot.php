<?php declare(strict_types=1);

// This is the document loaded into the floating chatbot panel. It is not a
// second BASE3 bootstrap: Runtime remains the single composition boundary.
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/classes/ChatbotBubble.php';
require_once __DIR__ . '/classes/Runtime.php';

if (!\local_base3\ChatbotBubble::visible()) {
	http_response_code(403);
	exit('Chatbot access denied.');
}

$PAGE->set_url(new moodle_url('/local/base3/chatbot.php'));
$PAGE->set_context(context_system::instance());
$data = \local_base3\ChatbotBubble::chatbotData();
$html = \local_base3\Runtime::renderEmbedded('chatbotdisplay', $data);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
?>
<!doctype html>
<html lang="<?php echo s(current_language()); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo s(get_string('chatbotbubble_launcher', 'local_base3')); ?></title>
	<style>
		html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; }
		body { display: flex; min-width: 0; font-family: Arial, sans-serif; }
		body > .base3-chatbot { box-sizing: border-box; flex: 1 1 auto; min-width: 0; min-height: 0; height: 100%; --base3-chatbot-height: 100%; }
		body > .base3-chatbot.is-started { --base3-chatbot-height: 100%; }
	</style>
</head>
<body>
<?php echo $html; ?>
</body>
</html>
