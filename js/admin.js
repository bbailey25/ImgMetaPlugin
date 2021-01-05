jQuery(document).ready(function ($) {

    var links = "";

    /*
    Description: Writes the description containing the entry name, alternate
                 text, phone number, and any links associated.
    */
    function imgMD_writeDescription() {
        var entryName = document.getElementById('imgMD_entry_name');
        var altText = document.getElementById('imgMD_alt_text');
        var phone = document.getElementById('imgMD_phone');

        document.getElementById('imgMD_description').value =
            altText.value + '\n' +
            entryName.value + '\n' +
            phone.value + '\n' +
            links;
    }

    /*
    Description: Clears all the locations lists of their contents. Used before
                 repopulating locations from the database.
    */
    function imgMD_clearLocationData() {
        var locationsSelect = document.getElementById('imgMD_locations_select');
        var locationList = document.getElementById('imgMD_location_list');
        var locationsSelectOutput =
            document.getElementById('imgMD_location_select_output');

        while (locationsSelect.firstChild) {
            locationsSelect.removeChild(locationsSelect.firstChild);
        }

        while (locationList.firstChild) {
            locationList.removeChild(locationList.firstChild);
        }

        while (locationsSelectOutput.firstChild) {
            locationsSelectOutput.removeChild(locationsSelectOutput.firstChild);
        }
    }

    /*
    Description: Takes a list of locations to populate the various lists and
                 selects with.
    */
    function imgMD_repopulateLocationData(locations = null) {
        var locationsSelect = document.getElementById('imgMD_locations_select');
        var locationsSelectOutput =
            document.getElementById('imgMD_location_select_output');
        var locationList = document.getElementById('imgMD_location_list');

        var option = document.createElement('option');
        option.text = "NO LOCATION DATA";
        locationsSelect.add(option);
        var option2 = document.createElement('option');
        option2.text = "NO LOCATION DATA";
        locationsSelectOutput.add(option2);

        if (locations != null) {
            for (i = 0; i < locations.length; i++) {
                var option = document.createElement('option');
                option.text = locations[i];
                locationsSelect.add(option);
                var option2 = document.createElement('option');
                option2.text = locations[i];
                locationsSelectOutput.add(option2);
                var li = document.createElement('li');
                li.appendChild(document.createTextNode(locations[i]));
                li.setAttribute('class', 'imgMD_location_list_item');
                locationList.appendChild(li);
            }
        }
    }

    /*
    Description: Takes a location name (format: city, state), the latitude, and
                 the longitude. Passes those to the location database through an
                 ajax call. On a successful return, the database will send an
                 array of all current locations in the database. Clear the
                 location lists and repopulate them with the up-to-date data.
    */
    function imgMD_addLocationDbEntry(locationName, latitude, longitude) {

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'imgMD_dbAddLocationEntry',
                imgMD_location_name: locationName,
                imgMD_latitude: latitude,
                imgMD_longitude: longitude
            },
            success: function (data) {
                locations = JSON.parse(data);
                imgMD_clearLocationData();
                imgMD_repopulateLocationData(locations);
            },
            error: function () {
                alert('Error adding a location to the database.');
            }
        });
    }

    /*
    Desciption: Checks to see if the preset entry already exists in the preset
                entry database. Returns false if the entry does not exist or if
                there was an error of any sort. Returns true if the entry
                already exists in the database.
    */
    function imgMD_presetEntryExists() {
        var entryExists = false;
        var entryName = document.getElementById('imgMD_entry_name');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'imgMD_dbPresetEntryExists',
                imgMD_entry_name: entryName.value
            },
            // Ensures the ajax request finishes before continuing.
            async: false,
            success: function (data) {
                if (data > 0) {
                    entryExists = true;
                }
            },
            error: function () {
                alert('Error checking if the preset entry exists in the database.');
            }
        });

        return entryExists;
    }

    /*
    Description: Checks to see if the location entry already exiss in the
                 location database. Returns false if it does not exist or if 
                 there is an error. Returns true if it does exist.
    */
    function imgMD_locationEntryExists(locationName) {
        var entryExists = false;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'imgMD_dbLocationEntryExists',
                imgMD_location_name: locationName
            },
            // Ensures the ajax request finishes before continuing.
            async: false,
            success: function (data) {
                if (data > 0) {
                    entryExists = true;
                }
            },
            error: function () {
                alert('Error checking if the location already exists in the database.');
            }
        });

        return entryExists;
    }

    /*
    Description: Gathers the information in the various preset fields and sends
                 it through an ajax call to add to the preset database.
    */
    function imgMD_addPreset() {

        var locationsSelect = document.getElementById('imgMD_locations_select');
        var selectedLocation =
            locationsSelect.options[locationsSelect.selectedIndex].value;
        var entryName = document.getElementById('imgMD_entry_name');
        var altText = document.getElementById('imgMD_alt_text');
        var phone = document.getElementById('imgMD_phone');

        // Remove the trailing new line.
        links = links.replace(/\n$/, '');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'imgMD_dbAddPresetEntry',
                imgMD_entry_name: entryName.value,
                imgMD_entry_alt_txt: altText.value,
                imgMD_entry_phone: phone.value,
                imgMD_links: links,
                imgMD_entry_location: selectedLocation
            },
            error: function () {
                alert('Error adding the preset entry to the database.');
            }
        });
    }

    /*
    Description: Takes an entry name and sends an ajax request to have that
                 entry name removed from the preset database and its associated
                 links in the link database. 
    */
    function imgMD_removeEntry(entryName) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'imgMD_dbRemovePresetEntry',
                imgMD_entry_name: entryName
            },
            error: function () {
                alert('Error when removing entry.');
            }
        });
    }

    /*
    Description: Takes an entry name and sends an ajax request to return all the
                 data associated. Includes location, alt_txt, phone number, 
                 and links. If the ajax request succeeds it takes all that data
                 in array form and sets the various fields associated.
    */
    function imgMD_returnEntry(entry) {

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'imgMD_dbReturnPresetEntry',
                imgMD_entry_name: entry
            },
            success: function (data) {
                entryData = JSON.parse(data);

                // This will put the location associated with the selected
                // entry at the top of a drop down. This will allow the
                // user to decide if they want the location associated or
                // have no location on the selected images(s).
                var option, i = 0;
                var locationOutput =
                    document.getElementById('imgMD_location_select_output');
                while (option = locationOutput.options[i++]) {
                    if (option.value == entryData[0]['imgMD_entry_location']) {
                        option.selected = true;
                        break;
                    }
                }

                document.getElementById('imgMD_entry_name_upload').value =
                    entryData[0]['imgMD_entry_name'];
                document.getElementById('imgMD_alt_text_upload').value =
                    entryData[0]['imgMD_entry_alt_txt'];
                document.getElementById('imgMD_phone_upload').value =
                    entryData[0]['imgMD_entry_phone'];

                linkVal = "";
                descriptionVal =
                    entryData[0]['imgMD_entry_alt_txt'] + '\n' +
                    entryData[0]['imgMD_entry_name'] + '\n' +
                    entryData[0]['imgMD_entry_phone'] + '\n';

                // Puts the links in a nice comma-separated string.
                for (var i = 0; i < entryData.length; i++) {
                    var link = entryData[i].imgMD_link_text;
                    linkVal = linkVal + link + ', ';
                    descriptionVal = descriptionVal + link + '\n';
                }

                // Remove trailing comma and space.
                linkVal = linkVal.replace(/, $/, '');
                document.getElementById('imgMD_links_upload').value = linkVal;
                document.getElementById('imgMD_description_upload').value =
                    descriptionVal;
            },
            error: function () {
                alert('Error returning preset entry.');
            }
        });
    }

    /*
    Description: Clears all the output entry data. Usually called when the user 
                 selects a different entry to display or when an entry gets 
                 deleted.
    */
    function imgMD_clearEntryData() {
        document.getElementById('imgMD_entry_name_upload').value = "";
        document.getElementById('imgMD_alt_text_upload').value = "";
        document.getElementById('imgMD_phone_upload').value = "";
        document.getElementById('imgMD_links_upload').value = "";
        document.getElementById('imgMD_description_upload').value = "";
    }

    /*
    Description: This just allows the user to highlight their location choice
                 so they can be sure which location they are deleting.
    */
    $('.imgMD_location_list').on('click', 'li', function () {
        $('.highlight').removeClass('highlight');
        $(this).addClass('highlight');
    });

    /*
    Description: This just keeps the description part dynamic when the user is
                 adding a new preset.
    */
    document.getElementById('imgMD_add_preset_form').oninput = function () {
        imgMD_writeDescription();
    };

    /*
    Description: When this add button is clicked it adds a link to the link list
                 and to the bottom of the description.
    */
    document.getElementById('imgMD_add_link_btn').onclick = function () {
        var link = document.getElementById('imgMD_link');
        if (link.value.trim() != "") {
            links = links + link.value + '\n';
            link.value = "";
            imgMD_writeDescription();
        }
    };

    /*
    Description: When this add button is clicked it first checks to make sure
                 the entry name is not blank. It then confirms that the entry
                 name has not already been used. If both of those succeed it 
                 adds a new option to the entries select, adds the entry to the
                 database through the addPreset function call, clears the
                 fields, and writes the description again (blank).
    */
    document.getElementById('imgMD_add_entry_btn').onclick = function () {
        // Ensure no blank entry names get accepted.
        var entryName = document.getElementById('imgMD_entry_name');
        var altText = document.getElementById('imgMD_alt_text');
        var phone = document.getElementById('imgMD_phone');
        entryName.value = entryName.value.trim();
        if (entryName.value != "") {
            if (!imgMD_presetEntryExists()) {
                var option = document.createElement("option");
                option.value = entryName.value;
                option.text = entryName.value;
                document.getElementById('imgMD_entries_select').add(option);
                imgMD_addPreset();
                entryName.value = "";
                altText.value = "";
                phone.value = "";
                links = "";
                imgMD_writeDescription();
            }
            else {
                alert('Entry name has already been used. Try again.');
            }
        }
        else {
            alert('Entry name can\'t be blank. Try again.');
        }
    };

    /*
    Description: When the delete entry button is clicked we check to see what
                 entry the user wants to be deleted. If it isn't the "NO DATA"
                 option, because we always want that there, then we remove the
                 entry from the database through imgMD_removeEntry, clear the entry
                 output data, and remove the select.
    */
    document.getElementById('imgMD_delete_entry_btn').onclick = function () {
        var addEntrySelect = document.getElementById('imgMD_entries_select');
        var entry = addEntrySelect.options[addEntrySelect.selectedIndex].text;
        if (entry != "NO DATA") {
            imgMD_removeEntry(entry);
            imgMD_clearEntryData();
            $("#imgMD_entries_select option:selected").remove();
        }
    };

    /*
    Description: When the add location button is clicked it first checks to make
                 sure that none of the fields are blank. It then ensures that
                 the location has not already been added to the location
                 database. If both of those things are true, the location is
                 added to the location database and the location fields are
                 cleared.
    */
    document.getElementById('imgMD_add_location_btn').onclick = function () {
        var cityName = document.getElementById('imgMD_city_name');
        var stateName = document.getElementById('imgMD_state_name');
        var latitude = document.getElementById('imgMD_latitude');
        var longitude = document.getElementById('imgMD_longitude');


        // Ensure that no values are blank and they are of the right type
        if ((cityName.value.trim() == "") ||
            (stateName.value.trim() == "") ||
            (latitude.value.trim() == "") ||
            (longitude.value.trim() == "")) {

            alert('No location information can be missing. Latitude and \
                Longitude must be numbers. Try again.');
            return;
        }

        // Range check our latitude and longitude values to make sure they are
        // valid values.
        if (latitude.value < -90 || latitude.value > 90) {
            alert('Latitude is out of range. Valid values are between -90 and \
                +90. Try again.');
            return;
        }

        if (longitude.value < -180 || longitude.value > 180) {
            alert('Longitude is out of range. Valid values are between -180 \
                and +180. Try again.');
            return;
        }

        var locationName = cityName.value.trim() + ", " + stateName.value.trim();
        if (!imgMD_locationEntryExists(locationName)) {
            imgMD_addLocationDbEntry(locationName, latitude.value, longitude.value);
            cityName.value = "";
            stateName.value = "";
            latitude.value = "";
            longitude.value = "";
        }
        else {
            alert('Location has already been used. Try again.')
        }
    };

    /*
    Description: When the clear links button is clicked the links string is
                 emptied, link field is emptied, and the description is typed
                 again to reflect this.
    */
    document.getElementById('imgMD_clear_links_btn').onclick = function () {
        links = "";
        document.getElementById('imgMD_link').value = "";
        imgMD_writeDescription();
    };

    /*
    Description: When the delete location button is clicked the location list
                 is traversed for the highlighted location. This is the location
                 the user wants deleted. If there is a location highlighted an
                 ajax call will remove the selected location from the database.
                 On success, the ajax call will return an array of locations
                 which are used to repopulate the location lists once the data
                 is cleared.
    */
    document.getElementById('imgMD_delete_location_btn').onclick = function () {
        var locationList = document.getElementById("imgMD_location_list");
        var listItems = locationList.getElementsByTagName('li');
        var selectedItem = '';

        for (var i = 0; i < listItems.length; i++) {
            if (listItems[i].classList.contains('highlight')) {
                selectedItem = listItems[i].textContent;
            }
        }

        if (selectedItem != '') {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'imgMD_dbRemoveLocationEntry',
                    imgMD_location_name: selectedItem
                },
                success: function (data) {
                    locations = JSON.parse(data);
                    imgMD_clearLocationData();
                    imgMD_repopulateLocationData(locations);
                },
                error: function () {
                    alert('Error deleting the location.');
                }
            });
        }
    };

    /*
    Description: When the user selects a new entry from the dropdown this
                 function is triggered. If the entry selected is "NO DATA", the
                 fields are emptied. Otherwise imgMD_returnEntry is called to handle
                 the database interaction required to get the entry information.
    */
    document.getElementById('imgMD_entries_select').onchange = function () {
        var addEntrySelect = document.getElementById('imgMD_entries_select');
        var entry = addEntrySelect.options[addEntrySelect.selectedIndex].text;

        if (entry == "NO DATA") {
            imgMD_clearEntryData();
            document.getElementById(
                'imgMD_location_select_output').options[0].selected = true;
        }
        else {
            imgMD_returnEntry(entry);
        }
    };
});