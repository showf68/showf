<?php
function addCron($script, $when = '* * * * *')
{
    return exec("crontab -l | { cat; echo '$when php $script'; } |crontab -");
}

function sox($original, $converted, $delete_original = 0, $simple = false)
{
    $sox = "sox $original -t raw -r 8000 -e signed-integer -c 1 $converted";
    if($simple)
	    $sox = "sox $original -r 8000 $converted";
	$answer = exec($sox);
    if ($delete_original)        unlink($original);
    return $answer;
}
//lame --decode file.mp3 - | sox -v 0.5 -t wav - -t wav -b 16 -r 8000 -c 1 file.wav

//sox beep2.wav -r 8000 -c 1 file2.wav -q


