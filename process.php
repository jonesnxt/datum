<?php
// so I get the text data, the hashed data, and maybe a token and store it all with a timestamp for later

//size check first...
if(strlen($_POST['textdata']) > 50000) die("Textdata is too long");

$archive = json_decode(file_get_contents("archive.json"));

$datum = new STDClass();
$datum->textdata = $_POST['textdata'];
$datum->hashdata = $_POST['hashhere'];
$datum->timestamp = time();
if(isset($_POST['token']) && strlen($_POST['token']) > 0)
{
	$datum->token = $_POST['token'];
}

array_push($archive->list, $datum);

echo "Operation Successful (:<br/>Remember to save the original textdata to refer back to if needed<br/>Datum:<pre>".json_encode($datum)."</pre>";
echo "<script>window.location = './index.php'; </script>";
file_put_contents("archive.json", json_encode($archive));
?>