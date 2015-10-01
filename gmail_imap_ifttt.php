#!/usr/bin/php5
<?php
include 'include_mail.php';

function getFileExtension($fileName){
   $parts=explode(".",$fileName);
   return $parts[count($parts)-1];
}

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); 

$debug == false; 

//echo "parsing ini file...\n"; 
//$ini = parse_ini_file('g.ini'); 
//$user = $ini['user']; 
//$pass = $ini['pass']; 
$year = '2012'; 
echo "account {$ugmail} - killing msgs from before {$year}\n\n"; 
//Get the timestamp to compare with
//$dstamp = strtotime("12/31/2011");
$dstamp = strtotime("4 days ago");
//print $dstamp;

$cleanFrom = array("action@ifttt.com"); //High volume email for my address
/* 
"notify@twitter.com",
"groups-noreply@linkedin.com",
"messages-noreply@linkedin.com",
"clash@clashdaily.com",
"info@patriotoutdoornews.com",
"mail@patriotdepot.com",
"support@investorsobserver.com",
"updates@girlsjustwannahaveguns.com",
"rakuten@newsletter.rakuten.com",
"reply@dailycaller-alerts.com",
"specials@galleryofguns.com",
"sigsauer@sigsauer.com",
"fliers@natchezss.com",
"cisco@emessenger.cisco.com",
"deals@gearhog.chompon.com",
"no-reply@woot.com",
"dailydigest@email.pjmedia.com",
"donotreply@alloutdoor.com",
"proflowers@email.proflowers.com",
"info@all-battery.com",
"brownells@email.brownells.com",
"rewards@rewards.shopyourwayrewards.com",
"books@sitepoint.com",
"homedepotcustomercare@email.homedepot.com",
"email@email.cabelas.com",
"mailing@suarezinternational.com");
/* */ 

$imap = imap_open($servGmail, $ugmail, $pwgmail) 
				or die("imap connection error". imap_last_error() . "\n"); 
//$mailboxes = imap_list($imap, $servGmailAllBox, '*');	//Get a list of all mailboxes
//print_r($mailboxes); die ("mailboxes list");  		//List all mailboxes

echo "checking current mailbox...\n"; 
$mbox = imap_check($imap) or die("imap_check connection error". imap_last_error() . "\n"); 

print "Mailbox date ".$mbox->Date . "\n";
print "Recent Message Count ".$mbox->Recent . "\n";
print "Total Message Count ".$mbox->Nmsgs . "\n";

//$message_count = ($mbox->Recent > 0)?$mbox->Recent:2; 
$message_count = $mbox->Nmsgs; 

//die($message_count . "count of messages accessed ");

$flaggedForDelete = 0;

for ($m = $mbox->Nmsgs,$k = 0; $k < $message_count; ++$k, --$m){
	
    $header = imap_headerinfo($imap, $m);
    //print_r($header);

	//Gather all header details in the email array for each msg
    $email[$m]['from']        = $header->from[0]->mailbox.'@'.$header->from[0]->host;
	$email[$m]['uid']         = imap_uid($imap,$header->Msgno); //Msgno may change, uid is fixed 
 //    $email[$m]['fromaddress'] = $header->from[0]->personal;
//    $email[$m]['to']          = $header->to[0]->mailbox;
//    $email[$m]['subject']     = $header->subject;
//    $email[$m]['message_id']  = $header->message_id;
//    $email[$m]['date']        = $header->udate;
//    $email[$m]['flagged']     = trim($header->Flagged);

//    $from_email = $email[$m]['from'];
//    $to         = $email[$m]['to'];
//    $subject    = $email[$m]['subject'];
	if ((trim($header->Flagged) !== 'F') && ($header->udate < $dstamp)) { //If msg is not Flagged and msg older than dstamp
		foreach ($cleanFrom as $mopup){ 									//For eash sender
		  	if ($email[$m]['from'] == $mopup) {								//If msg sender is to be cleaned 
				//Prints slow this process down, Only print on debug
				if ($debug == true) {
					print " Deleting message \n";
					print "\n "     . $header->subject . "\n";
					print "udate: " . $header->udate . "\n";	
					print " date: " . $header->date . "\n";
					print " from: " . $header->from[0]->personal . "\n";
					print "   to: " . $header->to[0]->mailbox . "\n";
					print "msgid: " . $header->message_id . "\n";
					print "  uid: " . $email[$m]['uid'] . "\n";
					print "flagged: " . ((trim($header->Flagged) === 'F') ?"yes\n":"no\n");
				}
				imap_mail_move($imap, "{$email[$m]['uid']}:{$email[$m]['uid']}", $trashBox, CP_UID); //Gmail specific
	 			imap_delete($imap, $email[$m]['uid'], FT_UID); 										 //Other mail box types
				$flaggedForDelete++;
				break;
				//print "flagged: -" . $header->Flagged . "-\n";
			}
		}
	}
 /*
	 		//imap_delete($imap, $email[$m]['uid'], FT_UID);

    $structure = imap_fetchstructure($imap, $m); //Get the structure holding attachments
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
            } 
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
					if (file_put_contents("/home/wmi/Pictures/gmail/'".$name."'", $contents) == false) {
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

print "Expunging mailbox {$flaggedForDelete} messages flagged to delete\n";

imap_expunge($imap); //Make sure the deletes happen


imap_close($imap);

?>
