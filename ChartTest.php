<!DOCTYPE html>
<html>
<head>
	<!-- 
		Author: John Bartel
		Last Mod: Feb-14 2019
	 -->
	<meta charset="utf-8">
	<?php
		//Connection settings
		$DBHost = 'localhost';
		$DBUser = 'root';
		$DBPass = '';
		$DBName = 'gps';
		$DBTable = 'test';

		$readings = array();											//Array to hold points for graph.
		if($conn = mysqli_connect($DBHost, $DBUser, $DBPass, $DBName)){	//Set connection, proceed if successful.
			$qry = "SELECT `trace_data` as `trace_data`, `trace_time` FROM $DBTable ORDER BY `trace_id`";
			if($result = mysqli_query($conn, $qry)){
				while($data=mysqli_fetch_assoc($result)){
					$temp = bin2hex(iconv('UTF-8', 'UCS-2BE', $data['trace_data'])); 	//Converts result to 16-bit hex.
					$start = $end = 0;												//Position vars.
					$points = array();
					for($i = 0; $i < strlen($temp)/16; $i++){
						$tempStr1 = substr($temp, $start, 16);						//Takes 16 hex characters for number.
						$start+=16;
						$tempStr2 = '';
						$slice = 0;
						for($j = 0; $j < strlen($tempStr1); $j++){
							$tempStr2 .= substr(substr($tempStr1, $slice, 4), 2);	//For each group of 4 take last 2 since we need only last 8 bits since first 8 are 00.
							$slice += 4;
						}
						$points[] = unpack("l", pack("l", hexdec("0x".$tempStr2)))[1]/1000;	//Converts to signed int required for graphing.
						print_r($points);
					}
					$readings[]=array($points, $data['trace_time']);
				}
			}
			mysqli_close($conn);
		}else{ 							//Else kill script.
		  die("Connection error: Please Contact an Administrator");
		}
	?>
	<script type="text/javascript">
		var readings = <?php echo json_encode($readings); ?>;
		console.log(readings);
	</script>
	<title>Chart Test</title>
</head>
<body>
	<h1>Chart:</h1>
</body>
</html>