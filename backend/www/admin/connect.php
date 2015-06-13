<?php
	function db_connect() {
		return new PDO('sqlite:/var/sqlite_db/shit.db');
	}
?>