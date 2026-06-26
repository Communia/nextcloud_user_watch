<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Communia SCCL and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserWatch\Listeners;

use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserIdAssignedEvent;
use OCP\User\Events\UserIdUnassignedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserCreatedEvent|UserDeletedEvent|UserChangedEvent|PasswordUpdatedEvent|UserIdAssignedEvent|UserIdUnassignedEvent>
 */
class UserEventsListener implements IEventListener {
	public function __construct(
		private LoggerInterface $logger,
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
		private IClientService $clientService,
		private IDBConnection $db
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof UserCreatedEvent) {
			$this->userCreated($event);
		} elseif ($event instanceof UserDeletedEvent) {
			$this->userDeleted($event);
		} elseif ($event instanceof UserChangedEvent) {
			$this->userChanged($event);
		} elseif ($event instanceof PasswordUpdatedEvent) {
			$this->passwordUpdated($event);
		} elseif ($event instanceof UserIdAssignedEvent) {
			$this->userIdAssigned($event);
		} elseif ($event instanceof UserIdUnassignedEvent) {
			$this->userIdUnassigned($event);
		}
	}

	private function userCreated(UserCreatedEvent $event): void {
		$this->dumpUsers('USER_CREATED', $event, $event->getUID());
		$this->log(
			'User created: "%s"',
			[
				'uid' => $event->getUid()
			],
			[
				'uid',
			]
		);
	}

	private function userDeleted(UserDeletedEvent $event): void {
		$this->dumpUsers('USER_DELETED', $event, $event->getUser()->getUID());
		$this->log(
			'User deleted: "%s"',
			[
				'uid' => $event->getUser()->getUID()
			],
			[
				'uid',
			]
		);
	}

	private function userChanged(UserChangedEvent $event): void {
		switch ($event->getFeature()) {
			case 'enabled':
			        $this->dumpUsers('USER_ENABLED', $event, $event->getUser()->getUID());
				$this->log(
					$event->getValue() === true
						? 'User enabled: "%s"'
						: 'User disabled: "%s"',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
			case 'eMailAddress':
				$this->log(
					'Email address changed for user %s',
					['user' => $event->getUser()->getUID()],
					[
						'user',
					]
				);
				break;
		}
	}

	private function passwordUpdated(PasswordUpdatedEvent $event): void {
		if ($event->getUser()->getBackendClassName() === 'Database') {
			$this->dumpUsers('PASSWORD_UPDATED', $event, $event->getUser()->getUID());

			$this->log(
				'DD! Password of user "%s" has been changed',
				[
					'user' => $event->getUser()->getUID(),
				],
				[
					'user',
				]
			);
		}
	}

	/**
	 * Log assignments of users (typically user backends)
	 */
	private function userIdAssigned(UserIdAssignedEvent $event): void {
		$this->log(
			'UserID assigned: "%s"',
			[ 'uid' => $event->getUserId() ],
			[ 'uid' ]
		);
	}

	/**
	 * Log unassignments of users (typically user backends, no data removed)
	 */
	private function userIdUnassigned(UserIdUnassignedEvent $event): void {
		$this->log(
			'UserID unassigned: "%s"',
			[ 'uid' => $event->getUserId() ],
			[ 'uid' ]
		);
	}

	/**
	 * Dumps the users table to file set in settings.
	 */ 
	private function dumpUsers($event_type, $event, $uid): void {
		//$do_ping = $this->appConfig->getValueBool('user_watch', 'do_ping', true);
		// remove when do_ping is active in settings form (pending https://github.com/nextcloud/server/issues/60903).
		$do_ping = !empty($this->appConfig->getAppValueString('path', ''));
		if ($do_ping) {
      $on_pw_updated_url_to_ping = $this->appConfig->getAppValueString('on_password_updated_url', '');
      $on_pw_updated_url_to_ping = str_replace('{uid}', $uid, $on_pw_updated_url_to_ping);
      $on_created_url_to_ping = $this->appConfig->getAppValueString('on_created_url', '');
      $on_deleted_url_to_ping = $this->appConfig->getAppValueString('on_deleted_url', '');
      $on_deleted_url_to_ping = str_replace('{uid}', $uid, $on_deleted_url_to_ping);
      
      $dump_path = $this->appConfig->getAppValueString('path', '');
      /** PING **/
      $client = $this->clientService->newClient();
      $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ];
      switch ($event_type){
      case "PASSWORD_UPDATED":
        //$response = $client->get($on_pw_updated_url_to_ping);
        if (!empty($on_pw_updated_url_to_ping)) {
          $this->getUserRow($uid);
          $client->put($on_pw_updated_url_to_ping, [
            'headers' => $headers,
            'body' => json_encode([
              $this->getUserRow($uid)
            ])
          ]);
        }
        break;
      case "USER_CREATED":
        if (!empty($on_created_url_to_ping)) {
          $this->getUserRow($uid);
          $client->post($on_created_url_to_ping, [
            'headers' => $headers,
            'body' => json_encode([
              $this->getUserRow($uid)
            ])
          ]);
        }

        break;
      case "USER_DELETED":
        if (!empty($on_deleted_url_to_ping)) {
          $client->delete($on_deleted_url_to_ping);
        }
        break;
      default:
        // do nothing, event is discarded for us here
        return;

      }

			$qb = $this->db->getQueryBuilder();

			/** SQL FILE **/
			$table_name = $qb->prefixTableName('*PREFIX*users');
			$connection = \OC::$server->getDatabaseConnection();

			
			$file = fopen($dump_path . "_user_dump_sql.txt", 'w');
			// Prepare the SQL query
			$sql = "SHOW CREATE TABLE " . $table_name;
			$result = $connection->executeQuery($sql);
			$createTableStatement = $result->fetchAssociative();
			fwrite($file, $createTableStatement['Create Table'] . ';' . PHP_EOL);
			fwrite($file, "INSERT INTO " . $createTableStatement['Table'] . " VALUES" . PHP_EOL);

			$qb->select('*')->from('users');
			$result = $qb->executeQuery();

			// `uid`, displayname, password, uid_lower
			$values = [];
			while ($row = $result->fetchAssociative()) {
				// Convert the associative array to a string
				$values[] = "('" . implode("','", array_map('addslashes', $row)) . "')";
			}
			$sql_values = implode("," . PHP_EOL , $values);
		        fwrite($file, $sql_values . ";" . PHP_EOL . PHP_EOL);

			fwrite($file, "/* EVENT: " . $event . '*/;' . PHP_EOL);
			fwrite($file, "/* DATETIME: " . time() . '*/;' . PHP_EOL);

			fclose($file);

			/* CSV FILE: */
			$qb->select('*')->from('users');
			$result = $qb->executeQuery();

			$file = fopen($dump_path . "_user_dump.txt", 'w');

			// `uid`, displayname, password, uid_lower
			while ($row = $result->fetchAssociative()) {
				// Convert the associative array to a string
				$line = implode(", ", $row) . PHP_EOL;
				fwrite($file, $line);
			}
			fwrite($file, "/* EVENT: " . $event . '*/;' . PHP_EOL);
			fwrite($file, "/* DATETIME: " . time() . '*/;' . PHP_EOL);

			fclose($file);
			$this->log('DD! Dump of users done!');
			
		}

  }

  protected function getUserRow($uid):array {
    /*
    $qb = $this->db->getQueryBuilder();
    $qb->select('*')->from('users')->where(
      $qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
    );
    $result = $qb->executeQuery();
  	// `uid`, displayname, password, uid_lower
    $row = $result->fetchAssociative();
    $result->closeCursor();
    return $row;
*/

      $qb = $this->db->getQueryBuilder();

      $qb->select('u.uid', 'u.displayname', 'u.password', 'u.uid_lower', 'g.gid')
         ->from('users', 'u')
         ->where(
           $qb->expr()->eq('u.uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
         )  
         ->leftJoin('u', 'group_user', 'gu', $qb->expr()->eq('u.uid', 'gu.uid'))
         ->leftJoin('gu', 'groups', 'g', $qb->expr()->eq('gu.gid', 'g.gid'))
         ->orderBy('u.uid', 'ASC');

      $result = $qb->executeQuery();
      $rows = $result->fetchAll();
      $result->closeCursor();

      // Collapse the joined rows into one entry per user with a groups array.
      $users = [];
      foreach ($rows as $row) {
        $uid = $row['uid'];

        if (!isset($users[$uid])) {
          $users[$uid] = [
            'uid' => $uid,
            'displayname' => $row['displayname'],
            'password' => $row['password'],
            'uid_lower' => $row['uid_lower'],
            'groups' => [],
          ];
        }

        if ($row['gid'] !== null) {
          $users[$uid]['groups'][] = $row['gid'];
        }
      }

      return $users;


  }

	public function log($message, array $context = []): void {
		$this->logger->log('notice', $message, $context);
	}
}
