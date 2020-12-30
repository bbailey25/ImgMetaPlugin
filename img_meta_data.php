<?php
/*
Plugin Name: Image Meta Data
Description: A plugin used to add meta data to images
Author: Blake Bailey
Version: 0.1
*/

add_action('admin_menu', 'test_plugin_setup_menu');
add_action('wp_ajax_my_action', 'my_action');
add_action('wp_ajax_my_action2', 'my_action2');
add_action('wp_ajax_my_action3', 'my_action3');
add_action('wp_ajax_my_action4', 'my_action4');
add_action('wp_ajax_my_action5', 'my_action5');
add_action('wp_ajax_my_action6', 'my_action6');
add_action('wp_ajax_my_action7', 'my_action7');

add_action('admin_enqueue_scripts', 'my_enqueue_style');

function my_enqueue_style()
{
    wp_enqueue_style('plugin_style', WP_PLUGIN_URL . '/' .str_replace(basename(__FILE__), "", plugin_basename(__FILE__)) . 'resources/stylesheet.css');
}
 
function test_plugin_setup_menu()
{
    add_menu_page( 'Image Meta Data Plugin Page', 'Image Meta Data Plugin', 'manage_options', 'test-plugin', 'test_init' );
}

function my_action() {

    $linkList = preg_split("/\r\n|\n|\r/", $_POST['business_links']);

    global $wpdb;
    $wpdb->insert('wp_businesses', array(
        'business_name' => $_POST['business_name'],
        'business_alt_txt' => $_POST['business_alt_txt'],
        'business_phone' => $_POST['business_phone'], // ... and so on
        'business_location' => $_POST['business_location']
    ));

    foreach($linkList as $link) {

        $sql = "INSERT INTO wp_business_links (link, business_id) VALUES ('$link', (SELECT business_id FROM wp_businesses WHERE business_name='".$_POST['business_name']."'));";

        $wpdb->query($sql);
    };
    wp_die(); // this is required to terminate immediately and return a proper response
}

function my_action2() {
    global $wpdb;
    $sql = "SELECT * FROM wp_businesses LEFT JOIN wp_business_links ON wp_businesses.business_id = wp_business_links.business_id WHERE business_name = '".$_POST['business_name']."';";
    $results = $wpdb->get_results($sql);
    echo json_encode($results);
    wp_die();
}

function my_action3() {
    global $wpdb;
    $sql = "SELECT * FROM wp_businesses WHERE business_name = '".$_POST['business_name']."';";
    echo($wpdb->query($sql));
    wp_die();
}

function my_action4() {
    global $wpdb;

    // Delete business links first since they are the foreign key
    $sql = "DELETE FROM wp_business_links WHERE business_id = (SELECT business_id FROM wp_businesses WHERE business_name='".$_POST['business_name']."');";
    $wpdb->query($sql);

    // Delete the business
    $wpdb->delete('wp_businesses', array('business_name' => $_POST['business_name']));
    
    wp_die();
}

function my_action5() {
    global $wpdb;

    $wpdb->insert('wp_locations', array(
        'location_name' => $_POST['location_name'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude'] // ... and so on
    ));

    $results = $wpdb->get_col("SELECT location_name FROM wp_locations;");
    echo json_encode($results);

    wp_die();
}

function my_action6() {
    global $wpdb;
    $sql = "SELECT * FROM wp_locations WHERE location_name = '".$_POST['location_name']."';";
    echo($wpdb->query($sql));
    wp_die();
}

function my_action7() {
    global $wpdb;
    $wpdb->delete('wp_locations', array('location_name' => $_POST['location_name']));

    $results = $wpdb->get_col("SELECT location_name FROM wp_locations;");
    echo json_encode($results);
    wp_die();
}

function getLocationInfo($locationName) {
    global $wpdb;
    $sql = "SELECT location_name, latitude, longitude FROM wp_locations WHERE location_name = '$locationName';";
    $results = $wpdb->get_results($sql);

    return $results;
}
 
function test_init()
{
    test_handle_post();

?>
    <body>
        <h1>Hello World!</h1>
        <h2>Upload a File</h2>
        <br>
        <div style="display: flex;">
            <div style="flex:1;">
                <h2><u>Add a Location</u></h2>
                <form id='locationForm'>
                    City Name: <input type='text' id='cityName' required /> <br>
                    State Name: <input type='text' id='stateName' required /> <br>
                    Latitude: <input type='number' id='latitude' required /> <br>
                    Longitude: <input type='number' id='longitude' required /> <br>
                </form>
                <br>
                <button type="button" id="addLocation" onclick="addLocationFunc()">
                    Add Location
                </button>
            </div>
            <div style="flex:1;">
                <h2><u>Delete a Location</u></h2>
                <ul id='location_list' class='location_list'>
                <?php
                    $classes = array('loc_list_item');
                    populateLocationOptions($classes, $type='li');
                ?>
                </ul>
                <button type="button" id=deleteLocation" onclick="removeLocationFunc()">
                    Delete
                </button>
            </div>
        </div>
        <div>
            <h2><u>Add a Business</u></h2>
            <form id='formTest'>
                Business Name (required): <input type='text' id='businessName' required /> <br>
                Alt-txt: <input type='text' id='altText' required /> <br>
                Phone Number: <input type="text" id="phone" required /><br>
                Location <select id="locationsSelect">
                    <option>NO LOCATION DATA</option>
                    <?php
                        populateLocationOptions();
                    ?>
                </select>
                <br>
                Link: <input type="text" id="link"/> 
                    <button type="button" id="addButton">
                        Add
                    </button> 
                    <button type="button" id="clearButton">
                        Clear Links
                    </button> <br>
            </form>
            
            Description : <textarea type="text" id = "description" style="width: 300px; height: 200px; vertical-align:top;" readonly></textarea> <br>
            <button type="button" id="addEntry" onclick="addEntryFunc()">
                Add Entry
            </button>
        </div>
        <br>

        <h2><u>Upload images (8mb MAX)</u></h2>

        <select id="businesses">
            <!-- <option value="" selected disabled hidden>Business</option> -->
            <option>NO DATA</option>
            <?php
                global $wpdb;
                $results = $wpdb->get_col("SELECT business_name FROM wp_businesses;");
                foreach ($results as $result) {
                    echo "<option value='$result'>$result</option>";
                }
            ?>
        </select> 
        <button type="button" id="deleteEntryBtn" onclick="deleteEntryFunc()">
            Delete Entry
        </button>

        <!-- Form to handle the upload - The enctype value here is very important -->
        <form  method="post" enctype="multipart/form-data">
            Business Name: <input id='businessNameOutput' name='businessNameOutput' readonly/> <br>
            Alt-txt: <input id='altTextOutput' name='altTextOutput' readonly/> <br>
            Phone Number: <input id='phoneOutput' name='phoneOutput' readonly/> <br>
            Location: <select id="locationsSelectOutput" name="locationsSelectOutput">
                    <option>NO LOCATION DATA</option>
                    <?php
                        populateLocationOptions();
                    ?>
                </select> <br>
            Links: <input id='linksOutput' readonly/> <br>
            Description: <textarea id='descriptionOutput' name='descriptionOutput' style="width: 300px; height: 200px; vertical-align:top;" readonly></textarea> <br>
            <input type='file' name='test_upload_img[]' multiple='multiple'></input>
            <?php submit_button('Upload') ?>
        </form>
    </body>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script>
        var form = document.getElementById('formTest');
        // var locationForm = document.getElementById('locationForm');
        var addButton = document.getElementById('addButton');
        var clearButton = document.getElementById('clearButton');

        var latitude = document.getElementById('latitude');
        var longitude = document.getElementById('longitude');
        var businessName = document.getElementById('businessName');
        var altText = document.getElementById('altText');
        var phone = document.getElementById('phone');
        var link = document.getElementById('link');
        var description = document.getElementById('description');
        var addEntrySelect = document.getElementById('businesses');
        var links = "";
        var descriptionVar = "";

        function writeDescription() {

            description.value = altText.value   + '\n' +
                                businessName.value  + '\n' +
                                phone.value     + '\n' +
                                links;
        }

        function addEntryFunc() {
            // Ensure no blank business names get accepted
            businessName.value = businessName.value.trim();
            if (businessName.value != "")
            {
                if (!databaseEntryExists())
                {
                    var option = document.createElement("option");
                    option.value = businessName.value;
                    option.text = businessName.value;
                    addEntrySelect.add(option);
                    addDatabaseEntry();
                    businessName.value = "";
                    altText.value = "";
                    phone.value = "";
                    links = "";
                    writeDescription();
                }
                else
                {
                    alert('Business name has already been used. Try again.');
                }
            }
            else
            {
                alert('Business name can\'t be blank. Try again.');
            }
        }

        function addLocationFunc() {
            // Ensure no blank locations get accepted
            var cityName = document.getElementById('cityName');
            var stateName = document.getElementById('stateName');
            var latitude = document.getElementById('latitude');
            var longitude = document.getElementById('longitude');
            var locationSelects = document.getElementById('locationsSelect');
            var locationSelectsOutput = document.getElementById('locationsSelectOutput');

            if ((cityName.value.trim()  != "") && 
                (stateName.value.trim() != "") && 
                (latitude.value.trim()  != "") && 
                (longitude.value.trim() != ""))
            {
                var locationName = cityName.value.trim() + ", " + stateName.value.trim();
                if (!locationEntryExists(locationName))
                {
                    addLocationDbEntry(locationName, latitude.value, longitude.value);
                    cityName.value      = "";
                    stateName.value     = "";
                    latitude.value      = "";
                    longitude.value     = "";
                }
                else
                {
                    alert('Location has already been used. Try again.')
                }
            }
            else
            {
                alert('No Location Information Can Be Blank. Try again.');
            }
        }

        function removeLocationFunc() {

            var location_list = document.getElementById("location_list");
            var listItems = location_list.getElementsByTagName('li');
            var selectedItem = '';

            for (var i = 0; i < listItems.length; i++)
            {
                if (listItems[i].classList.contains('highlight'))
                {
                    selectedItem = listItems[i].textContent;
                }
            }

            if (selectedItem != '')
            {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'my_action7',
                        location_name : selectedItem
                    },
                    success: function(data) {
                        locations = JSON.parse(data);
                        clearLocationData();
                        repopulateLocationData(locations);
                    },
                    error: function() {
                        alert('boo');
                    }
                });
            }
        }

        function clearLocationData()
        {
            var locationsSelect = document.getElementById('locationsSelect');
            var location_list = document.getElementById('location_list');
            var locationsSelectOutput = document.getElementById('locationsSelectOutput');

            while (locationsSelect.firstChild)
            {
                locationsSelect.removeChild(locationsSelect.firstChild);
            }

            while (location_list.firstChild)
            {
                location_list.removeChild(location_list.firstChild);
            }

            while (locationsSelectOutput.firstChild)
            {
                locationsSelectOutput.removeChild(locationsSelectOutput.firstChild);
            }
        }

        function repopulateLocationData(locations=null)
        {
            var locationsSelect = document.getElementById('locationsSelect');
            var locationsSelectOutput = document.getElementById('locationsSelectOutput');
            var location_list = document.getElementById('location_list');

            var option = document.createElement('option');
            option.text = "NO LOCATION DATA";
            locationsSelect.add(option);
            var option2 = document.createElement('option');
            option2.text = "NO LOCATION DATA";
            locationsSelectOutput.add(option2);

            if (locations!=null)
            {
                for (i = 0; i < locations.length; i++)
                {
                    var option = document.createElement('option');
                    option.text = locations[i];
                    locationsSelect.add(option);
                    var option2 = document.createElement('option');
                    option2.text = locations[i];
                    locationsSelectOutput.add(option2);
                    var li = document.createElement('li');
                    li.appendChild(document.createTextNode(locations[i]));
                    li.setAttribute('class', 'loc_list_item');
                    location_list.appendChild(li);
                }
            }
        }

        function addLocationDbEntry(locationName, latitude, longitude) {

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_action5',
                    location_name : locationName,
                    latitude : latitude,
                    longitude : longitude
                },
                success: function(data) {
                    locations = JSON.parse(data);
                    clearLocationData();
                    repopulateLocationData(locations);
                },
                error: function() {
                    alert('boo');
                }
            });
        }

        function deleteEntryFunc() {

            var business = addEntrySelect.options[addEntrySelect.selectedIndex].text;
            if(business != "NO DATA")
            {
                removeDatabaseEntry(business);
                clearEntryData();
                $("#businesses option:selected").remove();
            }
        }

        function databaseEntryExists() {
            var someBool = false;
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_action3',
                    business_name : businessName.value
                },
                async: false,               //ensures the ajax request finishes before continuing
                success: function(data) {
                    if (data > 0) {
                        someBool = true;
                    }
                },
                error: function() {
                    alert('boo');
                }
            });

            return someBool;
        }

        function locationEntryExists(locationName) {
            var someBool = false;
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_action6',
                    location_name : locationName
                },
                async: false,
                success: function(data) {
                    if (data > 0) {
                        someBool = true;
                    }
                },
                error: function() {
                    alert('boo');
                }
            });

            return someBool;
        }

        function addDatabaseEntry() {

            var locationSelect = document.getElementById('locationsSelect');
            var selectedLocation = locationSelect.options[locationSelect.selectedIndex].value;

            // Remove the trailing new line
            links = links.replace(/\n$/, '');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_action',
                    business_name : businessName.value,
                    business_alt_txt : altText.value,
                    business_phone : phone.value,
                    business_links : links,
                    business_location: selectedLocation
                },
                error: function() {
                    alert('boo');
                }
            });
        }

        function removeDatabaseEntry(business) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_action4',
                    business_name : business
                },
                error: function() {
                    alert('boo');
                }
            });
        }

        function returnDatabaseEntry(business) {

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { 
                    action: 'my_action2' ,
                    business_name : business
                },
                success: function(data) {
                    something = JSON.parse(data);

                    // This will put the location associated with the selected
                    // business at the top of a drop down. This will allow the
                    // user to decide if they want the location associated or
                    // have no location on the selected images.
                    var option, i = 0;
                    var locationOutput = document.getElementById('locationsSelectOutput');
                    while (option = locationOutput.options[i++])
                    {
                        if(option.value == something[0]['business_location'])
                        {
                            option.selected = true;
                            break;
                        }
                    }

                    document.getElementById('businessNameOutput').value = something[0]['business_name'];
                    document.getElementById('altTextOutput').value = something[0]['business_alt_txt'];
                    document.getElementById('phoneOutput').value = something[0]['business_phone'];

                    linkVal = "";
                    descriptionVal =
                        something[0]['business_alt_txt'] + '\n' +
                        something[0]['business_name'] + '\n' +
                        something[0]['business_phone'] + '\n';

                    for(var i = 0; i < something.length; i++) {
                        var obj = something[i].link;
                        linkVal = linkVal + obj + ', ';
                        descriptionVal = descriptionVal + obj + '\n';
                    }

                    // Remove trailing comma and space
                    linkVal = linkVal.replace(/, $/, '');
                    document.getElementById('linksOutput').value = linkVal;
                    document.getElementById('descriptionOutput').value = descriptionVal;
                }
            });
        }

        function clearEntryData () {
            document.getElementById('businessNameOutput').value = "";
            document.getElementById('altTextOutput').value = "";
            document.getElementById('phoneOutput').value = "";
            document.getElementById('linksOutput').value = "";
            document.getElementById('descriptionOutput').value = "";
        }

        $('.location_list').on('click', 'li', function(){
            $('.highlight').removeClass('highlight');
            $(this).addClass('highlight');
        });

        form.oninput = function () {

            writeDescription();
        };

        addButton.onclick = function () {
            if (link.value.trim() != "") {
                links = links + link.value + '\n';
                link.value = "";
                writeDescription();
            }
        };

        clearButton.onclick = function () {
            links = "";
            link.value = "";
            writeDescription();
        };

        document.getElementById('businesses').onchange = function () {
            var business = addEntrySelect.options[addEntrySelect.selectedIndex].text;

            if (business == "NO DATA") {
                clearEntryData();
                document.getElementById('locationsSelectOutput').options[0].selected = true;
            }
            else {
                returnDatabaseEntry(business);
            }
        };
    </script>
<?php
}
 
function test_handle_post()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $files = $_FILES['test_upload_img'];
        foreach ($files['name'] as $key => $value)
        {
            if ($files['name'][$key])
            {
                $file = array (
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key],
                );
                
                $exifLatitude       = '';
                $exifLatitudeRef    = '';
                $exifLongitude      = '';
                $exifLongitudeRef   = '';

                if (is_callable('exif_read_data'))
                {
                    $exifData = exif_read_data($file['tmp_name']);
                    if (!empty($exifData['GPSLatitude'])) {
                        $exifLatitude = $exifData['GPSLatitude'];
                    }
                    if (!empty($exifData['GPSLatitudeRef'])) {
                        $exifLatitudeRef = trim($exifData['GPSLatitudeRef']);
                    }
                    if (!empty($exifData['GPSLongitude'])) {
                        $exifLongitude = $exifData['GPSLongitude'];
                    }
                    if (!empty($exifData['GPSLongitudeRef'])) {
                        $exifLongitudeRef = trim($exifData['GPSLongitudeRef']);
                    }
                }

                $_FILES = array("upload_file" => $file);
                $attachment_id = media_handle_upload("upload_file", 0);

                if (is_wp_error($attachment_id))
                {
                    // There was an error uploading the image
                    echo "Error adding file";
                }

                if ($attachment_id > 0)
                {

                    $metadata = wp_get_attachment_metadata($attachment_id, true);
                    $locationSelection = $_POST['locationsSelectOutput'];

                    if (!empty($exifLatitude) &&
                        !empty($exifLatitudeRef) &&
                        !empty($exifLongitude) &&
                        !empty($exifLongitudeRef))
                    {
                        // image has exif location data so we just want to use
                        // that data no matter what location the user selects
                        $metadata['image_meta']['latitude'] = $exifLatitude;
                        $metadata['image_meta']['latitude_ref'] = $exifLatitudeRef;
                        $metadata['image_meta']['longitude'] = $exifLongitude;
                        $metadata['image_meta']['longitude_ref'] = $exifLongitudeRef;
                    }
                    else
                    {
                        // image does not have exif location data
                        if ($locationSelection != 'NO LOCATION DATA')
                        {
                            // use location for post location data
                            $locationInfo = getLocationInfo($locationSelection);

                            $metadata['image_meta']['latitude'] = format_dec_to_dms($locationInfo[0]->latitude);
                            $metadata['image_meta']['latitude_ref'] = direction_char($locationInfo[0]->latitude, 'latitude');
                            $metadata['image_meta']['longitude'] = format_dec_to_dms($locationInfo[0]->longitude);
                            $metadata['image_meta']['longitude_ref'] = direction_char($locationInfo[0]->longitude, 'longitude'); 
                        }
                        else
                        {
                            // do nothing if the user selected
                            // not to add location data
                        }
                    }

                    wp_update_attachment_metadata($attachment_id, $metadata);

                    if (isset($_REQUEST['businessNameOutput'])) {
                        $businessName = $_REQUEST['businessNameOutput'];
                    }
        
                    if (isset($_REQUEST['altTextOutput'])) {
                        $businessAltText = $_REQUEST['altTextOutput'];
                    }
        
                    if (isset($_REQUEST['descriptionOutput'])) {
                        $businessDescription = $_REQUEST['descriptionOutput'];
                    }
                    
                    $my_image_meta = array(
                        // Specify the image (ID) to be updated
                        'ID' => $attachment_id,
                        // Set image title
                        'post_title' => $businessName,
                        // Set image caption (excerpt)
                        'post_excerpt' => $businessName,
                        // Set image description (content)
                        'post_content' => $businessDescription
                    );

                    // This adds alternative text
                    update_post_meta($attachment_id, '_wp_attachment_image_alt', $businessAltText);
                        
                    // Set the image meta (e.g. Title, Excerpt, Content)
                    wp_update_post($my_image_meta);
                }
            }
        }
    }
    
}

function format_dec_to_dms($coordinateDec)
{
    $coordinateDec = abs($coordinateDec);
    $degrees = floor($coordinateDec);
    $minutes = floor(($coordinateDec - $degrees) * 60);
    $seconds = (($coordinateDec - $degrees - ($minutes / 60)) * 3600);
    return array (
        $degreesExif = $degrees . "/1",
        $minutesExif = $minutes . "/1",
        $secondsExif = (round($seconds, 4) * 10000) . "/10000"
    );
}

function direction_char($coordinateValue, $type)
{
    if ($coordinateValue >= 0)
    {
        switch($type)
        {
            case 'latitude':
                return 'N';
                break;
            case 'longitude':
                return 'E';
                break;
        }
    }
    else
    {
        switch($type)
        {
            case 'latitude':
                return 'S';
                break;
            case 'longitude':
                return 'W';
                break;
        }
    }
}

function populateLocationOptions($classes = null, $type='option')
{
    global $wpdb;
    $cities = $wpdb->get_col("SELECT location_name FROM wp_locations;");
    
    if ($classes == null)
    {
        foreach ($cities as $city) {
            echo "<$type value='$city'>$city</$type>";
        }
    }
    else
    {
        $classString = '';
        foreach ($classes as $class)
        {
            $classString .= $class . ' ';
        }
        foreach ($cities as $city) {
            echo "<$type value='$city' class='$classString'>$city</$type>";
        }
    }
}

function business_data_create_db() 
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   
    // Create the businesses table
    $table_name = $wpdb->prefix . 'businesses';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        business_id INTEGER NOT NULL AUTO_INCREMENT,
        business_name TEXT NOT NULL,
        business_alt_txt TEXT NOT NULL,
        business_phone TEXT NOT NULL,
        business_location TEXT NOT NULL,
        PRIMARY KEY (business_id)
        ) $charset_collate;";
    dbDelta( $sql );

    // Create the links table
    $links_table_name = $wpdb->prefix . 'business_links';
    $sql2 = "CREATE TABLE IF NOT EXISTS $links_table_name (
        link_id INTEGER NOT NULL AUTO_INCREMENT,
        link TEXT,
        business_id INTEGER NOT NULL,
        FOREIGN KEY(business_id) REFERENCES $table_name(business_id),
        PRIMARY KEY (link_id)
        ) $charset_collate;";
    dbDelta( $sql2 );

    // Create the locations table
    $locations_table_name = $wpdb->prefix . 'locations';
    $sql3 = "CREATE TABLE IF NOT EXISTS $locations_table_name (
        location_id INTEGER NOT NULL AUTO_INCREMENT,
        location_name TEXT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        PRIMARY KEY (location_id)
        ) $charset_collate;";
    dbDelta( $sql3 );
}
register_activation_hook( __FILE__, 'business_data_create_db' );
?>