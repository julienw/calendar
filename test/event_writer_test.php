<html>
<body>
<dl>
<?php

require_once '../includes/event_writer.inc.php';

$event = new Event_Writer();

print $event->get_as_html('1', '00:00', 'evenement quelconque');
?>
</dl>
<pre>
<?php
print $event->get_as_javascript();
?>
</pre>
</body>
</html>
