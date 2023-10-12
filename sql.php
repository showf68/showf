<?php

function PrepareSQL($databaseSQL1, $userSQL1, $passSQL1, $serverSQL1 = 'localhost', $portSQL1 = 3306)
{
    global $databaseSQL, $userSQL, $passSQL, $serverSQL, $portSQL, $objectSQL;
    $databaseSQL = $databaseSQL1;
    $userSQL = $userSQL1;
    $passSQL = $passSQL1;
    $serverSQL = $serverSQL1;
    $portSQL = $portSQL1;
    $objectSQL = null;
}

function ConnectSQL($databaseSQL, $userSQL, $passSQL, $serverSQL = 'localhost', $portSQL = 3306)
{
    global $objectSQL, $errorSQL;
    try {
        $pdo_optionsSQL[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $objectSQL = new PDO("mysql:host=$serverSQL; port=$portSQL; dbname=$databaseSQL; charset=utf8mb4", $userSQL, $passSQL, $pdo_optionsSQL);
        $objectSQL->exec("SET NAMES 'utf8mb4'");
    } catch (Exception $eSQL) {
        $errorSQL = $eSQL->getMessage();
    }
}

function CheckSQL()
{
    global $objectSQL, $databaseSQL, $userSQL, $passSQL, $serverSQL, $portSQL;
    if (!$objectSQL) ConnectSQL($databaseSQL, $userSQL, $passSQL, $serverSQL, $portSQL);
}

function ChangeSQL($dbname, $prefix0 = false)
{
    CheckSQL();
    global $objectSQL, $prefixSQL, $errorSQL;
//    if (!is_object($objectSQL)) return 'ERROR';

    try {
        $objectSQL->exec('USE ' . $dbname);
        if ($prefix0) $prefixSQL = $prefix0;
        if ($prefix0 == '-') $prefixSQL = false;
    } catch (Exception $e) {
        $errorSQL = $e->getMessage();
        return 'ERROR';
    }
}

function EmptySQL($table)
{
    CheckSQL();
    global $objectSQL;
    $table = tikunTable($table);

    $sql = "TRUNCATE TABLE $table";
    $objectSQL->exec($sql);
}

function CreateSQL($table, $fields)
{
    CheckSQL();
    global $objectSQL;
    $table = tikunTable($table);

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

function InsertSQL2($table, $insertArray = [], $echo = false)
{
    CheckSQL();
    $table = tikunTable($table);

    $keysString = implode(', ', array_keys($insertArray));
    $questionsString = implode(', ', array_fill(0, count($insertArray), '?'));
    $values = array_values($insertArray);

    $requeteText = "INSERT INTO $table";
    if ($insertArray) $requeteText .= "($keysString) VALUES($questionsString)";

    if ($echo) {
        echo $requeteText;
        pre($insertArray, 1);
    }
    return ExecuteSQL($requeteText, $values, 'id');
}

function tikunTable($table)
{
    global $prefixSQL;

    if (isset($prefixSQL) and $prefixSQL and substr($table, 0, 1) != '#' and !strpos($table, '.'))
        return $prefixSQL . ($table ? '_' . $table : '');
    else
        return ltrim($table, '#');

}

function InsertSQL($table, $insert_array, $replace = false, $echo = false, $ignore = false)
{
    CheckSQL();
//	global $prefixSQL;
//	$table = (isset($prefixSQL) and substr($table, 0, 1) != '#') ? $table = $prefixSQL . '_' . $table : $table = ltrim($table, '#');
    $table = tikunTable($table);

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
        $requete_text = "INSERT " . ($ignore ? "IGNORE" : "") . " INTO $table($insertA) VALUES($insertB)";

    if ($echo) {
        echo $requete_text;
        pre($insert_array);
    }
    return ExecuteSQL($requete_text, $insert_array, 'id');
}

function InsertSQLSimple($table, $insert_array, $echo = false, $ignore = false)
{
    if (!$insert_array) return;

    CheckSQL();
    $table = tikunTable($table);

    $columns = implode(', ', array_keys($insert_array[0]));
    $requestSQL = "INSERT " . ($ignore ? "IGNORE" : "") . " INTO $table($columns) VALUES ";
    foreach ($insert_array as $values)
        $requestSQL .= "('" . implode("','", $values) . "'),";
    $requestSQL = substr($requestSQL, 0, -1);
    if ($echo) pre($requestSQL, 1);

    return ExecuteSQL($requestSQL, [], 'id');
}

function DeleteSQL($table, $where = false, $echo = false)
{
    CheckSQL();
    $table = tikunTable($table);

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
    $table = tikunTable($table);

    list($update_text, $update_array) = WhereSQL($update, ",");
    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "UPDATE $table";
    $requete_text .= " SET $update_text";
    if ($where_text) $requete_text .= " WHERE $where_text";
    $merged_array = array_merge($update_array, $where_array);

    if ($echo) {
        pre($requete_text);
        return $merged_array;
    }
    return ExecuteSQL($requete_text, $merged_array);
}

function SelectSQL($table, $where = array(), $order_by = false, $limit = false, $echo = false)
{
    CheckSQL();
    //   if($table == 'inbox+') verbose($where);
    $table = tikunTable($table);

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
    $table = tikunTable($table);

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

function ColumnsSQL($database, $table)
{
    $sql = SelectSQL('#INFORMATION_SCHEMA.COLUMNS+', array('TABLE_SCHEMA' => $database, 'TABLE_NAME' => $table));
    return array_column($sql, 'COLUMN_NAME');
}

function SumSQL($table, $key, $where = array(), $echo = false)
{
    CheckSQL();
    $table = tikunTable($table);

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

    if ($echo)
        return $requete_text;

    return ExecuteSQL($requete_text, [], $fetch);
}

function CustopSQL($param, $requete_text, $where_array = [])
{
    CheckSQL();
    if ($param === true) $fetch = "all";
    elseif ($param === false) $fetch = "one";
    else $fetch = $param;

    if (is_string($where_array)) $where_array = [$where_array];
    return ExecuteSQL($requete_text, $where_array, $fetch);
}

function ExistsSQL($table, $where = array(), $echo = false)
{
    CheckSQL();
    $table = tikunTable($table);

    list($where_text, $where_array) = WhereSQL($where);

    $requete_text = "SELECT 1 AS verif FROM $table";
    if ($where_text) $requete_text .= " WHERE $where_text";
    $requete_text .= " LIMIT 1";

    if ($echo) {
        echo $requete_text;
        pre($where_array);
        exit;
    }
    return ExecuteSQL($requete_text, $where_array, 'verif');
}

function ExecuteSQL($text, $array = [], $flag = false)
{
    global $objectSQL, $errorSQL, $log_error_sql;

    if (!is_object($objectSQL)) return 'ERROR';

    $return = $errorSQL = false;

    try {
        $requete = $objectSQL->prepare($text);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $append = "$text<br>key: $key is an array" . pre($array, 'MAIL');
                Mailgun('yossef68e@gmail.com', 'new bug SQL', 'contact@showf.tk', '', 'ExecuteSQL error ' . gethostname(), $append, 2);
            }
        }

        $requete->execute($array);
        if ($flag == 'verif') $return = $requete->fetch()['verif'];
        if ($flag == 'count') $return = $requete->fetch()[0];
        if ($flag == 'one') $return = $requete->fetch(PDO::FETCH_ASSOC);
        if ($flag == 'all') $return = $requete->fetchAll(PDO::FETCH_ASSOC);
        if ($flag == 'id') $return = $objectSQL->lastInsertId();
        $requete->closeCursor();
    } catch (Exception $e) {
        $errorSQL = $e->getMessage();
        $return = 'ERROR';
        $backtrace = debug_backtrace();
        $append = $errorSQL . "\n<br>" . pre($backtrace, 'MAIL') . "\n<br>";

        if ($backtrace[1]['file'] != '/mnt/remoteAsterisk/API/track.php')
            Mailgun('yossef68e@gmail.com', 'error SQL', 'contact@showf.tk', '', 'error sql', $append, 2);

    }
    return $return;
}

function WhereSQL0($where, $separator = "AND")
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
            $text .= "$w_value $w_operator ? $separator ";
            //           $array[$w_value] = $z;
        }
        $text = substr($text, 0, -strlen($separator) - 2);
        $array = array_values($where);
    } else
        $text = $where;

    return [$text, $array];
}

function MergeRequestsSQL($toAddArray, $toRemoveArray = false, $debug = false)
{
    global $errorSQL;

    if ($toAddArray and !is_array($toAddArray)) $toAddArray = [$toAddArray];
    if ($toRemoveArray and !is_array($toRemoveArray)) $toRemoveArray = [$toRemoveArray];

    $mergedArray = array();

    foreach ($toAddArray as $request) {
        if (!$request) continue;
        if ($debug) echo "TO ADD: $request \n";
        if (!$answer0 = CustomSQL('all', $request)) continue;
        if ($errorSQL) exit($errorSQL . "\n");
        if (!array_key_exists('value', $answer0[0])) exit('NO COLUMNAME' . BRn);

        $answer = array_column($answer0, 'value');
        $mergedArray = array_merge($mergedArray, $answer);
        $mergedArray = array_values(array_filter(array_unique($mergedArray)));

        if ($debug) echo "Numbers in list: " . count($answer) . ". Numbers in total list: " . count($mergedArray) . "\n";
    }

    if ($toRemoveArray) {
        foreach ($toRemoveArray as $request) {
            if (!$request) continue;
            if ($debug) echo "TO REMOVE: $request \n";
            if (!$answer0 = CustomSQL('all', $request)) continue;
            if ($errorSQL) exit($errorSQL . "\n");
            if (!array_key_exists('value', $answer0[0])) exit('NO COLUMNAME' . BRn);

            $answer = array_column($answer0, 'value');
            $mergedArray = array_diff($mergedArray, $answer);
            $mergedArray = array_values(array_filter(array_unique($mergedArray)));

            if ($debug) echo "Numbers in list: " . count($answer) . ". Numbers in total list: " . count($mergedArray) . "\n";
        }
    }
    sort($mergedArray);
    return $mergedArray;
}
