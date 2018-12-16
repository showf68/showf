<?php

namespace showf68\showf;

class AGIshowf extends \AGI  {
    public function sayYemot($say)
    {
        foreach (explode('.', $say) AS $s) {
            $mode = substr($s, 0, 1);
            $data = substr($s, 2);
            switch ($mode) {
                case 't': $this -> stream_file($this -> bingTTS($data)); break;
                case 'f': $this -> stream_file($data); break;
                case 'd': $this -> say_digits($data); break;
                case 'n': $this -> say_number($data); break;
            }
        }
    }

    private function bingTTS($text)
    {
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

        //$format = 'raw-8khz-8bit-mono-mulaw';
        $format = 'riff-24khz-16bit-mono-pcm';
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

        $directory = '/tmp/';
        $filename = $directory . uniqid('bing_');
        file_put_contents("$filename.wav", $result);
        exec("/usr/bin/sox $filename.wav -t raw -r 8000 -e signed-integer -c 1 $filename.sln");
        unlink("$filename.wav");
        return $filename;
    }
}
