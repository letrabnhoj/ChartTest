<!DOCTYPE html>
<html>
<head>
	<!-- 
		Author: John Bartel
		Last Mod: Feb-14 2019
	 -->
	<meta charset="utf-8">
	<style type="text/css">
		body {background-color: lightgrey; margin: 0 2%;}
		#fraym {width: 100%;}
	</style>
	<script type="text/javascript">
		<?php
			//Connection settings
			$DBHost = 'localhost';
			$DBUser = 'root';
			$DBPass = '';
			$DBName = 'GPS';
			$DBTable = 'test';

			$readings = array();											//Array to hold points for graph.
			if($conn = mysqli_connect($DBHost, $DBUser, $DBPass, $DBName)){	//Set connection, proceed if successful.
				$qry = "SELECT `trace_data`, `trace_time`, `trace_id` FROM $DBTable ORDER BY `trace_id`";
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
						}
						$readings[]=array($points, $data['trace_time'], $data['trace_id']);
					}
				}
				mysqli_close($conn);
			}else{ 							//Else kill script.
			  die("Connection error: Please Contact an Administrator");
			}
		?>

		var counter = 0;											//Seconds counter.
		function createGraph(){
			var readings = <?php echo json_encode($readings); ?>;	//encoded to json and set as javascript var.
			var svgns = "http://www.w3.org/2000/svg";				//Namespace for svg.
			var chart = document.createElementNS(svgns, "svg:svg"); //Create svg in namespace.
			var index = (counter++)%readings.length;				//Index of readings array for values.  Modulus by array length so it repeats on completion.
			var xNext = xLine = xBuffer = yBuffer = 20; 			//Graph buffer.
			var vOffset = 50+yBuffer;								//Vertical offset for plotting points.	
			var coorStr = '';										//Coordinate string for graph points.
			const hAxisL = 850;										//Horizontal axis ticks lower value.
			const hAxisU = 1150;									//Horizontal axis ticks upper value.
			const hAxisDiv = 2;										//Horizontal axis division count.
			const hAxisInt = (hAxisU-hAxisL)/hAxisDiv;				//Horizontal axis tick interval amount.
			var hAxisSep = 0;										//Tracks horizontal axis tick count.

			for(var i = 0; i < readings[index][0].length; i++){
				var point = readings[index][0][i];					//Sets y axiz point from array.
		    	coorStr += xNext+","+(0-point+yBuffer)+" ";			//Saves to coordinate string to be used to draw line.
				xNext = xLine++;									//Increments xaxis position points.
			}
			xLine-=2;												//Have to subtract 2 since it ends 2 points farther then last plotted line.
			chart.appendChild(createPolyline(coorStr, 0.3, "yellow"));	//Add line to chart.

			for(var i = 0;i<=70;i+=10){ 								//Y-axis visuals at 10 point increments.
				chart.appendChild(createPolyline(((xBuffer-1)+","+(i+yBuffer)+" "+xLine+","+(i+yBuffer)), 0.2, "white")); //Y-axis lines
				chart.appendChild(createLabel(0, (yBuffer+i+1), 5, (-i+" dBm"))); 	//Y-axis labels
			}

			for(var i = xBuffer; i<= xLine;i+=(xLine-xBuffer)/hAxisDiv){			//X-axis visuals.
				chart.appendChild(createPolyline((i+","+yBuffer+" "+i+","+(vOffset+yBuffer+2)), 0.2, "white"));				//X-axis lines
				chart.appendChild(createLabel((i-6), (vOffset+yBuffer*1.5), 5, (hAxisL+(hAxisInt*hAxisSep++)+" MHZ")));		//Xaxis labels
			}
			chart.appendChild(createLabel(((xLine+xBuffer)/2-xBuffer), 15, 6, ("Trace ID: "+readings[index][2])));			//Trace id label
			chart.appendChild(createLabel(((xLine+xBuffer)/2-xBuffer*1.5), (vOffset+yBuffer*2), 5, ("Trace Time: "+readings[index][1]))); 	//Trace time label
			chart.setAttribute("viewBox", "0 0 " + (xLine+xBuffer) + " " + (vOffset+yBuffer*3));											//Sets view box size
			chart.setAttribute("style", "background-color: black;");
			document.getElementById("fraym").src ="data:image/svg+xml;charset=utf-8,"+(new XMLSerializer).serializeToString(chart);  		//Serializes svg so it can be set as image src.
		}

		function createLabel(x,y,s,t){ //Returns label svg. Takes relatively x and y positions, stroke-width, and text string.
			var svgns = "http://www.w3.org/2000/svg";
			var label = document.createElementNS(svgns, "text");	
			label.setAttribute("x", x);
			label.setAttribute("y", y);
			label.setAttribute("font-family", "sans-serif");
			label.setAttribute("font-size", s);
			label.setAttribute("fill", "white");
			label.appendChild(document.createTextNode(t));
			return label;
		}

		function createPolyline(coord,s,c){	//Returns lines svg element. Takes line coordinates, stroke, and line color arguments relatively.
			var svgns = "http://www.w3.org/2000/svg";
			var line = document.createElementNS(svgns, "polyline");
			line.setAttribute("points",coord);
			line.setAttribute("stroke", c);
			line.setAttribute("stroke-width", s);
			line.setAttribute("fill", "none");
			return line;
		}

		//Sets interval for chart to call once a second.
		if (window.addEventListener) {
		   window.addEventListener("load", function(){createGraph(); var timer = setInterval(createGraph,1000);}, false);
		} else if (window.attachEvent) {
		   window.attachEvent("onload", function(){createGraph(); var timer = setInterval(createGraph,1000);});
		}
	</script>
	<title>Chart Test</title>
</head>
<body>
	<h1>Chart:</h1>
	<img id='fraym' />
</body>
</html>