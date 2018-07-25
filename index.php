<!DOCTYPE html>

<html>
    <head>
  		<link rel = "stylesheet" href = "/css/master.css">
        <style>
    		/* Re-adjusting the overall map size*/
    		#map {
        		height: 600px;   /* Height specification for the map */
        		width: 100%;     /* Width specification for the map */
       		}
        </style>
    </head>
    <body>
        <?php
        	# Used by the header file
	        $active = "home";
		
		    # Imports config file to provide SQL connection data, and page header and footer
		    require_once 'include/header.php';
		    require_once 'include/config.php';
		    require_once 'requests.php';
		
		    # Connects to the database
	        $conn = new mysqli($hostname, $username, $password, $database);
		    if ($conn->connect_error) die($conn->connect_error);
	
	    ?>

		<script>
			var map;
			var infowindow;
			var infoWindows = [];
			var bounds;
			var markers = [];
			var timer;
			var route_s_name
				
			/* Once an input is selected, the function below is executed */	
		    $(document).ready(function() {
				$('#drop_down').change(function() {
					/* Gets the selected route */
					var selected_route = $('#drop_down option:selected');
					route_s_name = selected_route.val();
					/* Resets the refresh timer every time a new route is selected */
					clearTimeout(timer);
					/* Performs an SQL request and displays the returned data onto the map */
					post();
				});
			});			
		</script>	
		
	    <form>
	      <p>
	         <?php
		         # Label for the drop down box that contains the routes       
		         echo "<label>Available Routes:  </label>";
		         
		         # The select query to gather the information for the drop down box
				 $filter = $conn->query("SELECT DISTINCT route_short_name FROM routes");
				 echo "<select id='drop_down'>";
				 
				 # Adds an empty option to the drop down box
				 echo '<option selected="selected" value="'." ".'">'.'</option>';
				 
				 # Populates the route-selection drop down box with all available routes	 
				 while ($row = mysqli_fetch_array($filter)) {
		              unset($route_short_name);
		              $route_short_name = $row['route_short_name'];
		              echo '<option value="'.$route_short_name.'">'.$route_short_name.'</option>';      
				 }
		         echo "</select>";   
		         
		         echo "<label id = 'no_bus_label' > </label>";
			 ?>
	      </p>
	    </form>
    
	    <div id="map"></div>
	    
	    <script async defer
	    	src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCnRgdLoZ68boj0j2s4ysgF8QpA2MfOAdY&callback=initMap">
	    </script>
	    
	    <script 
	    	src="scripts/map.js">
	    </script>
    
    </body>
</html>


