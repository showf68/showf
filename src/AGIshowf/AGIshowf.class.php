<?php

namespace AGIshowf;


class AGIshowf extends AGI
{
    public function sayYemot($say)
    {
        foreach (explode('.', $say) AS $s) {
            $mode = substr($s, 0, 1);
            $data = substr($s, 2);
            switch ($mode) {
                case 't': $this -> stream_file(bingTTS($data)); break;
                case 'f': $this -> stream_file($data); break;
                case 'd': $this -> say_digits($data); break;
                case 'n': $this -> say_number($data); break;
            }
        }
    }
}
