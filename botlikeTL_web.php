<?php 
@ini_set("output_buffering", "Off"); 
@ini_set('implicit_flush', 1); 
@ini_set('zlib.output_compression', 0); 
@ini_set('max_execution_time',120000); 
header( 'Content-type: text/html; charset=utf-8' ); 

function NgeSubmitData(){
function SendRequest($url, $post, $post_data, $user_agent, $cookies) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://i.instagram.com/api/v1/'.$url);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


    if($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
$fllp_kuki = 'kuki_'.$_POST['u'].'.txt';
    if($cookies) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $fllp_kuki);            
    } else {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $fllp_kuki);
    }

    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);

    
    return array($http, $response);
}

function GenerateGuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
            mt_rand(0, 65535), 
            mt_rand(0, 65535), 
            mt_rand(0, 65535), 
            mt_rand(16384, 20479), 
            mt_rand(32768, 49151), 
            mt_rand(0, 65535), 
            mt_rand(0, 65535), 
            mt_rand(0, 65535));
}


function GenerateSignature($data) {
    return hash_hmac('sha256', $data, 'b4a23f5e39b5929e0666ac5de94c89d1618a2916');
}


function GenerateUserAgent() {	
	$resolutions = array('720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320');
	$versions = array('GT-N7000', 'SM-N9000', 'GT-I9220', 'GT-I9100');
	$dpis = array('120', '160', '320', '240');

	$ver = $versions[array_rand($versions)];
	$dpi = $dpis[array_rand($dpis)];
	$res = $resolutions[array_rand($resolutions)];
	
	return 'Instagram 4.'.mt_rand(1,2).'.'.mt_rand(0,2).' Android ('.mt_rand(10,11).'/'.mt_rand(1,3).'.'.mt_rand(3,5).'.'.mt_rand(0,5).'; '.$dpi.'; '.$res.'; samsung; '.$ver.'; '.$ver.'; smdkc210; en_US)';
}

function GetPostData($filename) {
    if(!$filename) {
        echo "The image doesn't exist ".$filename;
    } else {
        $post_data = array('device_timestamp' => time(), 
                            'photo' => '@'.$filename);
        return $post_data;
    }
}

// Set the username and password of the account that you wish to post a photo to
$username = $_POST['u'];
$password = $_POST['p'];


// Set the path to the file that you wish to post.
// This must be jpeg format and it must be a perfect square
$xfilename = 'image.jpg';

// Set the caption for the photo
$xcaption = "...";

// Define the user agent
$xagent = 'Instagram 6.21.2 Android (19/4.4.2; 480dpi; 1152x1920; Meizu; MX4; mx4; mt6595; en_US)';
$agent = GenerateUserAgent();

// Define the GuID
$guid = GenerateGuid();


// Set the devide ID
$device_id = "android-".$guid;

/* LOG IN */
// You must be logged in to the account that you wish to post a photo too
// Set all of the parameters in the string, and then sign it with their API key using SHA-256
$data = '{"device_id":"'.$device_id.'","guid":"'.$guid.'","username":"'.$username.'","password":"'.$password.'","Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}';
$sig = GenerateSignature($data);
$data = 'signed_body='.$sig.'.'.urlencode($data).'&ig_sig_key_version=4';
$login = SendRequest('accounts/login/', true, $data, $agent, false);


if(strpos($login[1], "Sorry, an error occurred while processing this request.")) {
    echo "Request failed, there's a chance that this proxy/ip is blocked";
} else {			
	if(empty($login[1])) {
		echo "Empty response received from the server while trying to login";
	} else {			
		// Decode the array that is returned
		$obj = @json_decode($login[1], true);

		if(empty($obj)) {
			echo "Could not decode the response: ";
		} else {
			echo "Hallo $username!<br>";
$bloop = file_get_contents('https://www.instagram.com/'.$_POST['u'].'/media/');
$dat = json_decode($bloop);
$targetID = $dat->items[0]->user->id;
$next_max_id = null;
$text = $_POST['text'];
//***************** GET Feed Timeline  ****************************************
echo "Mencari Post di timeline...<br/>";
$feed = SendRequest('feed/timeline/?max_id=', false, false, $agent, true);
$obj = json_decode($feed[1]);
foreach ($obj->items as $items) {
	$has_liked = $items->has_liked;
	if($has_liked == False) {
		//************* Like/Unlike Media ****************
		$media_id = $items->id;
		$action = 'like'; //like, unlike
		$data = '{"media_id":"'.$media_id.'"}';   
		$sig = GenerateSignature($data);
		$new_data = 'signed_body='.$sig.'.'.urlencode($data).'&ig_sig_key_version=4';
		$like = SendRequest('media/'.$media_id.'/'.$action.'/', true, $new_data, $agent, true);
		sleep(1);
		//************* Kirim DM ****************
		
		$data_dm = '{"client_context":"'.$device_id.'","text":"'.$text.'","thread_ids":"0","recipient_users":"'.$items->user->pk.'"}'; 

		$sig_dm = GenerateSignature($data_dm);
		$new_dm = 'signed_body='.$sig_dm.'.'.urlencode($data_dm).'&ig_sig_key_version=4';
		$ngedm = SendRequest('direct_v2/threads/broadcast/text/', true, $new_dm, $agent, true);
		


		sleep(1);
		//********************************************	
		echo "Ngelike status si @" . $items->user->username . " (" . $items->user->pk . ") Sukses<br/>";
		//echo "NgeDM si @" . $items->user->username . " Sukses<br/>";
	} else {
		echo "Status si @" . $items->user->username . " sudah diLike<br/>";
	}
}
//****************************************************************************
echo "Proses selesai :D<br/>";
}

}
}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SMM Panel">
    <meta name="author" content="author">

    <title>Instagram: Like TL</title>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="assets/css/sb-admin-2.css" rel="stylesheet">
    <link href="assets/fonts/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head><body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Like TL</h3>
                    </div>
                    <div class="panel-body">
<form role="form" method="post">
                            <fieldset>
							                                <div class="form-group">
                                    <input class="form-control" placeholder="Username" name="u" type="text" value="">
                                </div>
							                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="p" type="password" value="">
                                </div>
                                <!-- Change this to a button or input when using this as a form -->
                                <button type="submit" name="submit" class="btn btn-lg btn-success btn-block">Login</button>
                            </fieldset>
                            
<?php if (isset($_POST['submit'])) NgeSubmitData(); ?>
                        </form>
                    </div>
                </div>
            </div>
            </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/plugins/metisMenu/metisMenu.min.js"></script>
    <script src="assets/js/sb-admin-2.js"></script>
</body>
</html></div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/plugins/metisMenu/metisMenu.min.js"></script>
    <script src="assets/js/sb-admin-2.js"></script>
</body>
</html>