<?php
	class XMLRPClientWordPress {
		var $XMLRPCURL = "";
		var $UserName  = "";
		var $PassWord = "";
		
		// constructor
		public function __construct($xmlrpcurl, $username, $password) {
			$this->XMLRPCURL = $xmlrpcurl;
			$this->UserName  = $username;
			$this->PassWord = $password;   
		}
		function send_request($requestname, $params, $datePost) {
			$request = xmlrpc_encode_request($requestname, $params);
			$request = str_replace('<string>%pubdate%</string>','<dateTime.iso8601>'.$datePost.'</dateTime.iso8601>', $request);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			curl_setopt($ch, CURLOPT_URL, $this->XMLRPCURL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 1);
			$results = curl_exec($ch);
			curl_close($ch);
			return $results;
		}
		
		function create_post($title,$body,$category,$datePost,$keywords='',$encoding='UTF-8') {
			$title = htmlentities($title,ENT_NOQUOTES,$encoding);
			$keywords = htmlentities($keywords,ENT_NOQUOTES,$encoding);
			$content = array(
				'title'=>$title,
				'description'=>$body,
				'date_created_gmt' => '%pubdate%',
				'mt_allow_comments'=>0,  // 1 to allow comments
				'mt_allow_pings'=>0,  // 1 to allow trackbacks
				'post_type'=>'post',
				'mt_keywords'=>$keywords,
				'categories'=>array("MapMyRide")
			);
			$params = array(0,$this->UserName,$this->PassWord,$content,true);
			return $this->send_request('metaWeblog.newPost',$params,$datePost);
		}
	}
	//add the WordPress URL, username and password for your wordpress user (user should have at least "Author" privledges
	$objXMLRPClientWordPress = new XMLRPClientWordPress("http://PATH_TO_WP_INSTALL/xmlrpc.php" , "USERNAME" , "PASSWORD");

	//add in your MapMyRide/Run/Fitness UserID
	$url = 'http://api.mapmyfitness.com/3.1/workouts/get_workouts?&o=json&user_id=USER_ID_GOES_HERE';
	$content = file_get_contents($url);
	$json = json_decode($content, true);
	
	for ($i=0; $i<$json['result']['output']['count']; $i++) {
	  $avgSpeed = $json['result']['output']['workouts'][$i]['avg_speed'];
	  $date = $json['result']['output']['workouts'][$i]['created_date'];
	  $title = $json['result']['output']['workouts'][$i]['workout_type_name'];
	  $description = $json['result']['output']['workouts'][$i]['workout_description'];
	  $calories = $json['result']['output']['workouts'][$i]['calories_burned'];
	  $type = $json['result']['output']['workouts'][$i]['workout_type_name'];
	  $id = $json['result']['output']['workouts'][$i]['route_id'];
	  $workoutID = $json['result']['output']['workouts'][$i]['workout_id'];
	  $time = $json['result']['output']['workouts'][$i]['time_taken'];
	  $distance = $json['result']['output']['workouts'][$i]['distance'];
	  
	  $distance = round($distance, 2);
	  $avgSpeed = round($avgSpeed, 1);
	  $hours = floor($time/3600);
	  $minutes = floor(($time / 60) % 60);
	  $seconds = $time % 60;
	  if ($minutes < 10) {
	  	$minutes = "0".$minutes;
	  }
	  if ($seconds < 10) {
	  	$seconds = "0".$seconds;
	  }
	  $tTime = $hours.":".$minutes.":".$seconds;
	  $datePost = date('c', strtotime($date));
	  $datePost = str_replace("-", "", $datePost);
	  
	  $workoutDeets = 'http://www.mapmyride.com/workout/'.$workoutID;
	  $mapEmbed = '<div class="maper"><iframe id="mapmyfitness_route" src="http://snippets.mapmycdn.com/routes/view/embedded/'.$id.'?width=560&height=400&elevation=true&info=true&line_color=E60f0bdb&rgbhex=DB0B0E&distance_markers=0&unit_type=imperial&map_mode=ROADMAP&last_updated='.$date.'" height="550px" width="600px" frameborder="0"></iframe><div style="text-align: right; padding-right: 20px;"><a href="'.$workoutDeets.'" target="_blank">Check out the full workout details!</a></div></div>';
	  
	  $body = '<div id="maper"><div id="workout_stat_boxes"><div class="parent_distance parent_item"><span>'.$distance.'<span class="unit">mi</span></span><p>Distance</p></div><div class="parent_duration parent_item"><span>'.$tTime.'</span><p>Duration</p></div><div class="parent_speed parent_item"> <span>'.$avgSpeed.'<span class="unit">mi/h</span></span><p>Avg Speed</p></div><div class="parent_calories parent_item" style="border-right:0 none;"><span>'.$calories.'</span><p>kCal</p></div></div>'.$mapEmbed.'</div>';
	
	//if you want to import all the workouts
	//echo '<p>'.$objXMLRPClientWordPress->create_post($description,$body,$type,$datePost).'</p>';
	
	//if you only want to import workouts that are 1 day old. I'm used a cron job to run the script daily.    
	if(strtotime($date) > strtotime("-1 day")) {
		echo $objXMLRPClientWordPress->create_post($description,$body,$type,$datePost);
	} else {
		echo $description. "nope<br>";
	}  
	
	/*
	  //Print out the workout info for testing
	  print "<h2>".$description."</h2>";
	  print "<p>Date: ".$date."</p>";
	  print "<p>Category: ".$type."</p>";
	  print '<div id="workout_stat_boxes"><div class="parent_distance parent_item"><span>'.$distance.'<span class="unit">mi</span></span><p>Distance</p></div><div class="parent_duration parent_item"><span>'.$tTime.'</span><p>Duration</p></div><div class="parent_speed parent_item"> <span>'.$avgSpeed.'<span class="unit">mi/h</span></span><p>Avg Speed</p></div><div class="parent_calories parent_item" style="border-right:0 none;"><span>'.$calories.'</span><p>kCal</p></div></div>';
	  print $mapEmbed."<br><br>";
	*/
	}
?>

<html>
<head>
	<!--CSS Style Info for displaying the info nicely-->
    <style>
		.maper {
			width:600px;
		}
		#workout_stat_boxes {
			border: 1px solid #ccc;
			-webkit-border-radius: 2px 2px 2px 2px;
			-moz-border-radius: 2px 2px 2px 2px;
			border-radius: 2px 2px 2px 2px;
			-moz-box-shadow: 1px 1px 2px #333;
			-webkit-box-shadow: 1px 1px 2px #333;
			box-shadow: 1px 1px 2px #333;
			background: #f1f1f1;
			background: linear-gradient(top, #ffffff 0%,#f1f1f1 100%);
			background: -moz-linear-gradient(top, #fff 0%, #f1f1f1 100%);
			background: -webkit-linear-gradient(top, #fff 0%, #f1f1f1 100%);
			background: -o-linear-gradient(top, #fff 0%, #f1f1f1 100%);
			background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #fff), color-stop(100%, #f1f1f1));
			filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFFFF',endColorstr='#FFF1F1F1');
			height: 55px;
			width:600px;
			margin: 10px 0;
		}
		.parent_item {
			float: left;
			width: 24%;
			text-align: center;
			color: #323232;
			padding: 8px 2px;
			border-right: 1px solid #ccc;
		}
		.parent_item .unit {
			font-size: 12px;
			color: #999;
		}
		.parent_item p {
			margin: 2px 0;
			color: #999999;
		}
	</style>
    <!-- END CSS Style Info for displaying the info nicely-->
 </head>
 <body>
 	
 </body>
 </html>