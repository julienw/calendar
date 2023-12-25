<?php
function prepare($db, $query) {
  $statement = $db->prepare($query);
  if (!$statement) {
    die(print_r($statement->errorInfo(), true));
  }

  return $statement;
}

function query($statement, $values) {
  if (!$statement->execute($values)) {
    die(print_r($statement->errorInfo(), true));
  }
  return $statement;
}

function getOne($statement, $values) {
  query($statement, $values);
  $result = $statement->fetchColumn();
  return $result;
}
?>
