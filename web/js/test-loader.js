/**
 * Created by Vinegar on 018 18.04.17.
 */
$(document).ready(function () {
    if (window.jQuery) {

        /** Do not touch this **/
        var path = $("#main-div").attr("data-path");
        var $factory = $('#archive_entry_add_form_factory');
        var form = $("#archive_entry_add_form");
        //var form = $(this).closest('form');

        /** Seriously, it'a a bad idea  :) **/

        /** Archive entries main table factory->settings AJAX loader **/

        $factory.on("change", function settingsLoadAction() {
            //create array for AJAX request
            var data = {};
            data[$factory.attr('name')] = $factory.val();
            $.ajax({
                url: form.attr('action'),
                method: form.attr('method'),
                data: data,
                success: function (html) {
                    $('#archive_entry_add_form_setting').replaceWith(
                        $(html).find('#archive_entry_add_form_setting')
                    );
                }
            });
        });

        /** Archive entries main table content AJAX loader **/

        $("#archive_entry_search_form").on("submit", function searchAction(event) {

            event.preventDefault();
            //create array for AJAX request
            var fields = form.serializeArray();
            var values = {};
            jQuery.each(fields, function (i, field) {
                values[field.name] = field.value;
            });
            // send AJAX request
            $.ajax({
                url: path,
                method: form.attr('method'),
                data: values,
                success: function (html) {
                    $('#main-tbody').replaceWith(
                        $(html).find('#main-tbody')
                    );
                }
            });
        });

        /** Archive entries main table search form reset to default stdout AJAX loader **/

        $("#archive_entry_search_form_resetButton").on("click", function resetAction(event) {
            //event.preventDefault();
            form.trigger('reset');
            //send AJAX null request
            $.ajax({
                url: path,
                method: form.attr('method'),
                data: null,
                success: function (html) {
                    $('#main-tbody').replaceWith(
                        $(html).find('#main-tbody')
                    );
                }
            });
        });

        /** Archive entries content loader **/

        $(document).on("click", "img[name='entryid']", function openContents() {
            $rowId = $(this).parent().attr("id");
            $.ajax({
                url: path,
                method: form.attr('method'),
                data: { rowId: $rowId},
                success: function (html) {

                    $("tr[name='" + $rowId + "']").show();

                }
            });
        });
    }
});