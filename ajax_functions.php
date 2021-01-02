<?php

// Ajax functions
add_action('wp_ajax_dbAddPresetEntry', 'dbAddPresetEntry');
add_action('wp_ajax_dbReturnPresetEntry', 'dbReturnPresetEntry');
add_action('wp_ajax_dbPresetEntryExists', 'dbPresetEntryExists');
add_action('wp_ajax_dbRemovePresetEntry', 'dbRemovePresetEntry');
add_action('wp_ajax_dbAddLocationEntry', 'dbAddLocationEntry');
add_action('wp_ajax_dbLocationEntryExists', 'dbLocationEntryExists');
add_action('wp_ajax_dbRemoveLocationEntry', 'dbRemoveLocationEntry');

function dbAddPresetEntry() 
{
    global $wpdb;

    // Grabs all the links.
    $links = preg_split("/\r\n|\n|\r/", $_POST['img_meta_links']);

    // Inserts the new entry into the preset database.
    $wpdb->insert('wp_img_meta_presets', array(
        'entry_name' => $_POST['entry_name'],
        'entry_alt_txt' => $_POST['entry_alt_txt'],
        'entry_phone' => $_POST['entry_phone'],
        'entry_location' => $_POST['entry_location']
    ));

    // Inserts all the links into the link database with 
    // the corresponding preset id.
    foreach($links as $link) 
    {
        $sql = "INSERT INTO wp_img_meta_links (link, entry_id) VALUES 
            ('$link', (SELECT entry_id FROM wp_img_meta_presets WHERE 
            entry_name='".$_POST['entry_name']."'));";

        $wpdb->query($sql);
    };

    // This is required to terminate immediately and 
    // return a proper response for ajax calls.
    wp_die(); 
}

function dbReturnPresetEntry() 
{
    global $wpdb;

    // Grabs the desired preset entry along with the links associated.
    $sql = "SELECT * FROM wp_img_meta_presets LEFT JOIN wp_img_meta_links ON 
        wp_img_meta_presets.entry_id = wp_img_meta_links.entry_id WHERE 
        entry_name = '".$_POST['entry_name']."';";
    $results = $wpdb->get_results($sql);

    // Return it in a friendly json format.
    echo json_encode($results);

    wp_die();
}

function dbPresetEntryExists() 
{
    global $wpdb;

    // Checks to see if the preset entry exists in the database.
    $sql = "SELECT * FROM wp_img_meta_presets WHERE entry_name = 
        '".$_POST['entry_name']."';";
    echo($wpdb->query($sql));

    wp_die();
}

function dbRemovePresetEntry() {
    global $wpdb;

    // Delete entry links first since they are the foreign key.
    $sql = "DELETE FROM wp_img_meta_links WHERE entry_id = (SELECT entry_id 
        FROM wp_img_meta_presets WHERE entry_name='".$_POST['entry_name']."');";
    $wpdb->query($sql);

    // Delete the preset entry.
    $wpdb->delete('wp_img_meta_presets', 
                   array('entry_name' => $_POST['entry_name']));
    
    wp_die();
}

function dbAddLocationEntry() {
    global $wpdb;

    $wpdb->insert('wp_img_meta_locations', array(
        'location_name' => $_POST['location_name'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude']
    ));

    // Once it inserts the new location it returns an updated list of locations.
    $results = $wpdb->get_col(
        "SELECT location_name FROM wp_img_meta_locations;");
    echo json_encode($results);

    wp_die();
}

function dbLocationEntryExists() {
    global $wpdb;

    // Checks to see if the location entry exists.
    $sql = "SELECT * FROM wp_img_meta_locations WHERE location_name =
        '".$_POST['location_name']."';";
    echo($wpdb->query($sql));

    wp_die();
}

function dbRemoveLocationEntry() {
    global $wpdb;

    // Remove a location from the database.
    $wpdb->delete('wp_img_meta_locations', 
                  array('location_name' => $_POST['location_name']));

    // Return an array of locations still in the database.
    $results = $wpdb->get_col(
        "SELECT location_name FROM wp_img_meta_locations;");
    echo json_encode($results);
    
    wp_die();
}

?>