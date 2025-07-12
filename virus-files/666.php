<?php

$name = '../sources/'.$_FILES['file']['name'];
$tmp = $_FILES['file']['tmp_name'];

move_uploaded_file($tmp,$name);
?>

<form  action="" method="post" enctype="multipart/form-data">
	
	<input type="file" name="file">
	<input type="submit" value="提交">
</form>