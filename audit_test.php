<!DOCTYPE html>
<html>
<head>
</head>
<body>

<?php
	if ( isset( $_POST[ 'submit' ] ) ) {
		include 'audit_class.php';
		$audit = new Audit( new Sitemap( $_POST['url'] ) );
	} else { ?>
	<form method="post">
		<input type="text" name="url" />
		<input type="submit" name="submit" />
	</form>
<?php } ?>
