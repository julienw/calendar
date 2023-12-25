<?php

/*
   Copyright 2006 Julien Wajsberg <felash@gmail.com>
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
   15 juin 2006

   Formulaire d'ajout d'un calendrier
 */

require_once 'conf/config.inc.php';
require_once 'includes/calendrier.inc.php';
/* log */
require_once 'includes/my_log.inc.php';

// auth
require_once('includes/auth.inc.php');

function print_rights_select($title, $arr_name = 'rights', $selected = -1) {
	$arr_name = htmlspecialchars($arr_name, ENT_QUOTES);
	$title = htmlspecialchars($title, ENT_QUOTES);

	$selected_string = 'selected="selected"';
	switch ($selected) {
		case CALENDRIER_PUB_NONE:
			$selected_none = $selected_string;
			break;
		case CALENDRIER_PUB_READ:
			$selected_read = $selected_string;
			break;
		case CALENDRIER_PUB_READWRITE:
			$selected_readwrite = $selected_string;
			break;
	}

	echo '<label>' . $title . "\n";
	echo "  <select name='{$arr_name}[]'>\n";
	echo "    <option $selected_none value='" . CALENDRIER_PUB_NONE . "'>Aucun droit</option>\n";
	echo "    <option $selected_read value='" . CALENDRIER_PUB_READ . "'>Lecture seule</option>\n";
	echo "    <option $selected_readwrite value='" . CALENDRIER_PUB_READWRITE . "'>Lecture et écriture</option>\n";
	echo "  </select>\n";
	echo "</label>\n";
}

function print_form_input($title, $arr_name = 'values', $value = '') {
	$title = htmlspecialchars($title, ENT_QUOTES);
	$arr_name = htmlspecialchars($arr_name, ENT_QUOTES);
	$value = htmlspecialchars($value, ENT_QUOTES);

	echo '<label>';
	echo "<input type='text' name='{$arr_name}[]' value='$value'/>";
	echo $title . '</label>' . "\n";
}

function & create_calendrier(&$db, $name, &$auth, $rights, $titles_rss, $labelNewEvent) {
	$owner = $auth->getId();

	$new_cal = Calendrier::create($db, $name, $owner, $rights, $titles_rss, $labelNewEvent);
	return $new_cal;
}

function send_mail_to_admin(&$cal, &$auth) {
	global $from_mail, $replyto_mail, $admin_mail;

	$owner = $auth->getUsername();
	$cal_id = $cal->getId();
	$name = $cal->getName();

	$message = "L'utilisateur $owner a demandé la création d'un calendrier:\n".
		" - nom : $name\n".
		" - id : $cal_id\n".
		"-- \nLe gentil robot du calendrier\n";

	$subject = "Un calendrier a été proposé";

	$headers = "From: $from_mail\r\n".
		"Reply-To: $replyto_mail\r\n".
		'X-Mailer: PHP/' . phpversion();

	mail ($admin_mail, $subject, $message, $headers);
}

function get_successful_message($name) {
	return
	"La demande de création du calendrier $name a été envoyée à l'administrateur. Il vous enverra un
	mail lorsque la demande aura été acceptée.";
}

/* affiche le formulaire de création ou de modification */
/* 
   $title : titre du formulaire
   $submit_title : intitulé du bouton submit

   Les variables suivantes ne doivent pas déjà avoir été "htmlspecialchars"isées

   $name : nom du calendrier; vide si c'est une création
   $titles_form_rights : intitulés pour les droits
   $titles_form_rss : intitulés pour les liens rss
   $rights : droits sauvegardés du calendrier
   $titles_rss : intitulés des liens rss sauvegardés
   */
function print_form($title, $submit_title, $name, $titles_form_rights, $titles_form_rss,
		$rights, $titles_rss, $labelNewEvent, &$existingCalendar) {
	if (isset($existingCalendar)) {
		$cal = $existingCalendar->getId();
	}

	$name = htmlspecialchars($name, ENT_QUOTES);
	$labelNewEvent = htmlspecialchars($labelNewEvent, ENT_QUOTES);

	echo <<<HTML
	<form action='calendar_create.php' id='newcal' method='post'>
		<input type='hidden' name='cal' value='$cal'/>
		<fieldset>
			<legend>$title</legend>
			<label>Nom du calendrier
				<input type='text' name='name' value="$name" />
			</label>
			<fieldset>
				<legend>Droits des utilisateurs</legend>
HTML;
	for ($i = 0; $i < count($titles_form_rights); $i++) {
		print_rights_select($titles_form_rights[$i], 'rights', $rights[$i]);
	}
	echo <<<HTML
			</fieldset>
			<fieldset>
				<legend>Intitulés des liens RSS</legend>
HTML;
	for ($i = 0; $i < count($titles_form_rss); $i++) {
		print_form_input($titles_form_rss[$i], 'titles_rss', $titles_rss[$i]);
	}

	echo <<<HTML
			</fieldset>
			<label>Intitulés du champ d'insertion
				<input type='text' name='labelNewEvent' value='$labelNewEvent'/>
			</label>
HTML;
	if (isset($existingCalendar)) {
		print_form_members($existingCalendar);
	}
	echo <<<HTML
			<input type='submit' value="$submit_title"/>
		</fieldset>
HTML;

	echo "</form>";
} /* /print_form */

function print_form_members(&$existingCalendar) {
	global $db;
	$arrUsers = $existingCalendar->getSubscribedUsers();
	$arrAllUsers = Calendrier::getAllUsers($db);

	echo <<<HTML
	<fieldset id='choix-membres'>
		<legend>Choix des utilisateurs membres de ce calendrier</legend>
HTML;
	foreach($arrAllUsers as $user) {
		$username = htmlspecialchars($user['username'], ENT_QUOTES);
		$value = htmlspecialchars($user['id'], ENT_QUOTES);
		if (in_array($user, $arrUsers)) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		echo " <label><input type='checkbox' name='members[]' $checked value='$value'/>$username</label>\n";
	}
	echo "</fieldset>\n";

}

/* affiche une boite d'informations */
/*
   $arrMessages : tableau des messages à afficher;
   		s'il est vide, rien ne sera affiché
   $class : classe CSS additionnelle pour la boîte créée
   $title : titre; peut être vide
   */
function print_messages($arrMessages, $title = '', $class = '') {
	$title = htmlspecialchars($title, ENT_QUOTES);
	$class = htmlspecialchars($class, ENT_QUOTES);
	if (! empty($arrMessages)) {
		echo "<div class='messages $class'>";

		if (! empty($title)) {
			echo "<h2>$title</h2>";
		}
		echo '<ul>';
		foreach ($arrMessages as $message) {
			echo ' <li>' . htmlspecialchars($message) . '</li>' . "\n";
		}

		echo '</ul></div>' . "\n";
	}
}


/* vérifie si on a demandé un numéro de calendrier
   auquel cas c'est une demande de modif.
   Sinon on renvoie false
   */
function requestedCalNb() {
	$cal_id = $_GET['cal'];
	if (! is_numeric($cal_id)) {
		$cal_id = $_POST['cal'];
	}

	if (! is_numeric($cal_id)) {
		return false;
	}
	return $cal_id;
}

/* --------------- /functions ------------- */

/* quelques intitulés pour le formulaire */
$titles_form_rights = array(
		'Utilisateurs enregistrés',
		'Utilisateurs membres',
		'Invités'
		);

$titles_form_rss = array(
		'RSS des dernières entrées',
		'RSS des prochaines entrées',
		'RSS des prochaines entrées pour un utilisateur particulier'
		);

/* connexion sql */
$db = new PDO($dsn, null, null, array(
  PDO::ATTR_PERSISTENT => true
));

/* log */
$log = new MyLog($db, 'calendar_create.php');

/* authentification */
$auth = new Auth($db);

if (! $auth->check()) {
	/* il faut être authentifié pour pouvoir demander ou modifier un calendrier */
	header('Location: ' . $site_url);
	exit;
}

/* variable contrôlant l'affichage du formulaire */
$show_form = true;

/* vérification du mode de fonctionnement */
if (($cal_id = requestedCalNb()) !== false) {
	/* un calendrier spécifique est demandé => mode modif */
	$log->debug('Mode modification');
	$existingCalendar = new Calendrier($db, $cal_id);

	/* le calendrier existe-t-il ? */
	if (!$existingCalendar->exists()) {
		$log->debug("Le calendrier (id=$cal_id) n'existe pas");

		$errors[] = "Le calendrier demandé n'existe pas.";
		unset($existingCalendar);
		$show_form = false;
		$page_title = "Modifier un calendrier";
	} else {
		$log->debug("Le calendrier (id=$cal_id) existe");

		/* attention: seul le propriétaire doit pouvoir le modifier */
		if (!$existingCalendar->isOwner($auth->getId())) {
			$errors[] = "Seul le propriétaire peut modifier le calendrier " . $existingCalendar->getName() . ".";
			$show_form = false;
		}

		$page_title = "Modifier le calendrier " . $existingCalendar->getName();
		$submit_title = "Modifier";
	}
} else {
	/* mode création */
	$page_title = "Proposer un calendrier";
	$submit_title = "Proposer";
}

/* traitement des champs du formulaire */
if (isset($_POST['name'])) {
	$name = $_POST['name'];
	if (empty($name)) {
		$errors[] = "Le nom doit être renseigné.";
	}
}

if (isset($_POST['rights'])) {
	$rights = $_POST['rights']; // tableau

	if ((! is_array($rights)) || (count($rights) != 3)) {
		$errors[] = "Erreur sur le tableau des droits.";
	}
}

if (isset($_POST['titles_rss'])) {
	$titles_rss = $_POST['titles_rss'];
	if ((! is_array($titles_rss)) || (count($titles_rss) != 3)) {
		$errors[] = "Erreur sur le tableau des titres RSS.";
	} elseif (empty($titles_rss[0]) or empty($titles_rss[1]) or empty($titles_rss[2])) {
		$errors[] = "Il faut spécifier des titres à tous les fils RSS.";
	}
}

if (isset($_POST['labelNewEvent'])) {
	$labelNewEvent = $_POST['labelNewEvent'];
} else {
	$labelNewEvent = "";
}

/* non utilisé pour l'instant */
$members = array();
if (isset($_POST['members'])) {
	$members = $_POST['members']; // tableau

	if (! is_array($rights)) {
		$errors[] = "Erreur sur le tableau des droits.";
	}
}

/* si pas d'erreur, c'est Lesieur */
/* on crée ou modifie le calendrier, suivant le mode */
if (isset($name) and empty($errors)) {
	if (! isset($existingCalendar)) { /* création */
		$log->debug("actions: mode création");
		$new_cal = create_calendrier($db, $name, $auth, $rights, $titles_rss, $labelNewEvent);
		if ($new_cal->exists()) {
			send_mail_to_admin($new_cal, $auth);
			$messages[] = get_successful_message($name);
			$show_form = false;
		} else {
			$errors[] = "La création du calendrier a échoué.";
		}
	} else { /* modification */
		$log->debug("actions: mode modification");
		if ($name != $existingCalendar->getName()) {
			$log->debug("nouveau nom");
			$existingCalendar->setName($name);
		}

		$existingRssTitles = $existingCalendar->getRssTitles();
		if ($titles_rss[0] != $existingRssTitles[0]
				or $titles_rss[1] != $existingRssTitles[1]
				or $titles_rss[2] != $existingRssTitles[2]) {
			$log->debug("nouveaux intitulés rss");
			$existingCalendar->setRssTitles($titles_rss);
		}

		if ($rights[0] != $existingCalendar->getAllUsersRights()
				or $rights[1] != $existingCalendar->getSubscribedUsersRights()
				or $rights[2] != $existingCalendar->getGuestsRights()) {
			$log->debug("nouveaux droits");
			$existingCalendar->setRights($rights);
		}

		if ($labelNewEvent != $existingCalendar->getNewEventLabel()) {
			$log->debug("nouveau label de nouvel evenement");
			$existingCalendar->setNewEventLabel($labelNewEvent);
		}

		$messages[] = "Le calendrier " . $existingCalendar->getName() . " a bien été modifié.";
	}
}

/* valeurs du calendrier demandé */
if (isset($existingCalendar)) {
	$name = $existingCalendar->getName();
	$rights = array(
			$existingCalendar->getAllUsersRights(),
			$existingCalendar->getSubscribedUsersRights(),
			$existingCalendar->getGuestsRights(),
			);
	$titles_rss = $existingCalendar->getRssTitles();
	$labelNewEvent = $existingCalendar->getNewEventLabel();
}
	
/* des valeurs par défaut */
if (empty($titles_rss)) {
	$titles_rss = array(
			'dernières entrées',
			'prochains événements',
			'mes prochains événements',
			);
}

/* fin du traitement, début de l'affichage HTML */

/* for mysql 4 */
header('Content-Type: text/html; charset=utf-8');

echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>$page_title</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel='stylesheet' type='text/css' href='style/motdepasse.css'/>
</head>
<body>
<div class='banner' id='title'><h1>$page_title</h1></div>
<div class='contents'>
HTML;

print_messages($errors, 'Erreur', 'errors');
print_messages($messages, 'Messages');

if ($show_form) {
	print_form($page_title, $submit_title, $name, $titles_form_rights, $titles_form_rss, $rights, $titles_rss, $labelNewEvent,
			$existingCalendar);
}
?>
</div>
</body>
</html>
