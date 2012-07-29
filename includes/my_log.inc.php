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

require_once 'Log.php';

class MyLog {
	var $log;
	var $loglevel = PEAR_LOG_INFO;

	function MyLog (&$db, $ident) {
		global $table_prefix;

		
		$this->log =& Log::singleton('sql', $table_prefix . 'log', getmypid() . ' ' . $ident, array('db' => $db), $this->loglevel);
	}

	function debug($str) {
		$this->log->debug($str);
	}

	function info($str) {
		$this->log->info($str);
	}

	function notice($str) {
		$this->log->notice($str);
	}
}
