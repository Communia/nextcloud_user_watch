<?php

declare(strict_types=1);

namespace OCA\UserWatch\AppInfo;

use Closure;
use OCA\UserWatch\Listeners\UserEventsListener;
use OCA\UserWatch\Settings\Admin\UserWatchSettingsForm;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use function OCP\Log\logger;

class Application extends App implements IBootstrap {
	public const APP_ID = 'user_watch';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDeclarativeSettings(UserWatchSettingsForm::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn($this->registerEventListeners(...));
		//$context->injectFn(Closure::fromCallable([$this, 'registerCallableEventListeners']));
	}
	public function registerEventListeners(
		IConfig $config,
		IEventDispatcher $eventDispatcher
	): void {
		$eventDispatcher->addServiceListener(PasswordUpdatedEvent::class, UserEventsListener::class);
	}
	/**
	 * @todo move the OCP events and then move the registration to `register`
	 */
	private function registerCallableEventListeners(IEventDispatcher $dispatcher,
		ContainerInterface $appContainer): void {
		$dispatcher->addListener(UserUpdatedEvent::class, function (UserUpdatedEvent $event) use ($appContainer): void {
			//logger('user_watch')->warning('look, no dependency injection');
			/** @var UpdateLookupServer $updateLookupServer */
			error_log("Me user changed");
//			$updateLookupServer = $appContainer->get(UpdateLookupServer::class);
//			$updateLookupServer->userUpdated($event->getUser());
		});
	}
}
