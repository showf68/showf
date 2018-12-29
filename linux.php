<?php
function addCron($script, $when = '* * * * *')
{
    return exec("crontab -l | { cat; echo '$when php $script'; } |crontab -");
}

function sox($original, $converted, $delete_original = 0)
{
    $answer = ("sox $original -t raw -r 8000 -e signed-integer -c 1 $converted");
    if ($delete_original)
        unlink($delete_original);
    return $answer;
}
