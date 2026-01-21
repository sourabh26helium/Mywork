var placeSearch, place, autocompleteFields;

var componentForm = {
    locality: 'long_name',
    administrative_area_level_1: 'long_name',
    sublocality_level_1: 'long_name',
    country: 'short_name',
    postal_code: 'long_name'
};

var autocomplete = [];

function initAutocomplete(restriction) {
    autocompleteFields = document.querySelectorAll('.js-find-store');
    if (restriction === undefined) {
        var options = {
            types: ['(regions)']
        };
    } else {
        var options = {
            types: ['(regions)'],
            componentRestrictions: restriction
        };
    }

    for (let index = 0; index < autocompleteFields.length; index++) {

      autocomplete[index] = new google.maps.places.Autocomplete(
          autocompleteFields[index],
          options
      );
  
      autocomplete[index].setFields(['address_component', 'geometry']);
      autocomplete[index].addListener('place_changed', function() {
        fillInAddress(index)
      });
    }
}

function minicartAutocomplete() {
    if (window.minicartonce) {
        return;
    } else {
        window.minicartonce = true;
    }
    var restriction;
    restriction = {country: 'AU'};
    autocompleteFields = document.querySelectorAll('.minicart-js-find-store');
    if (restriction === undefined) {
        var options = {
            types: ['(regions)']
        };
    } else {
        var options = {
            types: ['(regions)'],
            componentRestrictions: restriction
        };
    }

    for (let index = 0; index < autocompleteFields.length; index++) {

      autocomplete[index] = new google.maps.places.Autocomplete(
          autocompleteFields[index],
          options
      );
  
      autocomplete[index].setFields(['address_component', 'geometry']);
      autocomplete[index].addListener('place_changed', function() {
        fillInAddress(index)
      });
    }
}
function fillInAddress(index) {
    place = autocomplete[index].getPlace();

    if (place.geometry === undefined) {
        return;
    }

    resetFields();
    document.getElementById('mw-sl__lat').value = place.geometry.location.lat();
    document.getElementById('mw-sl__lng').value = place.geometry.location.lng();

    if (document.getElementById('minicart-mw-sl__lat')) {
        document.getElementById('minicart-mw-sl__lat').value = place.geometry.location.lat();
        document.getElementById('minicart-mw-sl__lng').value = place.geometry.location.lng();
    }
    
    var placeName = '';

    for (var i = 0; i < place.address_components.length; i++) {
        var addressType = place.address_components[i].types[0];
        if (componentForm[addressType]) {
            document.getElementById('mw_' + addressType).value = place.address_components[i][componentForm[addressType]];

            if (document.getElementById('minicart-mw-sl__lat')) {
                document.getElementById('minicart-mw_' + addressType).value = place.address_components[i][componentForm[addressType]];
            }

            if (placeName) {
                placeName += ', ';
            }
            placeName += place.address_components[i][componentForm[addressType]];
        }
    }

    if (document.getElementById("mw_location_current_location_info")) {
      document.getElementById("mw_location_current_location_info").innerHTML = placeName;
    }

    jQuery(".mw-sl__search__current-location").removeClass('mw-sl__search__current-location__loaded');
}

function resetFields() {
    for (var component in componentForm) {
        document.getElementById('mw_' + component).value = '';
    }
}
