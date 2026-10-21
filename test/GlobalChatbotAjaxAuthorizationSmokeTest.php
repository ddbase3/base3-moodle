<?php declare(strict_types=1);

/** Run from the Moodle BASE3 source checkout without requiring an installed Moodle runtime. */

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/base3-moodle-global-chatbot-auth-' . bin2hex(random_bytes(8));
$local = $temporary . '/moodle/local/base3';
$code = <<<'CODE'
<?php declare(strict_types=1);

namespace Base3\Api {
	interface IBase { public static function getName(): string; }
}
namespace Base3\Translation\Api {
	interface ITranslation { public function translate(string $set, string $section, string $key, string $fallback = '', array $replacements = []): string; }
}
namespace Base3\Authorization\Api {
	interface IFeature extends \Base3\Api\IBase {
		public function getLabel(): string;
		public function getDescription(): string;
		public function getPermissions(): array;
		public function getDefaultAudience(): array;
	}
	interface IAuthorizationService {
		public function getDefaultAuthority(): string;
		public function decide(\Base3\Authorization\Dto\AuthorizationRequest $request): \Base3\Authorization\Dto\AuthorizationDecision;
		public function decideMany(array $requests): array;
	}
}
namespace Base3\Usermanager {
	final class Permission {
		public function __construct(public string $scope, public string $permission) {}
		public static function for(string $scope, string $permission): self { return new self($scope, $permission); }
	}
}
namespace Base3\Authorization\Dto {
	final class AuthorizationRequest {
		public function __construct(public string $authority, public \Base3\Usermanager\Permission $permission) {}
	}
	final class AuthorizationDecision {
		public function __construct(private bool $allowed) {}
		public function isAllowed(): bool { return $this->allowed; }
	}
}
namespace Base3MoodleLab\Service {
	final class Base3MoodleLabSettings {
		public const GLOBAL_CHATBOT_GROUP = 'chatbot';
		public const GLOBAL_CHATBOT_NAME = 'default';
	}
}
namespace {
	$_SERVER['REQUEST_METHOD'] = 'GET';
	parse_str((string) getenv('MOODLE_TEST_QUERY'), $_GET);
	register_shutdown_function(static function(): void {
		file_put_contents((string)getenv('MOODLE_TEST_STATUS_FILE'), (string)http_response_code());
	});
	function require_sesskey(): void {}
	$GLOBALS['authorization'] = new class implements \Base3\Authorization\Api\IAuthorizationService {
		public function getDefaultAuthority(): string { return 'moodle'; }
		public function decide(\Base3\Authorization\Dto\AuthorizationRequest $request): \Base3\Authorization\Dto\AuthorizationDecision {
			if ($request->authority !== 'moodle' || $request->permission->scope !== 'feature'
				|| $request->permission->permission !== 'globalchatbotlauncherfeature') {
				throw new \RuntimeException('Unexpected feature authorization request.');
			}
			return new \Base3\Authorization\Dto\AuthorizationDecision(getenv('MOODLE_TEST_ALLOWED') === '1');
		}
		public function decideMany(array $requests): array { return []; }
	};
}
CODE;
$stubRuntime = <<<'CODE'
<?php declare(strict_types=1);

namespace local_base3;

final class Runtime {
	public static function boot(): void {}
	public static function container(): object {
		return new class {
			public function get(string $id): object {
				if ($id !== \Base3\Authorization\Api\IAuthorizationService::class) throw new \RuntimeException('Unknown service.');
				return $GLOBALS['authorization'];
			}
		};
	}
	public static function render(string $name, mixed $data, string $out, bool $final): string {
		return 'RENDER:' . $name;
	}
	public static function finish(): void {}
}
CODE;

/** @return array{body:string,status:string} */
function runAjax(string $ajaxFile, string $query, bool $allowed): array {
	$command = [PHP_BINARY, $ajaxFile];
	$statusFile = tempnam(sys_get_temp_dir(), 'base3-ajax-status-');
	if ($statusFile === false) throw new RuntimeException('Cannot create AJAX test status file.');
	$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null,
		array_merge($_ENV, [
			'MOODLE_TEST_QUERY' => $query,
			'MOODLE_TEST_ALLOWED' => $allowed ? '1' : '0',
			'MOODLE_TEST_STATUS_FILE' => $statusFile
		]));
	if (!is_resource($process)) throw new RuntimeException('Failed to execute AJAX test.');
	$output = stream_get_contents($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$status = proc_close($process);
	$httpStatus = (string)file_get_contents($statusFile);
	unlink($statusFile);
	if ($status !== 0 || $error !== '') throw new RuntimeException('AJAX test execution failed: ' . $error);
	return ['body' => (string)$output, 'status' => $httpStatus];
}

function ensure(bool $condition, string $message): void {
	if (!$condition) throw new RuntimeException('FAIL: ' . $message);
}

try {
	if (!mkdir($local . '/classes', 0700, true)) throw new RuntimeException('Cannot create test fixture.');
	copy($root . '/moodle/local/base3/ajax.php', $local . '/ajax.php');
	copy($root . '/moodle/local/base3/classes/ChatbotBubble.php', $local . '/classes/ChatbotBubble.php');
	copy($root . '/modules/Base3MoodleLab/src/Feature/GlobalChatbotLauncherFeature.php', $local . '/classes/GlobalChatbotLauncherFeature.php');
	file_put_contents($temporary . '/config.php', substr($code, 0, -1) . "\n\trequire_once __DIR__ . '/moodle/local/base3/classes/GlobalChatbotLauncherFeature.php';\n}\n");
	file_put_contents($local . '/classes/Runtime.php', $stubRuntime);
	$ajax = $local . '/ajax.php';
	$identity = static fn(string $name): string => rtrim(strtr(base64_encode(json_encode([
		'config_group' => 'chatbot', 'config_name' => $name
	], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
	$global = http_build_query(['name' => 'chatbotdisplay', 'out' => 'html', 'base3_display_data' => $identity('default')]);
	$embedded = http_build_query(['name' => 'chatbotdisplay', 'out' => 'html', 'base3_display_data' => $identity('course-math')]);
	$denied = runAjax($ajax, $global, false);
	ensure($denied['status'] === '403' && $denied['body'] === 'Global chatbot access denied.',
		'denied global AJAX load returns HTTP 403 before rendering');
	// ChatbotDisplay trims the supplied configuration identity before loading it.
	$spaced = rtrim(strtr(base64_encode(json_encode([
		'config_group' => ' chatbot ', 'config_name' => ' default '
	], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
	$spacedRequest = http_build_query(['name' => 'chatbotdisplay', 'base3_display_data' => $spaced]);
	ensure(runAjax($ajax, $spacedRequest, false)['status'] === '403',
		'normalized global identity cannot bypass the feature check');
	ensure(runAjax($ajax, $global, true)['body'] === 'RENDER:chatbotdisplay', 'allowed global AJAX load is dispatched');
	ensure(runAjax($ajax, $embedded, false)['body'] === 'RENDER:chatbotdisplay', 'unrelated embedded chatbot remains available');
	ensure(runAjax($ajax, 'name=otherdisplay&out=html', false)['body'] === 'RENDER:otherdisplay', 'unrelated outputs remain available');
	$invalid = runAjax($ajax, 'name=chatbotdisplay&base3_display_data=invalid!', false);
	ensure($invalid['status'] === '400' && $invalid['body'] === 'Invalid BASE3 display data.',
		'invalid display data returns HTTP 400 before rendering');
	echo "GlobalChatbotAjaxAuthorizationSmokeTest OK: denied and allowed global loads, embedded and unrelated displays.\n";
} finally {
	foreach ([
		$local . '/ajax.php', $local . '/classes/Runtime.php',
		$local . '/classes/ChatbotBubble.php', $local . '/classes/GlobalChatbotLauncherFeature.php',
		$temporary . '/config.php'
	] as $file) if (is_file($file)) unlink($file);
	if (is_dir($local . '/classes')) rmdir($local . '/classes');
	if (is_dir($local)) rmdir($local);
	if (is_dir($temporary . '/moodle/local')) rmdir($temporary . '/moodle/local');
	if (is_dir($temporary . '/moodle')) rmdir($temporary . '/moodle');
	if (is_dir($temporary)) rmdir($temporary);
}
