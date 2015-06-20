<?php
	function setSession($username, $login_time) {
		return sha1($username . $login_time);
	}
	/*I
	function password_verify($password, $crypt_password) {
		if ($crypt_password === crypt($password, $crypt_password)) {
			return true;
		} else {
			return false;
		}
	}
	*/
?>
