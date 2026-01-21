define([
    "jquery", "uiRegistry", "findAStoreUpdate"
], function ($, registry) {

    var dataFromRegistry = registry.get('store_locator_product_data');

    var findInStoreToggle = document.getElementById('setLocationToggler');
    var findInStoreContainer = document.getElementById('findInStoreContainer');
    var findInStorePopup = document.getElementById('findInStorePopup');

    findInStoreToggle.onclick = function () {

        if (!findInStoreContainer.classList.contains('active')) {
            findInStorePopup.hidden = false;

            setTimeout(function () {
                findInStoreContainer.classList.add('active');
                findInStoreToggle.classList.add('active');

                //update popup content
                if (dataFromRegistry && !arrayEquals(dataFromRegistry, self.data)) {
                    self.data = registry.get('store_locator_product_data');

                    $.ajax({
                        url: url,
                        type: 'POST',
                        isAjax: true,
                        dataType: 'html',
                        data: self.data,
                        success: function (xhr, status, errorThrown) {
                            $('#locationPopup').html(xhr);
                            // stupid out-of-the-box bugs....
                            if (document.querySelector('#locationPopup #locationPopup')) {
                                document.querySelector('#locationPopup #locationPopup').classList.remove('modal');
                                document.querySelector('#locationPopup #locationPopup').id = 'locationPopupInner';
                            }
                            $('#locationPopup').trigger('map_initialize');
                            target.modal('openModal');
                        },
                        error: function (xhr, status, errorThrown) {
                            console.log('There was an error loading stores popup.');
                            console.log(errorThrown);
                        }
                    });
                }
            }, 1);
        } else {
            findInStoreContainer.classList.remove('active');
            findInStoreToggle.classList.remove('active');
            setTimeout(function () {
                findInStorePopup.hidden = true;
            }, 200);
        }
    };

    function arrayEquals(a, b) {
        return Array.isArray(a) &&
            Array.isArray(b) &&
            a.length === b.length &&
            a.every((val, index) => val === b[index]);
    }
});
