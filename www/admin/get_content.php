<?php
    require_once("connect.php");
    require_once("lib.php");

    $session = (isset($_REQUEST["s"]) && ($_REQUEST["s"] !== "")) ? (string) $_REQUEST["s"] : NULL;
    $id = (isset($_REQUEST["id"]) && ($_REQUEST["id"] !== "")) ? (string) $_REQUEST["id"] : NULL;

    $debug = false;
    $respond = [];
    $respond["status"] = "success";

    $db = db_connect();
    if (!$db) {
        $respond["status"] = "failure";
        $respond["msg"] = "System down";
    } else {
        if (!verify_session($db, $session)) {
            $respond["status"] = "failure";
            $respond["msg"] = "error";
        } else {
            $select_stmt =  $db->prepare("select * from shit where id = :id");
            if (!$select_stmt) {
                $respond["status"] = "failure";
                $respond["msg"] = ($debug ? "select_stmt prepare failed: (" . $db->errorCode() . ") " . implode(",", $db->errorInfo()) : "error");
            } else {
                if ($select_stmt->bindParam(":id", $id, PDO::PARAM_INT) === false) {
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
                            $respond["content"] = $result;
                        }
                    }
                }
            }
        }
    }
    echo "ap(" . json_encode($respond) . ")";
?>
