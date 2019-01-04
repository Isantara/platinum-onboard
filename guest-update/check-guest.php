<?php

//
// Globals
//
date_default_timezone_set('EST');
$startdate=date('m/d/Y 00:01');
$enddate=date('m/d/Y 23:59');
$post_header = array(
  "Content-Type: application/vnd.com.cisco.ise.identity.guestuser.2.0+xml",
  "Accept: application/vnd.com.cisco.ise.identity.guestuser.2.0+xml"
);

//
// Start of functions DOMCdataSection
//

// Random Password Generator
function random_str(
    $length,
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
)
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    if ($max < 1) {
        throw new Exception('$keyspace must be at least two characters long');
    }
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

// API Updater
function updateAPI($APIurl)
{
  $retmsg='';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $APIurl);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  if(curl_errno($ch) !== 0)
  {
      $retmsg= 'cURL error when connecting to ' . $url . ': ' . curl_error($ch);
  }
  else
  {
      echo "\r\nInformation successfully sent to API.\r\n";
  }
  curl_close($ch);
  return $retmsg;
}

//
// End of functions section
//


if(isset($_SERVER['REQUEST_METHOD'] ))
{
  parse_str($_SERVER['QUERY_STRING'], $output);
	$emailaddy=$output['emailid'];
  $url = "https://python-guest:LkjLkj@192.168.1.129:9060/ers/config/guestuser/?filter=emailAddress.EQ." . $emailaddy;
  $headers = [
    'Accept: application/vnd.com.cisco.ise.identity.guestuser.2.0+xml'
  ];

  $ch = curl_init();

  // set URL and other appropriate options
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  // grab URL and pass it to the browser
  $output = curl_exec($ch);
  if ( ! $output )
  {
    print curl_errno($ch) .':'. curl_error($ch);
  }
  curl_close($ch);

  $p = xml_parser_create();
  xml_parse_into_struct($p, $output, $vals, $index);
  xml_parser_free($p);

  $passed_id=$vals[2]['attributes']['ID'];
  $passed_email=$vals[2]['attributes']['NAME'];

  if ($passed_email != $emailaddy)
  {
    echo "\r\nCreating User....\r\n\r\n";
	  $passwd=random_str(10);
	  $post_string = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
	   <ns0:guestuser xmlns:ns0="identity.ers.ise.cisco.com">
	    <customFields>
	    </customFields>
	    <guestAccessInfo>
        <fromDate>'.$startdate.'</fromDate>
      	<location>San Jose</location>
      	<toDate>'.$enddate.'</toDate>
      	<validDays>1</validDays>
	    </guestAccessInfo>
	    <guestInfo>
 	     <emailAddress>'.$emailaddy.'</emailAddress>
 	     <enabled>true</enabled>
 	     <password>'.$passwd.'</password>
 	     <userName>'.$emailaddy.'</userName>
	    </guestInfo>
	    <guestType>Contractor (default)</guestType>
	    <portalId>c945bfc2-f761-11e8-a29a-aa0cee21782f</portalId>
	    <sponsorUserName>python-guest</sponsorUserName>
	   </ns0:guestuser>';

    $ch = curl_init();
	  $url2= "https://python-guest:LkjLkj@192.168.1.129:9060/ers/config/guestuser";
	  curl_setopt($ch, CURLOPT_URL, $url2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $post_header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $output = curl_exec($ch);
    $error_msg = curl_error($ch);
    curl_close($ch);
    if (!empty($error_msg))
	  {
      echo "Error in POST....(".$error_msg.") exiting";
		  exit;
	  }
	  else
	  {
      // Need to  call dbAPI and update status to created
		  $dbURL="http://24.239.120.11:9999/api/update-status-guest-account?emailid=".$emailaddy."&status=Completed&guestpassword=".$passwd;
      $message=updateAPI($dbURL);
      echo $message;
    }
  }
  elseif ($passed_email == $emailaddy)
  {
    $status="0";
    $url = "https://python-guest:LkjLkj@192.168.1.129:9060/ers/config/guestuser/" . $passed_id;
    $headers = [
      'Accept: application/vnd.com.cisco.ise.identity.guestuser.2.0+xml'
	  ];
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    $output = curl_exec($ch);
    $error_msg = curl_error($ch);
    curl_close($ch);
    if (!empty($error_msg))
    {
      echo "Error in POST....(".$error_msg.") exiting";
    }
    else
    {
      $p = xml_parser_create();
      xml_parse_into_struct($p, $output, $vals, $index);
      xml_parser_free($p);
      if(array_key_exists("value",$vals[20]))
      {
        $status=$vals[20]['value'];
	if($status == "EXPIRED")
	{
          $url = "https://python-guest:LkjLkj@192.168.1.129:9060/ers/config/guestuser/" . $passed_id;
	  $passwd=random_str(10);
	  $post_string = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
	    <ns0:guestuser xmlns:ns0="identity.ers.ise.cisco.com">
		          <customFields>
		          </customFields>
		          <guestAccessInfo>
		            <fromDate>'.$startdate.'</fromDate>
		            <location>San Jose</location>
		            <toDate>'.$enddate.'</toDate>
		            <validDays>1</validDays>
  		        </guestAccessInfo>
		          <guestInfo>
		            <emailAddress>'.$emailaddy.'</emailAddress>
		            <enabled>true</enabled>
		            <password>'.$passwd.'</password>
		            <userName>'.$emailaddy.'</userName>
		          </guestInfo>
		          <guestType>Contractor (default)</guestType>
		          <portalId>c945bfc2-f761-11e8-a29a-aa0cee21782f</portalId>
		          <sponsorUserName>python-guest</sponsorUserName>
		        </ns0:guestuser>';

	  $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
  	  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
	  curl_setopt($ch, CURLOPT_HTTPHEADER, $post_header);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 	  $output = curl_exec($ch);
          $error_msg = curl_error($ch);
          curl_close($ch);
          if (!empty($error_msg))
          {
            echo "Error in POST....(".$error_msg.") exiting";
          }
	  else
	  {
	    echo "User Enabled.\r\n\r\n";
	    $dbURL="http://24.239.120.11:9999/api/update-status-guest-account?emailid=".$emailaddy."&status=Updated&guestpassword=".$passwd;
            $message=updateAPI($dbURL);
            echo $message;
	  }
	}
      }
      elseif($status == "AWAITING_INITIAL_LOGIN")
      {
	echo "\r\nAccount is not expired and already active\r\n\r\n";
      }
      else
      {
	echo "\r\nError in status.  Unknown status found\r\n\r\n";
      }
    }
  }
  else
  {
	   echo "ERROR in the progam!  Bailing";
  }
}
else
{
  echo "Error in POST!";
}

?>
