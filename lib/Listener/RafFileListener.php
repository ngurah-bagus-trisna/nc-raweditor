<?php

declare(strict_types=1);

namespace OCA\RawEditor\Listener;

use OCA\RawEditor\BackgroundJob\ScanUserJob;
use OCA\RawEditor\Service\ScanService;
use OCA\RawEditor\Service\UtilsService;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;

/** @template-implements IEventListener<Event|NodeCreatedEvent|NodeWrittenEvent> */
class RafFileListener implements IEventListener {
	public function __construct(
		private readonly ScanService $scanService,
		private readonly IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof NodeCreatedEvent || $event instanceof NodeWrittenEvent) {
			$this->handleNode($event->getNode());
		}
	}

	private function handleNode(\OCP\Files\Node $node): void {
		if (!($node instanceof File) || !UtilsService::isRafFile($node->getName())) {
			return;
		}

		$owner = $node->getOwner();
		if ($owner === null) {
			return;
		}

		$uid = $owner->getUID();
		if (!$this->scanService->isFileInScope($node, $uid)) {
			return;
		}

		$this->scanService->indexFile($uid, $node);
		$this->jobList->add(ScanUserJob::class, ['uid' => $uid]);
	}
}
