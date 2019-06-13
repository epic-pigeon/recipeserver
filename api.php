<?php

$dbc = mysqli_connect("localhost", "checkchecker", "JJWMdF6riGuHDoVr", "checkchecker") or die("failed to connect to db");
mysqli_set_charset($dbc, 'utf8');

function executeInsert($dbc, $table, $args, $resolve, $rejectMYSQLError) {
    $query = "INSERT INTO `$table` (";
    $array = [];
    foreach ($args as $k => $v) {
        array_push($array, "`".$k."`");
    }
    $query .= implode(", ", $array);
    $query .= ") VALUES (";
    $array = [];
    foreach ($args as $k => $v) {
        array_push($array, "'".mysqli_real_escape_string($dbc, $v)."'");
    }
    $query .= implode(", ", $array);
    $query .= ")";
    $result = mysqli_query($dbc, $query);
    if ($result) $resolve($result, null, mysqli_insert_id($dbc)); else $rejectMYSQLError(mysqli_error($dbc));
}

function executeDelete($dbc, $table, $args, $resolve, $rejectMYSQLError) {
    $query = "DELETE FROM `$table` WHERE ";
    $array = [];
    foreach ($args as $k => $v) {
        array_push($array, "`".$k."` = '".mysqli_real_escape_string($dbc, $v)."'");
    }
    $query .= implode(" AND ", $array);
    $result = mysqli_query($dbc, $query);
    if ($result) $resolve($result); else $rejectMYSQLError(mysqli_error($dbc));
}

function executeUpdate($dbc, $table, $args, $conditions, $resolve, $rejectMYSQLError, $info = null) {
    $query = "UPDATE `$table` SET ";
    $array = [];
    foreach ($args as $k => $v) {
        array_push($array, "`".$k."` = '".mysqli_real_escape_string($dbc, $v)."'");
    }
    $query .= implode(", ", $array);
    $query .= " WHERE ";
    $array = [];
    foreach ($conditions as $k => $v) {
        array_push($array, "`".$k."` = '".mysqli_real_escape_string($dbc, $v)."'");
    }
    $query .= implode(" AND ", $array);
    $result = mysqli_query($dbc, $query);
    if ($result) $resolve($result, $info); else $rejectMYSQLError(mysqli_error($dbc));
}

$operations = [];

$methods = [$_GET, $_POST];

foreach ($methods as $query) if (isset($query['operation'])) {
    $name = $query['operation'];
    if (isset($operations[$name])) try {
        $operations[$name](
            function ($result, $info = null, $lastID = null) {
                $output = [
                    'success' => "true",
                    'result' => null
                ];
                if ($result === true) {
                    $output['result'] = [];
                } else if (gettype($result) == "array") {
                    foreach ($result as $key => $value) {
                        $toJSON = [];
                        while ($row = mysqli_fetch_array($value)) {
                            array_push($toJSON, $row);
                        }
                        $output['result'][$key] = $toJSON;
                    }
                } else {
                    $toJSON = [];
                    while ($row = mysqli_fetch_array($result)) {

                        array_push($toJSON, $row);
                    }
                    $output['result'] = $toJSON;
                }
                if ($info != null) {
                    $output['info'] = $info;
                }
                if ($lastID != null) {
                    $output['last_id'] = $lastID;
                }
                echo json_encode($output);
            },
            function (...$errors) {
                echo '{"success":"false", "error":"Bad arguments: ' . implode(", ", $errors) . '"}';
            },
            function ($err) {
                echo '{"success":"false", "error":"MYSQL error: ' . $err . '"}';
            },
            $dbc,
            $query
        );
    } catch (\Exception $e) {
        echo '{"success":"false", "error":"'.$e->getMessage().'"}';
    } else echo '{"success":"false", "error":"No such operation exists"}';
}