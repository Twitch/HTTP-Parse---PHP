<?

/*********************************\
            parse.php
   A minimal HTTP parsing thing
    Written by Benjamin.Sauls
             Twitch
\*********************************/
      

function get_heads ($host, $port, $path) {
// A function to get the response headers from a site.
// This currently uses a connection for each request, but I would like to work in pipelined requests in the future.

   // Build the query.
   $q = "GET $path HTTP/1.1\n";
   $q .= "Host: $host\n";
   $q .= "Connection: Close\n";
   // Insert extra request headers here, to your heart's content.

   $q .= "\n";

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
   // The script FINs and tears down the connection as soon as the headers are received, but the server continues to send the response in packets which are reset by the script.
   // We could use a HEAD method rather than a GET, but some servers may not support this.

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

if(isset($argv[1])) { 
   $host = $argv[1];
} else {
   $host = "localhost";
}
if(isset($argv[2])) {
   $port = $argv[2];
} else {
   $port = "80";
}
if(isset($argv[3])) {
   $path = $argv[3];
} else {
   $path = "/";
}


$header = get_heads($host, $port, $path);




echo $header["response"][1];
echo " ".$header["response"][2];
echo " ".$header["response"][3]."\n";

   foreach($header As $header_name => $header_value) {
      if($header_name == "response") {
      } else {
         echo "$header_name:$header_value\n";
      } 
   }

preg_match("/([A-Za-z0-9\=\-\:]*);/", $header["Set-Cookie"], $ck);
echo $ck[1]."\n";
$cookies = explode(":", $header["Set-Cookie"]);

//var_dump($cookies);

?>
