<?

/*********************************\
           webparse.php
   A minimal HTTP parsing thing
 Written to interface via the web.
    Written by Benjamin.Sauls
             Twitch
\*********************************/
      

function get_heads ($host, $port, $path, $method, $version, $headers = NULL) {
// A function to get the response headers from a site.
// This currently uses a connection for each request, but I would like to work in pipelined requests in the future.
 
   // Make sure we have the leading slash for the resource.
   if($path[0] != "/") {
      $path = "/".$path;
   }
   
   // Build the query.
   $q = "$method $path HTTP/$version\n";
   $q .= $headers;

   $q .= "\n\n";

   // Open the socket and/or report errors.
   $sock = fsockopen($host, $port, $errno, $errmsg);
   if(!$sock) { die("*** Socket Error #$errno: $errmsg.\n"); }

   // Set a boolean to mark/check for the end of the headers.
   $in_header = true;
   $hi = 0;

   // Send the request down the socket.
   fwrite($sock, $q);
   // Parse the response as long as we are in the headers.
   while($in_header == true) {
      $li = fgets($sock);
      // Check for the empty line signaling the end of the headers.
      if($li == "\r\n") { $in_header = false; }
      else { 
         // Remove the \n from the lines, we can add these manually as needed.
         $li = trim($li);
         $lines[$hi] = $li;
      }
      $hi++;
   }
   // Close the socket.
   fclose($sock);
   // This leads to interesting TCP behavior. 
   // The script FINs and tears down the connection as soon as the headers are 
   // received, but the server continues to send the response in packets which 
   // are reset by the script. We could use a HEAD method rather than a GET, 
   // but some servers may not support this.

   // Now, parse through the response lines. 
   // Set up some easier to use variables.
   foreach($lines As $lin => $lv) {
      if($lin == "0") {
         preg_match("/(HTTP\/...) ([0-9]{3}) (.*)/", $lv, $rsline);
         $header["response"] = $rsline;
      } else {
         preg_match("/^([A-Za-z0-9-]*):(.*)/", $lv, $hname);
         $header[$hname[1]] = $hname[2];
      }
   }
   $header["q"] = $q;
   return $header;

   // Return values:
   // This will return an associative array using the header names for
   // associative indecies.
   // There is also a special array 'response' which contains the first
   // line of the response. The sub elements of this are then broken further.

   // ["response"]
   //   -  [1] = "HTTP/1.1" 
   //   -  [2] = "200"
   //   -  [3] = "OK"
}

// Build an array of methods, even though some aren't fully supported by most servers.
// This will make it easier to add/remove these as needed.
$methods = array(
   "GET",
   "POST",
   "HEAD",
   "OPTIONS",
   "PUT",
   "DELETE",
   "TRACE",
   "CONNECT"
);

// Build our request form now.

?>

<html>
<head>
   <title>Request Building / Testing Engine</title>
</head>

<body style="background-color: #dedede;">

<div style="background-color: #aaa8a7;">

<form action="<?=$_SERVER["PHP_SELF"];?>" method="POST">
<table width="75%" align="center">
   <tr>
      <td align="center">Host:<br><input type="text" name="host" value="<?=$_POST["host"];?>"></td>
   </tr>
   <tr>
      <td align="center">
         <select name="method" size="1">
         <?
         foreach($methods As $m) {
            echo "         <option value=\"$m\"";
            if($_POST["method"] == $m) { echo " SELECTED"; }
            echo ">$m</option>\n";
         }
         ?>
         </select>
         
         <input type="text" size="50" name="resource" value="<?=$_POST["resource"];?>">

         <select name="version" size="1">
         <option value="1.1">1.1</option>
         <option value="1.0">1.0</option>
         <option value="0.9">0.9</option>
         </select>
      </td>
   </tr>
   <tr>
      <td align="center">
         <textarea name="headers" cols="50" rows="10"><?=$_POST["headers"];?></textarea>
      </td>
   </tr>
   <tr>
      <td align="center"><input type="submit" value="Request" name="requesting"></td>

</table>
</form>
</div>

<hr>
<?
// If we have just POSTed a request, let's parse and print it.

if(isset($_POST["requesting"])) {
if($_POST["host"] == "") {
// I would like to make this fail nicer later.
   die("Please set a host value.");
}

// Run our function to get the headers.
$header = get_heads($_POST["host"], '80', $_POST["resource"], $_POST["method"], $_POST["version"], $_POST["headers"]);

// Let's display the full request so we can remember what's really going on, here.
// First we need to "br" those newlines.
$newq = nl2br($header["q"]);

echo "<div style=\"background-color: #17c077;\">$newq</div>";

// Let's set an array so we can change the colors based on the type of status code.
// Nifty.
$bgs = array(
   "1" => "6dafe1",
   "2" => "6dafe1",
   "3" => "d3b87f",
   "4" => "d27777",
   "5" => "fc5555"
);

echo "<div style=\"background-color: #". $bgs[$header["response"][2][0]] .";\">";
echo "<hr>";

echo "<b>";
echo $header["response"][1]." ".$header["response"][2]." ".$header["response"][3]."<p>";
echo "</b>";

// This is sort of confusing. I needed to reference the actual response line
// and wanted to be able to print out the query, as well. To do this, though
// I needed to pass them in the return value. So we have to make sure we don't
// confuse these for being headers.
//
// Excluding the query and response, let's spit out the headers.


foreach($header As $header_name => $header_value) {
   if($header_name == "response" || $header_name == "q") {
   } else {
      echo "$header_name:$header_value<br>";
   }
}

// I believe I was aiming to set variables for each cookie here, or something.
// Honestly I don't recall. I'll update if I do. =/
preg_match("/([A-Za-z0-9\=\-\:]*);/", $header["Set-Cookie"], $ck);
echo $ck[1]."\n";
$cookies = explode(":", $header["Set-Cookie"]);

}


?>

</div>

</body>
</html>
