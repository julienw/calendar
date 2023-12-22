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
   18 avril 2006

   Changement de mot de passe lorsqu'il n'y a pas de JS
 */

require_once 'conf/config.inc.php';

// auth
require_once('includes/auth.inc.php');

require_once 'DB.php';
require_once "Text/Password.php";
require_once "Mail.php";

function checkUsernameAndEmail($username, $email) {
	global $db, $table_prefix;
	$count = $db->getOne("SELECT count(id) FROM " . $table_prefix . "users WHERE username = ? AND email = ?", array($username, $email));
	if (PEAR::isError($count)) {
		die($count->getMessage());
	}

	return ($count > 0);
}

function checkUsernameAndPassword($username, $password) {
	global $db, $table_prefix;

	$count = $db->getOne("SELECT count(id) FROM " . $table_prefix . "users WHERE username = ? AND passwd = ?",
			array($username, sha1($password)));

	if (PEAR::isError($count)) {
		die($count->getMessage());
	}

	return ($count > 0);
}

function changePassword($username, $password) {
	global $db, $table_prefix;

	$result = $db->query("UPDATE " . $table_prefix . "users SET passwd = ? WHERE username = ?",
			array(sha1($password), $username));

	if (PEAR::isError($result)) {
		die($result->getMessage());
	}
}

function checkUsername($username) {
	global $db, $table_prefix;
	$email = $db->getOne("SELECT email FROM " . $table_prefix . "users WHERE username = ?", $username);

	if (PEAR::isError($email)) {
		die($email->getMessage());
	}

	return $email;
}

function checkEmail($email) {
	global $db, $table_prefix;
	$username= $db->getOne("SELECT username FROM " . $table_prefix . "users WHERE email = ?", $email);

	if (PEAR::isError($username)) {
		die($username->getMessage());
	}

	return $username;
}

$db = DB::connect($dsn);
if (PEAR::isError($db)) {
	die($db->getMessage());
}

$auth = new Auth($db);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Changement de mot de passe</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel='stylesheet' type='text/css' href='style/motdepasse.css'/>
</head>
<body>
<div class='banner' id='title'><h1>Changement de mot de passe</h1></div>
<div class='contents'>
<?php
if ($auth->check()) {
	// on est loggé
	$show_form = true;
	$username = $auth->getUsername();

	if (isset($_POST['oldpassword']) && ! empty($_POST['oldpassword'])) {
		if (!checkUsernameAndPassword($username, $_POST['oldpassword'])) {
			$error = "L'ancien mot de passe est incorrect.";
		} elseif ((! isset($_POST['newpassword1'])) || empty($_POST['newpassword1'])
				|| (! isset($_POST['newpassword2'])) || empty($_POST['newpassword2'])) {
			$error = "Merci de renseigner les deux champs <i>Nouveau mot de passe</i>.";
		} elseif ($_POST['newpassword1'] != $_POST['newpassword2']) {
			$error = "Les deux champs <i>Nouveau mot de passe</i> ne correspondent pas&nbsp;!";
		} else {
			$newpassword = $_POST['newpassword1'];

			changePassword($username, $newpassword);

			echo "<p>Votre mot de passe a bien été changé.</p>\n";
			echo "<p>Il faut <a href='login.php'>vous connecter à nouveau</a>.</p>\n";
			$show_form = false;
		}
	}

	if ($show_form) {
		if (isset($error)) {
			echo '<div class="messages errors"><h2>Erreur</h2>' . $error . '</div>';
		}
		echo <<<HTML
<form action='motdepasse.php' method='post' id='nouveau'>
<fieldset>
<legend>Changement de mot de passe</legend>
<p>Bonjour $username&nbsp;! Entrez votre ancien mot de passe ainsi que votre nouveau mot de passe, et nous ferons le changement dans notre base de
données.</p>
<label>Ancien mot de passe
<input name='oldpassword' id='oldpassword' type='password'/>
</label>
<label>Nouveau mot de passe
<input name='newpassword1' id='newpassword1' type='password'/>
</label>
<label>Nouveau mot de passe (confirmation)
<input name='newpassword2' id='newpassword2' type='password'/>
</label>
<input type='submit'/>
</fieldset>
</form>
HTML;
	}
} else {
	// on n'est pas loggé
	$show_form = true;
	$confirm = false;
	$confirmok = false;

	if (isset($_GET['confirm']) && ! empty($_GET['confirm'])) {
		$confirm = true;
		// verif du cookie de confirmation
		$result = $db->getRow("SELECT username, email FROM " . $table_prefix .
				"users WHERE confirmcookie = ?", array($_GET['confirm']), DB_FETCHMODE_ASSOC);
		if (PEAR::isError($result)) {
			die($result->getMessage());
		}

		if (empty($result)) {
			$error = "Désolé, le code de confirmation n'a pas été trouvé dans la base.";
		} else {
			$username = $result['username'];
			$email = $result['email'];
			$confirmok = true;
			$show_form = false;
		}
	}
	
	if (isset($_POST['username']) && isset($_POST['email']) 
			&& (! empty($_POST['username'])) && (! empty($_POST['email']))) {
		if (checkUsernameAndEmail($_POST['username'], $_POST['email'])) {
			$username = $_POST['username'];
			$email = $_POST['email'];
			$show_form = false;
		} else {
			$error = "Le nom d'utilisateur (" . htmlspecialchars($_POST['username'], ENT_QUOTES) .") et
				l'adresse de courrier électronique (" . htmlspecialchars($_POST['email'], ENT_QUOTES) . ") ne
				correspondent pas.";
		}
	} elseif (isset($_POST['username']) && ! empty($_POST['username'])) {
		$thisemail = checkUsername($_POST['username']);
		if (isset($thisemail) && !empty($thisemail)) {
			$username = $_POST['username'];
			$email = $thisemail;
			$show_form = false;
		} else {
			$error = "Le nom d'utilisateur (" . htmlspecialchars($_POST['username'], ENT_QUOTES) . ")
				n'a pas été trouvé dans la base de données.";
		}
	} elseif (isset($_POST['email']) && ! empty($_POST['email'])) {
		$thisusername = checkEmail($_POST['email']);
		if (isset($thisusername) && !empty($thisusername)) {
			$username = $thisusername;
			$email = $_POST['email'];
		} else {
			$error = "L'adresse de courrier électronique (" . htmlspecialchars($_POST['email'], ENT_QUOTES) . ")
				n'a pas été trouvée dans la base de données.";
		}
	}

	if ($show_form) {
		if (isset($error)) {
			echo '<div class="messages errors"><h2>Erreur</h2>' . $error . '</div>';
		}
?>
<form action='motdepasse.php' method='post' id='oubli'>
<fieldset>
<legend>Changement de mot de passe</legend>
<p>Entrez votre nom d'utilisateur ou votre adresse de courrier électronique dans les cases ci-dessous&nbsp;; nous
essaierons de trouver une correspondance dans notre base de données, et vous enverrons ensuite un mail de
confirmation.</p>
<label>Nom d'utilisateur
<input name='username' id='username' type='text' value='<?php echo htmlspecialchars($_POST['username'], ENT_QUOTES)?>' />
</label>
<label>Courrier électronique
<input name='email' id='email' type='text' value='<?php echo htmlspecialchars($_POST['email'], ENT_QUOTES)?>'/>
</label>
<input type='submit'/>
</fieldset>
</form>
<?php
	} elseif (!$confirm) {
		$confirmcookie = Text_Password::create(20, 'unpronounceable', 'alphanumeric');
		// base de donnees

		$result = $db->query("UPDATE " . $table_prefix . "users SET confirmcookie = ? WHERE username = ?", array($confirmcookie, $username));
		if (PEAR::isError($result)) {
			die($result->getMessage());
		}

		// envoi de mail
		$mail = Mail::factory('mail');
		$mail->send($email,
				array(
					'From' => $from_mail,
					'Reply-to' => $replyto_mail,
					'Subject' => "Mail de confirmation pour changement de mot de passe pour l'application de calendrier",
					),
				"Bonjour :-)\n\nL'application de calendrier a reçu une demande de changement de mot de passe pour l'utilisateur $username.\nSi vous n'avez rien demandé, vous pouvez juste ignorer ce mail. Autrement, merci de cliquer sur l'adresse de confirmation suivante:\n$site_url/motdepasse.php?confirm=$confirmcookie\n\nL'administrateur"
			);
		echo '<p>Un mail de confirmation vous a été envoyé à votre adresse de courrier
			électronique&nbsp;: ' . htmlspecialchars($email) . '.</p>';

	}

	if ($confirmok) {
		// génération de password
		$password = Text_Password::create();

		// Modification de la base
		$result = $db->query(
				"UPDATE " . $table_prefix . "users SET passwd = ?, confirmcookie = NULL
				WHERE username = ?",
				array(sha1($password), $username));

		if (PEAR::isError($result)) {
			die($result->getMessage());
		}

		// envoi de mail
		$mail = Mail::factory('mail');
		$mail->send($email,
				array(
					'From' => $from_mail,
					'Reply-to' => $replyto_mail,
					'Subject' => "Nouveau mot de passe pour l'application de calendrier",
					),
				"Bonjour :-)\n\nUn nouveau mot de passe a été généré pour l'application de calendrier, pour l'utilisateur $username.\nCe nouveau mot de passe est : $password\n\nVous pouvez le changer sur l'interface du calendrier.\n\nJe vous rappelle que le calendrier est disponible à l'adresse suivante : $site_url\n\nL'administrateur"
			);
		echo '<p>Un nouveau mot de passe a été généré et vous a été envoyé à votre adresse de courrier
			électronique&nbsp;: ' . htmlspecialchars($email) . '.</p>';
	}
}
?>
</div>
</body>
</html>
