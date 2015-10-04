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
            $select_stmt = $db->prepare("select * from http where id = :id");
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
                    }
                }
            }
        }
    }
    echo "ap(" . json_encode($respond) . ")";
?>
