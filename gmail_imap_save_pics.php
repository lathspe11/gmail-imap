#!/usr/bin/php5
<?php
include 'include_mail.php';

function getFileExtension($fileName){
   $parts=explode(".",$fileName);
   return $parts[count($parts)-1];
}

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); 

//echo "parsing ini file...\n"; 
//$ini = parse_ini_file('g.ini'); 
//$user = $ini['user']; 
//$pass = $ini['pass']; 
$year = '2000'; 
echo "account {$ugmail} - killing msgs from before {$year}\n\n"; 

$imap = imap_open($servGmail, $ugmail, $pwgmail) 
				or die("imap connection error". imap_last_error() . "\n"); 

echo "checking current mailbox...\n"; 
$mbox = imap_check($imap) or die("imap_check connection error". imap_last_error() . "\n"); 

print "Mailbox date ".$mbox->Date . "\n";
print "Recent Message Count ".$mbox->Recent . "\n";
print "Total Message Count ".$mbox->Nmsgs . "\n";

$message_count = $mbox->Nmsgs ; //($mbox->Recent > 0)?$mbox->Recent:2;
//die($message_count . "count of messages accessed ");
$allheaders = imap_headers($imap, $m);
for ($m = $mbox->Nmsgs,$k = 0; $k < $message_count; ++$k, --$m){
	
    $header = imap_headerinfo($imap, $m);
    //print_r($header);

    $email[$m]['from'] = $header->from[0]->mailbox.'@'.$header->from[0]->host;
    $email[$m]['fromaddress'] = $header->from[0]->personal;
    $email[$m]['to'] = $header->to[0]->mailbox;
    $email[$m]['subject'] = $header->subject;
    $email[$m]['message_id'] = $header->message_id;
    $email[$m]['date'] = $header->udate;

    $from = $email[$m]['fromaddress'];
    $from_email = $email[$m]['from'];
    $to = $email[$m]['to'];
    $subject = $email[$m]['subject'];

    $structure = imap_fetchstructure($imap, $m);

    $attachments = array();
    if(isset($structure->parts) && count($structure->parts)) {

        for($i = 0; $i < count($structure->parts); $i++) {

            $attachments[$i] = array(
                'is_attachment' => false,
                'filename' => '',
                'name' => '',
                'attachment' => ''
            );
//print "parts ";
//var_dump($structure->parts);

            if($structure->parts[$i]->ifdparameters <> 0) {
                foreach($structure->parts[$i]->dparameters as $object) {
                    if(strtolower($object->attribute) == 'filename') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['filename'] = $object->value;
                    }
                }
            }

            if($structure->parts[$i]->ifparameters <> 0) {
                foreach($structure->parts[$i]->parameters as $object) {
                    if(strtolower($object->attribute) == 'name') {
                        $attachments[$i]['is_attachment'] = true;
                        $attachments[$i]['name'] = $object->value;
                    }
                }
            }

            if($attachments[$i]['is_attachment']) {
                $attachments[$i]['attachment'] = imap_fetchbody($imap, $m, $i+1);
                if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                    $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                }
                elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                    $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                }
            } /* */
        }
    }



    foreach ($attachments as $key => $attachment) {
		if ($attachment['is_attachment']){
		    $name = $attachment['name'];
		    $contents = $attachment['attachment'];
		    //file_put_contents($name, $contents);
			echo "Message :".$m."\n";
			echo $from_email . "\n";
			echo $to . "\n";
			echo "\t".$subject . "\n";
			print "has attachment " . $name ."\n";

			switch (mb_strtolower(substr($name,-4))) {
				case '.jpg':
				case 'jpeg':
				case '.png':
				case '.gif':
					print "is an image \n";
					if (file_put_contents($destPhotos.$name., $contents) == false) {
						die("File_put_contents error");
					}
				  break;
			}
		}
    }

/* */
    //imap_setflag_full($imap, $i, "\\Seen");
    //imap_mail_move($imap, $i, 'Trash');
}

imap_close($imap);

?>
