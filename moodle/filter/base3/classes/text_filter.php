<?php declare(strict_types=1);

namespace filter_base3;

/** Converts explicit [[base3 name="..." data='{"...":...}']] tags into normal BASE3 displays. */
final class text_filter extends \core_filters\text_filter {

	public function filter(string $text, array $options = []): string {
		if (!str_contains($text, '[[base3 ')) return $text;
		return preg_replace_callback('~\[\[base3\s+([^\]]+)\]\]~i', function(array $match): string {
			$attrs = [];
			preg_match_all('/([a-z_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $match[1], $fields, PREG_SET_ORDER);
			foreach ($fields as $field) $attrs[strtolower($field[1])] = $field[2] !== '' ? $field[2] : $field[3];
			$name = $attrs['name'] ?? '';
			if (!preg_match('/^[a-z0-9_-]+$/i', $name)) return $match[0];
			$data = null;
			if (isset($attrs['data'])) {
				try { $data = json_decode(html_entity_decode($attrs['data'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR); }
				catch (\JsonException) { return $match[0]; }
			}
			require_once $GLOBALS['CFG']->dirroot . '/local/base3/classes/Runtime.php';
			try { return \local_base3\Runtime::renderEmbedded($name, $data); }
			catch (\Throwable $error) { debugging($error->getMessage(), DEBUG_DEVELOPER); return ''; }
		}, $text) ?? $text;
	}
}
