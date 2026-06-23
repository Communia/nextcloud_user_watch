<?php
namespace OCA\UserWatch\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;


class DumpController extends Controller {
  private const ALLOWED_GROUPS = ['admin', 'dovecot_provider'];

  public function __construct(
    $appName,
    IRequest $request,
    private IDBConnection $db,
    private IGroupManager $groupManager,
    private IUserSession $userSession,
  ) {
    parent::__construct($appName, $request);
  }

 
	/**
	 * Returns true if the current user belongs to at least one of
	 * the groups listed in ALLOWED_GROUPS.
	 */
	private function currentUserIsAllowed(): bool {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return false;
		}
 
		foreach (self::ALLOWED_GROUPS as $group) {
			if ($this->groupManager->isInGroup($user->getUID(), $group)) {
				return true;
			}
		}
 
		return false;
	}
 
  #[NoAdminRequired]
  #[NoCSRFRequired]
  #[ApiRoute(verb: 'GET', url: '/v1/dump')]
  public function returnDump(): JSONResponse { 
		if (!$this->currentUserIsAllowed()) {
			return new JSONResponse(
				['message' => 'You are not allowed to access this resource'],
				Http::STATUS_FORBIDDEN
			);
		}
    
    try {
      $qb = $this->db->getQueryBuilder();

      $qb->select('u.uid', 'u.displayname', 'u.password', 'u.uid_lower', 'g.gid')
         ->from('users', 'u')
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

      return new JSONResponse(array_values($users));
    } catch (\Exception $ex) {
      return new JSONResponse(array('msg' => 'not found!'), Http::STATUS_NOT_FOUND);
    }
  }
}

