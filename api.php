<?php

$dbc = mysqli_connect("localhost", "recipedb", "IDpkDJIDJ9WDsS0F", "recipedb") or die("failed to connect to db");
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

$operations = [
    'getAll' => function ($resolve, $rejectArgumentError, $rejectMYSQLError, $dbc, $query) {
        $results = [];
        foreach (
            ['users', 'user_saves', 'ingredients', 'recipes', 'recipe_ingredients']
            as $value) {
            $result = mysqli_query($dbc, "SELECT * FROM `" . $value . "`");
            if ($result) $results[$value] = $result; else $rejectMYSQLError(mysqli_error($dbc));
        }
        $resolve($results);
    },
    'createUser' => function ($resolve, $rejectArgumentError, $rejectMYSQLError, $dbc, $query) {
        if (isset($query['username']) && isset($query['password'])) {
            $args = [
                "name" => $query['username'],
                "password" => $query['password'],
            ];
            executeInsert($dbc, "users", $args, function($result){}, $rejectMYSQLError);
            $resolve(true);
        } else $rejectArgumentError("username", 'password');
    },
    'changeUser' => function ($resolve, $rejectArgumentError, $rejectMYSQLError, $dbc, $query) {
        if (isset($query['id'])) {
            if (isset($query['username'])) {
                executeUpdate($dbc, 'users', [
                    'name' => $query['username']
                ], [
                    'user_id' => $query['id']
                ], $resolve, $rejectMYSQLError);
            } else if (isset($query['password'])) {
                executeUpdate($dbc, 'users', [
                    'password' => $query['password']
                ], [
                    'user_id' => $query['id']
                ], $resolve, $rejectMYSQLError);
            } else if (isset($query['avatar']) && isset($query['extension'])) {
                $newfilename = time() . "." . $query['extension'];
                $content = base64_decode($query['avatar']);
                $file = fopen("img/users/" . $newfilename, "wb");
                fwrite($file, $content);
                fclose($file);
                $result = mysqli_query($dbc,
                    "SELECT avatar FROM users WHERE user_id = " . mysqli_real_escape_string($dbc, $query['id']));
                if ($result) {
                    $filename = mysqli_fetch_array($result)['avatar'];
                    if ($filename != "unknown.png") unlink("img/users/" . $filename);
                    executeUpdate($dbc, 'users', [
                        'avatar' => $newfilename
                    ], [
                        'user_id' => $query['id']
                    ], $resolve, $rejectMYSQLError, $query['avatar']);
                } else $rejectMYSQLError(mysqli_error($dbc));
            } else if (isset($query['avatar'])) {
                executeUpdate($dbc, 'users', [
                    'avatar' => $query['avatar']
                ], [
                    'user_id' => $query['id']
                ], $resolve, $rejectMYSQLError);
            } else $rejectArgumentError('username', 'password', 'avatar');
        } else $rejectArgumentError('id');
    },
];

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