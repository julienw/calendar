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

/*
   Julien Wajsberg <felash@gmail.com>
   10 mai 2005

   Cette classe est un Helper pour définir à un seul endroit le contenu
   d'une ligne d'événément.
 */

require_once('event_form_writer.inc.php');
require_once('event_text_writer.inc.php');
require_once('event_textempty_writer.inc.php');

class Event_Writer {
	var $text;
	var $form;
	var $empty;
	var $instance;

	function Event_Writer() {
		$this->text = new Event_Text_Writer();
		$this->form = new Event_Form_Writer();
		$this->empty = new Event_TextEmpty_Writer();
	}

	function &getInstance() {
		static $instance = null;
		if ($instance == null) {
			$instance = new Event_Writer();
		}
		return $instance;
	}

	function get_as_html($id, $horaire, $data, $users, $class, $action, $date) {
		$retour = $this->text->get_as_html($id, $horaire, $data, $users, $class, $action, $date);
		$retour .= $this->form->get_as_html($id, $horaire, $data, $users, $class, $action, $date);
		return $retour;
	}

	function get_empty_as_html($id, $horaire, $data, $users, $class = '', $action = '', $date = '') {
		$retour = $this->empty->get_as_html($id, $horaire, $data, $users, $class, $action, $date);
		return $retour;
	}

	function get_as_javascript() {
		$retour = <<<JAVASCRIPT
  var day_of_events = document.getElementById('dl' + day);
  if (! day_of_events) {
	var daycell = document.getElementById('day' + day);
	day_of_events = document.createElement('dl');
	day_of_events.setAttribute('id', 'dl' + day);
	var dd = document.createElement('dd');
	dd.appendChild(day_of_events);
	daycell.appendChild(dd);
  }
JAVASCRIPT;

		$count = 1;

		$retour .= $this->text->get_as_javascript('day_of_events', $count);
		$retour .= $this->form->get_as_javascript('day_of_events', $count);

		return $retour;
	}
}
