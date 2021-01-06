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

require_once(plugin_dir_path(__FILE__) . 'ajax_functions.php');
require_once(plugin_dir_path(__FILE__) . 'admin_functions.php');

add_action('admin_menu', 'imgMD_setup_menu');
register_activation_hook( __FILE__, 'imgMD_create_db' );

function imgMD_enqueue_external_files()
{
    // Adds the external css file
    wp_enqueue_style('plugin_style', plugins_url('css/admin.css', __FILE__));

    // Adds jquery and external js file
    wp_enqueue_script('jquery');
    wp_enqueue_script('plugin_script', plugins_url('js/admin.js', __FILE__));
}
 
function imgMD_setup_menu()
{
    $my_page = add_menu_page('Image Meta Save', 
                             'Image Meta Save',
                             'manage_options',
                             'image-meta-save',
                             'imgMD_init' );

    // Load the JS and CSS conditionally to avoid loading on other admin pages
    add_action('load-' . $my_page, 'imgMD_load_admin_scripts_and_styles');
}

function imgMD_load_admin_scripts_and_styles()
{
    add_action('admin_enqueue_scripts', 'imgMD_enqueue_external_files');
}
 
function imgMD_init()
{
    imgMD_handle();

?>
    <body>
        <div class='imgMD_header_div'>        
            <h1>Image Meta Save<br></h1>
            <h4>
                Upload images to the media library with saved meta data information
            </h4>
        </div>
        <hr class='imgMD_line_divider'/>
        <div class='imgMD_location_div'>
            <div>
                <h2><u>Add a Location</u></h2>
                <form>
                    City Name: <input type='text' id='imgMD_city_name' required /> <br>
                    State Name: <input type='text' id='imgMD_state_name' required /> <br>
                    Latitude: <input type='number' id='imgMD_latitude' required /> <br>
                    Longitude: <input type='number' id='imgMD_longitude' required /> <br>
                </form>
                <br>
                <button type="button" id="imgMD_add_location_btn">
                    Add Location
                </button>
            </div>
            <div>
                <h2><u>Delete a Location</u></h2>
                <ul id='imgMD_location_list' class='imgMD_location_list'>
                <?php
                    $classes = array('imgMD_location_list_item');
                    imgMD_populateLocationOptions('li', $classes);
                ?>
                </ul>
                <button type="button" id="imgMD_delete_location_btn">
                    Delete
                </button>
            </div>
        </div>
        <hr class='imgMD_line_divider'/>
        <div class='imgMD_preset_div'>
            <div>
                <h2><u>Add a Preset</u></h2>
                <form id='imgMD_add_preset_form'>
                    Name (required): <input type='text' id='imgMD_entry_name' required /> <br>
                    Alt-txt: <input type='text' id='imgMD_alt_text' /> <br>
                    Phone Number: <input type="text" id="imgMD_phone" /><br>
                    Location <select id="imgMD_locations_select">
                        <option>NO LOCATION DATA</option>
                        <?php
                            imgMD_populateLocationOptions('option');
                        ?>
                    </select>
                    <br>
                    Link: <input type="text" id="imgMD_link"/> 
                        <button type="button" id="imgMD_add_link_btn">
                            Add
                        </button> 
                        <button type="button" id="imgMD_clear_links_btn">
                            Clear Links
                        </button> <br>
                </form>
                Description : <textarea type="text" id = "imgMD_description" readonly></textarea> <br>
                <button type="button" id="imgMD_add_entry_btn"">
                    Add Entry
                </button>
            </div>
            <div>
                <h2><u>Upload images (8mb MAX)</u></h2>
                <select id="imgMD_entries_select">
                    <option>NO DATA</option>
                    <?php
                        global $wpdb;
                        $presets_table_name = $wpdb->prefix . 'imgMD_presets';
                        $results = $wpdb->get_col("SELECT imgMD_entry_name FROM $presets_table_name;");
                        foreach ($results as $result) {
                            echo "<option>$result</option>";
                        }
                    ?>
                </select> 
                <button type="button" id="imgMD_delete_entry_btn">
                    Delete Entry
                </button>

                <!-- form to handle the upload - The enctype value here is very important -->
                <form  method="post" enctype="multipart/form-data">
                    Name: <input type='text' id='imgMD_entry_name_upload' name='imgMD_entry_name_upload' readonly/> <br>
                    Alt-txt: <input type='text' id='imgMD_alt_text_upload' name='imgMD_alt_text_upload' readonly/> <br>
                    Phone Number: <input type='text' id='imgMD_phone_upload' name='imgMD_phone_upload' readonly/> <br>
                    Location: <select id="imgMD_location_select_output" name="imgMD_location_select_output">
                            <option>NO LOCATION DATA</option>
                            <?php
                                imgMD_populateLocationOptions('option');
                            ?>
                        </select> <br>
                    Links: <input id='imgMD_links_upload' readonly/> <br>
                    Description: <textarea id='imgMD_description_upload' name='imgMD_description_upload' readonly></textarea> <br>
                    <input type='file' name='imgMD_upload_img[]' multiple='multiple'></input>
                    <?php submit_button('Upload') ?>
                </form>
            </div>
        </div>
    </body>
<?php
}

function imgMD_create_db() 
{
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
   
    // Create the preset table
    $presets_table_name = $wpdb->prefix . 'imgMD_presets';
    $sql = "CREATE TABLE IF NOT EXISTS $presets_table_name (
        imgMD_entry_id INTEGER NOT NULL AUTO_INCREMENT,
        imgMD_entry_name TEXT NOT NULL,
        imgMD_entry_alt_txt TEXT NOT NULL,
        imgMD_entry_phone TEXT NOT NULL,
        imgMD_entry_location TEXT NOT NULL,
        PRIMARY KEY (imgMD_entry_id)
        ) $charset_collate;";
    dbDelta( $sql );

    // Create the links table
    $links_table_name = $wpdb->prefix . 'imgMD_links';
    $sql2 = "CREATE TABLE IF NOT EXISTS $links_table_name (
        imgMD_link_id INTEGER NOT NULL AUTO_INCREMENT,
        imgMD_link_text TEXT,
        imgMD_entry_id INTEGER NOT NULL,
        FOREIGN KEY(imgMD_entry_id) REFERENCES $presets_table_name(imgMD_entry_id),
        PRIMARY KEY (imgMD_link_id)
        ) $charset_collate;";
    dbDelta( $sql2 );

    // Create the locations table
    $locations_table_name = $wpdb->prefix . 'imgMD_locations';
    $sql3 = "CREATE TABLE IF NOT EXISTS $locations_table_name (
        imgMD_location_id INTEGER NOT NULL AUTO_INCREMENT,
        imgMD_location_name TEXT NOT NULL,
        imgMD_latitude DECIMAL(10, 8) NOT NULL,
        imgMD_longitude DECIMAL(11, 8) NOT NULL,
        PRIMARY KEY (imgMD_location_id)
        ) $charset_collate;";
    dbDelta( $sql3 );
}
?>