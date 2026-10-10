<?php declare(strict_types=1);

/** Generic embedded BASE3 display. */
final class block_base3 extends block_base {

	public function init(): void { $this->title = get_string('pluginname', 'block_base3'); }
	public function instance_allow_multiple(): bool { return true; }
	public function has_config(): bool { return false; }
	public function applicable_formats(): array { return ['all' => true]; }

	public function get_content() {
		if ($this->content !== null) return $this->content;
		$this->content = new stdClass();
		$this->content->text = '';
		$this->content->footer = '';
		$name = trim((string) ($this->config->name ?? ''));
		if ($name === '') return $this->content;
		if (!preg_match('/^[a-z0-9_-]+$/i', $name)) return $this->content;
		$data = null;
		$raw = (string) ($this->config->data ?? '');
		if ($raw !== '') {
			try { $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
			catch (JsonException $error) { debugging($error->getMessage(), DEBUG_DEVELOPER); return $this->content; }
		}
		require_once $GLOBALS['CFG']->dirroot . '/local/base3/classes/Runtime.php';
		try {
			$this->content->text = \local_base3\Runtime::renderEmbedded($name, $data);
		} catch (Throwable $error) {
			debugging($error->getMessage(), DEBUG_DEVELOPER);
		}
		if (!empty($this->config->title)) $this->title = (string) $this->config->title;
		return $this->content;
	}
}
