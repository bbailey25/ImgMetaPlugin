jQuery(document).ready(function ($) {
    var form = document.getElementById('formTest');
    // var locationForm = document.getElementById('locationForm');
    var addButton = document.getElementById('addButton');
    var clearButton = document.getElementById('clearButton');
    var addEntryBtn = document.getElementById('addEntryBtn');
    var deleteEntryBtn = document.getElementById('deleteEntryBtn');
    var addLocationBtn = document.getElementById('addLocationBtn');
    var deleteLocationBtn = document.getElementById('deleteLocationBtn');
    var businessName = document.getElementById('businessName');
    var altText = document.getElementById('altText');
    var phone = document.getElementById('phone');
    var link = document.getElementById('link');
    var description = document.getElementById('description');
    var addEntrySelect = document.getElementById('businesses');
    var links = "";

    function writeDescription() {

        description.value = altText.value + '\n' +
            businessName.value + '\n' +
            phone.value + '\n' +
            links;
    }

    function clearLocationData() {
        var locationsSelect = document.getElementById('locationsSelect');
        var location_list = document.getElementById('location_list');
        var locationsSelectOutput = document.getElementById('locationsSelectOutput');

        while (locationsSelect.firstChild) {
            locationsSelect.removeChild(locationsSelect.firstChild);
        }

        while (location_list.firstChild) {
            location_list.removeChild(location_list.firstChild);
        }

        while (locationsSelectOutput.firstChild) {
            locationsSelectOutput.removeChild(locationsSelectOutput.firstChild);
        }
    }

    function repopulateLocationData(locations = null) {
        var locationsSelect = document.getElementById('locationsSelect');
        var locationsSelectOutput = document.getElementById('locationsSelectOutput');
        var location_list = document.getElementById('location_list');

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
                location_name: locationName,
                latitude: latitude,
                longitude: longitude
            },
            success: function (data) {
                locations = JSON.parse(data);
                clearLocationData();
                repopulateLocationData(locations);
            },
            error: function () {
                alert('boo');
            }
        });
    }

    function databaseEntryExists() {
        var someBool = false;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_action3',
                business_name: businessName.value
            },
            async: false,               //ensures the ajax request finishes before continuing
            success: function (data) {
                if (data > 0) {
                    someBool = true;
                }
            },
            error: function () {
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
                location_name: locationName
            },
            async: false,
            success: function (data) {
                if (data > 0) {
                    someBool = true;
                }
            },
            error: function () {
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
                business_name: businessName.value,
                business_alt_txt: altText.value,
                business_phone: phone.value,
                business_links: links,
                business_location: selectedLocation
            },
            error: function () {
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
                business_name: business
            },
            error: function () {
                alert('boo');
            }
        });
    }

    function returnDatabaseEntry(business) {

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_action2',
                business_name: business
            },
            success: function (data) {
                something = JSON.parse(data);

                // This will put the location associated with the selected
                // business at the top of a drop down. This will allow the
                // user to decide if they want the location associated or
                // have no location on the selected images.
                var option, i = 0;
                var locationOutput = document.getElementById('locationsSelectOutput');
                while (option = locationOutput.options[i++]) {
                    if (option.value == something[0]['business_location']) {
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

                for (var i = 0; i < something.length; i++) {
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

    function clearEntryData() {
        document.getElementById('businessNameOutput').value = "";
        document.getElementById('altTextOutput').value = "";
        document.getElementById('phoneOutput').value = "";
        document.getElementById('linksOutput').value = "";
        document.getElementById('descriptionOutput').value = "";
    }

    $('.location_list').on('click', 'li', function () {
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

    addEntryBtn.onclick = function () {
        // Ensure no blank business names get accepted
        businessName.value = businessName.value.trim();
        if (businessName.value != "") {
            if (!databaseEntryExists()) {
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
            else {
                alert('Business name has already been used. Try again.');
            }
        }
        else {
            alert('Business name can\'t be blank. Try again.');
        }
    };

    deleteEntryBtn.onclick = function () {
        var business = addEntrySelect.options[addEntrySelect.selectedIndex].text;
        if (business != "NO DATA") {
            removeDatabaseEntry(business);
            clearEntryData();
            $("#businesses option:selected").remove();
        }
    };

    addLocationBtn.onclick = function () {

        // Ensure no blank locations get accepted
        var cityName = document.getElementById('cityName');
        var stateName = document.getElementById('stateName');
        var latitude = document.getElementById('latitude');
        var longitude = document.getElementById('longitude');

        if ((cityName.value.trim() != "") &&
            (stateName.value.trim() != "") &&
            (latitude.value.trim() != "") &&
            (longitude.value.trim() != "")) {
            var locationName = cityName.value.trim() + ", " + stateName.value.trim();
            if (!locationEntryExists(locationName)) {
                addLocationDbEntry(locationName, latitude.value, longitude.value);
                cityName.value = "";
                stateName.value = "";
                latitude.value = "";
                longitude.value = "";
            }
            else {
                alert('Location has already been used. Try again.')
            }
        }
        else {
            alert('No Location Information Can Be Blank. Try again.');
        }
    };

    clearButton.onclick = function () {
        links = "";
        link.value = "";
        writeDescription();
    };

    deleteLocationBtn.onclick = function () {
        var location_list = document.getElementById("location_list");
        var listItems = location_list.getElementsByTagName('li');
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
                    action: 'my_action7',
                    location_name: selectedItem
                },
                success: function (data) {
                    locations = JSON.parse(data);
                    clearLocationData();
                    repopulateLocationData(locations);
                },
                error: function () {
                    alert('boo');
                }
            });
        }
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
});