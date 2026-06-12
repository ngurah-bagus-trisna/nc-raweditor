<?php

declare(strict_types=1);

namespace OCA\RawEditor\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\RawEditor\Listener\LoadFilesPluginListener;
use OCA\RawEditor\Listener\RafFileListener;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'raweditor';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadFilesPluginListener::class);
		$context->registerEventListener(NodeCreatedEvent::class, RafFileListener::class);
		$context->registerEventListener(NodeWrittenEvent::class, RafFileListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
