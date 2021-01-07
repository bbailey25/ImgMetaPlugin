<?php

/*
Description: This function takes a location name and uses that to fetch location
             data from the database and return it.
*/
function imgMD_dbReturnLocationEntry($locationName) {
    global $wpdb;
    $locations_table_name = $wpdb->prefix . 'imgMD_locations';

    $sql = "SELECT imgMD_location_name, imgMD_latitude, imgMD_longitude FROM $locations_table_name
        WHERE imgMD_location_name = '$locationName';";
    $results = $wpdb->get_results($sql);

    return $results;
}

/*
Description: This function is responsible for handling the image uploading. It
             allows for multiple images to be uploaded and goes through them one
             at a time doing the following: reads the exif information for
             location data, checks for errors when uploading into a post, edits
             the post meta data to include the exif metadata if it has it or the
             metadata from the selected location in the correct format, updates
             the post metadata, updates the name, caption, alt-text, and 
             descripton before updating the post once more. That's one photo. It
             will do this for each one in the array of images.
*/
function imgMD_handle()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Verify the current user can upload files
        if (!current_user_can('upload_files'))
        {
            wp_die(__('You do not have permission to upload files.'));
        }

        $files = $_FILES['imgMD_upload_img'];
        foreach ($files['name'] as $key => $value)
        {
            if ($files['name'][$key])
            {   
                $file = array (
                    'name' => sanitize_file_name($files['name'][$key]),
                    'type' => sanitize_text_field($files['type'][$key]),
                    'tmp_name' => sanitize_text_field($files['tmp_name'][$key]),
                    'error' => (int) $files['error'][$key],
                    'size' => (int) $files['size'][$key],
                );

                // Check the extension of the file to make sure it's in
                // an allowable format.
                switch ($file['type'])
                {
                    case "image/png":
                    case "image/jpeg":
                        break;
                    default:
                        printf("You tried to load a %s file called %s, but are restricted to image files only. Please load images with a file type of .jpg, .jpeg, or .png only.<br>", $file['type'], $file['name']);
                        continue 2;
                }

                // Check to see if the file is actually an image file.
                if (empty(getimagesize($file['tmp_name'])))
                {
                    printf("The file %s does not appear to be an image file.<br>", $file['name']);
                    continue;
                }
                
                $exifLatitude       = '';
                $exifLatitudeRef    = '';
                $exifLongitude      = '';
                $exifLongitudeRef   = '';

                if ((exif_imagetype($file['tmp_name']) == IMAGETYPE_JPEG) && 
                    is_callable('exif_read_data'))
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
                    // There was an error uploading the image.
                    echo "Error adding file";
                }

                if ($attachment_id > 0)
                {

                    $metadata = wp_get_attachment_metadata($attachment_id,
                                                           true);

                    $locationSelection = sanitize_text_field($_POST['imgMD_location_select_output']);

                    if (!empty($exifLatitude) &&
                        !empty($exifLatitudeRef) &&
                        !empty($exifLongitude) &&
                        !empty($exifLongitudeRef))
                    {
                        // Image has exif location data so we just want to use
                        // that data no matter what location the user selects.
                        $metadata['image_meta']['latitude'] = 
                            $exifLatitude;
                        $metadata['image_meta']['latitude_ref'] = 
                            $exifLatitudeRef;
                        $metadata['image_meta']['longitude'] = 
                            $exifLongitude;
                        $metadata['image_meta']['longitude_ref'] = 
                            $exifLongitudeRef;
                    }
                    else
                    {
                        // Image does not have exif location data.
                        if ($locationSelection != 'NO LOCATION DATA')
                        {
                            // Use location for post location data.
                            $locationInfo = 
                                imgMD_dbReturnLocationEntry($locationSelection);

                            $metadata['image_meta']['latitude'] = imgMD_format_dec_to_dms($locationInfo[0]->imgMD_latitude);
                            $metadata['image_meta']['latitude_ref'] = 
                                imgMD_direction_char($locationInfo[0]->imgMD_latitude,
                                               'latitude');
                            $metadata['image_meta']['longitude'] = 
                                imgMD_format_dec_to_dms($locationInfo[0]->imgMD_longitude);
                            $metadata['image_meta']['longitude_ref'] = 
                                imgMD_direction_char($locationInfo[0]->imgMD_longitude, 
                                               'longitude'); 
                        }
                        else
                        {
                            // Do nothing if the user selected
                            // not to add location data.
                        }
                    }

                    wp_update_attachment_metadata($attachment_id, $metadata);

                    if (isset($_REQUEST['imgMD_entry_name_upload'])) 
                    {
                        $entryName = sanitize_text_field($_REQUEST['imgMD_entry_name_upload']);
                    }
        
                    if (isset($_REQUEST['imgMD_alt_text_upload'])) 
                    {
                        $entryAltText = sanitize_text_field($_REQUEST['imgMD_alt_text_upload']);
                    }
        
                    if (isset($_REQUEST['imgMD_description_upload'])) 
                    {
                        $entryDescription = sanitize_textarea_field($_REQUEST['imgMD_description_upload']);
                    }
                    
                    $my_image_meta = array(
                        // Specify the image (ID) to be updated.
                        'ID' => $attachment_id,
                        // Set image title.
                        'post_title' => $entryName,
                        // Set image caption (excerpt).
                        'post_excerpt' => $entryName,
                        // Set image description (content).
                        'post_content' => $entryDescription
                    );

                    // This adds alternative text.
                    update_post_meta($attachment_id, 
                                     '_wp_attachment_image_alt',
                                     $entryAltText);
                        
                    // Set the image meta (e.g. Title, Excerpt, Content).
                    wp_update_post($my_image_meta);
                }
            }
        }
    }
}

/*
Description: Takes a coordinate in decimal form and converts it to dms (degrees,
             minutes, and seconds) form. Returns an array with three strings.
*/
function imgMD_format_dec_to_dms($coordinateDec)
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

/*
Description: Takes a coordinate value in decimal form and the type it is
             (latitude or longitude) and returns a character indicating the
             direction the decimal coordinate value corresponds to.
*/
function imgMD_direction_char($coordinateValue, $type)
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

/*
Description: Takes $type (e.g. option, li, etc) and a list of classes
             (defaults to NULL) to add.
*/
function imgMD_populateLocationOptions($type, $classes = null)
{
    global $wpdb;
    $locations_table_name = $wpdb->prefix . 'imgMD_locations';
    
    $cities = $wpdb->get_col("SELECT imgMD_location_name FROM 
        $locations_table_name;");
    
    if ($classes == null)
    {
        foreach ($cities as $city) {
            printf("<$type>$city</$type>");
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
            printf("<%s class='%s'>%s</%s>",
                    $type,
                    esc_attr($classString),
                    $city,
                    $type);
        }
    }
}

?>