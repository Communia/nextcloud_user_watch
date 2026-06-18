<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\UserWatch\AppInfo\Application::APP_ID, OCA\UserWatch\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\UserWatch\AppInfo\Application::APP_ID, OCA\UserWatch\AppInfo\Application::APP_ID . '-main');

?>

<div id="user_watch"></div>
