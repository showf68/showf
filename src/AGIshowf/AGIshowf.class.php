<?php

/*
 * by @showf
 * https://github.com/showf68/showf/blob/master/src/AGIshowf/AGIshowf.class.php
 */

namespace AGIshowf;

class AGIshowf extends \AGI  {

    private $bingTokenFile = '/tmp/bingToken.txt';
    private $soundsFolder;

    public function soundsSetFolder($folder) {
        $this -> soundsFolder = $folder;
    }

    public function say($say, $max_digits = 1)
    {
        $dtmf_allowed = '0123456789*#';
        foreach (explode('.', $say) AS $s) {
            $mode = substr($s, 0, 1);
            $data = substr($s, 2);
            switch ($mode) {
                case 't':
                    $digit = $this -> stream_file($this -> bingTTS($data), $dtmf_allowed)['result'];
                    break;
                case 'f':
                    // if($this -> soundsFolder)
                    $digit = $this -> stream_file('user/'. $this -> soundsFolder .'/'. $data, $dtmf_allowed)['result'];
                    // else
                    //    $digit = $this -> stream_file('user/'. $data, $dtmf_allowed)['result'];
                    break;
                case 'd':
                    $digit = $this -> say_digits($data)['result'];
                    break;
                case 'n':
                    $digit = $this -> say_number($data)['result'];
                    break;
            }
            if($max_digits AND $digit) {
                $dtmf = chr($digit);
                if($dtmf == '#') return '';
                if($max_digits == 1)  return $dtmf;
                for($i = 1; $i < $max_digits; $i++){
                    $digit = $this -> wait_for_digit()['result'];
                    if(chr($digit) == '#') return $dtmf;
                    $dtmf .= chr($digit);
                }
                return $dtmf;
            }
        }
    }

    private function bingGetToken()  {
        global $apiKey;

        $AccessTokenUri = "https://westus.api.cognitive.microsoft.com/sts/v1.0/issueToken";
        $options = array(
            'http' => array(
                'header' => "Ocp-Apim-Subscription-Key: " . $apiKey . "\r\n" .
                    "content-length: 0\r\n",
                'method' => 'POST',
            ),
        );
        $context = stream_context_create($options);
        $access_token = file_get_contents($AccessTokenUri, false, $context);
        file_put_contents($this -> bingTokenFile, $access_token);
        return $access_token;
    }

    private function bingGetAnswer($text, $access_token)
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement("speak");
        $root->setAttribute("version", "1.0");
        $root->setAttribute("xml:lang", "he-IL");
        $voice = $doc->createElement("voice");
        $voice->setAttribute("xml:lang", "he-IL");
        $voice->setAttribute("xml:gender", "Male");
        $voice->setAttribute("name", "Microsoft Server Speech Text to Speech Voice (he-IL, Asaf)");
        $textNode = $doc->createTextNode($text);

        $voice->appendChild($textNode);
        $root->appendChild($voice);
        $doc->appendChild($root);
        $data = $doc->saveXML();

        $format = 'raw-8khz-8bit-mono-mulaw';
        $options = array(
            'http' => array(
                'header' => "Content-type: application/ssml+xml\r\n" .
                    "X-Microsoft-OutputFormat: $format\r\n" .
                    "Authorization: " . "Bearer " . $access_token . "\r\n" .
                    "User-Agent: TTSPHP\r\n" .
                    "content-length: " . strlen($data) . "\r\n",
                'method' => 'POST',
                'content' => $data,
            ),
        );
        $ttsServiceUri = "https://westus.tts.speech.microsoft.com/cognitiveservices/v1";
        $context = stream_context_create($options);
        $result = file_get_contents($ttsServiceUri, false, $context);
        return $result;
    }

    private function bingTTS($text)
    {
        $directory = '/usr/share/asterisk/sounds/tts/';
        $filename = $directory.'bing_'.hash('md5', $text);
        if(file_exists($filename.'.ulaw'))   return $filename;

        if(file_exists($this -> bingTokenFile) AND (time() - filectime($this -> bingTokenFile) < 600))
            $access_token = file_get_contents($this -> bingTokenFile);
        else
            $access_token = $this -> bingGetToken();

        $result = $this -> bingGetAnswer($text, $access_token);
        $size = strlen($result);
        if($size < 80)            return 'beep';
        file_put_contents("$filename.ulaw", $result);

        return $filename;
    }
}
