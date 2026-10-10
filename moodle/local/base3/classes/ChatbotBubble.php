<?php declare(strict_types=1);

namespace local_base3;

/** Host-owned configuration and access boundary for the floating chatbot. */
final class ChatbotBubble {

	public static function enabled(): bool {
		return (bool) get_config('local_base3', 'chatbotbubble_enabled');
	}

	public static function visible(): bool {
		if (!self::enabled()) return false;
		if (function_exists('during_initial_install') && during_initial_install()) return false;

		$visibility = (string) (get_config('local_base3', 'chatbotbubble_visibility') ?: 'authenticated');
		switch ($visibility) {
			case 'all':
				return true;
			case 'authenticated':
				return isloggedin() && !isguestuser();
			case 'administrators':
				return isloggedin() && !isguestuser()
					&& has_capability('moodle/site:config', \context_system::instance());
			default:
				return false;
		}
	}

	/** @return array{config_group:string,config_name:string} */
	public static function chatbotData(): array {
		$group = trim((string) (get_config('local_base3', 'chatbotbubble_group') ?: 'chatbot'));
		$name = trim((string) (get_config('local_base3', 'chatbotbubble_name') ?: 'default'));
		foreach ([$group, $name] as $part) {
			if (!preg_match('/^[a-zA-Z0-9_.-]{1,64}$/D', $part)) {
				throw new \InvalidArgumentException('Invalid floating chatbot configuration identity.');
			}
		}
		return ['config_group' => $group, 'config_name' => $name];
	}

	public static function renderLauncher(): string {
		$identity = self::chatbotData();
		$label = trim((string) (get_config('local_base3', 'chatbotbubble_label') ?: ''));
		if ($label === '') $label = get_string('chatbotbubble_launcher', 'local_base3');
		$title = trim((string) (get_config('local_base3', 'chatbotbubble_title') ?: ''));
		if ($title === '') $title = $label;

		$attrs = [
			'ajax-url' => (new \moodle_url('/local/base3/chatbot.php'))->out(false),
			'button-label' => $label,
			'launcher-icon' => (new \moodle_url('/local/base3/assets/chatbot/chatbot-icon.svg'))->out(false),
			'header-icon' => '',
			'header-title' => $title,
			'config-group' => $identity['config_group'],
			'config-name' => $identity['config_name'],
			'close-label' => get_string('chatbotbubble_close', 'local_base3'),
			'loading-label' => get_string('chatbotbubble_loading', 'local_base3'),
			'load-error' => get_string('chatbotbubble_error', 'local_base3'),
			'mode-overlay-label' => get_string('chatbotbubble_modeoverlay', 'local_base3'),
			'mode-modal-label' => get_string('chatbotbubble_modemodal', 'local_base3'),
			'mode-docked-label' => get_string('chatbotbubble_modedocked', 'local_base3'),
			'mode-cover-label' => get_string('chatbotbubble_modecover', 'local_base3'),
			'mode-fullscreen-label' => get_string('chatbotbubble_modefullscreen', 'local_base3'),
			'mode-overlay-enabled' => '1',
			'mode-modal-enabled' => self::settingBool('chatbotbubble_modal', true),
			'mode-docked-enabled' => self::settingBool('chatbotbubble_docked', false),
			'mode-cover-enabled' => self::settingBool('chatbotbubble_cover', false),
			'mode-fullscreen-enabled' => self::settingBool('chatbotbubble_fullscreen', false),
			'resizable-overlay' => self::settingBool('chatbotbubble_resizeoverlay', false),
			'resizable-modal' => self::settingBool('chatbotbubble_resizemodal', false),
			'resizable-docked' => self::settingBool('chatbotbubble_resizedocked', false),
			'remember-last-mode' => self::settingBool('chatbotbubble_remembermode', false),
			'reopen-overlay' => self::settingBool('chatbotbubble_reopenoverlay', false),
			'reopen-docked' => self::settingBool('chatbotbubble_reopendocked', false)
		];

		$markup = '<div id="base3-chatbot-config" hidden';
		foreach ($attrs as $key => $value) {
			$markup .= ' data-' . $key . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
		}
		$markup .= '></div>';

		$css = (new \moodle_url('/local/base3/assets/chatbot/bubble.css', ['v' => '1']))->out(false);
		$js = (new \moodle_url('/local/base3/assets/chatbot/bubble.js', ['v' => '1']))->out(false);
		return '<link rel="stylesheet" href="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">'
			. $markup
			. '<script src="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '" defer></script>';
	}

	private static function settingBool(string $key, bool $default): string {
		$value = get_config('local_base3', $key);
		if ($value === false || $value === null) return $default ? '1' : '0';
		return (string) $value === '1' ? '1' : '0';
	}
}
