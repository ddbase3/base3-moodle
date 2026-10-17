<?php declare(strict_types=1);

// Run from the BASE3 Moodle source checkout: php test/ChatbotLocaleSmokeTest.php

function current_language(): string {
	return 'de_du';
}

function s(string $text): string {
	return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_string(string $key, string $component): string {
	return 'Chatbot';
}

class moodle_url {
	public function __construct(string $url) {}
}

class context_system {
	public static function instance(): self {
		return new self();
	}
}

class TestContainer {
	public function get(string $id): object {
		if ($id !== \Base3\Language\Api\ILanguage::class) {
			throw new RuntimeException('Unexpected service requested: ' . $id);
		}
		return new \Base3Moodle\Adapter\MoodleLanguage();
	}
}

$root = dirname(__DIR__);
require_once $root . '/modules/Base3Framework/src/Language/Api/ILanguage.php';
require_once $root . '/modules/Base3Moodle/src/Adapter/MoodleLanguage.php';

$base = sys_get_temp_dir() . '/base3-moodle-chatbot-locale-' . bin2hex(random_bytes(8));
$directory = $base . '/moodle/local/base3';
if (!mkdir($directory . '/classes', 0700, true)) {
	throw new RuntimeException('Could not create temporary test fixture.');
}

$files = [
	$base . '/config.php',
	$directory . '/classes/ChatbotBubble.php',
	$directory . '/classes/Runtime.php',
	$directory . '/chatbot.php'
];

try {
	file_put_contents($files[0], <<<'CODE'
<?php
$PAGE = new class {
    public function set_url(moodle_url $url): void {}
    public function set_context(context_system $context): void {}
};
CODE
	);
	file_put_contents($files[1], <<<'CODE'
<?php
namespace local_base3;
class ChatbotBubble {
    public static function visible(): bool { return true; }
    public static function chatbotData(): array { return ['config_group' => 'chatbot', 'config_name' => 'default']; }
}
CODE
	);
	file_put_contents($files[2], <<<'CODE'
<?php
namespace local_base3;
class Runtime {
    public static function renderEmbedded(string $name, mixed $data): string { return '<div>Chatbot</div>'; }
    public static function container(): \TestContainer { return new \TestContainer(); }
}
CODE
	);
	if (!copy($root . '/moodle/local/base3/chatbot.php', $files[3])) {
		throw new RuntimeException('Could not copy chatbot template into fixture.');
	}

	ob_start();
	try {
		require $files[3];
		$html = ob_get_contents();
	} finally {
		ob_end_clean();
	}

	if (!str_contains($html, '<html lang="de">')) {
		throw new RuntimeException('Chatbot iframe did not normalize de_du to a valid HTML language tag.');
	}
	if (str_contains($html, 'lang="de_du"')) {
		throw new RuntimeException('Raw Moodle language-pack identifier leaked into iframe.');
	}
	fwrite(STDOUT, "OK: Moodle de_du uses HTML lang=de.\n");
} finally {
	foreach ($files as $file) {
		if (is_file($file)) unlink($file);
	}
	rmdir($directory . '/classes');
	rmdir($directory);
	rmdir($base . '/moodle/local');
	rmdir($base . '/moodle');
	rmdir($base);
}
