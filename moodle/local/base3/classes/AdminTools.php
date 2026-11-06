<?php declare(strict_types=1);

namespace local_base3;

use Base3\Api\IAssetResolver;
use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use RuntimeException;

final class AdminTools {

	public static function render(): string {
		Runtime::boot();
		$c = Runtime::container();
		$display = $c->get(IClassMap::class)->getInstanceByInterfaceName(IDisplay::class, 'settingsdisplay');
		if (!$display instanceof IDisplay) throw new RuntimeException('ClientStack SettingsDisplay is not installed.');
		return $display->getOutput('html', false);
	}

	public static function requireAssets(): void {
		global $PAGE;
		Runtime::boot();
		$resolver = Runtime::container()->get(IAssetResolver::class);
		$root = 'plugin/ClientStack/assets/';
		Runtime::requireClientStackAssetLoader();
		$css = $resolver->resolve($root . 'jqueryui/jquery-ui.css');
		if ($css !== $root . 'jqueryui/jquery-ui.css') $PAGE->requires->css(new \moodle_url($css));
	}
}
