<?php

// to be run every 24 hours (:
// so lets first check to see if we have any data to commit in the first place
$baseline = time()-(60*60*24);
$archive = json_decode(file_get_contents("archive.json"));
$listrev = array_reverse($archive->list);
$commits = array();
foreach($listrev as $lst)
{
	if($lst->timestamp > $baseline) array_push($commits, $lst);
	else break;
}
if(count($commits) == 0) die("nothing needed here");

// now we have changes we need to commit, lets format the data and sha256
$pool = "";
foreach($commits as $com)
{
	$pool .= "*" . $com->hashdata . "-" . $com->timestamp;
	if(isset($com->token)) $pool .= "(" . $com->token . ")";
}

// now pool has the cool stuff... lets hash it now (:
echo $pool;

$hashed = hash("sha256", $pool);
echo $hashed;
$nowtime = time();

$datum = new STDClass();
$datum->timestamp = $nowtime;
$datum->hash = $hashed;
$datum->data = $pool;

array_push($archive->hashed, $datum);
// make the tx...
file_put_contents("archive.json", json_encode($archive));

// ok now make the tx
$passphase = "price bliss proud caught separate sane enter silently diamond hardly roam reply";
$addr = "NXT-KLR4-887V-H74E-6ALT9";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"http://jnxt.org:7876/nxt");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
            "requestType=sendMessage&recipient=".$addr."&feeNQT=100000000&deadline=1440&secretPhrase=".$passphase."&message=".$hashed);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$output = curl_exec ($ch);
echo $output;
curl_close ($ch);


?>