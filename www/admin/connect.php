<?php
	date_default_timezone_set('Asia/Taipei');
	function db_connect() {
		/* FIXME - Should use absolute path */
		return new PDO('sqlite:../../backend/shit.db');
	}
?>
