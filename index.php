<!DOCTYPE html>
<?php 

	function timeago($timestamp)
	{

		$fromnow =  time() - $timestamp;
		
		$days =  floor($fromnow/86400);
		$hours = floor(($fromnow%86400)/3600);
		$minutes = floor(($fromnow%3600)/60);
		$seconds = floor($fromnow&60);
		$acc = "";
		if($days != 0 && $days != 1) $acc = $days . " days ago";
		else if($days == 1) $acc = " 1 day ago";
		else if($hours != 0 && $hours != 1) $acc = $hours . " hours ago";
		else if($hours == 1) $acc = "1 hour ago";
		else if($minutes != 0 && $minutes != 1) $acc = $minutes . " minutes ago";
		else if($minutes == 1) $acc = "1 minute ago";
		else if($seconds != 0 && $seconds != 1) $acc = $seconds . " seconds ago";
		else if($seconds == 1) $acc = "1 second ago";
		else $acc = "just now";
		
		return $acc;
	}


?>
<html>
<head>
<title>Datum Project</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="../jquery.js"></script>
<script src="jsbn.js"></script>
<script src="jsbn2.js"></script>
<script src="converters.js"></script> 
<script src="curve25519.js"></script>
<script src="3rdparty/jssha256.js"></script>
<script src="sha256worker.js"></script>
<script src="nxtaddress.js"></script>
<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css"/>
<script>
//var BigInteger = require("jsbn");
$(document).ready(function() {

$(".signdata").hide();

var _hash = {
		init: SHA256_init,
		update: SHA256_write,
		getBytes: SHA256_finalize
	};


function byteArrayToBigInteger(byteArray, startIndex) {
		var value = new BigInteger("0", 10);
		var temp1, temp2;
		for (var i = byteArray.length - 1; i >= 0; i--) {
			temp1 = value.multiply(new BigInteger("256", 10));
			temp2 = temp1.add(new BigInteger(byteArray[i].toString(10), 10));
			value = temp2;
		}

		return value;
	}

function simpleHash(message) {
		_hash.init();
		_hash.update(message);
		return _hash.getBytes();
	}

function check(secretPhrase, accid)
{

	SHA256_init();
	SHA256_write(converters.stringToByteArray(secretPhrase));
	var ky = converters.byteArrayToHexString(curve25519.keygen(SHA256_finalize()).p);

	var hex = converters.hexStringToByteArray(ky);

		_hash.init();
		_hash.update(hex);

		var account = _hash.getBytes();

		account = converters.byteArrayToHexString(account);

		var slice = (converters.hexStringToByteArray(account)).slice(0, 8);

		var accountId = byteArrayToBigInteger(slice).toString();
	if(accountId == accid) return true;
	else return false;
}
var epochNum = 1385294400;
function getPublicKey(secretPhrase)
{
	SHA256_init();
	SHA256_write(converters.stringToByteArray(secretPhrase));
	var ky = converters.byteArrayToHexString(curve25519.keygen(SHA256_finalize()).p);

	return converters.hexStringToByteArray(ky);
}

function toByteArray(long) {
    // we want to represent the input as a 8-bytes array
    var byteArray = [0, 0, 0, 0];

    for ( var index = 0; index < byteArray.length; index ++ ) {
        var byte = long & 0xff;
        byteArray [ index ] = byte;
        long = (long - byte) / 256 ;
    }

    return byteArray;
};

function signBytes (message, secretPhrase) {
		var messageBytes = message;
		var secretPhraseBytes = converters.stringToByteArray(secretPhrase);

		var digest = simpleHash(secretPhraseBytes);
		var s = curve25519.keygen(digest).s;

		var m = simpleHash(messageBytes);
		_hash.init();
		_hash.update(m);
		_hash.update(s);
		var x = _hash.getBytes();

		var y = curve25519.keygen(x).p;

		_hash.init();
		_hash.update(m);
		_hash.update(y);
		var h = _hash.getBytes();

		var v = curve25519.sign(h, x, s);


		return v.concat(h);
	}


// ill get this eventually...
function tokenize(websiteString, secretPhrase)
{
		//alert(converters.stringToHexString(websiteString));
		var hexwebsite = converters.stringToHexString(websiteString);
        var website = converters.hexStringToByteArray(hexwebsite);
        var data = [];
        data = website.concat(getPublicKey(secretPhrase));
        var unix = Math.round(+new Date()/1000);
        var timestamp = unix-epochNum;
        var timestamparray = toByteArray(timestamp);
        data = data.concat(timestamparray);

        var token = [];
        token = getPublicKey(secretPhrase).concat(timestamparray);

        var sig = signBytes(data, secretPhrase);

        token = token.concat(sig);
        var buf = "";

        for (var ptr = 0; ptr < 100; ptr += 5) {

        	var nbr = [];
        	nbr[0] = token[ptr] & 0xFF;
        	nbr[1] = token[ptr+1] & 0xFF;
        	nbr[2] = token[ptr+2] & 0xFF;
        	nbr[3] = token[ptr+3] & 0xFF;
        	nbr[4] = token[ptr+4] & 0xFF;
        	var number = byteArrayToBigInteger(nbr);

            if (number < 32) {
                buf+="0000000";
            } else if (number < 1024) {
                buf+="000000";
            } else if (number < 32768) {
                buf+="00000";
            } else if (number < 1048576) {
                buf+="0000";
            } else if (number < 33554432) {
                buf+="000";
            } else if (number < 1073741824) {
                buf+="00";
            } else if (number < 34359738368) {
                buf+="0";
            }
            buf +=number.toString(32);

        }
        return buf;

    }


document.write(tokenize("abc","place beyond delight butterfly throne night student warmth worry bomb honey autumn"));


$(".textdata").bind('input propertychange', function() {

	var hash = converters.byteArrayToHexString(simpleHash($(".textdata").val()));
	$(".hashhere").val(hash);
})
/*
$(".passphrase").bind('input propertychange', function() {

	var secretPhrase = $(".passphrase").val();
	SHA256_init();
	SHA256_write(converters.stringToByteArray(secretPhrase));
	var ky = converters.byteArrayToHexString(curve25519.keygen(SHA256_finalize()).p);

	var hex = converters.hexStringToByteArray(ky);

		_hash.init();
		_hash.update(hex);

		var account = _hash.getBytes();

		account = converters.byteArrayToHexString(account);

		var slice = (converters.hexStringToByteArray(account)).slice(0, 8);

		var accountId = byteArrayToBigInteger(slice).toString();

		var addr = new NxtAddress();
		addr.set(accountId);
		var fin = addr.toString();
		if($(".passphrase").val().length !== 0)
		{
			$(".passinfo").text("Address to sign with: "+ fin);
			var tok = tokenize(fin);
		}
		
		else $(".passinfo").text("Not using passphase");


})*/

$(".hashdata").click(function() {


})

})

</script>
</head>
<body>
<div class="container container-fluid" role="main">
	<?php include_once("../topbar.php"); ?>
		
		<div class="page-header"><h2>Datum<small> - Proof of Existence</h2></div>
		<div class="row col-md-12">
			<div class="well">
			<p class="lead">Datum is a way to easily prove that data existed at a specific point in time as well as it being signed by multiple parties.</p><p style="font-size: 12pt">Enter your text to be stored below and then use your nxt account to sign the data if needed. Every 12 hours, all of the data is organized, hashed together and submitted to the nxt network to store.
			</p>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<div class="panel panel-default">
			<div class="panel-heading"><h3> Store Some Data</h3></div>
			<div class="panel-body">
				<form method="post" action="process.php">
			<textarea class="form-control textdata" name="textdata" rows="6">Data here (max 50 kb)</textarea><br/>
			<br/>
			
				<label for="hashhere">Hashed data:</label>
				<input type="text" name="hashhere" class="form-control hashhere" readonly>
					<br/>
				<div class="alert alert-info" style="font-size: 12pt">Once you have hashed data you can:<br/> - You may create the signature yourself using the "Token" feature of an nxt wallet <br/> - You can also choose to leave this blank if you don't wish to have any signature.</div>
				<!--<label for="passphrase">Nxt Passphase (optional signing):</label>
				<input type="text" class="form-control passphrase"/>
				<div class="bs-callout bs-callout-info passinfo">Passphase signing info: none yet</div>
					<br/>-->
				<label for="token">Nxt account Token (optional):</label>
				<input type="text" name="token" class="form-control token"/><br/>

				<button class="btn btn-primary btn-large btn-block hashdata">Send Data</button>

			</form>
			</div></div></div>
		</div>
<div class="row">
<div class="form-group col-md-12">
			<div class="panel panel-default">
			<div class="panel-heading"><h3>Recent data stored</h3></div>
			<div class="panel-body">

<table class="table table-striped table-hover">
<thead>
<td>Data</td><td>Time</td><td>Hash</td>
</thead>
<?php 
	$rows = json_decode(file_get_contents("archive.json"));

	$list = array_reverse($rows->list);
	$counter = 0;
		foreach($list as $lst)
		{
			echo "<tr>";
			if(strlen($lst->textdata) > 50) $dt = substr($lst->textdata, 0, 47)."...";
			else $dt = $lst->textdata;
			echo "<td>".$dt."</td>";
			echo "<td>".timeago($lst->timestamp)."</td>";
			echo "<td>".$lst->hashdata."</td>";
			echo "</tr>";

			$counter ++;
			if($counter == 7) break;
		}
	
?>
</table>
<div class="bs-callout bs-callout-info">

Datum archive file: <em><a href="./archive.json">./archive.json</a></em><br/>
Download a copy to help with the system integrity
</div>
</div></div></div>
</div>

<div class="row"><div class="form-group col-md-12">
			<div class="panel panel-default">
			<div class="panel-heading"><h3> Past hashes and data</h3></div>
			<div class="panel-body">
	<table class="table table-striped table-hover">
<thead>
<td>Data</td><td>Time</td><td>Hash</td>
</thead>
<?php 
	$hashes = array_reverse($rows->hashed);
	$counter = 0;
		foreach($hashes as $hash)
		{
			echo "<tr>";
			echo "<td>".$hash->data."</td>";
			echo "<td>".timeago($hash->timestamp)."</td>";
			echo "<td>".$hash->hash."</td>";
			echo "</tr>";

			$counter ++;
			if($counter == 7) break;
		}
	
?>
</table>
<div class="bs-callout bs-callout-info">
<?php
function query($type, $attr)
{
	return json_decode(file_get_contents("http://jnxt.org:7876/nxt?requestType=".$type."&".$attr));
}
$account = "NXT-KLR4-887V-H74E-6ALT9";
$acc = query("getAccount", "account=".$account);
echo "Service has enough nxt for <strong>".($acc->balanceNQT/100000000)."</strong> more days.";
?>
<br/>
Datum announcing address: <em><?php echo $account; ?></em><br/>

</div>

</form>
</div></div></div></div>
		
		
		
</div>
</body>
</html>
