<?php

/*
 * by @showf
 * https://github.com/showf68/showf/blob/master/src/AGIshowf/AGIshowf.class.php
 */

const RECORD = 'RECORD';
const SAY = 'SAY';
const YESNO = 'YESNO';

class AGIshowf extends AGI  {

    private $bingTokenFile = '/tmp/bingToken.txt';
    private $soundsFolder = 'user/';
    private $sayModes = array('t' => 'STREAM FILE', 'f' => 'STREAM FILE', 'd' => 'SAY DIGITS', 'n' => 'SAY NUMBER');

    public function getCID() {
        $phone = preg_replace("#[^0-9]#", "", $this -> request['agi_callerid']);
        return $phone;
    }

    public function soundsSetFolder($folder) {
        $this -> soundsFolder = "user/$folder/";
    }

    public function say($say, $readMode = SAY, $default_choice = false)
    {
        $dtmf = '';
        switch($readMode) {
            case SAY:         $dtmf_allowed = '';             break;
            case YESNO:       $dtmf_allowed = '12';           break;
            default :         $dtmf_allowed = AST_DIGIT_ANY;  break;
        }
        foreach (explode('.', $say) AS $s) {
            $sayMode = substr($s, 0, 1);
            $data = substr($s, 2);
            if(!$sayMode OR !$data OR !array_key_exists($sayMode, $this -> sayModes))   continue;
            switch($sayMode) {
                case 't':
                    $data = $this -> bingTTS($data);
                    break;
                case 'f':
                    $data = (substr($data, 0, 1) == '/') ? substr($data, 1) : $this -> soundsFolder . $data;
                    break;
            }
            $digit = $this -> evaluate($this -> sayModes[$sayMode] ." $data '$dtmf_allowed'")['result'];
            if (!$digit OR $readMode == SAY) continue;
            $dtmf = chr($digit);
            goto getDTMF;
        }
        if ($default_choice) return $default_choice;
        if ($readMode == SAY) return;
        if ($readMode == RECORD) {
            $STTfilename = 'STT/'.uniqid(rand());
            $digit = $this -> record_file($STTfilename, 'wav', AST_DIGIT_ANY, 15000, null, true, 4)['result'];
            $dtmf = chr($digit);
            if($dtmf != '#')   goto getDTMF;
            $text = $this -> googleSTT("/usr/share/asterisk/sounds/$STTfilename.wav");
            unlink("/usr/share/asterisk/sounds/$STTfilename.wav");
            return $text;
        }

        getDTMF:
        do {
            if($dtmf AND $readMode == YESNO OR substr($dtmf, -1) == '#')    break;
            $digit = $this -> wait_for_digit()['result'];
            $dtmf .= chr($digit);
        }
        while (true);
        $dtmf = rtrim($dtmf, '#');
        if($readMode == RECORD)     $dtmf = '^'.$dtmf;
        return $dtmf;
    }

    private function bingGetToken()  {
        global $bingApiKey;

        $AccessTokenUri = "https://westus.api.cognitive.microsoft.com/sts/v1.0/issueToken";
        $options = array(
            'http' => array(
                'header' => "Ocp-Apim-Subscription-Key: " . $bingApiKey . "\r\n" .
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
        $text = $doc->createTextNode($text);

        $voice->appendChild($text);
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
        $directory = '/usr/share/asterisk/sounds/TTS/';
        $filename = $directory . 'bing_' . hash('md5', $text);
        if (file_exists($filename . '.ulaw')) return $filename;

        if (file_exists($this->bingTokenFile) AND (time() - filectime($this->bingTokenFile) < 600))
            $access_token = file_get_contents($this->bingTokenFile);
        else
            $access_token = $this->bingGetToken();

        $result = $this->bingGetAnswer($text, $access_token);
        $size = strlen($result);
        if ($size < 80) {
            $this -> verbose("TTS ERROR: $text");
            return 'beep';
        }
        file_put_contents("$filename.ulaw", $result);
        return $filename;
    }

    /**
     * @param string $filename
     * @param string $language
     * @return string|null
     */
    private function googleSTT($filename, $language = 'he')
    {
        $return = null;
        $speech = new \Google\Cloud\Speech\SpeechClient(['languageCode' => $language, 'keyFilePath' => '/usr/share/php/STTkey.json']);
        $results = $speech->recognize(fopen($filename, 'r'), ['encoding' => 'LINEAR16'], ['sampleRate' => '8000']);
        if(isset($results[0])) $return = $results[0]->topAlternative()['transcript'];
        return $return;
    }
}


