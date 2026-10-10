<?php declare(strict_types=1);

namespace local_base3;

use core\hook\output\before_standard_footer_html_generation;

/** Native Moodle page hook for one optional globally configured BASE3 display. */
final class hook_callbacks {

	public static function embed_global_display(before_standard_footer_html_generation $hook): void {
		global $CFG;
		if (!empty($CFG->upgraderunning) || (function_exists('during_initial_install') && during_initial_install())) return;

		$html = '';
		$name = trim((string) (get_config('local_base3', 'globaldisplay') ?: ''));
		if ($name !== '') {
			if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
				throw new \InvalidArgumentException('Invalid global BASE3 display name.');
			}
			$text = trim((string) (get_config('local_base3', 'globaldata') ?: ''));
			$data = $text === '' ? null : json_decode($text, true, 512, JSON_THROW_ON_ERROR);
			require_once __DIR__ . '/Runtime.php';
			$html = Runtime::renderEmbedded($name, $data);
		}

		require_once __DIR__ . '/ChatbotBubble.php';
		if (ChatbotBubble::visible()) {
			$html .= ChatbotBubble::renderLauncher();
		}
		if ($html !== '') $hook->add_html($html);
	}
}
