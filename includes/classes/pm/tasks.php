<?php

namespace PM;

class TasksException extends \Exception {}

class Tasks extends \PM\DB\DBIterator {
	private $filters = [];
	private $db_schema;

	private $base_query = "
		SELECT
			{SELECTFIELDS}
		FROM
			{SCHEMA}.tasks
		INNER JOIN
			{SCHEMA}.logins AS assigner
			ON
			assigner.login_id = tasks.assignedby_login_id
		{WHERECLAUSE}
		ORDER BY
			tasks.due_on ASC
	";

	public function __construct($fields = Null) {
		global $session;

		if(ERP_SYSTEM === 'Neuron') {
			$this->db_schema = 'public';
		} else if(ERP_SYSTEM === 'PRO') {
			$this->db_schema = DB_SCHEMA_INTERNAL;
		}

		if(empty($fields)) {
			$fields = [
				'tasks.task_id',
				'tasks.subject',
				'tasks.added_on',
				'tasks.description',
				'tasks.status',
				'tasks.priority',
				'tasks.due_on',
				'tasks.archive'
			];
			if(ERP_SYSTEM === 'PRO') {
				$fields = array_merge($fields, [
					'assigner.login_id AS assignee_login_id',
					'assigner.first_name AS assigner_first_name',
					'assigner.last_name AS assigner_last_name',
					'assigner.initials AS assigner_initials'
				]);
			} else if(ERP_SYSTEM === 'Neuron') {
				$fields = array_merge($fields, [
					'assigner.login_id',
					'assigner.first_name AS assigner_first_name',
					'assigner.last_name AS assigner_last_name'
				]);
			}
		}
		$fields = implode(', ', $fields);
		$query = $this->base_query;
		$query = str_replace('{SCHEMA}', $this->db_schema, $query);
		$query = str_replace('{SELECTFIELDS}', $fields, $query);
		parent::__construct($query);

		$this->_addWhereClause(
			"tasks.account_id = " . $this->getDB()->quote(ACCOUNT_ID)
		);
	}

	public function getStatuses() {
		$grab_statuses = $this->getDB()->query("
			SELECT
				task_statuses.status,
				task_statuses.name
			FROM
				" . $this->db_schema . ".task_statuses
			WHERE
				task_statuses.account_id = " . $this->getDB()->quote(ACCOUNT_ID) . "
			ORDER BY
				task_statuses.status
		");
		$statuses = [];
		foreach($grab_statuses as $status) {
			$statuses[$status['status']] = $status['name'];
		}
		if(empty($statuses)) {
			$statuses = [
				'0' => 'Assigned',
				'10' => 'Declined',
				'20' => 'Do Later',
				'30' => 'Need Info',
				'40' => 'Accepted',
				'50' => 'Planning',
				'60' => 'In Progress',
				'90' => 'Wrapping Up',
				'100' => 'Complete'
			];
		}
		return $statuses;
	}

	public function addStatus($name, $status) {
		$grab_status = $this->getDB()->query("
			SELECT
				task_statuses.name,
				task_statuses.status
			FROM
				" . $this->db_schema . ".task_statuses
			WHERE
				task_statuses.account_id = " . $this->getDB()->quote(ACCOUNT_ID) . "
				AND
				(
					task_statuses.name = " . $this->getDB()->quote($name) . "
					OR
					task_statuses.status = " . $this->getDB()->quote($status) . "
				)
		");
		$status_result = $grab_status->fetch();
		if(!empty($status_result)) {
			if($status_result['name'] == $name) {
				throw new TasksException('Status already exists with the name specified');
			} else if($status_result['status'] == $status) {
				throw new TasksException('Status already exists with the Status Code specified');
			}
		}

		// Looks good, let's add it.
		$this->getDB()->query("
			INSERT INTO
				" . $this->db_schema . ".task_statuses
			(
				account_id,
				name,
				status
			) VALUES (
				" . $this->getDB()->quote(ACCOUNT_ID) . ",
				" . $this->getDB()->quote($name) . ",
				" . $this->getDB()->quote(status) . "
			)
		");
	}

	public function removeStatus() {
		// Ensure staus isn't applied to an existing task
	}

	public function filterByAssignee($login_id) {
		$where = "tasks.task_id IN (
			SELECT DISTINCT
				task_assignees.task_id
			FROM
				" . $this->db_schema . ".task_assignees
			INNER JOIN
				" . $this->db_schema . ".tasks
				ON
				tasks.task_id = task_assignees.task_id
			WHERE
				tasks.account_id = " . $this->getDB()->quote(ACCOUNT_ID) . "
				AND
				task_assignees.login_id = " . $this->getDB()->quote($login_id) . "
		)";
		$this->_addWhereClause($where);
	}

	public function filterByStatus($status) {
		$where = "tasks.status = " . $this->getDB()->quote($status);
		$this->_addWhereClause($where);
	}
	
	public function filterByPriority($priority) {
		$where = "tasks.priority = " . $this->getDB()->quote($priority);
		$this->_addWhereClause($where);
	}
	
	public function filterByOmitArchive() {
		$where = "(tasks.archive IS NULL OR tasks.archive = 0)";
		$this->_addWhereClause($where);
	}
}
