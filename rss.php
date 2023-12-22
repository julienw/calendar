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

require_once('DB.php');
require_once('includes/calendrier.inc.php');

require_once 'conf/config.inc.php';
require_once 'includes/auth.inc.php';
require_once 'includes/calendar_auth.inc.php';

// tiré d'un commentaire de http://fr.php.net/htmlentities
function xmlentities($string, $quote_style=ENT_QUOTES)
{
	static $trans;
	if (!isset($trans)) {
		$trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
		foreach ($trans as $key => $value)
			$trans[$key] = '&#'.ord($key).';';
		// dont translate the '&' in case it is part of &xxx;
		$trans[chr(38)] = '&';
	}
	// after the initial translation, _do_ map standalone '&' into '&#38;'
	return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;" , strtr($string, $trans));
}

function createMissingFieds(&$value, $key) {
	if (isset($_GET['next'])) {
		$value['time'] = $value['jour'] . 'T00:00:00+0000';
	} else {
		$value["time"] = date('Y-m-d\TG:i:sO', $value["timestamp"]);
	}
	$value["uri"] = "http://www.everlong.org/calendrier/" . $value["id"];
	if (isset($_GET['cal'])) {
		$cal = "cal=" . xmlentities($_GET['cal']) . "&amp;";
	}
	if (isset($value['id_cal'])) {
		$cal = "cal=" . xmlentities($value['id_cal']) . "&amp;";
	}
	$value["url"] = "http://www.everlong.org/calendrier/?$cal" . strtr($value['jour'], '-', '/');
	$jour = preg_replace('/^(\d+)-(\d+)-(\d+).*$/', '$3/$2', $value['jour']);
	$value['event'] = xmlentities(iconv("UTF-8", "ISO-8859-15", $jour . ' ' . $value['event']));
	$value['username'] = xmlentities(ucfirst(iconv("UTF-8", "ISO-8859-15", $value['username'])));
}

/* connexion à la db */
$db = DB::connect($dsn);
if (PEAR::isError($db)) {
	die($db->getMessage());
}

/* instanciation des routines d'accès au calendrier */
if (isset($_GET['cal'])) {
	$calendrier = new Calendrier($db, $_GET['cal']);
	if (!$calendrier->isActive()) {
		exit;
	}
} else {
	$calendrier = new Calendrier($db);
}

$auth = new Auth($db);
$cal_auth = new CalendarAuth($calendrier, $auth);
if (! $cal_auth->checkRead()) exit;

if (isset($_GET['next'])) {
	if (isset($_GET['user']) && ! empty($_GET['user'])) {
		$title = "Prochains concerts pour {$_GET['user']}";
		$entries = $calendrier->getNextEntriesForUser($_GET['user']);
	} else {
		$title = "Prochains concerts";
		$entries = $calendrier->getNextEntries();
	}
} else {
	$title = "Derniers concerts entrés";
	$entries = $calendrier->getLastEntries();
}

/* time "à la" w3c */
array_walk($entries, 'createMissingFieds');
$more_recent_time = $entries[0]['time'];

header('Content-Type: text/xml');

echo "<?xml version='1.0' encoding='ISO-8859-15' ?>\n";
?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns="http://purl.org/rss/1.0/">

<channel rdf:about="http://www.everlong.org/calendrier/">
  <title><?php echo $title ?></title>
  <description />
  <link>http://www.everlong.org/calendrier/</link>
  <dc:language>fr</dc:language>
  <dc:creator>Julien Wajsberg</dc:creator>
  <dc:date><?php echo $more_recent_time ?></dc:date>
  <admin:generatorAgent rdf:resource="http://www.everlong.org" />
  <sy:updatePeriod>hourly</sy:updatePeriod>
  <sy:updateFrequency>1</sy:updateFrequency>
  <sy:updateBase><?php echo $more_recent_time ?></sy:updateBase>

  <items>
    <rdf:Seq>
<?php
foreach ($entries as $entry) {
	echo "      <rdf:li rdf:resource='{$entry['uri']}' />\n";
}
?>
    </rdf:Seq>
  </items>
</channel>

<?php
foreach ($entries as $entry) {
	echo "<item rdf:about='{$entry['uri']}'>\n";
	echo "  <title>". $entry['event'] . "</title>\n";
	echo "  <link>". $entry['url'] ."</link>\n";
	echo "  <dc:date>" . $entry['time'] . "</dc:date>\n";
	echo "  <dc:language>fr</dc:language>\n";
	echo "  <dc:creator>" . $entry['username'] . "</dc:creator>\n";
	echo "  <dc:subject>Photos</dc:subject>\n";
	echo "  <dc:description>" . $entry['event'] . "</dc:description>\n";
	echo "</item>\n";
}
?>
</rdf:RDF>

