$(document).ready(function () {
    if (window.jQuery) {
        let path = Routing.generate('entries-new');
        let $factorySelect = $('#entry_form_factory');
        let $entryAddForm = $('#archive_entry_form');
        let $factoryAddForm = $('#factory_form');
        let $settingAddForm = $('#setting_form');

        /** Settings list reload after page refresh **/

        //$(function () {
        //    settingsLoader();
        //});

        /** Archive entries new entry page factory->settings AJAX loader **/

        function settingsLoader() {

            //create array for AJAX request
            let data = {};
            data[$factorySelect.attr('name')] = $factorySelect.val();
            $.ajax({
                url: path,
                method: $entryAddForm.attr('method'),
                data: data,
                success: function (response) {
                    $('#entry_form_setting').replaceWith(
                        $(response).find('#entry_form_setting')
                    );
                }
            });

            return false;
        }

        /** Load settings list when other factory was selected **/

        $factorySelect.on("change", function () {
            settingsLoader();
        });

        /** Archive new factory submission **/

        $factoryAddForm.on("submit", function (event) {
            event.preventDefault();
            let factorySerialized = $factoryAddForm.serialize();

            $.ajax({
                url: path,
                method: $factoryAddForm.attr('method'),
                data: factorySerialized,
                success: function (response) {
                    //alert(response);
                    $('#flash-messages').replaceWith(
                        $(response).find('#flash-messages'));
                    $.ajax({
                        url: path,
                        method: $entryAddForm.attr('method'),
                        data: null,
                        success: function (response) {
                            $('#setting_form_factory').replaceWith(
                                $(response).find('#setting_form_factory'));
                            $('#entry_form_factory').replaceWith(
                                $(response).find('#entry_form_factory'));
                        }
                    });
                }
            });

            return false;
        });

        /** Archive new setting submission **/

        $settingAddForm.on("submit", function (event) {
            event.preventDefault();
            let settingSerialized = $settingAddForm.serialize();
            $.ajax({
                url: path,
                method: $settingAddForm.attr('method'),
                data: settingSerialized,
                success: function (response) {
                    $('#flash-messages').replaceWith(
                        $(response).find('#flash-messages'));
                    $.ajax({
                        url: path,
                        method: $settingAddForm.attr('method'),
                        data: null,
                        success: function () {
                            let $changedFactory = $settingAddForm.find('select[id="setting_form_factory"]').val();
                            let $selectedFactoryInAddForm = $entryAddForm.find('select[id="entry_form_factory"]').val();
                            if ($changedFactory === $selectedFactoryInAddForm) {
                                settingsLoader();
                            }
                        }
                    });
                }
            });

            return false;
        });

        /** Archive new entry submission **/

        $entryAddForm.on("submit", function (event) {
            event.preventDefault();
            let entrySerialized = $entryAddForm.serialize();
            $.ajax({
                url: path,
                method: $entryAddForm.attr('method'),
                data: entrySerialized,
                success: function (response) {
                    $('#flash-messages').replaceWith(
                        $(response).find('#flash-messages')
                    );
                }
            });

            return false;
        });
    }
});