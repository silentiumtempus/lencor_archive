$(document).ready(function () {
    if (!window.jQuery) {
    } else {
        /** Do not touch this **/
        let path = $("#main-table").attr("data-path");
        let $factory = $('#archive_entry_search_form_factory');
        let searchForm = $("#archive_entry_search_form");
        let resetButton = $("#archive_entry_search_form_resetButton");
        let createFolderBlock = $("#addFolder");
        let uploadFileBlock = $("#addFile");
        let downloadFileBlock = $("#downloadFile");
        /** Seriously, it'a a bad idea  :) **/

        /** Archive entries main table factory->settings AJAX loader **/

        $factory.on("change", function settingsLoadAction() {
            let data = {};
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
            return false;
        });

        /** Archive entries main table content AJAX loader **/

        searchForm.on("submit", function searchAction(event) {
            event.preventDefault();
            let fields = searchForm.serializeArray();
            let values = {};
            jQuery.each(fields, function (i, field) {
                values[field.name] = field.value;
            });
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
            return false;
        });

        /** Archive entries main table search form reset to default stdout AJAX loader **/

        resetButton.on("click", function resetAction() {
            searchForm.trigger('reset');
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
            return false;
        });

        /** Archive entries content loader **/

        $(document).on("click", "a[name='entryid']", openEntryContents);

        function openEntryContents() {
            let entryId = $(this).parent().attr("id");
            let contentPlace = $('#entryContent_' + entryId);
            if ($(contentPlace).is(":hidden")) {
                $.ajax({
                    url: "/new/web/app_dev.php/lencor_entries_view",
                    method: searchForm.attr('method'),
                    data: {entryId: entryId},
                    success: function (response) {
                        contentPlace.html($(response));
                        loadLastUpdateInfo(entryId, null);
                        let folderId = contentPlace.find('#rootEntry').children('td').attr('id');
                        openFolder(folderId);
                        contentPlace.show();
                    }
                });
            }
            else {
                $(contentPlace).hide();
            }
            return false;
        }

        /** Archive entries content navigation **/

        $(document).on("click", "a[name='openFolder']", function () {
            let folderId = $(this).attr("id");
            openFolder(folderId);
            return false;
        });

        function openFolder(folderId) {
            let folderContent = $('#folderContent_' + folderId);
            let fileContent = $('#fileContent_' + folderId);
            if ($(folderContent).is(":hidden")) {
                $.ajax({
                    url: "/new/web/app_dev.php/lencor_entries/view_folders",
                    method: "POST",
                    data: {folderId: folderId},
                    success: function (response) {
                        folderContent.html(response);
                        loadLastUpdateInfo(null, folderId);
                    }
                });
                $.ajax({
                    url: "/new/web/app_dev.php/lencor_entries/view_files",
                    method: "POST",
                    data: {folderId: folderId},
                    success: function (response) {
                        fileContent.html(response);
                    }
                });
                folderContent.show();
                fileContent.show();
            }
            else {
                folderContent.hide();
                fileContent.hide();
            }
            return false;
        }

        /** Archive entries folder creation with form loader **/

        $(document).on("click", 'a[name="addFolder"]', createFolder);

        function createFolder() {
            let entryId = $(this).attr("id");
            /** Load folder creation form **/
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/new_folder",
                method: searchForm.attr('mehtod'),
                data: {entryId: entryId},
                success: function (loadFormResponse) {
                    createFolderBlock.html($(loadFormResponse));
                    createFolderBlock.show();
                    let $folderAddForm = createFolderBlock.find('#folder_add_form');
                    $folderAddForm.on("submit", function createFolder(event) {
                        event.preventDefault();
                        let folderSerialized = $folderAddForm.serialize();
                        /** Submit new folder **/
                        $.ajax({
                            url: "/new/web/app_dev.php/lencor_entries/new_folder",
                            method: $folderAddForm.attr('method'),
                            data: folderSerialized,
                            success: function () {
                                createFolderBlock.hide();
                                let folderId = $folderAddForm.find('select[id="folder_add_form_parentFolder"]').val();
                                let folderContent = $('#folderContent_' + folderId);
                                /** Reload folder view order **/
                                $.ajax({
                                    url: "/new/web/app_dev.php/lencor_entries/view_folders",
                                    method: searchForm.attr('method'),
                                    data: {folderId: folderId},
                                    success: function (reloadResponse) {
                                        // @TODO: create proper design
                                        folderContent.hide();
                                        openFolder(folderId);
                                        folderContent.html(reloadResponse);
                                        folderContent.show();
                                        loadLastUpdateInfo(null, folderId);
                                    }
                                });
                                loadFlashMessages();
                            }
                        });
                    });
                }
            });
            return false;
        }

        /** Archive entries file upload with form loader **/

        $(document).on("click", 'a[name="addFile"]', uploadFile);

        function uploadFile() {
            let entryId = $(this).attr("id");
            /** Load file upload form **/
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/new_file",
                method: searchForm.attr('mehtod'),
                data: {entryId: entryId},
                success: function (loadFormResponse) {
                    uploadFileBlock.html($(loadFormResponse));
                    uploadFileBlock.show();
                    let $fileAddForm = uploadFileBlock.find('#file_add_form');
                    $fileAddForm.on("submit", function uploadFile(event) {
                        event.preventDefault();
                        //var fileSerialized = $fileAddForm.serialize();
                        let fileSerialized = new FormData($(this)[0]);
                        /** Submit new file **/
                        $.ajax({
                            url: "/new/web/app_dev.php/lencor_entries/new_file",
                            method: $fileAddForm.attr('method'),
                            data: fileSerialized,
                            processData: false,
                            contentType: false,
                            success: function () {
                                uploadFileBlock.hide();
                                let folderId = $fileAddForm.find('select[id="file_add_form_parentFolder"]').val();
                                let fileContent = $('#fileContent_' + folderId);
                                /** Reload folder view order **/
                                $.ajax({
                                    url: "/new/web/app_dev.php/lencor_entries/view_files",
                                    method: searchForm.attr('method'),
                                    data: {folderId: folderId},
                                    success: function (reloadResponse) {
                                        // @TODO: create proper design
                                        openFolder(folderId);
                                        fileContent.hide();
                                        fileContent.html(reloadResponse);
                                        fileContent.show();
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
            return false;
        }

        /** Archive entries file download with md5 verification **/

        $(document).on("click", 'a[name="downloadFile"]', downloadFile);

        function downloadFile() {
            let fileId = $(this).attr("id");
            /** Load file download block **/
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/download_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (downloadBlockResponse) {
                    downloadFileBlock.html($(downloadBlockResponse));
                    downloadFileBlock.show();
                }
            });
            let fileInfo = $('#file_' + fileId);
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/reload_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (reloadFileInfo) {
                    fileInfo.replaceWith(reloadFileInfo);
                }
            });
            return false;
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
            return false;
        }

        /** Archive entries file removal action **/

        $(document).on("click", 'a[name="removeFile"]', removeFile);

        function removeFile() {
            let fileId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/remove_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRemoval) {
                    $('#file_' + fileId).replaceWith(fileRemoval);
                }
            });
            return false;
        }

        /** Archive entries file restore action **/

        $(document).on("click", 'a[name="restoreFile"]', restoreFile);

        function restoreFile() {
            let fileId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/restore_file",
                method: "POST",
                data: {fileId: fileId},
                success: function (fileRestoration) {
                    $('#file_' + fileId).replaceWith(fileRestoration);
                }
            });
            return false;
        }

        /** Archive entries folder removal action **/

        $(document).on("click", 'a[name="removeFolder"]', removeFolder);

        function removeFolder() {
            let folderId = $(this).attr("id");
            let folderContent = $('#folderContent_' + folderId);
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/remove_folder",
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRemoval) {
                    $('#folder_' + folderId).replaceWith($(folderRemoval).find('#folder_' + folderId));
                    folderContent.html('');
                    folderContent.hide();
                }
            });
            return false;
        }

        /** Archive entries folder restore action **/

        $(document).on("click", 'a[name="restoreFolder"]', restoreFolder);

        function restoreFolder() {
            let folderId = $(this).attr("id");
            $.ajax({
                url: "/new/web/app_dev.php/lencor_entries/restore_folder",
                method: "POST",
                data: {folderId: folderId},
                success: function (folderRestoration) {
                    $('#folder_' + folderId).replaceWith($(folderRestoration).find('#folder_' + folderId));
                }
            });
            return false;
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
            return false;
        }
    }
});
