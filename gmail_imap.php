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
$year = '1 month ago'; 
echo "account {$ugmail} - killing msgs from before {$year}\n\n"; 
//Get the timestamp to compare with
//$dstamp = strtotime("12/31/2011");
$dstamp = strtotime($year);
//print $dstamp;

$cleanFrom = array("action@ifttt.com",
"notify@twitter.com",
"groups-noreply@linkedin.com",
"updates-noreply@linkedin.com",
"messages-noreply@linkedin.com",
"clash@clashdaily.com",
"team@email.digg.com",
"info@patriotoutdoornews.com",
"mail@patriotdepot.com",
"support@investorsobserver.com",
"updates@girlsjustwannahaveguns.com",
"rakuten@newsletter.rakuten.com",
"reply@dailycaller-alerts.com",
"specials@galleryofguns.com",
"sigsauer@sigsauer.com",
"fliers@natchezss.com",
"memberservice@billpayment.firsttechfed.com",
"cisco@emessenger.cisco.com",
"deals@gearhog.chompon.com",
"no-reply@woot.com",
"dudley.brown@rmgo.org",
"admin001@1911forum.com",
"admin@shootersforum.com",
"admin@survivalistboards.com",
"dailydigest@email.pjmedia.com",
"donotreply@alloutdoor.com",
"jagent@route.monster.com",
"ebay@ebay.com",
"proflowers@email.proflowers.com",
"info@all-battery.com",
"no-reply@youversion.com",
"brownells@email.brownells.com",
"rewards@rewards.shopyourwayrewards.com",
"books@sitepoint.com",
"webmaster@nwcu.com",
"homedepotcustomercare@email.homedepot.com",
"email@email.cabelas.com",
"searscardissuedbycitibank@info4.searscard.com",
"mailing@suarezinternational.com",
"no-reply@nextguide.tv",
"mailer@mail2.clubexpress.com");

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
//$allheaders = imap_headers($imap, $m);
$flaggedForDelete = 0;

for ($m = $mbox->Nmsgs,$k = 0; $k < $message_count; ++$k, --$m){
	
    $header = imap_headerinfo($imap, $m);
	//Gather all header details in the email array for each msg
    $email[$m]['from']        = $header->from[0]->mailbox.'@'.$header->from[0]->host;
	$email[$m]['uid']         = imap_uid($imap,$header->Msgno); //Msgno may change, uid is fixed 
    //$email[$m]['fromaddress'] = $header->from[0]->personal;
    //$email[$m]['to']          = $header->to[0]->mailbox;
    //$email[$m]['subject']     = $header->subject;
    //$email[$m]['message_id']  = $header->message_id;
    //$email[$m]['date']        = $header->udate;
    //$email[$m]['flagged']     = trim($header->Flagged);
 
//    $from_email = $email[$m]['from'];
//    $to         = $email[$m]['to'];
//    $subject    = $email[$m]['subject'];
	if ((trim($header->Flagged) !== 'F') && ($header->udate < $dstamp)) {
		foreach ($cleanFrom as $mopup){
		  	if ($email[$m]['from'] == $mopup) {
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
				imap_mail_move($imap, "{$email[$m]['uid']}:{$email[$m]['uid']}", $trashBox, CP_UID);
	 			imap_delete($imap, $email[$m]['uid'], FT_UID);
				$flaggedForDelete++;

				break; //Found the sender in cleanout list

				//print "flagged: -" . $header->Flagged . "-\n";
			}
		}
		
	}
}

print "Expunging mailbox {$flaggedForDelete} messages flagged to delete\n";

imap_expunge($imap); //Make sure the deletes happen

imap_close($imap);

?>
