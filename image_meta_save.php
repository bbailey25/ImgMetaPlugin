<?php
/*
Plugin Name: Image Meta Save
Description: A plugin used to add meta data to images and upload them to the media library. The meta data can be saved together as a preset in a database to be used over and over.
Author: Blake Bailey
License: GPL2+
Version: 1.0
*/

/*
Image Meta Save is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Image Meta Save is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Image Meta Save. If not, see <http://www.gnu.org/licenses/>.
*/

require_once(ABSPATH . 'wp-content/plugins/image_meta_data/ajax_functions.php');
require_once(ABSPATH . 'wp-content/plugins/image_meta_data/admin_functions.php');

add_action('admin_menu', 'img_meta_setup_menu');
register_activation_hook( __FILE__, 'data_create_db' );

function enqueue_external_files()
{
    // Adds the external css file
    wp_enqueue_style('plugin_style', 
                     WP_PLUGIN_URL . '/' .str_replace(basename(__FILE__), 
                     "",
                     plugin_basename(__FILE__)) . 'css/admin.css');

    // Adds jquery and external js file
    wp_enqueue_script('jquery');
    wp_enqueue_script('plugin_script',
                      WP_PLUGIN_URL . '/' .str_replace(basename(__FILE__),
                      "",
                      plugin_basename(__FILE__)) . 'js/admin.js', 
                      array('jquery'), '1.0', true);
}
 
function img_meta_setup_menu()
{
    $my_page = add_menu_page('Image Meta Save', 
                             'Image Meta Save',
                             'manage_options',
                             'image-meta-save',
                             'img_meta_init' );

    // Load the JS and CSS conditionally to avoid loading on other admin pages
    add_action('load-' . $my_page, 'load_admin_scripts_and_styles');
}

function load_admin_scripts_and_styles()
{
    add_action('admin_enqueue_scripts', 'enqueue_external_files');
}
 
function img_meta_init()
{
    img_meta_handle();

?>
    <body>
        <div class='header_div'>        
            <h1>Image Meta Save<br></h1>
            <h4>
                Upload images to the media library with saved meta data information
            </h4>
        </div>
        <hr class='line_divider'/>
        <div class='location_div'>
            <div>
                <h2><u>Add a Location</u></h2>
                <form id='locationform'>
                    City Name: <input type='text' id='cityName' required /> <br>
                    State Name: <input type='text' id='stateName' required /> <br>
                    Latitude: <input type='number' id='latitude' required /> <br>
                    Longitude: <input type='number' id='longitude' required /> <br>
                </form>
                <br>
                <button type="button" id="addLocationBtn">
                    Add Location
                </button>
            </div>
            <div>
                <h2><u>Delete a Location</u></h2>
                <ul id='locationList' class='location_list'>
                <?php
                    $classes = array('loc_list_item');
                    populateLocationOptions('li', $classes);
                ?>
                </ul>
                <button type="button" id="deleteLocationBtn">
                    Delete
                </button>
            </div>
        </div>
        <hr class='line_divider'/>
        <div class='preset_div'>
            <div>
                <h2><u>Add a Preset</u></h2>
                <form id='formTest'>
                    Name (required): <input type='text' id='entryName' required /> <br>
                    Alt-txt: <input type='text' id='altText' /> <br>
                    Phone Number: <input type="text" id="phone" /><br>
                    Location <select id="locationsSelect">
                        <option>NO LOCATION DATA</option>
                        <?php
                            populateLocationOptions('option');
                        ?>
                    </select>
                    <br>
                    Link: <input type="text" id="link"/> 
                        <button type="button" id="addLinkBtn">
                            Add
                        </button> 
                        <button type="button" id="clearLinksBtn">
                            Clear Links
                        </button> <br>
                </form>
                Description : <textarea type="text" id = "description" style="width: 300px; height: 200px; vertical-align:top;" readonly></textarea> <br>
                <button type="button" id="addEntryBtn"">
                    Add Entry
                </button>
            </div>
            <div>
                <h2><u>Upload images (8mb MAX)</u></h2>
                <select id="entriesSelect">
                    <option>NO DATA</option>
                    <?php
                        global $wpdb;
                        $results = $wpdb->get_col("SELECT entry_name FROM wp_img_meta_locations;");
                        foreach ($results as $result) {
                            echo "<option value='$result'>$result</option>";
                        }
                    ?>
                </select> 
                <button type="button" id="deleteEntryBtn">
                    Delete Entry
                </button>

                <!-- form to handle the upload - The enctype value here is very important -->
                <form  method="post" enctype="multipart/form-data">
                    Name: <input id='entryNameOutput' name='entryNameOutput' readonly/> <br>
                    Alt-txt: <input id='altTextOutput' name='altTextOutput' readonly/> <br>
                    Phone Number: <input id='phoneOutput' name='phoneOutput' readonly/> <br>
                    Location: <select id="locationsSelectOutput" name="locationsSelectOutput">
                            <option>NO LOCATION DATA</option>
                            <?php
                                populateLocationOptions('option');
                            ?>
                        </select> <br>
                    Links: <input id='linksOutput' readonly/> <br>
                    Description: <textarea id='descriptionOutput' name='descriptionOutput' style="width: 300px; height: 200px; vertical-align:top;" readonly></textarea> <br>
                    <input type='file' name='test_upload_img[]' multiple='multiple'></input>
                    <?php submit_button('Upload') ?>
                </form>
            </div>
        </div>
    </body>
<?php
}

function data_create_db() 
{
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
   
    // Create the preset table
    $table_name = $wpdb->prefix . 'img_meta_presets';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        entry_id INTEGER NOT NULL AUTO_INCREMENT,
        entry_name TEXT NOT NULL,
        entry_alt_txt TEXT NOT NULL,
        entry_phone TEXT NOT NULL,
        entry_location TEXT NOT NULL,
        PRIMARY KEY (entry_id)
        ) $charset_collate;";
    dbDelta( $sql );

    // Create the links table
    $links_table_name = $wpdb->prefix . 'img_meta_links';
    $sql2 = "CREATE TABLE IF NOT EXISTS $links_table_name (
        link_id INTEGER NOT NULL AUTO_INCREMENT,
        link TEXT,
        entry_id INTEGER NOT NULL,
        FOREIGN KEY(entry_id) REFERENCES $table_name(entry_id),
        PRIMARY KEY (link_id)
        ) $charset_collate;";
    dbDelta( $sql2 );

    // Create the img_meta_locations table
    $locations_table_name = $wpdb->prefix . 'img_meta_locations';
    $sql3 = "CREATE TABLE IF NOT EXISTS $locations_table_name (
        location_id INTEGER NOT NULL AUTO_INCREMENT,
        location_name TEXT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        PRIMARY KEY (location_id)
        ) $charset_collate;";
    dbDelta( $sql3 );
}
?>