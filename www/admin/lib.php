<?php
	require_once("connect.php");

	function setSession($username, $login_time) {
		return sha1($username . $login_time);
	}
	/*
	function password_verify($password, $crypt_password) {
		if ($crypt_password === crypt($password, $crypt_password)) {
			return true;
		} else {
			return false;
		}
	}
	*/

	function verify_session($db, $session) {

		$valid_date = 1800; // 30分鐘
		$result = true;
		$now = date("Y-m-d H:i:s");
		
		$select_stmt = $db->prepare("select * from admin where session = :session");

		if (!$select_stmt) {
			$result = false;
		} else {
			if (!($select_stmt->bindParam(":session", $session, PDO::PARAM_STR))) {
				$result = false;
			} else {
				if (!($select_stmt->execute())) {
					$result = false;
				} else {
					if (($result = $select_stmt->fetch(PDO::FETCH_ASSOC)) === false) {
						$result = false;
					} else {
						if ((strtotime($result["last_login_time"]) + $valid_date) < strtotime($now)) {
							$result = false;
						}
					}
				}
			}
		}

		$update_stmt = $db->prepare("update admin set last_login_time = :last_login_time where session = :session");

		if (!$update_stmt) {
			$result = false;
		} else {
			$update_1 = $update_stmt->bindParam(":last_login_time", $now, PDO::PARAM_STR);
			$update_2 = $update_stmt->bindParam(":session", $session, PDO::PARAM_STR);
			if (!$update_1 || !$update_2) {
				$result = false;
			} else {
				if (!$update_stmt->execute()) {
					$result = false;
				}
			}
		}

		return $result;
	}
?>
