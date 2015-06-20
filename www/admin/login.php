<?php
	require_once("connect.php");
	require_once("lib.php");

	$username = (isset($_REQUEST["username"]) && ($_REQUEST["username"] !== "")) ? (string) $_REQUEST["username"] : NULL;
	$password = (isset($_REQUEST["password"]) && ($_REQUEST["password"] !== "")) ? (string) $_REQUEST["password"] : NULL;

	$debug = false;
	$respond = [];
	$respond["status"] = "success";

	if ((is_null($username)) || (is_null($password))) {
		$respond["status"] = "failure";
		$respond["msg"] = "Pls fill the field";
	} else {
		$db = db_connect();
		if (!$db) {
			$respond["status"] = "failure";
			$respond["msg"] = "System down";
		} else {
			$select_stmt = $db->prepare("select * from admin where username = :username");
			if (!$select_stmt) {
				$respond["status"] = "failure";
				$respond["msg"] = ($debug ? "select_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
			} else {
				if (!($select_stmt->bindParam(":username", $username, PDO::PARAM_STR))) {
					$respond["status"] = "failure";
					$respond["msg"] = ($debug ? "select_stmt bind failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
				} else {
					if (!($select_stmt->execute())) {
						$respond["status"] = "failure";
						$respond["msg"] = ($debug ? "select_stmt execute failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
					} else {
						if (($result = $select_stmt->fetch(PDO::FETCH_ASSOC)) === false) {
							$respond["status"] = "failure";
							$respond["msg"] = "Username or Password failed";
						} else {
							if (!password_verify($password, $result["password"])) {
								$respond["status"] = "failure";
								$respond["msg"] = "Username or Password failed";
							}
						}
					}
				}
			}
			if ($respond["status"] === "success") {
				$last_login_time = date("Y-m-d H:i:s");
				$session = setSession($username, $last_login_time);
				$update_stmt = $db->prepare("update admin set session = :session, last_login_time = :last_login_time where id = :id");
				if (!$update_stmt) {
					$respond["status"] = "failure";
					$respond["msg"] = ($debug ? "update_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
				} else {
					$update_1 = $update_stmt->bindParam(":session", $session, PDO::PARAM_STR);
					$update_2 = $update_stmt->bindParam(":last_login_time", $last_login_time, PDO::PARAM_STR);
					$update_3 = $update_stmt->bindParam(":id", $result["id"], PDO::PARAM_STR);
					if (!$update_1 || !$update_2 || !$update_3) {
						$respond["status"] = "failure";
						$respond["msg"] = ($debug ? "update_stmt bind failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
					} else {
						if (!$update_stmt->execute()) {
							$respond["status"] = "failure";
							$respond["msg"] = ($debug ? "update_stmt execute failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
						} else {
							$respond["content"]["session"] = setSession($username, $last_login_time);
						}
					}
				}
			}
			$db = null;
		}
	}
	echo "ap(" . json_encode($respond) . ")";
?>