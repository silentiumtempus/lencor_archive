/**
 * Created by Vinegar on 008 08.03.17.
 */
$(document).ready(function () {
    if (window.jQuery) {
        var path = $("#main-div").attr("data-path");
        var $factorySelect = $('#archive_entry_add_form_factory');
        var $entryAddForm = $('#archive_entry_add_form');
        var $factoryAddForm = $('#factory_add_form');
        var $settingAddForm = $('#setting_add_form');

        /** Settings list reload after page refresh **/

        //$(function () {
        //    settingsLoader();
        //});

        /** Archive entries new entry page factory->settings AJAX loader **/

        function settingsLoader() {

            //create array for AJAX request
            var data = {};
            data[$factorySelect.attr('name')] = $factorySelect.val();
            $.ajax({
                url: path,
                method: $entryAddForm.attr('method'),
                data: data,
                success: function (html) {
                    $('#archive_entry_add_form_setting').replaceWith(
                        $(html).find('#archive_entry_add_form_setting')
                    );
                }
            });
        }

        $factorySelect.on("change", function () {
            settingsLoader();
        });

        /** Archive new factory submission **/

        $factoryAddForm.on("submit", function (event) {
            event.preventDefault();
            var factorySerialized = $factoryAddForm.serialize();
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
                            $('#setting_add_form_factory').replaceWith(
                                $(response).find('#setting_add_form_factory'));
                            $('#archive_entry_add_form_factory').replaceWith(
                                $(response).find('#archive_entry_add_form_factory'));
                        }
                    });
                }
            });
        });

        /** Archive new setting submission **/

        $settingAddForm.on("submit", function (event) {
            event.preventDefault();
            var settingSerialized = $settingAddForm.serialize();
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
                        success: function (response) {
                            var $changedFactory = $settingAddForm.find('select[id="setting_add_form_factory"]').val();
                            var $selectedFactoryInAddForm = $entryAddForm.find('select[id="archive_entry_add_form_factory"]').val();
                            if($changedFactory === $selectedFactoryInAddForm) {
                                settingsLoader()
                            }
                        }
                    });
                }
            });
        });

        /** Archive new entry submission **/

        $entryAddForm.on("submit", function (event) {
            event.preventDefault();
            var entrySerialized = $entryAddForm.serialize();
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
        });
    }
});