<?php
//FW("OPEN");
$domain='localhost';
if(!isset($_GET["url"]))
    if(strpos($_SERVER['HTTP_HOST'],$domain)!== false)
        die("NO Self Baypass");
//die("NO IR Domain Baypass  ".$_SERVER['HTTP_HOST']);

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Set it true for debugging. */
$logHeaders = FALSE;

/* Site to forward requests to.  */
//$site = 'http://remotesite.domain.tld/';
if(!isset($_GET["url"]))
$site = $_SERVER['HTTP_HOST'];
else
$site = $_GET["url"];
/* Domains to use when rewriting some headers. */
//$remoteDomain = 'remotesite.domain.tld';
//$proxyDomain = 'proxysite.tld';
$remoteDomain = $site;
$proxyDomain = $domain;

FW_request($_SERVER);
$request = $_SERVER['REQUEST_URI'];

$ch = curl_init();

/* If there was a POST request, then forward that as well.*/
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
}
curl_setopt($ch, CURLOPT_URL, $site . $request);
curl_setopt($ch, CURLOPT_HEADER, TRUE);

$headers = getallheaders();
//die($headers);
/* Translate some headers to make the remote party think we actually browsing that site. */
$extraHeaders = array();
if (isset($headers['Referer'])) 
{
    $extraHeaders[] = 'Referer: '. str_replace($proxyDomain, $remoteDomain, $headers['Referer']);
}
if (isset($headers['Origin'])) 
{
    $extraHeaders[] = 'Origin: '. str_replace($proxyDomain, $remoteDomain, $headers['Origin']);
}

/* Forward cookie as it came.  */
curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
if (isset($headers['Cookie']))
{
    curl_setopt($ch, CURLOPT_COOKIE, $headers['Cookie']);
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

if ($logHeaders)
{
	$f = fopen("headers.txt", "a");
    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    curl_setopt($ch, CURLOPT_STDERR, $f);
}

curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

$headerArray = explode(PHP_EOL, $headers);

/* Process response headers. */
foreach($headerArray as $header)
{
    $colonPos = strpos($header, ':');
    if ($colonPos !== FALSE) 
    {
        $headerName = substr($header, 0, $colonPos);

        /* Ignore content headers, let the webserver decide how to deal with the content. */
        if (trim($headerName) == 'Content-Encoding') continue;
        if (trim($headerName) == 'Content-Length') continue;
        if (trim($headerName) == 'Transfer-Encoding') continue;
        if (trim($headerName) == 'Location') continue;
        /* -- */
        /* Change cookie domain for the proxy */
        if (trim($headerName) == 'Set-Cookie')
        {
            $header = str_replace('domain='.$remoteDomain, 'domain='.$proxyDomain, $header);
        }
        /* -- */

    }
    header($header, FALSE);
}
FW_response($body);
echo $body;

if ($logHeaders)
{
    fclose($f);
}
curl_close($ch);

function FW($string){
	global $logHeaders;
	if(!$logHeaders)
		return;
    $myfile = fopen("newfile.txt", "a") or die("Unable to open file!");

fwrite($myfile, $string);
fwrite($myfile, '\n');
fclose($myfile);
}
function FW_request($string){
	global $logHeaders;
	if(!$logHeaders)
		return;
    $t=time();
    $myfile = fopen("R_".$t.".txt", "w") or die("Unable to open file!");

fwrite($myfile,print_r( $string,TRUE));
fclose($myfile);
}
function FW_response($string){
	global $logHeaders;
	if(!$logHeaders)
		return;
    $t=time();
    $myfile = fopen("Response_".$t.".txt", "w") or die("Unable to open file!");

fwrite($myfile,print_r( $string,TRUE));
fclose($myfile);
}
?>