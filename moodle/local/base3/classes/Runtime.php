<?php declare(strict_types=1);

namespace local_base3;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IDisplay;
use Base3\CoreFoundation\Api\IAssetResolver;
use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Usermanager\Permission;
use Base3\UiFoundation\Api\ISettingsTab;
use Base3\CoreFoundation\Api\IOutput;
use Base3\CoreFoundation\Hook\Api\IHookManager;
use Base3\CoreFoundation\Logger\Api\ILogger;
use Base3\Core\Worker\MasterWorker;
use RuntimeException;

/** One BASE3 container and lifecycle per Moodle request, shared by all entry points. */
final class Runtime {

	private static ?ModuleCatalog $catalog = null;
	private static ?IContainer $container = null;
	private static ?IHookManager $hooks = null;
	private static bool $booting = false;
	private static bool $finished = false;
	private static ?array $administrationDisplays = null;
	private static bool $assetLoaderRegistered = false;

	public static function boot(): void {
		if (self::$container !== null) return;
		if (self::$booting) throw new RuntimeException('Recursive BASE3 boot is not allowed.');
		self::$booting = true;
		try {
			self::prepareEnvironment();
			require_once __DIR__ . '/MoodleBootstrap.php';
			(new MoodleBootstrap(self::catalog()))->run();
			register_shutdown_function([self::class, 'finish']);
		} finally {
			self::$booting = false;
		}
	}

	public static function setBootResult(IContainer $container, IHookManager $hooks): void {
		if (self::$container !== null) throw new RuntimeException('BASE3 container initialized twice.');
		self::$container = $container;
		self::$hooks = $hooks;
	}

	public static function container(): IContainer {
		return self::$container ?? throw new RuntimeException('BASE3 is not bootstrapped.');
	}

	public static function catalog(): ModuleCatalog {
		return self::$catalog ?? throw new RuntimeException('BASE3 module catalog is unavailable.');
	}

	public static function finish(): void {
		if (self::$finished || self::$hooks === null) return;
		self::$finished = true;
		self::$hooks->dispatch('bootstrap.finish');
	}

	public static function render(string $name, mixed $data = null, string $out = 'html', bool $final = false): string {
		self::boot();
		self::assertDisplayAccess($name);
		$map = self::container()->get(IClassMap::class);
		$display = $map->getInstanceByInterfaceName(IDisplay::class, $name);
		if ($display instanceof IDisplay) {
			$display->setData($data);
			return $display->getOutput($out, $final);
		}
		$output = $map->getInstanceByInterfaceName(IOutput::class, $name);
		if ($output instanceof IOutput) return $output->getOutput($out, $final);
		throw new RuntimeException('BASE3 output is not installed: ' . $name);
	}


	/** Direct entry points must enforce native administration and the tab's feature decision. */
	public static function assertDisplayAccess(string $name): void {
		self::boot();
		if (in_array($name, ['settingsdisplay', 'tabcontroldisplay'], true)) {
			require_login();
			require_capability('moodle/site:config', \context_system::instance());
			return;
		}

		$administration = [];
		if (self::$administrationDisplays === null) {
			foreach (self::container()->get(IClassMap::class)->getInstancesByInterface(ISettingsTab::class) as $tab) {
				if (!$tab instanceof ISettingsTab) continue;
				$display = $tab->getDisplayName();
				if ($display === null || $display === '') continue;
				if ($tab->getParentTabName() === 'gamessettingstab') continue;
				$administration[$display][] = $tab;
			}
			self::$administrationDisplays = $administration;
		}
		if (!isset(self::$administrationDisplays[$name])) return;
		require_login();
		require_capability('moodle/site:config', \context_system::instance());
		foreach (self::$administrationDisplays[$name] as $tab) {
			if ($tab->isVisible()) return;
		}
		throw new RuntimeException('BASE3 administration feature access denied.');
	}

	/** Register the shared browser loader once per Moodle request. */
	public static function requireClientStackAssetLoader(): void {
		if (self::$assetLoaderRegistered) return;
		self::boot();
		$resolver = self::container()->get(IAssetResolver::class);
		$path = 'plugin/ClientStack/assets/assetloader/assetloader.min.js';
		$url = $resolver->resolve($path);
		if ($url === $path) throw new RuntimeException('ClientStack assetloader is missing.');
		global $PAGE;
		$PAGE->requires->js(new \moodle_url($url), false);
		self::$assetLoaderRegistered = true;
	}

	/** Embed BASE3 displays with the registered ClientStack loader. */
	public static function renderEmbedded(string $name, mixed $data = null): string {
		$html = self::render($name, $data);
		self::requireClientStackAssetLoader();
		return $html;
	}

	public static function runWorker(): void {
		self::boot();
		$container = self::container();
		$workers = $container->get('workers');
		if (!is_array($workers)) throw new RuntimeException('BASE3 worker definitions are unavailable.');
		$logger = $container->get(ILogger::class);
		if (!$logger instanceof ILogger) throw new RuntimeException('BASE3 logger is missing.');
		$runner = new MasterWorker($logger, $workers);
		ob_start();
		try {
			$runner->getOutput('txt', true);
		} finally {
			ob_end_clean();
			self::finish();
		}
	}

	private static function prepareEnvironment(): void {
		global $CFG;
		$root = dirname(__DIR__, 4);
		$modules = $root . '/base3/modules/';
		$core = $modules . 'Core/';
		$coreFoundation = $modules . 'CoreFoundation/';
		if (!is_file($core . 'base3.json') || !is_file($coreFoundation . 'base3.json')
			|| !is_file($coreFoundation . 'src/Api/IModuleRegistry.php')) {
			throw new RuntimeException('BASE3 Core or CoreFoundation modules are missing below ' . $modules);
		}
		$data = rtrim((string) $CFG->dataroot, '/\\') . '/base3/';
		foreach ([$data, $data . 'cnf/', $data . 'cache/', $data . 'tmp/', $data . 'userfiles/'] as $directory) {
			if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
				throw new RuntimeException('BASE3 data directory is not writable: ' . $directory);
			}
		}
		$constants = [
			'DIR_ROOT' => $core, 'DIR_FRAMEWORK' => $core,
			'DIR_PLUGIN' => $modules, 'DIR_BASE3' => $modules,
			'DIR_SRC' => $core . 'src/', 'DIR_TEST' => $core . 'test/',
			'DIR_CNF' => $data . 'cnf/', 'DIR_TMP' => $data . 'cache/',
			'DIR_LOCAL' => $data, 'DIR_USERFILES' => $data . 'userfiles/',
			'DIR_TPL' => $core . 'tpl/'
		];
		foreach ($constants as $key => $value) {
			if (defined($key) && constant($key) !== $value) {
				throw new RuntimeException('BASE3 path constant conflict: ' . $key);
			}
			if (!defined($key)) define($key, $value);
		}
		if (getenv('DEBUG') === false) putenv('DEBUG=0');
		require_once $coreFoundation . 'src/Api/IModuleRegistry.php';
		require_once __DIR__ . '/ModuleCatalog.php';
		self::$catalog = new ModuleCatalog($modules);
		self::$catalog->all();
		require_once __DIR__ . '/ModuleAutoloader.php';
		ModuleAutoloader::register(self::$catalog);
		require_once __DIR__ . '/MoodleClassMap.php';
	}
}
