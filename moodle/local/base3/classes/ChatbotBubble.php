<?php declare(strict_types=1);

namespace local_base3;

/** Native Moodle visibility boundary for the shared global chatbot launcher. */
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
}
