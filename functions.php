<?php

function seconds2hours($init) {
    $hours = floor($init / 3600);
    $minutes = floor(($init / 60) % 60);
    $seconds = $init % 60;
    $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
    $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
    $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);

    $duration = $hours == '00' ? "$minutes:$seconds" : "$hours:$minutes:$seconds";
    return $duration;
}

function cutFrom($from, $string, $end = false) {
    $pos = strpos($from, $string);
    if($end)
        $return = $pos ? substr($from, $pos + strlen($string)) : $from;
    else
        $return = $pos ? substr($from, 0, $pos) : $from;
    return $return;
}

function redirect($url = './') {
    if ($url == 'reffer')
        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url();
    header('location: '.$url);
    exit;
}

function pre($aray, $exit = false) {
    echo '<pre>';
    print_r($aray);
    echo '</pre>';
    if($exit)	exit;
}


function GetPost(...$vars) {  //interdit d'avoir un post et get du meme nom !!
    $return = array();
    foreach($vars AS $var) {
        $return[$var] = '';
        if(isset($_GET[$var]))	$return[$var] = $_GET[$var];
        if(isset($_POST[$var]))	$return[$var] = $_POST[$var];
    }
    return $return;
}

function GetP(...$vars) {  //interdit d'avoir un post et get du meme nom !!
    $return = array();
    foreach($vars AS $var) {
        $return[$var] = '';
        if(isset($_GET[$var]))	$return[$var] = $_GET[$var];
        if(isset($_POST[$var]))	$return[$var] = $_POST[$var];
    }
    if(count($return) == 1)
        return $return[$vars[0]];
    else
        return $return;
}

function preg_extract($string, $pattern) {
    $pattern = str_replace('~', '(.*)', $pattern);
    if(preg_match("#$pattern#isU", $string, $match))
        return $match[1];
}

function Ycurl($url, $post = false, $cookie = false, $proxy = false, $useragent = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
    if($useragent)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $useragent);
    if($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if($cookie) {
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    }
    if($proxy)
        curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8888');
    $return = curl_exec($ch);
    return $return;
}

function clean_say($string, $mode = 't', $adress = false) {
    $max = 600;

    if ($adress) {
        preg_match('#([0-9]*)@[email36\.com|gmail26\.com|frum\.cf|mivtsar\.com]#isU', $string, $a);
        if($a AND $numero = $a[1])
            return "d-$numero";
    }

    Yremplace($string, array("'", '\#', '\+', '@', '\-', '<', '>', '/', '&'), 	' ');
    Yremplace($string, array("\n", "\r", "\."), '.t-');
    Yremplace($string, " *t\- *\.", '');
    Yremplace($string, " *\.t\- *", '.t-');
    Yremplace($string, "₪", 'שח');
    Yremplace($string, "[^a-zA-Zא-ת0-9 ,:\'\?!\.\-]", '');
    $string = trim($string);


    if($mode == 't') {
        $return = '';
        $arr = explode('t-', $string);

        foreach($arr AS $ar) {
            $ar = trim($ar, '.');
            $ar = trim($ar);

            if(strlen($ar) > $max) {
                $sol = '';
                $spac = explode(' ', $ar);
                foreach ($spac AS $spa) {
                    if(strlen(substr($sol, strrpos($sol, 't-'))) > $max)
                        $sol = trim($sol) . '.t-';
                    $sol .= $spa." ";
                }
                $ar = trim($sol);
            }
            if($ar) $return .= "t-$ar.";
        }
        $return = substr($return, 0, 3000);

        return trim($return, '.');
    } else
        return "$mode-$string";
}

function Yremplace(&$string, $pattern, $subject) {
    $patt = '';
    if(is_array($pattern)) {
        foreach($pattern AS $p)
            $patt .= "$p|";
        $patt = trim($patt, '|');
    } else
        $patt = $pattern;

    $string = preg_replace("#$patt#isU", $subject, $string);
}

