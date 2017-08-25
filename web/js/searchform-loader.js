/**
 * Created by Vinegar on 018 18.04.17.
 */
$(document).ready(function () {
    if (window.jQuery) {

        /** Do not touch this **/
        var path = $("#main-table").attr("data-path");
        var $factory = $('#archive_entry_search_form_factory');
        var searchForm = $("#archive_entry_search_form");
        var createFolderBlock = $("#addFolder");
        var uploadFileBlock = $("#addFile");

        /** Seriously, it'a a bad idea  :) **/

        /** Archive entries main table factory->settings AJAX loader **/

        $factory.on("change", function settingsLoadAction() {
            //create array for AJAX request
            var data = {};
            data[$factory.attr('name')] = $factory.val();
            $.ajax({
                url: path,
                method: searchForm.attr('method'),
                data: data,
                success: function (response) {
                    $('#archive_entry_search_form_setting').replaceWith(
                        $(response).find('#archive_entry_search_form_setting')
                    );
                }
            });
        });

        /** Archive entries main table content AJAX loader **/

        $("#archive_entry_search_form").on("submit", function searchAction(event) {

            event.preventDefault();
            //create array for AJAX request
            var fields = searchForm.serializeArray();
            var values = {};
            jQuery.each(fields, function (i, field) {
                values[field.name] = field.value;
            });
            // send AJAX request
            $.ajax({
                url: path,
                method: searchForm.attr('method'),
                data: values,
                success: function (response) {
                    $('#main-tbody').replaceWith(
                        $(response).find('#main-tbody')
                    );
                }
            });
        });

        /** Archive entries main table search form reset to default stdout AJAX loader **/

        $("#archive_entry_search_form_resetButton").on("click", function resetAction(event) {
            //event.preventDefault();
            searchForm.trigger('reset');
            //send AJAX null request
            $.ajax({
                url: path,
                method: searchForm.attr('method'),
                data: null,
                success: function (response) {

                    $('#main-tbody').replaceWith(
                        $(response).find('#main-tbody'));
                    $('#addFolder').hide();
                }
            });
        });

        /** Archive entries content loader **/

        $(document).on("click", "a[name='entryid']", openEntryContents);

        function openEntryContents() {
            var entryId = $(this).parent().attr("id");
            var contentPlace = $('#entryContent_' + entryId);
            if ($(contentPlace).is(":hidden")) {
                $.ajax({
                    url: "/new/web/app_dev.php/lencor_entries_view",
                    method: searchForm.attr('method'),
                    data: {entryId: entryId},
                    success: function (response) {
                        contentPlace.html($(response));
                        loadLastUpdateInfo(entryId, null);
                        var folderId = contentPlace.find('#rootEntry').children('ul').attr('id');
                        openFolder(folderId);
                        contentPlace.show();
                    }
                });
            }
            else {
                $(contentPlace).hide();
            }
        }

        /** Archive entries content navigation **/

        $(document).on("click", "a[name='openFolder']", function () {
            openFolder(folderId = $(this).attr("id"));
        });

        function openFolder(folderId) {
            var folderContent = $('#folderContent_' + folderId);
            if ($(folderContent).is(":hidden")) {
                $.ajax({
                    url: "/new/web/app_dev.php/lencor_entries/view_folders",
                    method: "POST",
                    data: {folderId: folderId},
                    success: function (response) {
                        folderContent.html($(response));
                        loadLastUpdateInfo(null, folderId);
                        $.ajax({
                            url: "/new/web/app_dev.php/lencor_entries/view_files",
                            method: "POST",
                            data: {folderId: folderId},
                            success: function (response) {
                                var foldersList = folderContent.children('ul');
                                foldersList.append(response);
                            }
                        });
                        folderContent.show();
                    }
                });
            }
            else {
                $(folderContent).hide();
            }
        }

        /** Archive entries folder creation with form loader **/

        $(document).on("click", 'a[name="addFolder"]', createFolder);

        function createFolder() {
            var entryId = $(this).attr("id");
            /** Load folder creation form **/
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/new_folder",
                method: searchForm.attr('mehtod'),
                data: {entryId: entryId},
                success: function (loadFormResponse) {
                    createFolderBlock.html($(loadFormResponse));
                    createFolderBlock.show();
                    var $folderAddForm = createFolderBlock.find('#folder_add_form');
                    $folderAddForm.on("submit", function createFolder(event) {
                        event.preventDefault();
                        var folderSerialized = $folderAddForm.serialize();
                        /** Submit new folder **/
                        $.ajax({
                            url: "/new/web/app_dev.php/lencor_entries/new_folder",
                            method: $folderAddForm.attr('method'),
                            data: folderSerialized,
                            success: function () {
                                createFolderBlock.hide();
                                var folderId = $folderAddForm.find('select[id="folder_add_form_parentFolder"]').val();
                                var folderContent = $('#folderContent_' + folderId);
                                folderContent.hide();
                                /** Reload folder view order **/
                                openFolder(folderId);
                                /** Load flash messages **/
                                loadFlashMessages();
                            }
                        });
                    });
                }
            });
        }

        /** Archive entries file upload with form loader **/

        $(document).on("click", 'a[name="addFile"]', uploadFile);

        function uploadFile() {
            var entryId = $(this).attr("id");
            /** Load file upload form **/
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/new_file",
                method: searchForm.attr('mehtod'),
                data: {entryId: entryId},
                success: function (loadFormResponse) {
                    uploadFileBlock.html($(loadFormResponse));
                    uploadFileBlock.show();
                    var $fileAddForm = uploadFileBlock.find('#file_add_form');
                    $fileAddForm.on("submit", function uploadFile(event) {
                        event.preventDefault();
                        //var fileSerialized = $fileAddForm.serialize();
                        var fileSerialized = new FormData($(this)[0]);
                        /** Submit new file **/
                        $.ajax({
                            url: "/new/web/app_dev.php/lencor_entries/new_file",
                            method: $fileAddForm.attr('method'),
                            data: fileSerialized,
                            processData: false,
                            contentType: false,
                            success: function () {
                                uploadFileBlock.hide();
                                folderId = $fileAddForm.find('select[id="file_add_form_parentFolder"]').val();
                                var folderContent = $('#folderContent_' + folderId);
                                /** Reload folder view order **/
                                $.ajax({
                                    url: "/new/web/app_dev.php/lencor_entries/view_files",
                                    method: searchForm.attr('method'),
                                    data: {folderId: folderId},
                                    success: function (reloadResponse) {
                                        folderContent.hide();
                                        var foldersList = folderContent.children('ul');
                                        foldersList.html(reloadResponse);
                                        folderContent.show();
                                        loadLastUpdateInfo(null, folderId);
                                    }
                                });
                                /** Load flash messages **/
                                loadFlashMessages();
                            }
                        });
                    });
                }
            });
        }

        /** Last entry update information load & refresh **/

        function loadLastUpdateInfo(entryId, folderId) {
            if (entryId !== null) {
                $.ajax({
                    url: "/new/web/app_dev.php/lencor_entries/last_update_info",
                    method: "POST",
                    data: {entryId: entryId},
                    success: function (reloadLastUpdateInfo) {
                        $($('#entryContent_' + entryId).find('#entry-content-buttons-spacer')).html(reloadLastUpdateInfo);
                    }
                });
            }
            else if (folderId !== null) {
                $.ajax({
                        url: "/new/web/app_dev.php/lencor_entries/get_folder_entryId",
                        method: "POST",
                        data: {folderId: folderId},
                        success: function (entryId) {
                            $.ajax({
                                url: "/new/web/app_dev.php/lencor_entries/last_update_info",
                                method: "POST",
                                data: {folderId: folderId},
                                success: function (reloadLastUpdateInfo) {
                                    $($('#entryContent_' + entryId).find('#entry-content-buttons-spacer')).html(reloadLastUpdateInfo);
                                }
                            });

                        }
                    }
                );
            }
        }

        /** Archive entries file removal action **/

        $(document).on("click", 'a[name="removeFile"]', removeFile);

        function removeFile() {
            var fileId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/remove_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRemoval) {
                    $('#file_' + fileId).replaceWith(fileRemoval);
                }
            })
        }

        /** Archive entries file restore action **/

        $(document).on("click", 'a[name="restoreFile"]', restoreFile);

        function restoreFile() {
            var fileId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/restore_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRestoration) {
                    $('#file_' + fileId).replaceWith(fileRestoration);
                }
            })
        }

        /** Archive entries folder removal action **/

        $(document).on("click", 'a[name="removeFolder"]', removeFolder);

        function removeFolder() {
            var folderId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/remove_folder",
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRemoval) {
                    $('#folder_' + folderId).replaceWith(folderRemoval);
                    $('#folderContent_' + folderId).html('');
                    $('#folderContent_' + folderId).hide();
                }
            })
        }

        /** Archive entries folder restore action **/

        $(document).on("click", 'a[name="restoreFolder"]', restoreFolder);

        function restoreFolder() {
            var folderId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/restore_folder",
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRestoration) {
                    $('#folder_' + folderId).replaceWith(folderRestoration);
                }
            })
        }

        /** Popup window close **/

        $(document).on("click", "a[name='closeForm']", function closeForm(event) {
            event.preventDefault();
            $(this).parent().hide();
        });

        /** Flash messages loader **/

        function loadFlashMessages() {
            $.ajax({
                url: "/new/web/app_dev.php/lencor_flash_messages",
                method: "POST",
                success: function (reloadFlashMessages) {
                    $('#flash-messages').replaceWith(
                        $(reloadFlashMessages));
                }
            });
        }
    }
});
