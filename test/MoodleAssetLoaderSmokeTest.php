<?php declare(strict_types=1);

// Standalone regression test: php test/MoodleAssetLoaderSmokeTest.php

namespace Base3\Api {
	interface IContainer { public function get(string $id): mixed; }
	interface IClassMap { public function getInstanceByInterfaceName(string $interface, string $name): mixed; }
	interface IDisplay {}
	interface IOutput { public function getOutput(string $out = 'html', bool $final = false): string; }
	interface IAssetResolver { public function resolve(string $path): string; }
}

namespace Base3\Hook\Api {
	interface IHookManager { public function dispatch(string $name): void; }
}

namespace Base3Moodle\Api {
	interface IBase3MoodleSettings { public function getAdministrationConfig(): array; }
}

namespace {
	use Base3\Api\IAssetResolver;
	use Base3\Api\IClassMap;
	use Base3\Api\IContainer;
	use Base3\Api\IDisplay;
	use Base3\Api\IOutput;
	use Base3\Hook\Api\IHookManager;
	use Base3Moodle\Api\IBase3MoodleSettings;
	use local_base3\AdminTools;
	use local_base3\Runtime;

	function verify(bool $valid, string $message): void {
		if (!$valid) throw new \RuntimeException('FAILED: ' . $message);
	}

	class moodle_url {
		public function __construct(private readonly string $url) {}
		public function out(bool $escaped = true): string { return $this->url; }
	}

	final class TestRequirements {
		public array $scripts = [];
		public int $requests = 0;
		public array $stylesheets = [];

		public function js(moodle_url $url, bool $inhead = false): void {
			$this->requests++;
			$position = $inhead ? 'head' : 'footer';
			$this->scripts[$position][$url->out(false)] = true;
		}

		public function css(moodle_url $url): void {
			$this->stylesheets[$url->out(false)] = true;
		}
	}

	$GLOBALS['PAGE'] = (object) ['requires' => new TestRequirements()];

	$asset = 'https://moodle.example/local/base3/assets/ClientStack/assetloader/assetloader.min.js?v=123';
	$resolver = new class($asset) implements IAssetResolver {
		public function __construct(private readonly string $asset) {}
		public function resolve(string $path): string {
			if (str_ends_with($path, 'assetloader/assetloader.min.js')) return $this->asset;
			return 'https://moodle.example/local/base3/assets/ClientStack/jqueryui/jquery-ui.css?v=123';
		}
	};
	$classmap = new class implements IClassMap {
		public function getInstanceByInterfaceName(string $interface, string $name): mixed {
			if ($interface !== IOutput::class || $name !== 'chatbotdisplay') return null;
			return new class implements IOutput {
				public function getOutput(string $out = 'html', bool $final = false): string {
					return '<section data-chatbot-state="ready"></section>';
				}
			};
		}
	};
	$settings = new class implements IBase3MoodleSettings {
		public function getAdministrationConfig(): array { return []; }
	};
	$container = new class($resolver, $classmap, $settings) implements IContainer {
		public function __construct(
			private readonly IAssetResolver $resolver,
			private readonly IClassMap $classmap,
			private readonly IBase3MoodleSettings $settings
		) {}

		public function get(string $id): mixed {
			return match ($id) {
				IAssetResolver::class => $this->resolver,
				IClassMap::class => $this->classmap,
				IBase3MoodleSettings::class => $this->settings,
				default => throw new \RuntimeException('Unexpected service: ' . $id)
			};
		}
	};
	$hooks = new class implements IHookManager {
		public function dispatch(string $name): void {}
	};

	require_once dirname(__DIR__) . '/moodle/local/base3/classes/Runtime.php';
	require_once dirname(__DIR__) . '/moodle/local/base3/classes/AdminTools.php';
	Runtime::setBootResult($container, $hooks);

	AdminTools::requireAssets();
	$html = Runtime::renderEmbedded('chatbotdisplay');
	$second = Runtime::renderEmbedded('chatbotdisplay');
	Runtime::requireClientStackAssetLoader();

	verify($html === '<section data-chatbot-state="ready"></section>', 'rendered display is unchanged');
	verify($second === $html, 'subsequent embedded displays render normally');
	verify(!str_contains($html, '<script'), 'embedded HTML must not add a second raw script');
	verify($GLOBALS['PAGE']->requires->requests === 1,
		'administration and embedded display register the ClientStack loader only once');
	verify(count($GLOBALS['PAGE']->requires->scripts['footer'] ?? []) === 1,
		'administration and embedded display share one Moodle asset registration');
	verify(isset($GLOBALS['PAGE']->requires->scripts['footer'][$asset]),
		'the original ClientStack asset URL is preserved');
	verify(count($GLOBALS['PAGE']->requires->stylesheets) === 1,
		'administration retains its normal CSS dependency');

	echo "MoodleAssetLoaderSmokeTest OK: Moodle requirements own the script, no duplicate raw tag.\n";
}
