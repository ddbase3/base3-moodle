<?php declare(strict_types=1);

namespace local_base3;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Usermanager\Permission;
use Base3MoodleLab\Feature\GlobalChatbotLauncherFeature;

/** Checks the single BASE3 feature decision for Moodle's floating launcher. */
final class ChatbotBubble {

	public static function visible(): bool {
		if (function_exists('during_initial_install') && during_initial_install()) return false;
		Runtime::boot();
		$authorization = Runtime::container()->get(IAuthorizationService::class);
		return $authorization->decide(new AuthorizationRequest(
			$authorization->getDefaultAuthority(),
			Permission::for('feature', GlobalChatbotLauncherFeature::getName())
		))->isAllowed();
	}
}
