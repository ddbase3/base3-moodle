<?php declare(strict_types=1);

namespace local_base3;

use Base3\Api\IAssetResolver;
use Base3\Api\IClassMap;
use Base3\Api\IDisplay;
use Base3Moodle\Api\IBase3MoodleSettings;
use RuntimeException;

final class AdminTools {

	public static function render(): string {
		Runtime::boot();
		$c = Runtime::container();
		$settings = $c->get(IBase3MoodleSettings::class);
		if (!$settings instanceof IBase3MoodleSettings) throw new RuntimeException('BASE3 Moodle Lab settings unavailable.');
		$tab = $c->get(IClassMap::class)->getInstanceByInterfaceName(IDisplay::class, 'tabcontroldisplay');
		if (!$tab instanceof IDisplay) throw new RuntimeException('ClientStack TabControlDisplay is not installed.');
		$active = isset($_GET['view']) && is_scalar($_GET['view']) ? trim((string) $_GET['view']) : '';
		$tab->setData(['tabs' => $settings->getVisibleAdministrationConfig(), 'active' => $active,
			'empty_message' => get_string('notools', 'local_base3')]);
		return $tab->getOutput('html', false);
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
