<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

class Session {
	private $db;
	public $logged_in = False;
	public $alias = False;
	public $login = array();

	function __construct() {
		$this->db = DB::get();
		$this->checkLogin();
	}

	/**
	 * Determines whether an individual is logged in. If they are, grab their
	 * account information from the database and make it accessible through
	 * $session->login.
	 */
	private function checkLogin() {
		$this->login['account_id'] = Null; // Key must always be present.
		if(isset($_COOKIE['login']) && is_numeric($_COOKIE['login']) && isset($_COOKIE['session'])) {
			// Grab user, ensuring the session is valid and hasn't expired.
			$grab_login = $this->db->query("
				SELECT
					logins.*,
					companies.company_id,
					companies.company,
					companies.shortname AS company_shortname,
					companies.dbname AS company_db,
					companies.compid,
					login_sessions.session_id,
					STUFF(
						(
							SELECT
								',' + login_locations.location 
							FROM
								" . DB_SCHEMA_INTERNAL . ".login_locations
							WHERE
								login_locations.login_id = logins.login_id
							FOR
								XML PATH('')
						), 1, 1, ''
					) as location_ids
				FROM
					" . DB_SCHEMA_INTERNAL . ".logins
				INNER JOIN
					" . DB_SCHEMA_INTERNAL . ".login_sessions
					ON
					logins.login_id = login_sessions.login_id
				INNER JOIN
					" . DB_SCHEMA_INTERNAL . ".companies
					ON
					login_sessions.company_id = companies.company_id
				WHERE
					logins.login_id = " . $this->db->quote($_COOKIE['login']) . "
					AND
					login_sessions.session_id = " . $this->db->quote($_COOKIE['session']) . "
					AND
					login_sessions.expires_on > GETDATE()
			");
			$login = $grab_login->fetch();

			if($login !== False) {
				$this->logged_in = True;
				$this->login = $login;

				// Cleanup location IDs.
				if($this->login['location_ids']) {
					$this->login['location_ids'] = explode(',', $this->login['location_ids']);
				} else {
					$this->login['location_ids'] = [];
				}

				// Query for and append this user's Roles to the login array.
				$grab_roles = $this->db->query("
					SELECT
						login_roles.role_id,
						roles.role
					FROM
						" . DB_SCHEMA_INTERNAL . ".login_roles
					INNER JOIN
						" . DB_SCHEMA_INTERNAL . ".roles
						ON
						login_roles.role_id = roles.role_id
					WHERE
						login_roles.login_id = " . $this->db->quote($this->login['login_id']) . "
					ORDER BY
						roles.role
				");
				$roles = $grab_roles->fetchAll();
				$this->login['roles'] = array();
				if($roles !== False) {
					foreach($roles as $role) {
						$this->login['roles'][$role['role']] = array(
							'role_id' => $role['role_id'],
							'permissions' => array()
						);
						// Query for and append permissions associated with this login's role.
						$grab_permissions = $this->db->query("
							SELECT
								login_role_permissions.permission_type,
								login_role_permissions.permission_value
							FROM
								" . DB_SCHEMA_INTERNAL . ".login_role_permissions
							WHERE
								login_role_permissions.role_id = " . $this->db->quote($role['role_id']) . "
								AND
								login_role_permissions.login_id = " . $this->db->quote($this->login['login_id']) . "
						");
						foreach($grab_permissions as $permission) {
							if(!isset($this->login['roles'][$role['role']]['permissions'][$permission['permission_type']])) {
								$this->login['roles'][$role['role']]['permissions'][$permission['permission_type']] = array();
							}
							$this->login['roles'][$role['role']]['permissions'][$permission['permission_type']][] = $permission['permission_value'];
						}
					}
				}

				// Flag alias sessions.
				if(isset($_COOKIE['admin-login'])&&isset($_COOKIE['admin-session'])){
					$this->alias = true;
				}
			}
		}
	}

	public function ensureLogin() {
		if($this->logged_in !== True) {
			header('Location: ' . BASE_URL . '/login?');
			exit();
		}
	}

	/**
	 * Checks if the current session has the specified role applied to their
	 * login
	 * 
	 * Returns True on yes, or False on no.
	 */
	public function hasRole($role_name) {
		// Ensure the user is logged in.
		if($this->logged_in !== True)  {
			return False;
		}

		// "Administration" roles inherit all roles and permissions.
		if(isset($this->login['roles']['Administration'])) {
			return True;
		}

		// Ensure the login has `role_name` specified.
		if(!isset($this->login['roles'][$role_name])) {
			return False;
		}
		return True;
	}

	/**
	 * Checks if the current session has the specified role applied to their
	 * login.
	 * 
	 * Allows the request to continue on yes, forces a 403 re-direct on no.
	 */
	public function ensureRole($role_name) {
		if(!$this->hasRole($role_name)) {
			header('Location: ' . BASE_URL . '/403');
			exit();
		}
	}

	public function hasPermission($role_name, $permission_name, $permission_value = Null) {
		// Ensure login has `role_name` specified.
		if(!$this->hasRole($role_name)) {
			return False;
		}

		// "Administration" roles get access to everything.
		if($this->hasRole('Administration')) {
			return True;
		}

		// Ensure login has `permission_name` specified.
		if(!isset($this->login['roles'][$role_name]['permissions']) || !isset($this->login['roles'][$role_name]['permissions'][$permission_name])) {
			return False;
		}

		// If `permission_value` provided, ensure login has `permission_value`
		// specified.
		if($permission_value !== Null) {
			if(is_array($this->login['roles'][$role_name]['permissions'][$permission_name])) {
				// Permission is an array of values.
				if(!in_array($permission_value, $this->login['roles'][$role_name]['permissions'][$permission_name]) ) {
					return False;
				}
			} else {
				// Permission is a single value.
				if($permission_value != $this->login['roles'][$role_name]['permissions'][$permission_name]) {
					return False;
				}
			}
		}
		return True;
	}

	public function getPermissions($role_name, $permission_name = False) {
		if($permission_name) {
			// If `permission_name` has been specified, only return permissions
			// for the `permission_name` specified.

			// Ensure login has the `role_name` specified.
			if(!$this->hasRole($role_name) || !isset($this->login['roles'][$role_name])) {
				return False;
			}

			// Ensure login has the `permission_name` specified.
			if(!$this->hasPermission($role_name, $permission_name) || !isset($this->login['roles'][$role_name]['permissions'][$permission_name])) {
				return False;
			}

			// Return the permissions.
			return $this->login['roles'][$role_name]['permissions'][$permission_name];
		} else {
			// If `permission_name` has not been specified, return all
			// permissions for the role specified.

			// Ensure login has the role specified.
			if(!$this->hasRole($role_name)) {
				return False;
			}

			// Return the permissions.
			return $this->login['roles'][$role_name]['permissions'];
		}
	}

	public function getRoleId($role_name) {
		$grab_role_id = $this->db->query("
			SELECT
				roles.role_id
			FROM
				" . DB_SCHEMA_INTERNAL . ".roles
			WHERE
				roles.role = " . $this->db->quote($role_name) . "
		");
		$role_id = $grab_role_id->fetch();
		return $role_id['role_id'];
	}

	public static function loginAlias($login_id){

		// If admin cookies exist, an alias has already been assumed.
		if(isset($_COOKIE['admin-login']) || isset($_COOKIE['admin-session'])){
			return;
		}

		// Keep the admin login id and session id.
		setcookie('admin-login', $_COOKIE['login'], 0, '/');
		setcookie('admin-session', $_COOKIE['session'], 0, '/');
 
		Session::LogIn($login_id);

	}

	public static function LogIn($login_id) {
		$db_conn = DB::get();

		// Delete old, expired sessions.
		$db_conn->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".login_sessions
			WHERE
				expires_on <= GETDATE()
		");

		// Generate random session id
		$session_id = Misc::RandomString();

		// Generate a date/time string representing the session's expiration
		// to be 7 days in the future.
		$session_expires_on = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 7));

		// Set user cookies.
		setcookie('login', $login_id, 0, '/');
		setcookie('session', $session_id, 0, '/');

		// Create a session for the user.
		$db_conn->query("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".login_sessions
			(
				login_id,
				session_id,
				expires_on,
				company_id
			) VALUES (
				" . $db_conn->quote($login_id) . ",
				" . $db_conn->quote($session_id) . ",
				" . $db_conn->quote($session_expires_on) . ",
				" . $db_conn->quote(COMPANY) . "
			)
		");

		Logging::Log('Account', 'Login', $login_id, 'Successful');
	}

	function logOutAlias(){

		// Log out of an alias account.

		setcookie('login', $_COOKIE['admin-login'], 0, '/');
		setcookie('session', $_COOKIE['admin-session'], 0, '/');

		setcookie('admin-login', '', -3600);
		setcookie('admin-session', '', -3600);

	}

	public static function LogOut() {
		setcookie('login', '', -3600);
		setcookie('session', '', -3600);
	}

	public function authenticate($login, $password) {
		$response = array(
			'success' => Null,
			'data' => array(),
			'errors' => array()
		);
		if(empty($login)) { // Ensure login is not empty
			$response['errors']['login'] = 'empty';
		}
		if(empty($password)) { // 
			$response['errors']['password'] = 'empty';
		}

		if(empty($response['errors'])) {
			$grab_login = $this->db->query("
				SELECT
					login_id,
					password_hashed,
					password_salt,
					status
				FROM
					" . DB_SCHEMA_INTERNAL . ".logins
				WHERE
					login = " . $this->db->quote($login) . "
			");
			$login = $grab_login->fetch();

			if($login === False) { // Login doesn't exist
				$response['errors']['login'] = '!exist';
				//History::Record(0, 'login', False, 'Login Incorrect');
			} else { // Login exists
				// TODO: Is this needed?
				$response['data']['login_id'] = $login['login_id'];

				if(hash(HASHING_ALGO, $login['password_salt'] . $password) != $login['password_hashed']) {
					// Invalid password
					//History::Record($login['login_id'], 'login', False, 'Password Incorrect');
					$response['errors']['password'] = 'incorrect';
				} else if($login['status'] == 0) {
					// Login suspended
					//History::Record($login['login_id'], 'login', False, 'Login Suspended');
					$response['errors']['login'] = 'suspended';
				} else {
					// Login successful
					//History::Record($login['login_id'], 'login', True);
				}
			}
		}

		if(empty($response['errors'])) { // No errors
			$response['success'] = True;
		} else { // Errors
			$response['success'] = False;
		}

		return $response;
	}
}
