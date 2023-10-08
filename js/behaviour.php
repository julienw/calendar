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
   4 octobre 2005
 */

require_once '../includes/event_writer.inc.php';
header('Content-Type: text/javascript');
?>
/* affichage du formulaire de modification,
   et disparition de la ligne d'evenement */
function showInput (id) {
    document.getElementById('lineoftitle' + id).style.display = 'none';
    document.getElementById('form' + id).style.display = 'block';
	document.getElementById('input' + id).focus();
}

// affichage de l'evenement, et disparition du formulaire
function showLink (id) {
    document.getElementById('form' + id).style.display = 'none';
    document.getElementById('lineoftitle' + id).style.display = 'block';
}

// le formulaire de modification a ete envoye
function submitOneForm(id) {
    var myinput = document.getElementById('input' + id);
    var data = myinput.value;

	// si la donnee est vide, alors on efface l'evenement
	if (data == '') {
		doDeleteData(id);
		return;
	}

	// sinon on modifie
	h.modifyData(data, id);

	// et on change le texte dans le lien
	changeEvent(id, data);
	showLink(id);
}

// suppression d'un evenement
function doDeleteData(id) {
	/* confirmation */
	if (! confirm("Voulez-vous vraiment supprimer cet evenement ?")) {
		return;
	}
	/* suppression dans la base */
	h.deleteData(id);

	/* recuperation de la ligne d'evenement dans la page */
	var line = document.getElementById('lineofevent' + id);

	/* suppression de l'element dans la page*/
	/* (ou juste mettre a display = none ?) */
	line.parentNode.removeChild(line);

	/* recuperation du formulaire associe */
	var form = document.getElementById('form' + id);
	form.parentNode.removeChild(form);
}

/* ajout d'un nouvel evenement */
function submitNewEvent(day) {
    var myinput = document.getElementById('inputnew' + day);
    var data = myinput.value;
	var horaire = "00:00";
	var users = currentuser;
	var myclass = 'remove';
	var toggleaction = 'removeUser';
	var date = year + '/' + month;
	
	if (data != '') {

		/* ecriture dans la base */
		/* synchrone pour l'instant */
		h.Sync();
		var id = h.writeData(data, year, month, day);
		h.Async(handler);
<?php
$event_writer =& Event_Writer::getInstance();
print $event_writer->get_as_javascript();
?>
	}
	
	/* on efface dans le formulaire d'ajout la valeur qu'on vient d'ajouter */
	myinput.value = '';
}

/* Remplacement du texte du lien */
function changeEvent(day, data) {
    var myevttitle = document.getElementById('evttitle' + day);
    myevttitle.innerHTML = data;
}

function doAddUser(id) {
	var elt = document.getElementById('users' + id);
	var users = elt.firstChild;
	var value = users.nodeValue;

	h.addUser(id);

	if (value == '[]') {
		value = '[' + currentuser + ']';
	} else {
		value = value.replace(/]$/, ', ' + currentuser + ']');
	}

	users.nodeValue = value;
}

function doRemoveUser(id) {
	var elt = document.getElementById('users' + id);
	var users = elt.firstChild;
	var value = users.nodeValue;

	h.removeUser(id);

	var myRegexp = new RegExp('(, )?' + currentuser + '(, )?', 'i');
	value = value.replace(myRegexp, '');
	users.nodeValue = value;
}

function togglePresenceUser(id) {
	var elt = document.getElementById('addremoveicon' + id);
	var className = ' ' + elt.className + ' ';
	
	if (className.match(/ add /)) {
		doAddUser(id);
		elt.setAttribute('class', 'icon remove');
		elt.setAttribute('className', 'icon remove');
	} else if (className.match(/ remove /)) {
		doRemoveUser(id);
		elt.setAttribute('class', 'icon add');
		elt.setAttribute('className', 'icon add');
	}
}

function attachEventsToFormNew() {
  for (let i = 1; i <= daysInMonth; i++) {
    let valueAtFocus = null;
		var elt = document.getElementById('formnew' + i);
		elt.onsubmit = function() { submitNewEvent(i); return false }

		elt = document.getElementById('inputnew' + i);
		elt.onfocus = function() { this.select(); valueAtFocus = this.value;};
    elt.onblur = function() {
      if (this.value !== valueAtFocus) { submitNewEvent(i); }
      valueAtFocus = null;
    }

		var elt2 = document.getElementById('td' + i);
		elt2.onclick = function() { document.getElementById('inputnew' + this.day).focus() }
		elt2.day = i;
	}
}

function pageLoaded() {
	attachEventsToFormNew();
}

function dontPropagate(e) {
	if (!e) var e = window.event;
	e.cancelBubble = true;
	if (e.stopPropagation) e.stopPropagation();
}

/* callback pour les fonctions asynchrones */
/* il ne fait rien, il ne sert qu'a recuperer les reponses */
function CalendrierCallBack() {}
CalendrierCallBack.prototype = {
	deleteData : function(response) {
				 },
				 
	modifyData : function(response) {
				 },

	writeData : function(response) {
				},
				
	addUser : function (response) {
			  },

	removeUser : function (response) {
			  }
}

/* instanciation des routines du calendrier */
var handler = new CalendrierCallBack();
var h = new Calendrier_xmlrpc(handler);
window.onload = pageLoaded;


