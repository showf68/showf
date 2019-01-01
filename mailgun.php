<?php

function Mailgun($to, $from_name, $from_mail, $reply, $subject, $text, $is_html = false, $fileContent = false, $filename = false){
$mgClient = new \Mailgun\Mailgun(API_KEY);

    $html = $text ?: ' ';
    if ($is_html != 2)
        $html = "<body style='text-align:right; direction:rtl;'>$html</body>";

    if ($from_mail AND $from_name) $from = "$from_name <$from_mail>";
    else                            $from = $from_mail ?: $from_name;
    $params = compact('from', 'to', 'subject', 'html');

    if ($fileContent) {
        $attachment = array('attachment' => array(compact('fileContent', 'filename')));
        $result = $mgClient->sendMessage(DOMAIN, $params, $attachment);
    } else {
        $result = $mgClient->sendMessage(DOMAIN, $params);
    }
    if ($result->http_response_body->message == 'Queued. Thank you.')
        return true;
}
