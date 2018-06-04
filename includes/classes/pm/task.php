<?php

namespace PM;

class TaskException extends \Exception {}

class Task {
	private $db;
	private $exists = False;
	private $task = [];

	function __construct($task_id) {
		// Get DB object for queries.
		$this->db = DB::get();

		// Load Task if passed.
		if(!empty($task_id)) {
			$this->__load($task_id);
		} else {
			$this->task['company_id'] = COMPANY;
		}
	}

	public function save() {
		global $session;
		$db = $this->db;

		if(!isset($this->task['assignedby_login_id'])) {
			$this->_setAssignedByLoginId($session->login['login_id']);
		}
		if(!isset($this->task['status'])) {
			$this->setStatus(0);
		}
		if(!isset($this->task['priority'])) {
			$this->setPriority(5);
		}

		if(!isset($this->task['subject'])) {
			throw new TaskException('Task Subject is required');
		}
		if(!isset($this->task['description'])) {
			throw new TaskException('Task Description is required');
		}

		if($this->exists) {
			$db->query("
				UPDATE
					" . DB_SCHEMA_INTERNAL . ".tasks
				SET
					subject = " . $db->quote($this->task['subject']) . ",
					description = " . $db->quote($this->task['description']) . ",
					status = " . $db->quote($this->task['status']) . ",
					priority = " . $db->quote($this->task['priority']) . "
				WHERE
					tasks.task_id = " . $db->quote($this->task['task_id']) . "
			");
		} else if(!$this->exists) {
			$grab_task = $db->query("
				INSERT INTO
					" . DB_SCHEMA_INTERNAL . ".tasks
				(
					subject,
					assignedby_login_id,
					description,
					status,
					priority,
					company_id
				)
				OUTPUT
					INSERTED.task_id,
				VALUES (
					" . $db->quote($this->task['subject']) . ",
					" . $db->quote($this->task['assignedby_login_id']) . ",
					" . $db->quote($this->task['description']) . ",
					" . $db->quote($this->task['status']) . ",
					" . $db->quote($this->task['priority']) . ",
					" . $db->quote($this->task['company_id']) . "
				)
			");
			$task = $grab_task->fetch();
			$this->exists = True;
			$this->__setTaskId($task['task_id']);
		}
	}

	public function getAssignees() {
		if(!$this->getTaskId()) {
			throw new TaskException('Task must be saved before interfacing with assignees');
		}
		
		$db = $this->db;

		$assignees = [];
		$grab_assignees = $db->query("
			SELECT
				task_assignees.*,
				assignee_login.initials AS assignee_initials,
				assignee_login.first_name AS assignee_first_name,
				assignee_login.last_name AS assignee_last_name,
				assigner_login.initials AS assigner_initials,
				assigner_login.first_name AS assigner_first_name,
				assigner_login.last_name AS assigner_last_name
			FROM
				" . DB_SCHEMA_INTERNAL . ".task_assignees
			INNER JOIN
				" . DB_SCHEMA_INTERNAL . ".logins AS assignee_login
				ON
				assignee_login.login_id = task_assignees.login_id
			INNER JOIN
				" . DB_SCHEMA_INTERNAL . ".logins AS assigner_login
				ON
				assigner_login.login_id = task_assignees.assignedby_login_id
			WHERE
				task_assignees.task_id = " . $db->quote($this->getTaskId()) . "
			ORDER BY
				task_assignees.added_on
		");
		foreach($grab_assignees as $assignee_data) {
			foreach($assignee_data as $key => $value) {
				if(ctype_digit((string)$key)) {
					continue; // Skip numeric keys.
				}
				$assignees[$key] = $value;
			}
		}
		return $assignees;
	}

	public function addAssignee($login_id) {
		if(!$this->getTaskId()) {
			throw new TaskException('Task must be saved before interfacing with assignees');
		}

		global $session;
		$db = $this->db;

		$db->query("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".task_assignees
			(
				task_id,
				assignedby_login_id,
				login_id
			) VALUES (
				" . $db->quote($this->getTaskId()) . ",
				" . $db->quote($session->login['login_id']) . ",
				" . $db->quote($login_id) . "
			)
		");
	}

	public function deleteAssignee($task_assignee_id) {
		if(!$this->getTaskId()) {
			throw new TaskException('Task must be saved before interfacing with assignees');
		}

		$db = $this->db;

		$db->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".task_assignees
			WHERE
				task_assignees.task_id = " . $this->quote($this->getTaskId()) . "
				AND
				task_assignees.task_assignee_id = " . $db->quote($task_assignee_id) . "
		");
	}

	public function addEntry($description) {
		if(!$this->getTaskId()) {
			throw new TaskException('Task must be saved before interfacing with entries');
		}

		global $session;
		$db = $this->db;

		$db->query("
			INSERT INTO
				" . DB_SCHEMA_INTERNAL . ".task_assignees
			(
				task_id,
				login_id,
				description
			) VALUES (
				" . $db->quote($this->getTaskId()) . ",
				" . $db->quote($session->login['login_id']) . ",
				" . $db->quote($description) . "
			)
		");
	}

	public function deleteEntry($task_entry_id) {
		if(!$this->getTaskId()) {
			throw new TaskException('Task must be saved before interfacing with entries');
		}

		$db = $this->db;

		$db->query("
			DELETE FROM
				" . DB_SCHEMA_INTERNAL . ".task_entries
			WHERE
				task_entries.task_id = " . $this->quote($this->getTaskId()) . "
				AND
				task_entries.task_entry_id = " . $db->quote($task_entry_id) . "
		");
	}

	public function getEntries() {
		if(!$this->getTaskId()) {
			throw new TaskException('Task must be saved before interfacing with entries');
		}

		$db = $this->db;

		$entries = [];
		$grab_entries = $db->query("
			SELECT
				task_entries.*
			FROM
				" . DB_SCHEMA_INTERNAL . ".task_entries
			WHERE
				task_entries.task_id = " . $db->quote($this->getTaskId()) . "
			ORDER BY
				task_entries.added_on
		");
		foreach($grab_entries as $entry_data) {
			foreach($entry_data as $key => $value) {
				if(ctype_digit((string)$key)) {
					continue; // Skip numeric keys.
				}
				$entries[$key] = $value;
			}
		}
		return $entries;
	}

	public function getSubject() {
		if(!isset($this->task['subject'])) {
			return Null;
		}
		return $this->task['subject'];
	}

	public function setSubject($subject) {
		$this->task['subject'] = (string)$subject;
	}

	public function getDescription() {
		if(!isset($this->task['description'])) {
			return Null;
		}
		return $this->task['description'];
	}

	public function setDescription($description) {
		$this->task['description'] = (string)$description;
	}

	public function getStatus() {
		if(!isset($this->task['status'])) {
			return Null;
		}
		return $this->task['status'];
	}

	public function setStatus($status) {
		$this->task['status'] = (int)$status;
	}

	public function getPriority() {
		if(!isset($this->task['priority'])) {
			return Null;
		}
		return $this->task['priority'];
	}

	public function setPriority($priority) {
		$this->task['priority'] = (int)$priority;
	}

	public function getTaskId() {
		if(!isset($this->task['task_id'])) {
			return Null;
		}
		return $this->task['task_id'];
	}
	
	private function __setAssignedByLoginId($login_id) {
		$this->task['assignedby_login_id'] = (int)$login_id;
	}
	
	private function __setTaskId($task_id) {
		$this->task['task_id'] = (int)$task_id;
	}

	private function __load($task_id) {
		$db = $this->db;

		/**
		 * Grab Task from DB.
		 */
		$grab_task = $db->query("
			SELECT
				tasks.*
			FROM
				" . DB_SCHEMA_INTERNAL . ".tasks
			WHERE
				tasks.task_id = " . $db->quote($task_id) . "
				AND
				tasks.company_id = " . $db->quote(COMPANY) . "
		");
		$task = $grab_task;
		if(empty($task)) {
			throw new TaskException('Task ID specified doesnt exist');
		}
		foreach($task as $key => $value) {
			if(ctype_digit((string)$key)) {
				continue; // Skip numeric keys.
			}
			$this->task[$key] = $value;
		}
		$this->exists = True;

		/**
		 * Grab Assignees from DB.
		 */
		$grab_assignees = $db->query("
			SELECT
				task_assignees.*
			FROM
				" . DB_SCHEMA_INTERNAL . ".task_assignees
			WHERE
				task_assignees.task_id = " . $db->quote($this->task['task_id']) . "
			ORDER BY
				task_assignees.task_assignee_id
		");
		foreach($grab_assignees as $assignee_data) {
			$assignee = [];
			foreach($assignee_data as $key => $value) {
				if(ctype_digit((string)$key)) {
					continue; // Skip numeric keys.
				}
				$assignee[$key] = $value;
			}
				
			$this->assignees[] = $assignee;
		}
	}
}
