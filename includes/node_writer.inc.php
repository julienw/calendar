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
   9 mai 2005

   Cette classe permet d'afficher en javascript et en html le même
   arbre XML.
 */

class Node_Writer {

	// array of XML_Tree_Node instances
	var $nodes;

	function __construct() {
		die("This class is abstract, it should not be instanciated.");
	}

	function get_as_javascript($root_variable, &$count) {
		$str = '';
		$pile = array();
		if (empty($this->nodes)) {
			return '';
		}

		foreach ($this->nodes as $node) {
			$pile[] = array(null, $node);
		}

		$first_child = "var$count";
		while (list($parent_var, $node) = array_shift($pile)) {
			$node_var = "var$count";
			$str .= "  var $node_var = document.createElement('" . $node->getName() . "');\n";
			foreach ($node->attributes() as $name => $value) {
				$value = $this->replace_variables_to_javascript($value);
				$str .= "  $node_var.setAttribute('$name', '$value');\n";
				if ($name == 'class') {
					$str .= "  $node_var.setAttribute('className', '$value'); // for Internet Explorer\n";
				}
			}
			
      $content = (string) $node;
      $content = preg_replace('/\s+/', ' ', $content);
			if ($content) {
				// TODO : enlever les entities
				$content = $this->replace_variables_to_javascript($content);
				$str .= "  $node_var.appendChild(document.createTextNode('" . $content . "'));\n";
			}
			
			foreach ($node->children() as $child) {
				$pile[] = array($node_var, $child);
			}

			if ($parent_var != null) {
				$str .= "  $parent_var.appendChild($node_var);\n";
			}
			

			$str .= "\n";

			$count++;
		}

		$str .= "  $root_variable.appendChild($first_child);\n\n";

		return $str;
	}

	/* fonction qui va remplacer les variables du type __VARIABLE__
	 * en une variable javascript du type 'variable'.
	 */
	function replace_variables_to_javascript($value) {
		$value = addslashes($value);
		$value = preg_replace_callback('/__([A-Z]+)__/', function ($matches) { return "' +" . addslashes(strtolower($matches[1])) . "+ '"; }, $value);
		return $value;
	}

  function get_as_html($id, $horaire, $data, $users, $class, $action, $date) {
    $str = '';
		foreach ($this->nodes as $node) {
			$xml = $node->asXML();
			$xml = str_replace(
					array('__ID__', '__HORAIRE__', '__DATA__', '__USERS__', '__MYCLASS__', '__TOGGLEACTION__', '__DATE__'),
					array($id, $horaire, $data, $users, $class, $action, $date),
					$xml);
			$str .= $xml;
		}

		return $str;
	}
}
?>
