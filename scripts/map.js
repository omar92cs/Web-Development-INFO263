/* Creates a new instance of a Google Map and centres it over the middle of Auckland */
function initMap() {
	map = new google.maps.Map(document.getElementById('map'), {
		 zoom: 10,
		 center: new google.maps.LatLng(-36.846323, 174.771978)
	});
	bounds = new google.maps.LatLngBounds();
};

/*Adding information to the marker label window*/
function infowindowAdd(start_time, id, latitude, longitude, timestamp, infowincontent) {
	/* Sets the element as a bold element on the info window */
    var strong = document.createElement('strong');
    /* Creates a string with the Bus ID and puts it on the info window */
    strong.textContent = "Bus ID: " + id;
    infowincontent.appendChild(strong);
    infowincontent.appendChild(document.createElement('br'));

	/* Sets the element as a plaintext element on the info window */
    var lat = document.createElement('text');
    /* Creates a string with the Latitude and puts it on the info window */
    lat.textContent = "Latitude: " + latitude;
	infowincontent.appendChild(lat);
	infowincontent.appendChild(document.createElement('br'));

	/* Sets the element as a plaintext element on the info window */
	var lon = document.createElement('text');
	/* Creates a string with the longitude and puts it on the info window */
    lon.textContent = "Latitude: " + longitude;
	infowincontent.appendChild(lon);
	infowincontent.appendChild(document.createElement('br'));

	/* Sets the element as a plaintext element on the info window */
	var start = document.createElement('text');
	/* Creates a string with the Start Time and puts it on the info window */
    start.textContent = "Start Time: " + start_time;
	infowincontent.appendChild(start);
	infowincontent.appendChild(document.createElement('br'));

	/* Splits the timestamp into hours, minutes and seconds */
	var hours = timestamp.getHours();
	var minutes = timestamp.getMinutes();
	var seconds = timestamp.getSeconds();
	
	if (minutes < 10) {
		minutes = '0' + minutes;
	}	
	if (seconds < 10) {
		seconds = '0' + seconds;
	}
								
	var real_time = hours + ':' + minutes + ':' + seconds;
    var text = document.createElement('text1');
    /* Displays the split timestamp on the info window */
    text.textContent = "Timestamp: " + real_time;
    infowincontent.appendChild(text);			            
}

/*Removing the current showing markers on the map*/
function clearMarkers() {
	/* for each marker on the map, set each marker to null
	then empty the markers list */
	for(i = 0; i < markers.length; i++) {
		markers[i].setMap(null);
	};
	markers = [];
};

/*Function that closes the the marker label windows*/
function closeAllInfoWindows() {
	for (var i=0;i<infoWindows.length;i++) {
		infoWindows[i].close();
	}
}

/*The main function that pulls data from the Auckland API where they are organised
in an array, markers are added, then information is added to the marker label
window, then the map zoom and positioning is re-adjusted to fit the markers*/		
function post() {
	/* if the selected route_short_name is blank, do nothing */
	if (route_s_name != ' ') {
		/* posts the route_short_name to sql_request_processing to query the AKL database */
		$.post("sql_request_processing.php", {route_name: route_s_name}, function(data) {
			/* $.post receives a JSON encoded array which is parsed into a js array */
			var results = JSON.parse(data);
			/* if results is not empty, display the data */
			if (results.length != 0) {
				bounds = new google.maps.LatLngBounds();
				clearMarkers();
				var index;
				/* for each vehicle in results... */
				for (index = 0; index < results.length; index++) {	
					/* assigns the relevant data to variables */		
					var start_time = results[index]["start_time"];
					var id = results[index]["id"];
					var latitude = results[index]["latitude"];
					var longitude = results[index]["longitude"];
					var timestamp = new Date(results[index]["timestamp"]*1000);
					var point = new google.maps.LatLng(latitude, longitude);
					var infowincontent = document.createElement('div');
					var icon = id;
					
					/* Sends all relevant info to be displayed on info windows */
		            infowindowAdd(start_time, id, latitude, longitude, timestamp, infowincontent);
		            
		            // Creates a marker
		            var marker = new google.maps.Marker({
		                map: map,
		                position: point,
		                label: icon
		            });
		            
		            // Push the marker to the page
		            markers.push(marker);
		            // Create a new InfoWindow instance
		            infowindow = new google.maps.InfoWindow();
		            // Sets the info window to popup on marker click
		            // and close on click of another marker or the 'X'
					google.maps.event.addListener(marker,'click', (function(marker,infowincontent,infowindow){ 
					    return function() {
					    	closeAllInfoWindows();
					        infowindow.setContent(infowincontent);
					        infowindow.open(map,marker);
					        infoWindows.push(infowindow);
					    };
					})(marker,infowincontent,infowindow));
					//extends the bounds to fit each marker in the map window
					bounds.extend(marker.getPosition());
				};
				// calls the map mto fit the bounds
				map.fitBounds(bounds);
				document.getElementById('no_bus_label').innerHTML = ' ';

			} else { // When there are no buses on the route, display to user
				var string = "(There are no buses currently on Route ";
				string += route_s_name + ')';
				document.getElementById('no_bus_label').innerHTML = string;
			}
		});
	};
	timer = setTimeout(post, 30000); //Refreshing the page every 30 seconds
};