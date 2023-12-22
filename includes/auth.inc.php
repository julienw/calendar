<?php
/*
   Copyright 2005 Julien Wajsberg <felash@gmail.com>
   This file is part of Concert Calendar.

   Concert Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Concert Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Concert Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'conf/config.inc.php';
require_once 'includes/my_log.inc.php';

class Auth {
	var $db;
	var $id;
	var $type;
	var $user;
	var $password;
	var $table;
	var $result;
	var $guest_user = "guest";
	var $log;
	
	function Auth(&$db) {
		global $table_prefix;
		$this->db =& $db;
		$this->table = $table_prefix . "users";

		$this->log = new MyLog($db, 'auth');
	}
	
	function check() {
		$this->log->debug("check()");
		if (isset($this->result)) {
			$this->log->debug("/check() -> we already have {$this->result}");
			return $this->result;
		}
		
		$this->getCredentials();

		if ($this->type == 'PHP') {
			$this->result = $this->authenticate();
    } else if ($this->type == "FORM") {
      $this->result = $this->authenticate();
      if ($this->result) {
        session_regenerate_id();
        $_SESSION["user"] = $this->user;
      }
		} else if ($this->type == 'EXTERN' or $this->type == 'SESSION') {
			$this->result = $this->fetchId();
		} else {
			$this->result = false;
			/* user guest */
			$this->user = $this->guest_user;
			$this->fetchId();
		}

		$this->log->debug("/check() -> we found {$this->result} (user={$this->user}, id={$this->id})");
		return $this->result;
	}

	function getCredentials() {
		$this->log->debug("getCredentials()");

		// seen on http://www.php.net/manual/en/features.http-auth.php
		//set http auth headers for apache+php-cgi work around
		if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches)) {
			list($name, $password) = explode(':', base64_decode($matches[1]));
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$this->user = $_SERVER['PHP_AUTH_USER'];
			$this->password = $_SERVER['PHP_AUTH_PW'];
			$this->type = 'PHP';
		} elseif (isset($_SERVER['REMOTE_USER'])) {
			$this->user = $_SERVER['REMOTE_USER'];
      $this->type = 'EXTERN';
    } elseif (isset($_POST['loginuser'])) {
      $this->user = $_POST['loginuser'];
      $this->password = $_POST['loginpassword'];
      $this->type = "FORM";
    } elseif (isset($_SESSION['user'])) {
      $this->user = $_SESSION['user'];
      $this->type = "SESSION";
		} else {
			$this->type = 'NONE';
		}
		$this->log->debug("auth using {$this->type} mecanism : user = {$this->user}");
		$this->log->debug("/getCredentials()");
	}

	function authenticate() {
		$this->log->debug("authenticate()");
		$query = 'SELECT id FROM ' . $this->table . ' WHERE username=? AND passwd=? LIMIT 1';
		$id = $this->db->getOne($query, array($this->user, sha1($this->password)));
		if (PEAR::isError($id)) {
			die($id->getMessage());
		}
		if (empty($id)) {
			$this->log->debug("/authenticate() -> false");
			return false;
		}
		
		$this->id = $id;
		$this->log->debug("/authenticate() -> true");
		return true;
	}

	function fetchId() {
		$this->log->debug("fetchId() <- user={$this->user}");
		$query = 'SELECT id FROM ' . $this->table . ' WHERE username=? LIMIT 1';
		$this->id = $this->db->getOne($query, array($this->user));
    if (empty($id)) {
      $this->log->debug("/fetchId() -> id hasn't been found");
      return false;
    }
		$this->log->debug("/fetchId() -> got id={$this->id}");
    return true;
	}
	
	function sendAuthRequest() {
		header('WWW-Authenticate: Basic realm="Calendrier"');
		header('HTTP/1.0 401 Unauthorized');
	}

	function getId() {
		return $this->id;
	}

	function getUsername() {
		return $this->user;
	}
}
