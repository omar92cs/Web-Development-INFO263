<?php
	# Configuration file to create database connections
	# Requests file to manage database queries using the apiCall function
	require_once 'include/config.php';
	require_once 'requests.php';
	
	# URL - a parameter used by the apiCall function to retrieve database results
	$url = "https://api.at.govt.nz/v2/public/realtime/vehiclelocations";
	
	# Creates a new connection to the database, or kills the connection if there is an error
    $connection = new mysqli($hostname, $username, $password, $database);
	if ($connection->connect_error) die($connection->connect_error);

	# Receives the route_short_name from index.php, and performs a query on the database
	# The query receives all trip ids that match the specified route_short_name
	$item = $_POST['route_name'];
	$filter = $connection->query("SELECT trip_id FROM trips, routes WHERE route_short_name='" . $item . "' AND trips.route_id = routes.route_id");
	
	$trips_on_route = array();
	
	# Creates an array of trip_ids from the query above
	while ($row = mysqli_fetch_array($filter)) {
		# Re-initializes the $trip_id variable, so that it can be assigned a new value
		unset($trip_id);
		$trip_id = $row['trip_id'];
		array_push($trips_on_route, $trip_id);  
	}

	
	# Creates an associative array, where the first reference 'tripid' points to the array of trip ids
	$params = array("tripid" => $trips_on_route);
	$results = apiCall($APIKey, $url, $params);
	
	# Initializes new arrays for data management and referencing
	$query_output = array();
	$bus_details = array();
	
	# A for loop that runs through the output of the apiCall function, and creates ...
	# ... an associative array for each bus on the given route. The resulting array ...
	# ... is then stored in a larger array, for easy processing in the map.js file
	foreach ($results as $result) {
		# Decodes the data for the bus
		$item = json_decode($result);
		# Checks if the response is empty (i.e. if the bus is currently on the route)
		if(!empty($item->response)) {
			foreach ($item->response->entity as $var) {
				# Extracts the data, and stores it in the associative bus_details array
				$bus_details += ["start_time" => $var->vehicle->trip->start_time];
				$bus_details += ["id" => $var->vehicle->vehicle->id];
				$bus_details += ["latitude" => $var->vehicle->position->latitude];
				$bus_details += ["longitude" => $var->vehicle->position->longitude];
				$bus_details += ["timestamp" => $var->vehicle->timestamp];
				# Stores the bus_details array in the query_output array for later use
				array_push($query_output, $bus_details);
				# Re-initializes the array for the next iteration in the for loop
				$bus_details = array(); 
			}
		}
	}
	# Encodes the query_output array to send back to the index.php file for further procesing
	$query_results = json_encode($query_output);
	echo $query_results;
	
?>