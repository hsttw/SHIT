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
                    if (($query = $db->query("select count(*) as total from http")) === false) {
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
                    $select_total_stmt =  $db->prepare("select count(*) as total from http where src like :src or dst like :dst or payload like :payload");

                    if (!$select_total_stmt) {
                        $respond["status"] = "failure";
                        $respond["msg"] = ($debug ? "select_total_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
                    } else {
                        $select_1 = $select_total_stmt->bindParam(":src", $keyword, PDO::PARAM_STR);
                        $select_2 = $select_total_stmt->bindParam(":dst", $keyword, PDO::PARAM_STR);
                        $select_3 = $select_total_stmt->bindParam(":payload", $keyword, PDO::PARAM_STR);

                        if (!$select_1 || !$select_2 || !$select_3) {
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
                        $select_stmt =  $db->prepare("select * from http limit :start, :page_size");
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
                                    $select_stmt->bindColumn(1, $id);
                                    $select_stmt->bindColumn(2, $src);
                                    $select_stmt->bindColumn(3, $dst);
                                    $select_stmt->bindColumn(4, $payload, PDO::PARAM_LOB);
                                    $select_stmt->bindColumn(5, $timestamp);
                                    $result_array = [];
                                    while($select_stmt->fetch()) {
                                        $result = [];
                                        $result["id"] = $id;
                                        $result["src"] = $src;
                                        $result["dst"] = $dst;
                                        $result["timestamp"] = $timestamp;
                                        $result["payload"] = base64_encode($payload);
                                        $result_array[] = $result;
                                    }
                                    $respond["content"] = $result_array;
                                    $respond["pager"]["total"] = $total;
                                    $respond["pager"]["number_of_pages"] = $number_of_pages;
                                }
                            }
                        }
                    } else {
                        $select_stmt =  $db->prepare("select * from http
                            where (src like :src or dst like :dst or payload like :payload) limit :start, :page_size");
                        if (!$select_stmt) {
                            $respond["status"] = "failure";
                            $respond["msg"] = ($debug ? "select_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
                        } else {
                            $select_1 = $select_stmt->bindParam(":src", $keyword, PDO::PARAM_STR);
                            $select_2 = $select_stmt->bindParam(":dst", $keyword, PDO::PARAM_STR);
                            $select_3 = $select_stmt->bindParam(":payload", $keyword, PDO::PARAM_STR);
                            $select_4 = $select_stmt->bindParam(":start", $start, PDO::PARAM_INT);
                            $select_5 = $select_stmt->bindParam(":page_size", $page_size, PDO::PARAM_INT);

                            if (!$select_1 || !$select_2 || !$select_3 || !$select_4 || !$select_5) {
                                $respond["status"] = "failure";
                                $respond["msg"] = ($debug ? "select_stmt bind failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
                            } else {
                                if (!$select_stmt->execute()) {
                                    $respond["status"] = "failure";
                                    $respond["msg"] = ($debug ? "select_stmt execute failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
                                } else {
                                    $select_stmt->bindColumn(1, $id);
                                    $select_stmt->bindColumn(2, $src);
                                    $select_stmt->bindColumn(3, $dst);
                                    $select_stmt->bindColumn(4, $payload, PDO::PARAM_LOB);
                                    $select_stmt->bindColumn(5, $timestamp);
                                    $result_array = [];
                                    while($select_stmt->fetch()) {
                                        $result = [];
                                        $result["id"] = $id;
                                        $result["src"] = $src;
                                        $result["dst"] = $dst;
                                        $result["timestamp"] = $timestamp;
                                        $result["payload"] = base64_encode($payload);
                                        $result_array[] = $result;
                                    }
                                    $respond["content"] = $result_array;
                                    $respond["pager"]["total"] = $total;
                                    $respond["pager"]["number_of_pages"] = $number_of_pages;
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
