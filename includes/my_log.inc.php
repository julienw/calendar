<?php
/*
   Copyright 2006 Julien Wajsberg <felash@gmail.com>
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
   14 février 2006
 */

class MyLog {
  var $db;
  var $statement;
	var $loglevel = 4;

	function __construct(&$db, $ident) {
		global $table_prefix;

    $sql = "INSERT INTO ${table_prefix}log (ident, priority, message) VALUES ('calendar_server', :priority, :message)";
    $this->statement = $db->prepare($sql);
	}

  function log($str, $level) {
    if ($level > $this->loglevel) {
      return;
    }
    $this->statement->execute(['priority' => $level, 'message' => substr($str, 0, 200)]);
  }

	function debug($str) {
    $this->log($str, 6);
	}

	function info($str) {
    $this->log($str, 5);
	}

	function warn($str) {
    $this->log($str, 4);
	}
}
