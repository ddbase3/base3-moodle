<?php declare(strict_types=1);

namespace local_base3\task;

final class run_workers extends \core\task\scheduled_task {
	public function get_name(): string { return get_string('workertask', 'local_base3'); }
	public function execute(): void {
		require_once dirname(__DIR__) . '/Runtime.php';
		\local_base3\Runtime::runWorker();
	}
}
