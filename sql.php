<?php
function PrepareSQL($database1, $user1, $pass1, $server1 = 'localhost', $port1 = 3306)
{
    global $database, $user, $pass, $server, $port, $objectSQL;
    $database = $database1;
    $user = $user1;
    $pass = $pass1;
    $server = $server1;
    $port = $port1;
    $objectSQL = null;
}

function ConnectSQL($database, $user, $pass, $server = 'localhost', $port = 3306)
{
    global $objectSQL, $errorSQL;

    try {
        $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $objectSQL = new PDO("mysql:host=$server; port=$port; dbname=$database; charset=utf8mb4", $user, $pass, $pdo_options);
        $objectSQL->exec("SET NAMES 'utf8mb4'");
    } catch (Exception $e) {
        $errorSQL = $e->getMessage();
    }
}

function CheckSQL()
{
    global $objectSQL, $database, $user, $pass, $server, $port;
    if (!$objectSQL) ConnectSQL($database, $user, $pass, $server, $port);
}

function EmptySQL($table)
{
    CheckSQL();
    global $prefixSQL, $objectSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    $sql = "TRUNCATE TABLE $table";
    $objectSQL->exec($sql);
}

function CreateSQL($table, $fields)
{
    CheckSQL();
    global $prefixSQL, $objectSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    $sql = "CREATE TABLE $table(";
    foreach ($fields as $field) {
        $name = isset($field[0]) ? $field[0] : false;
        $type = isset($field[1]) ? $field[1] : false;
        $opt1 = isset($field[2]) ? $field[2] : false;
        $opt2 = isset($field[3]) ? $field[3] : false;

        switch ($type) {
            case 'int':
                $sql .= "$name INT(10)";
                if ($opt1) $sql .= " AUTO_INCREMENT";
                if ($opt2) $sql .= " PRIMARY KEY";
                break;
            case 'varchar':
                $sql .= "$name VARCHAR(255) NULL";
                break;
            case 'text':
                $sql .= "$name TEXT NULL";
                break;
            case 'timestamp':
                $sql .= "$name TIMESTAMP";
                if ($opt1) $sql .= "  DEFAULT CURRENT_TIMESTAMP";
                break;
        }
        $sql .= ", ";
    }
    $sql = trim($sql, ', ') . ")";

    $objectSQL->exec($sql);
}

function InsertSQL($table, $insert_array, $replace = false, $echo = false)
{
    CheckSQL();
    global $prefixSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    if (is_string($insert_array)) $insert_array = compact(explode(' ', $insert_array));
    $insertA = '';
    $insertB = '';
    foreach ($insert_array as $w => $z) {
        $insertA .= "$w, ";
        $insertB .= ":$w, ";
    }
    $insertA = trim($insertA, ', ');
    $insertB = trim($insertB, ', ');
    if ($replace)
        $requete_text = "REPLACE INTO $table($insertA) VALUES($insertB)";
    else
        $requete_text = "INSERT INTO $table($insertA) VALUES($insertB)";

    if ($echo) {
        echo $requete_text;
        pre($insert_array);
    }
    return ExecuteSQL($requete_text, $insert_array, 'id');
}

function DeleteSQL($table, $where = false, $echo = false)
{
    CheckSQL();
    global $prefixSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "DELETE FROM $table";
    if ($where_text) $requete_text .= " WHERE $where_text";

    if ($echo) {
        echo $requete_text;
        pre($where_array);
    }
    return ExecuteSQL($requete_text, $where_array);
}

function UpdateSQL($table, $update, $where = false, $echo = false)
{
    CheckSQL();
    global $prefixSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    list($update_text, $update_array) = WhereSQL($update, ",");
    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "UPDATE $table";
    $requete_text .= " SET $update_text";
    if ($where_text) $requete_text .= " WHERE $where_text";
    $merged_array = array_merge($update_array, $where_array);

    if ($echo) {
        return $merged_array;
    }
    return ExecuteSQL($requete_text, $merged_array);
}

function SelectSQL($table, $where = array(), $order_by = false, $limit = false, $echo = false)
{
    CheckSQL();
    global $prefixSQL;
    $table = substr($table, 0, 1) == '#' ? ltrim($table, '#') : $prefixSQL . '_' . $table;

    $fetch = substr($table, -1) == '+' ? 'all' : 'one';
    $table = rtrim($table, '+');
    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "SELECT * FROM $table";
    if ($where_text) $requete_text .= " WHERE $where_text";
    if ($order_by) $requete_text .= " ORDER BY $order_by";
    if ($limit) $requete_text .= " LIMIT $limit";

//	if ($echo) {verbose($requete_text); verbose($where_array);}
    if ($echo) {
        echo $requete_text;
        pre($where_array);
        exit;
    }
    return ExecuteSQL($requete_text, $where_array, $fetch);
}

function CountSQL($table, $where = array(), $echo = false)
{
    CheckSQL();
    global $prefixSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "SELECT COUNT(*) FROM $table";
    if ($where_text) $requete_text .= " WHERE $where_text";

    if ($echo) {
        echo $requete_text;
        pre($where_array);
        exit;
    }
    return ExecuteSQL($requete_text, $where_array, 'count');
}

function SumSQL($table, $key, $where = array(), $echo = false)
{
    CheckSQL();
    global $prefixSQL;
    $table = (isset($prefixSQL) AND substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');

    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "SELECT SUM($key) FROM $table";
    if ($where_text) $requete_text .= " WHERE $where_text";

    if ($echo) {
        echo $requete_text;
        pre($where_array);
        exit;
    }
    return ExecuteSQL($requete_text, $where_array, 'count');
}

function CustomSQL($param, $requete_text, $echo = false)
{
    CheckSQL();
    if ($param === true) $fetch = "all";
    elseif ($param === false) $fetch = "one";
    else $fetch = $param;

    if ($echo) {
        echo $requete_text;
        exit;
    }
    return ExecuteSQL($requete_text, [], $fetch);
}

function ExecuteSQL($text, $array = [], $flag = false)
{
    global $objectSQL, $errorSQL, $log_error_sql;
    $return = '';
    try {
        $requete = $objectSQL->prepare($text);
        $requete->execute($array);
        if ($flag == 'count') $return = $requete->fetch()[0];
        if ($flag == 'one') $return = $requete->fetch();
        if ($flag == 'all') $return = $requete->fetchAll();
        if ($flag == 'id') $return = $objectSQL->lastInsertId();
        $requete->closeCursor();
    } catch (Exception $e) {
        $errorSQL = $e->getMessage();
        $return = 'ERROR';
        if (isset($log_error_sql)) {
            $fileOrig = debug_backtrace()[0];
            $append = "\n" . date('Y-m-d H:i:s')
                . "\n" . 'Function:' . $fileOrig['function'] . '  ' . $fileOrig['file'] . '  Line:' . $fileOrig['line']
                . "\n" . 'Args: ' . json_encode($fileOrig['args'])
                . "\n" . $errorSQL
                . "\n";
            file_put_contents($log_error_sql, $append, FILE_APPEND);
        }
    }
    return $return;
}

function WhereSQL($where, $separator = "AND")
{
    if (!$where) return ['', []];
    $text = '';
    $array = [];
    if (is_array($where)) {
        foreach ($where as $w => $z) {
            if (substr($w, -2, 1) != '|') $w .= '|=';
            $w_value = explode('|', $w)[0];
            $w_operator = explode('|', $w)[1];
            if ($w_operator == '!') $w_operator = '!=';
            $text .= "$w_value $w_operator :$w_value $separator ";
            $array[$w_value] = $z;
        }
        $text = substr($text, 0, -strlen($separator) - 2);
    } else
        $text = $where;

    return [$text, $array];
}
