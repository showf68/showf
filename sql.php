<?php
function PrepareSQL($tabley, $usery, $passy, $servery = 'localhost', $porty = 3306) {
	global $table, $user, $pass, $server, $port;
	$table = $tabley;
	$user = $usery;
	$pass = $passy;
	$server = $servery;
	$port = $porty;
}
 //a
function ConnectSQL($table, $user, $pass, $server = 'localhost', $port = 3306) {
	global $bdd, $sql_error;	
	
	try {
		$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$bdd = new PDO("mysql:host=$server; port=$port; dbname=$table; charset=utf8mb4", $user, $pass, $pdo_options);
		$bdd -> exec("SET NAMES 'utf8mb4'");
	}
	catch(Exception $e) {
		$sql_error = $e -> getMessage();
	}
}

function CheckSQL() {
	global $bdd, $table, $user, $pass, $server, $port;
	if(!$bdd)	ConnectSQL($table, $user, $pass, $server, $port);
}

function VerifyTableSQL($table) {
	global $bdd;
	global $prefix; $table = $prefix.'_'.$table;
	global $sql_details;
	CheckSQL();
	
	$requete_text = "SHOW TABLES FROM ".$sql_details['table']." LIKE '$table'";
	
	$requete = $bdd -> prepare($requete_text);
	$requete -> execute(array());
	$reponse = $requete -> fetch();
	$requete -> closeCursor();
	if ($reponse)
		return true;
	else
		return false;
}

function EmptySQL($table) {
	global $bdd, $prefix, $SQLerror;
	CheckSQL();
	
	if(substr($table, 0, 1) == '#')		$table = trim($table, '#');
	else								$table = $prefix.'_'.$table;

	$sql = "TRUNCATE TABLE $table";
	$bdd -> exec($sql);
}
	
function CreateSQL($table, $fields) {
	global $bdd;
	global $prefix; $table = $prefix.'_'.$table;
	CheckSQL();

    $sql = "CREATE TABLE $table(";
	foreach ($fields as $field) {
		$name = isset($field[0]) ? $field[0] : false;
		$type = isset($field[1]) ? $field[1] : false;
		$opt1 = isset($field[2]) ? $field[2] : false;
		$opt2 = isset($field[3]) ? $field[3] : false;
		
		switch ($type) {
			case 'int':
				$sql .= "$name INT(10)";
				if ($opt1)	$sql .= " AUTO_INCREMENT";
				if ($opt2)	$sql .= " PRIMARY KEY";
				break;
			case 'varchar':
				$sql .= "$name VARCHAR(255) NULL";
				break;
			case 'text':
				$sql .= "$name TEXT NULL";
				break;
			case 'timestamp':
				$sql .= "$name TIMESTAMP";
				if ($opt1)	$sql .= "  DEFAULT CURRENT_TIMESTAMP";
				break;		
		}
				$sql .= ", ";
	}
	$sql = trim($sql, ', ') . ")";

	$bdd -> exec($sql);
}

function InsertSQL($table, $insert_array, $replace = false, $echo = false) {
	global $bdd, $prefix, $SQLerror;
	CheckSQL();
	
	if(substr($table, 0, 1) == '#')		$table = trim($table, '#');
	else								$table = $prefix.'_'.$table;

    if(is_string($insert_array))    $insert_array = compact(explode(' ', $insert_array));
	$insertA = '';
	$insertB = '';
	foreach ($insert_array as $w => $z)	{
		$insertA .= "$w, ";
		$insertB .= ":$w, ";
	}
	$insertA = trim($insertA, ', ');
	$insertB = trim($insertB, ', ');
	if ($replace)
    	$requete_text = "REPLACE INTO $table($insertA) VALUES($insertB)";
    else
    	$requete_text = "INSERT INTO $table($insertA) VALUES($insertB)";
	if($echo) {
		echo "$requete_text<br>";pre($insert_array);
	}
	// $requete = $bdd -> prepare($requete_text);
	// $requete -> execute($insert_array);
	// $id = $bdd -> lastInsertId();
	// $requete -> closeCursor();
	// return $id; 
	try {
		$requete = $bdd -> prepare($requete_text);
		$requete -> execute($insert_array);
		$id = $bdd -> lastInsertId();
		$requete -> closeCursor();
		return $id; 
	} catch (Exception $e) {
		$SQLerror = $e -> getMessage();
		return 'ERROR';
	}	
}			

function DeleteSQL($table, $where_array) {
	global $bdd, $prefix;
	CheckSQL();
	
	if(substr($table, 0, 1) == '#')		$table = trim($table, '#');
	else								$table = $prefix.'_'.$table;
	
	$where = '';
	foreach ($where_array as $w => $z)
		$where .= "$w= :$w, ";
	$where = trim($where, ', ');

	$requete_text = "DELETE FROM $table WHERE $where";
	$requete = $bdd -> prepare($requete_text);
	$requete -> execute($where_array);
	$requete -> closeCursor();
}			

function UpdateSQL($table, $update_array, $where_array, $echo = false) {
	global $bdd, $prefix;
	CheckSQL();
	
	if(substr($table, 0, 1) == '#')		$table = trim($table, '#');
	else								$table = $prefix.'_'.$table;

	$update = '';
	foreach ($update_array as $w => $z)
		$update .= "$w= :$w, ";
	$update = trim($update, ', ');
	
	$where = '';
	foreach ($where_array as $w => $z)
		$where .= "$w= :$w, ";
	$where = trim($where, ', ');

	
	$requete_text = "UPDATE $table SET $update WHERE $where";
	$merged_array = array_merge($update_array, $where_array);
	if($echo) {
		echo $requete_text;
		pre($merged_array);
	}
	$requete = $bdd -> prepare($requete_text);
	$requete -> execute($merged_array);
	$requete -> closeCursor();
}			
 
function SelectSQL($table, $where_array = array(), $order_by = false, $limit = false, $echo = false, $all = false) {
	global $bdd, $prefix;
	CheckSQL();
	
	if(substr($table, 0, 1) == '#')		$table = trim($table, '#');
	else								$table = $prefix.'_'.$table;

	if(substr($table, -1) == '+') {
		$table = trim($table, '+');
		$all = true;
	}
	$requete_text = "SELECT * FROM $table";
 
	$where = '';				$new_where = array();
	if($where_array) {
		if(is_array($where_array)) {
			foreach ($where_array as $w => $z) {
				if(substr($w, -2, 1) != '|')		$w .= '|=';
				$w_value 	= explode('|', $w)[0];	$w_operator = explode('|', $w)[1];
				if($w_operator == '!') $w_operator = '!=';
				$where .= "$w_value $w_operator :$w_value AND ";
				$new_where[$w_value] = $z;
			}
		} else {
			$where = $where_array;
		}
		$where = trim($where);
		if(substr($where, -3) == 'AND')
			$where = substr($where, 0, -3);
//		$where = trim($where, ' AND ');
		$requete_text = "$requete_text WHERE $where";
	}
	if($order_by)
		$requete_text = "$requete_text ORDER BY $order_by";
	if($limit)
		$requete_text = "$requete_text LIMIT $limit";

	if ($echo) {echo $requete_text; pre($new_where);exit;}
	
	if(is_array($where_array)) {
        $requete = $bdd -> prepare($requete_text);
		$requete -> execute($new_where);
	} else {
		$requete = $bdd -> query($requete_text);
	}
	if($all)
		$reponse = $requete -> fetchAll();
	else
		$reponse = $requete -> fetch();
	$requete -> closeCursor();
	return $reponse;
}

function SelectAllSQL($table, $where_array = array(), $order_by = false, $limit = false, $echo = false) {
	return SelectSQL($table, $where_array, $order_by, $limit, $echo, true);
}

function ExtractSQL($all, $table, $select_string = '*', $where_array = array(), $order_by = false, $limit = false, $echo = false) {
	global $bdd, $prefix;
	CheckSQL();
	
	if(substr($table, 0, 1) == '#')		$table = trim($table, '#');
	else								$table = $prefix.'_'.$table;

	
	$requete_text = "SELECT $select_string FROM $table";

	if ($where_array) {
		if (is_array($where_array)) {
			$where = '';
			foreach ($where_array as $w => $z)
				$where .= "$w=:$w AND ";
		} else {
			$where = $where_array;
		}
		$where = trim($where, ' AND ');
		$requete_text = "$requete_text WHERE $where";
	}
	if ($order_by)
		$requete_text = "$requete_text ORDER BY $order_by";
	if ($limit)
		$requete_text = "$requete_text LIMIT $limit";

	if ($echo) {echo $requete_text; pre($where_array);exit;}
	
	try{
		if(is_array($where_array)) {
			$requete = $bdd -> prepare($requete_text);
			$requete -> execute($where_array);
		} else {
			$requete = $bdd -> query($requete_text);
		}
		if($all)
			$reponse = $requete -> fetchAll();
		else
			$reponse = $requete -> fetch();
		$requete -> closeCursor();
		return $reponse;
	} catch (Exception $e) {
		return '';
	}	
}

function SingleSQL($table, $where_array = array(), $order_by = false, $limit = false, $all = false) {
	return SelectSQL($table, $where_array, $order_by, $limit, $all);
}

function CustomSQL($all, $requete_text) {
	global $bdd;
	CheckSQL();

    $requete = $bdd -> query($requete_text);
	if ($all)
		$reponse = $requete -> fetchAll();
	else
		$reponse = $requete -> fetch();
	$requete -> closeCursor();
	return $reponse;
}
