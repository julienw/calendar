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

	function Calendrier(&$db, $cal = 0) {

		$this->init();
		$this->cal_nb = $cal;
		$this->db =& $db;

		$this->log =& new MyLog($db, 'calendrier');
	}

	/**
	  Cette méthode est une factory

	  $rights est un tableau à 3 entrées:
	  	- 1 : utilisateurs normaux
		- 2 : utilisateurs membres de ce calendrier
		- 3 : invités
	  */
	function & create (&$db, $name, $id_owner, $rights, $titles_rss, $labelNewEvent, $active = 0) {
		global $table_prefix;
		$statement = "INSERT INTO " . $table_prefix . "calendars (name, id_owner, " .
			"rights_users, rights_subscribed, rights_guests, rss_last, rss_next, rss_next_user, new_event_label, active) VALUES ".
			"(?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		array_unshift($rights, $name, $id_owner);
		$array = array_merge($rights, $titles_rss);
		array_push($array, $labelNewEvent);
		array_push($array, $active);

		$res =& $db->query($statement, $array);
		Calendrier::checkSql($res);

		// FIXME à modifier plus tard
		$cal_id = mysql_insert_id($db->connection);
		return new Calendrier($db, $cal_id);
	}

	/* cette méthode est une factory */
	function &getSubscribedCalendars(&$db, $id_user) {
		global $table_prefix;
		$statement_subscribed_calendars =
			"SELECT DISTINCT c.id id, c.name name
			FROM " . $table_prefix ."calendars c,
				 " . $table_prefix ."users u LEFT OUTER JOIN
				 " . $table_prefix ."calendars_users cu ON 1
			WHERE (c.rights_guests > 0)
			OR (c.rights_users > 0 AND u.id = ?)
			OR (c.rights_subscribed > 0 AND cu.id_user = ? AND cu.id_cal = c.id)
			ORDER BY c.name";

		$res =& $db->getAll($statement_subscribed_calendars, array($id_user, $id_user), DB_FETCHMODE_ASSOC);
		Calendrier::checkSql($res);

		foreach($res as $cal_info) {
			$newCal =& new Calendrier($db, $cal_info['id']);
			$newCal->_setName($cal_info['name']);
			$arrRes[] =& $newCal;
		}
		return $arrRes;
	}

	function &getAllUsers(&$db) {
		global $table_prefix;
		$statement = "SELECT id, username FROM " . $table_prefix . "users";
		$res =& $db->getAll($statement, array(), DB_FETCHMODE_ASSOC);
		Calendrier::checkSql($res);
		return $res;
	}
	
	function init() {
		global $table_prefix;
		$this->statement_write = "INSERT INTO " . $table_prefix . "events (event, jour, horaire, id_submitter, id_cal) VALUES ".
			"(?, ?, ?, ?, ?)";
		$this->statement_write_user = "INSERT INTO " . $table_prefix . "events_users (id_event, id_user) VALUES (?, ?)";
		$this->statement_read =
			"SELECT id, event, horaire, jour
			FROM " . $table_prefix . "events
			WHERE jour=? AND id_cal=?
			ORDER BY horaire ASC, id ASC";
		$this->statement_read_month =
			"SELECT id, event, horaire, jour
			FROM " . $table_prefix . "events
			WHERE YEAR(jour) = ? AND MONTH(jour) = ? AND id_cal = ?
			ORDER BY horaire ASC, id ASC";
		$this->statement_read_all =
			"SELECT id, event, horaire, jour, UNIX_TIMESTAMP(submit_timestamp) timestamp
			FROM " . $table_prefix . "events WHERE id_cal = ?
			ORDER BY jour ASC, horaire ASC, id ASC";
		$this->statement_read_users =
			"SELECT u.username username
			FROM " . $table_prefix . "users u, " . $table_prefix . "events_users cu
			WHERE cu.id_event = ? AND cu.id_user = u.id
			ORDER BY username";
		$this->statement_read_foruserid =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour
			FROM " . $table_prefix . "events c, " . $table_prefix . "events_users cu
			WHERE cu.id_event=c.id AND cu.id_user = ? AND YEAR(jour) = ? AND MONTH(jour) = ? AND c.id_cal = ?
			ORDER BY horaire ASC, id ASC";
		$this->statement_readall_foruser =
			"SELECT c.id id, c.event event, c.horaire horaire, c.jour jour, UNIX_TIMESTAMP(c.submit_timestamp) timestamp
			FROM " . $table_prefix . "events c, " . $table_prefix . "events_users cu, " . $table_prefix . "users u
			WHERE cu.id_event=c.id AND cu.id_user = u.id AND u.username = ? AND c.id_cal = ?
			ORDER BY jour ASC, horaire ASC, id ASC";
		$this->statement_users =
			"SELECT u.username username
			FROM " . $table_prefix . "users u, " . $table_prefix . "events_users cu
			WHERE u.id = cu.id_user AND cu.id_event = ? ORDER BY username";
		$this->statement_mod = "UPDATE " . $table_prefix . "events SET event = ?, submit_timestamp = submit_timestamp WHERE id = ?";
		$this->statement_del = "DELETE FROM " . $table_prefix . "events WHERE id = ?";
		$this->statement_del_users = "DELETE FROM " . $table_prefix . "events_users WHERE id_event = ?";
		$this->statement_remove_user = "DELETE FROM " . $table_prefix . "events_users WHERE id_event = ? AND id_user = ?";
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
		$this->statement_search =
			"SELECT jour FROM " . $table_prefix . "events
			WHERE %s AND jour >= NOW() AND id_cal = ?
			LIMIT 1";

		$this->statement_exists =
			"SELECT count(id) FROM " .$table_prefix ."calendars
			WHERE id = ?";
		$this->statement_fetchinfo =
			"SELECT name, rights_users, rights_subscribed, rights_guests,
			rss_last, rss_next, rss_next_user, new_event_label FROM " .$table_prefix ."calendars
			WHERE id = ?";

		$this->statement_is_subscribed =
			"SELECT count(*) FROM " . $table_prefix ."calendars_users
			WHERE id_cal = ? AND id_user = ?";

		$this->statement_subscribed_users =
			"SELECT u.id id, u.username username
			FROM " . $table_prefix ."calendars_users cu, " . $table_prefix . "users u
			WHERE cu.id_cal = ? AND u.id = cu.id_user";

		$this->statement_subscribe_user =
			"INSERT INTO " . $table_prefix ."calendars_users (id_cal, id_user) VALUES (?, ?)";

		$this->statement_delete_subscribed_users =
			"DELETE FROM " . $table_prefix ."calendars_users WHERE id_cal = ?";

		$this->statement_active_calendar =
			"UPDATE " . $table_prefix . "calendars SET active = 1 - active";

		$this->statement_is_active =
			"SELECT count(*) FROM " . $table_prefix ."calendars
			WHERE id = ? AND active = 1";

		$this->statement_get_owner =
			"SELECT id_owner FROM " . $table_prefix ."calendars
			WHERE id = ?";

		$this->statement_update = 
			"UPDATE " .  $table_prefix . "calendars SET %s WHERE id = ?";
	}

	function exists() {
		$res =& $this->db->getOne($this->statement_exists, array($this->cal_nb));
		$this->checkSql($res);
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
		$res =& $this->db->query($this->statement_fetchinfo, array($this->cal_nb));
		$this->checkSql($res);

		$row = $res->fetchRow();
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
		$res =& $this->db->query($this->statement_write, array($data, "$year-$month-$day", null, $user_id, $this->cal_nb));
		$this->checkSql($res);

		// FIXME à modifier plus tard
		$event_id = mysql_insert_id($this->db->connection);
		$this->addUserToEvent($event_id, $user_id);

		$this->log->debug("/writeData($data, $year, $month, $day, $user_id)");
		return $event_id;
	}

	function addUserToEvent($event_id, $user_id) {
		$this->log->debug("addUserToEvent(event_id=$event_id, user_id=$user_id)");
		$res =& $this->db->query($this->statement_write_user, array($event_id, $user_id));
		$this->checkSql($res);
		$this->log->debug("/addUserToEvent($event_id, $user_id)");
	}

	function removeUserFromEvent($event_id, $user_id) {
		$res =& $this->db->query($this->statement_remove_user, array($event_id, $user_id));
		$this->checkSql($res);
	}


	function & readData($year = null, $month = null, $day = null) {
		if ($day != null) {
			$data =& $this->db->getAll($this->statement_read,
					array("$year-$month-$day", $this->cal_nb), DB_FETCHMODE_ASSOC);
		} elseif ($month != null) {
			$data =& $this->db->getAll($this->statement_read_month,
					array($year, $month, $this->cal_nb), DB_FETCHMODE_ASSOC);
		} elseif ($year != null) {
			return 0;
		} else {
			$data =& $this->db->getAll($this->statement_read_all, array($this->cal_nb), DB_FETCHMODE_ASSOC);
		}
		$this->checkSql($data);
		
		return $data;
	}

	function & readUsersForEvent($id_event) {
		$data =& $this->db->getCol($this->statement_read_users, 0, array($id_event));
		$this->checkSql($data);
		return $data;
	}

	function & readDataForUserId($user_id, $year, $month) {
		$data =& $this->db->getAll($this->statement_read_foruserid,
				array($user_id, $year, $month, $this->cal_nb), DB_FETCHMODE_ASSOC);
		$this->checkSql($data);
		
		return $data;
	}

	function & readAllDataForUser($user) {
		$data =& $this->db->getAll($this->statement_readall_foruser,
				array($user, $this->cal_nb), DB_FETCHMODE_ASSOC);
		$this->checkSql($data);
		
		return $data;
	}
	
	function modifyData($data, $id) {
		$res =& $this->db->query($this->statement_mod, array($data, $id));
		$this->checkSql($res);
	}

	function deleteData($id) {
		$res =& $this->db->query($this->statement_del, array($id));
		$this->checkSql($res);
		$res =& $this->db->query($this->statement_del_users, array($id));
		$this->checkSql($res);
	}

	function checkSql(&$res) {
		if (PEAR::isError($res)) {
			die($res->getMessage());
		}
	}

	function & getUsersForEvent($id) {
		$res =& $this->db->getAll($this->statement_users, array($id));
		$this->checkSql($res);
		
		return $res;
	}

	function & getLastEntries($minnumber = 10, $delay = 5) {
		$res =& $this->db->getAll($this->statement_rss1, array($this->cal_nb, $delay), DB_FETCHMODE_ASSOC);
		$this->checkSql($res);
		if (count($res) >= $minnumber) {
			return $res;
		}

		$res =& $this->db->getAll($this->statement_rss2, array($this->cal_nb, $minnumber), DB_FETCHMODE_ASSOC);
		$this->checkSql($res);
		return $res;
	}

	function & getNextEntries($minnumber = 10) {
		$res =& $this->db->getAll($this->statement_rss_next, array($this->cal_nb, $minnumber), DB_FETCHMODE_ASSOC);
		$this->checkSql($res);

		return $res;
	}
	function & getNextEntriesForUser($user, $minnumber = 10) {
		$res =& $this->db->getAll($this->statement_rss_next_for_user, array($user, $minnumber), DB_FETCHMODE_ASSOC);
		$this->checkSql($res);

		return $res;
	}

	function getDateForKey($keys) {
		$keys = mysql_escape_string($keys);
		$keys = str_replace(array('%', '_'), array('\%', '\_'), $keys);
		$keys = explode(" ", $keys);
		$where_clause = "1";
		foreach ($keys as $key) {
			$where_clause .= " AND event LIKE '%$key%'";
		}
		
		$statement = sprintf($this->statement_search, $where_clause);

		$res =& $this->db->getOne($statement, array($this->cal_nb));
		$this->checkSql($res);

		return $res;
	}

	function isSubscribedUser($id_user) {
		$res =& $this->db->getOne($this->statement_is_subscribed, array($this->cal_nb, $id_user));
		$this->checkSql($res);
		return ($res > 0);
	}

	function toggleActive() {
		$res =& $this->db->query($this->statement_active_calendar);
		$this->checkSql($res);
	}

	function isActive() {
		$res =& $this->db->getOne($this->statement_is_active, $this->cal_nb);
		$this->checkSql($res);

		return ($res > 0);	
	}

	function getOwner() {
		if (! isset($this->id_owner)) {
			$res =& $this->db->getOne($this->statement_get_owner, $this->cal_nb);
			$this->checkSql($res);

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
		$statement = sprintf($this->statement_update, $where_statement);
		$values[] = $this->cal_nb;

		$res =& $this->db->query($statement, $values);
		$this->checkSql($res);

		$this->delayedWrite = false;
	}

	function setName($name) {
		$this->log->debug("setName($name)");
		$this->name = $name;
		if ($this->delayedWrite) {
			$this->updatedFields[] = array('field' => 'name', 'value' => $name);
		} else {
			$statement = sprintf($this->statement_update, "name = ?");
			$res =& $this->db->query($statement, array($name, $this->cal_nb));
			$this->checkSql($res);
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
			$statement = sprintf($this->statement_update, "rights_users = ?, rights_subscribed = ?, rights_guests = ?");
			$values = $rights;
			array_push($values, $this->cal_nb);
			$res =& $this->db->query($statement, $values);
			$this->checkSql($res);
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
			$statement = sprintf($this->statement_update, "rss_last = ?, rss_next = ?, rss_next_user = ?");
			$values = $rss_titles;
			array_push($values, $this->cal_nb);
			$res =& $this->db->query($statement, $values);
			$this->checkSql($res);
		}
		$this->log->debug("/setRssTitles");
	}

	function setNewEventLabel($label) {
		$this->new_event_label = $label;
		if ($this->delayedWrite) {
			$this->updatedFields[] = array('field' => 'new_event_label', 'value' => $label);
		} else {
			$statement = sprintf($this->statement_update, "new_event_label = ?");
			$res =& $this->db->query($statement, $label);
			$this->checkSql($res);
		}
	}

	function &getSubscribedUsers() {
		$res =& $this->db->getAll($this->statement_subscribed_users, array($this->cal_nb), DB_FETCHMODE_ASSOC);
		$this->checkSql($res);

		return $res;
	}

	function setSubscribedUsers(&$users) {
		$res =& $this->db->query($this->statement_delete_subscribed_users, $this->cal_nb);
		$this->checkSql($res);

		$sth = $this->db->prepare($this->statement_subscribe_user);
		$this->checkSql($sth);

		foreach ($users as $uid) {
			$res =& $this->db->execute($sth, array($this->cal_nb, $uid));
			$this->checkSql($res);
		}
	}

}

?>
