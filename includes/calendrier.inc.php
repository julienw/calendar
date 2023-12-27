<?php
/*
   Copyright 2005, 2006 Julien Wajsberg <felash@gmail.com>
   This file is part of Concert events.

   Concert events is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Concert events is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Concert events; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/*
   Julien Wajsberg <felash@gmail.com>
   24 avril 2005
 */

require_once 'conf/config.inc.php';
require_once 'includes/my_log.inc.php';
require_once 'includes/db_utils.inc.php';

/* constantes pour les droits */
define("CALENDRIER_PUB_NONE", 0); /* aucun droit */
define("CALENDRIER_PUB_READ", 1); /* lecture */
define("CALENDRIER_PUB_READWRITE", 2); /* lecture écriture */

/*
   Classe de routines d'accès à la table contenant
   les infos du calendrier
   */
class Calendrier {
	var $db;
	var $cal_nb;
	var $name;
	var $log;
	var $id_owner;

	/* rights */
	var $rights_all_users;
	var $rights_subscribed_users;
	var $rights_guests;

	var $rss_titles;
	var $new_event_label;

	/* delayed writes */
	var $delayedWrite = false;
	var $updatedFields;

	/* statements SQL */	
	var $statement_write;
	var $statement_write_user;
	var $statement_read;
	var $statement_read_all;
	var $statement_read_month;
	var $statement_read_users;
	var $statement_read_foruserid;
	var $statement_readall_foruser;
	var $statement_users;
	var $statement_mod;
	var $statement_del;
	var $statement_del_users;
	var $statement_remove_user;
	var $statement_rss2;
	var $statement_rss1;
	var $statement_rss_next;
	var $statement_rss_next_for_user;
	var $statement_search;
	var $statement_exists;
	var $statement_fetchinfo;
	var $statement_is_subscribed;
	var $statement_subscribed_users;
	var $statement_active_calendar;
	var $statement_is_active;
	var $statement_get_owner;
	var $statement_update;
	var $statement_delete_subscribed_users;
	var $statement_subscribe_user;

	function __construct(&$db, $cal = 0) {
    $this->db =& $db;
    $this->cal_nb = $cal;
    $this->log = new MyLog($db, 'calendrier');

		$this->init();
	}

	/**
	  Cette méthode est une factory

	  $rights est un tableau à 3 entrées:
	  	- 1 : utilisateurs normaux
		- 2 : utilisateurs membres de ce calendrier
		- 3 : invités
	  */
	function & create (&$db, $name, $id_owner, $rights, $titles_rss, $labelNewEvent, $active = '0') {
		global $table_prefix;
		/* mysql
		$statement = "INSERT INTO " . $table_prefix . "calendars (name, id_owner, " .
			"rights_users, rights_subscribed, rights_guests, rss_last, rss_next, rss_next_user, new_event_label, active) VALUES ".
			"(?, ?, ?, ?, ?, ?, ?, ?, ?)";
		*/
		/* pgsql */
		$sql = "INSERT INTO " . $table_prefix . "calendars (name, id_owner, " .
			"rights_users, rights_subscribed, rights_guests, rss_last, rss_next, rss_next_user, new_event_label, active) VALUES ".
			"(?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
		
		array_unshift($rights, $name, $id_owner);
		$values = array_merge($rights, $titles_rss);
		array_push($values, $labelNewEvent);
		array_push($values, $active);

    $statement = prepare($db, $sql);
    $cal_id = getOne($statement, $values);
		return new Calendrier($db, $cal_id);
	}

	/* cette méthode est une factory */
	static function &getSubscribedCalendars(&$db, $id_user) {
		global $table_prefix;
		/* "true" au lieu de "1" dans postgresql */
		$sql_subscribed_calendars =
			"SELECT DISTINCT c.id as id, c.name as name
			FROM " . $table_prefix ."calendars c,
				 " . $table_prefix ."users u LEFT OUTER JOIN
				 " . $table_prefix ."calendars_users cu ON true
			WHERE (c.rights_guests > 0)
			OR (c.rights_users > 0 AND u.id = ?)
			OR (c.rights_subscribed > 0 AND cu.id_user = ? AND cu.id_cal = c.id)
			ORDER BY c.name";

    $statement = prepare($db, $sql_subscribed_calendars);
		$res = query($statement, array($id_user, $id_user))->fetchAll();

		foreach($res as $cal_info) {
			$newCal = new Calendrier($db, $cal_info['id']);
			$newCal->_setName($cal_info['name']);
			$arrRes[] = $newCal;
		}
		return $arrRes;
	}

	static function &getAllUsers(&$db) {
		global $table_prefix;
		$statement = "SELECT id, username FROM " . $table_prefix . "users";
		$res = $db->query($statement);
		return $res;
	}

	function init() {
		global $table_prefix;
    $this->statement_write = prepare(
      $this->db,
      "INSERT INTO " . $table_prefix . "events (event, jour, horaire, id_submitter, id_cal) VALUES (?, ?, ?, ?, ?) RETURNING id"
    );
    $this->statement_write_user = prepare(
      $this->db,
      "INSERT INTO " . $table_prefix . "events_users (id_event, id_user) VALUES (?, ?)"
    );
    $this->statement_read = prepare(
      $this->db,
			"SELECT id, event, horaire, jour
			FROM " . $table_prefix . "events
			WHERE jour=? AND id_cal=?
      ORDER BY horaire ASC, id ASC"
    );
    $this->statement_read_month = prepare(
      $this->db,
			"SELECT id, event, horaire, jour
			FROM " . $table_prefix . "events
			WHERE extract(year from jour) = ? AND extract(month from jour) = ? AND id_cal = ?
      ORDER BY horaire ASC, id ASC"
    );
/* mysql
		$this->statement_read_all =
			"SELECT id, event, horaire, jour, UNIX_TIMESTAMP(submit_timestamp) timestamp
			FROM " . $table_prefix . "events WHERE id_cal = ?
			ORDER BY jour ASC, horaire ASC, id ASC";
*/
/* pgsql */
		$this->statement_read_all = prepare(
      $this->db, 
			"SELECT id, event, horaire, jour, extract(epoch from submit_timestamp) timestamp
			FROM " . $table_prefix . "events WHERE id_cal = ?
      ORDER BY jour ASC, horaire ASC, id ASC"
    );
    $this->statement_read_users = prepare(
      $this->db,
			"SELECT u.username username
			FROM " . $table_prefix . "users u, " . $table_prefix . "events_users cu
			WHERE cu.id_event = ? AND cu.id_user = u.id
      ORDER BY username"
    );
    $this->statement_read_foruserid = prepare(
      $this->db,
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour
			FROM " . $table_prefix . "events c, " . $table_prefix . "events_users cu
			WHERE cu.id_event=c.id AND cu.id_user = ? AND extract(year from jour) = ?
			AND extract(month from jour) = ? AND c.id_cal = ?
      ORDER BY horaire ASC, id ASC"
    );
/* for mysql
		$this->statement_readall_foruser =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour, UNIX_TIMESTAMP(c.submit_timestamp) timestamp
			FROM " . $table_prefix . "events c, " . $table_prefix . "events_users cu, " . $table_prefix . "users u
			WHERE cu.id_event=c.id AND cu.id_user = u.id AND u.username = ? AND c.id_cal = ?
			ORDER BY jour ASC, horaire ASC, id ASC";
*/
/* for pgsql */
    $this->statement_readall_foruser = prepare(
      $this->db,
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour, extract(epoch from c.submit_timestamp) timestamp
			FROM " . $table_prefix . "events c, " . $table_prefix . "events_users cu, " . $table_prefix . "users u
			WHERE cu.id_event=c.id AND cu.id_user = u.id AND u.username = ? AND c.id_cal = ?
      ORDER BY jour ASC, horaire ASC, id ASC",
    );
		$this->statement_users = prepare(
      $this->db,
			"SELECT u.username username
			FROM " . $table_prefix . "users u, " . $table_prefix . "events_users cu
      WHERE u.id = cu.id_user AND cu.id_event = ? ORDER BY username"
    );
    $this->statement_mod = prepare(
      $this->db,
      "UPDATE " . $table_prefix . "events SET event = ?, submit_timestamp = submit_timestamp WHERE id = ?"
    );
    $this->statement_del = prepare(
      $this->db,
      "DELETE FROM " . $table_prefix . "events WHERE id = ?"
    );
    $this->statement_del_users = prepare(
      $this->db,
      "DELETE FROM " . $table_prefix . "events_users WHERE id_event = ?"
    );
    $this->statement_remove_user = prepare(
      $this->db,
      "DELETE FROM " . $table_prefix . "events_users WHERE id_event = ? AND id_user = ?"
    );
/* mysql
		$this->statement_rss2 =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour,
			UNIX_TIMESTAMP(c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u
			WHERE c.id_submitter = u.id AND c.id_cal = ?
			ORDER BY c.submit_timestamp DESC
			LIMIT ?";
		$this->statement_rss1 =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour,
			UNIX_TIMESTAMP(c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u
			WHERE c.id_submitter = u.id AND c.id_cal = ?
			AND DATE_ADD(submit_timestamp, INTERVAL ? DAY) >= NOW()
			ORDER BY c.submit_timestamp DESC";
		$this->statement_rss_next =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour,
			UNIX_TIMESTAMP(c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u
			WHERE c.id_submitter = u.id AND jour >= NOW() AND c.id_cal = ?
			ORDER BY jour
			LIMIT ?";
		$this->statement_rss_next_for_user =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour, c.id_cal id_cal,
			UNIX_TIMESTAMP(c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u,"
			. $table_prefix . "users u2, " . $table_prefix . "events_users cu
			WHERE jour >= NOW() AND u2.username = ?
			AND u2.id = cu.id_user AND cu.id_event = c.id
			AND cu.sure = 1
			AND c.id_submitter = u.id
			ORDER BY jour
			LIMIT ?";
*/
/* pgsql */
    $this->statement_rss2 = prepare(
      $this->db,
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour,
			extract(epoch from c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u
			WHERE c.id_submitter = u.id AND c.id_cal = ?
			ORDER BY timestamp DESC
      LIMIT ?"
    );
    $this->statement_rss1 = prepare(
      $this->db,
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour,
			extract(epoch from c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u
			WHERE c.id_submitter = u.id AND c.id_cal = ?
			AND c.submit_timestamp + ?::interval >= NOW()
      ORDER BY c.submit_timestamp DESC"
    );
    $this->statement_rss_next = prepare(
      $this->db,
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour,
			extract(epoch from c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u
			WHERE c.id_submitter = u.id AND jour >= NOW() AND c.id_cal = ?
			ORDER BY jour
      LIMIT ?"
    );
    $this->statement_rss_next_for_user = prepare(
      $this->db,
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour, c.id_cal id_cal,
			extract(epoch from c.submit_timestamp) timestamp, u.username username
			FROM " . $table_prefix . "events c, " . $table_prefix . "users u,"
			. $table_prefix . "users u2, " . $table_prefix . "events_users cu
			WHERE jour >= NOW() AND u2.username = ?
			AND u2.id = cu.id_user AND cu.id_event = c.id
			AND cu.sure = 1
			AND c.id_submitter = u.id
			ORDER BY jour
      LIMIT ?"
    );
    $this->statement_search = prepare(
      $this->db,
			"SELECT jour FROM " . $table_prefix . "events
			WHERE %s AND jour >= NOW() AND id_cal = ?
      LIMIT 1"
    );

    $this->statement_exists = prepare(
      $this->db,
			"SELECT count(id) FROM " .$table_prefix ."calendars
      WHERE id = ?"
    );
    $this->statement_fetchinfo = prepare(
      $this->db,
			"SELECT name, rights_users, rights_subscribed, rights_guests,
			rss_last, rss_next, rss_next_user, new_event_label FROM " .$table_prefix ."calendars
      WHERE id = ?"
    );

    $this->statement_is_subscribed = prepare(
      $this->db,
			"SELECT count(*) FROM " . $table_prefix ."calendars_users
      WHERE id_cal = ? AND id_user = ?"
    );

    $this->statement_subscribed_users = prepare(
      $this->db,
			"SELECT u.id id, u.username username
			FROM " . $table_prefix ."calendars_users cu, " . $table_prefix . "users u
      WHERE cu.id_cal = ? AND u.id = cu.id_user"
    );

    $this->statement_subscribe_user = prepare(
      $this->db,
      "INSERT INTO " . $table_prefix ."calendars_users (id_cal, id_user) VALUES (?, ?)"
    );

    $this->statement_delete_subscribed_users = prepare(
      $this->db,
      "DELETE FROM " . $table_prefix ."calendars_users WHERE id_cal = ?"
    );

    $this->statement_active_calendar = prepare(
      $this->db,
      "UPDATE " . $table_prefix . "calendars SET active = !active"
    ); /* boolean in pgsql */

    $this->statement_is_active = prepare(
      $this->db,
			"SELECT count(*) FROM " . $table_prefix ."calendars
      WHERE id = ? AND active = '1'"
    );

    $this->statement_get_owner = prepare(
      $this->db,
			"SELECT id_owner FROM " . $table_prefix ."calendars
      WHERE id = ?"
    );

    $this->statement_update = prepare(
      $this->db,
      "UPDATE " .  $table_prefix . "calendars SET %s WHERE id = ?"
    );
	}

	function exists() {
		$res = getOne($this->statement_exists, array($this->cal_nb));
		return ($res > 0);
	}

	function getId() {
		return $this->cal_nb;
	}

	function getName() {
		if (!isset($this->name)) {
			$this->fetchCalendarInfo();
		}
		return $this->name;
	}

	/* internal protected */
	function _setName($name) {
		$this->name = $name;
	}

	function getAllUsersRights() {
		if (!isset($this->rights_all_users)) {
			$this->fetchCalendarInfo();
		}
		return $this->rights_all_users;
	}

	function getSubscribedUsersRights() {
		if (!isset($this->rights_subscribed_users)) {
			$this->fetchCalendarInfo();
		}
		return $this->rights_subscribed_users;
	}

	function getGuestsRights() {
		if (!isset($this->rights_guests)) {
			$this->fetchCalendarInfo();
		}
		return $this->rights_guests;
	}

	function getRssTitles() {
		if (!isset($this->rss_titles)) {
			$this->fetchCalendarInfo();
		}
		return $this->rss_titles;
	}

	function getNewEventLabel() {
		if (!isset($this->new_event_label)) {
			$this->fetchCalendarInfo();
		}
		return $this->new_event_label;
	}

	function fetchCalendarInfo() {
		$this->log->debug("fetchCalendarInfo()");
		$res = query($this->statement_fetchinfo, array($this->cal_nb));

		$row = $res->fetch();
		if (! $row) {
			$this->log->debug("request failed (cal={$this->cal_nb})");
			$this->name = '';
			$this->rights_all_users = $this->rights_subscribed_users = $this->rights_guests = 0;
			$this->rss_titles = array("", "", "");
			$this->new_event_label = "";
		} else {
			$debugstr = "request successful : ";
			foreach($row as $key => $value) {
				$debugstr .= "($key, $value) ";
			}
			$this->log->debug($debugstr);

			list ($this->name, $this->rights_all_users, $this->rights_subscribed_users, $this->rights_guests,
					$this->rss_titles[0], $this->rss_titles[1], $this->rss_titles[2], $this->new_event_label) = $row;
		}
	}

	function writeData($data, $year, $month, $day, $user_id) {
		$this->log->debug("writeData(data=$data, year=$year, month=$month, day=$day, user_id=$user_id)");
		$event_id = getOne($this->statement_write, array($data, "$year-$month-$day", null, $user_id, $this->cal_nb));
		$this->addUserToEvent($event_id, $user_id);

		$this->log->debug("/writeData($data, $year, $month, $day, $user_id) -> $event_id");
		return $event_id;
	}

	function addUserToEvent($event_id, $user_id) {
		$this->log->debug("addUserToEvent(event_id=$event_id, user_id=$user_id)");
		$res = query($this->statement_write_user, array($event_id, $user_id));
		$this->log->debug("/addUserToEvent($event_id, $user_id)");
	}

	function removeUserFromEvent($event_id, $user_id) {
		$res = query($this->statement_remove_user, array($event_id, $user_id));
	}


	function & readData($year = null, $month = null, $day = null) {
		if ($day != null) {
			$data = query($this->statement_read,
					array("$year-$month-$day", $this->cal_nb))->fetchAll();
		} elseif ($month != null) {
			$data = query($this->statement_read_month,
					array($year, $month, $this->cal_nb))->fetchAll();
		} elseif ($year != null) {
			return 0;
		} else {
			$data = query($this->statement_read_all, array($this->cal_nb))->fetchAll();
		}
		return $data;
	}

	function & readUsersForEvent($id_event) {
		$data = query($this->statement_read_users, array($id_event))->fetchAll(PDO::FETCH_COLUMN, 0);
		return $data;
	}

	function & readDataForUserId($user_id, $year, $month) {
		$data = query($this->statement_read_foruserid,
				array($user_id, $year, $month, $this->cal_nb))->fetchAll();
		return $data;
	}

	function & readAllDataForUser($user) {
		$data = query($this->statement_readall_foruser,
				array($user, $this->cal_nb))->fetchAll();
		return $data;
	}
	
	function modifyData($data, $id) {
		$res = query($this->statement_mod, array($data, $id));
	}

	function deleteData($id) {
		$res = query($this->statement_del, array($id));
		$res = query($this->statement_del_users, array($id));
	}

	function & getUsersForEvent($id) {
		$res = query($this->statement_users, array($id))->fetchAll();
		return $res;
	}

	function & getLastEntries($minnumber = 10, $delay = 5) {
		$res = query($this->statement_rss1, array($this->cal_nb, "$delay days"))->fetchAll();
		if (count($res) >= $minnumber) {
			return $res;
		}

		$res = query($this->statement_rss2, array($this->cal_nb, $minnumber))->fetchAll();
		return $res;
	}

	function & getNextEntries($minnumber = 10) {
		$res = query($this->statement_rss_next, array($this->cal_nb, $minnumber))->fetchAll();
		return $res;
	}
	function & getNextEntriesForUser($user, $minnumber = 10) {
		$res = query($this->statement_rss_next_for_user, array($user, $minnumber))->fetchAll();
		return $res;
	}

	function getDateForKey($keys) {
		$keys = str_replace(array('%', '_'), array('\%', '\_'), $keys);
		$keys = explode(" ", $keys);
		$where_clause = "TRUE";
		foreach ($keys as $key) {
			$where_clause .= " AND event LIKE " . pg_escape_literal("%$key%");
		}
		
		$sql = sprintf($this->statement_search, $where_clause);
    $statement = prepare($this->db, $sql);
		$res = getOne($statement, array($this->cal_nb));
		return $res;
	}

	function isSubscribedUser($id_user) {
		$res = getOne($this->statement_is_subscribed, array($this->cal_nb, $id_user));
		return ($res > 0);
	}

	function toggleActive() {
		$res = query($this->statement_active_calendar);
	}

	function isActive() {
		$res = getOne($this->statement_is_active, [$this->cal_nb]);
		return ($res > 0);
	}

	function getOwner() {
		if (! isset($this->id_owner)) {
			$res = getOne($this->statement_get_owner, [$this->cal_nb]);
			$this->id_owner = $res;
		}

		return $this->id_owner;
	}

	function isOwner($user_id) {
		return ($this->getOwner() == $user_id);
	}

	/* appeler cette méthode lorsqu'on veut empiler plusieurs requêtes d'écriture */
	function prepareForUpdate() {
		$this->delayedWrite = true;
	}

	function commitUpdates() {
		foreach($this->updatedFields as $change) {
			$where_statement .= "{$change['field']} = ?, ";
			$values[] = $change['value'];
		}

		$where_statement = substr($where_statement, 0, -2);
		$sql = sprintf($this->statement_update, $where_statement);
		$values[] = $this->cal_nb;
    $statement = prepare($this->db, $sql);
		$res = query($statement, $values);
		$this->delayedWrite = false;
	}

	function setName($name) {
		$this->log->debug("setName($name)");
		$this->name = $name;
		if ($this->delayedWrite) {
			$this->updatedFields[] = array('field' => 'name', 'value' => $name);
		} else {
			$sql = sprintf($this->statement_update, "name = ?");
      $statement = prepare($this->db, $sql);
			$res = query($statement, array($name, $this->cal_nb));
		}
		$this->log->debug("/setName");
	}

	function setRights($rights) {
		$this->log->debug("setRights({$rights[0]},{$rights[1]},{$rights[2]})");
		list($this->rights_all_users, $this->rights_subscribed_users, $this->rights_guests) = $rights;

		if ($this->delayedWrite) {
			$this->updatedFields[] = array('field' => 'rights_users', 'value' => $rights[0]);
			$this->updatedFields[] = array('field' => 'rights_subscribed', 'value' => $rights[1]);
			$this->updatedFields[] = array('field' => 'rights_guests', 'value' => $rights[2]);
		} else {
			$sql = sprintf($this->statement_update, "rights_users = ?, rights_subscribed = ?, rights_guests = ?");
			$values = $rights;
			array_push($values, $this->cal_nb);
      $statement = prepare($this->db, $sql);
			$res = query($statement, $values);
		}

		$this->log->debug("/setRights");
	}

	function setRssTitles($rss_titles) {
		$this->log->debug("setRssTitles({$rss_titles[0]},{$rss_titles[1]},{$rss_titles[2]})");
		$this->rss_titles = $rss_titles;

		if ($this->delayedWrite) {
			$this->updatedFields[] = array('field' => 'rss_last', 'value' => $rss_titles[0]);
			$this->updatedFields[] = array('field' => 'rss_next', 'value' => $rss_titles[1]);
			$this->updatedFields[] = array('field' => 'rss_next_user', 'value' => $rss_titles[2]);
		} else {
			$sql = sprintf($this->statement_update, "rss_last = ?, rss_next = ?, rss_next_user = ?");
			$values = $rss_titles;
			array_push($values, $this->cal_nb);
      $statement = prepare($this->db, $sql);
			$res = query($statement, $values);
		}
		$this->log->debug("/setRssTitles");
	}

	function setNewEventLabel($label) {
		$this->new_event_label = $label;
		if ($this->delayedWrite) {
			$this->updatedFields[] = array('field' => 'new_event_label', 'value' => $label);
		} else {
			$sql = sprintf($this->statement_update, "new_event_label = ?");
      $statement = prepare($this->db, $sql);
			$res = query($statement, [$label]);
		}
	}

	function &getSubscribedUsers() {
		$res = query($this->statement_subscribed_users, array($this->cal_nb))->fetchAll();

		return $res;
	}

	function setSubscribedUsers(&$users) {
		$res = query($this->statement_delete_subscribed_users, [$this->cal_nb]);

		$sth = prepare($this->db, $this->statement_subscribe_user);
		foreach ($users as $uid) {
			$res = query($sth, array($this->cal_nb, $uid));
		}
	}

}

?>
