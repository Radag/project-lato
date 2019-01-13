function setUploadForm(uploadDropzoneId, config) {
    
    var uploadTemplate = $('<li>' +
        '<div class="attached-file">' +
            '<a href="" target="_blank" class="file-name"></a>' +
            '<div class="flex-spacer"></div>' +
            '<a href="" class="remove-attachment"><i class="material-icons">delete</i></a>' +
        '</div>' +
        '<div class="progress upload-file-progress">' +
            '<div class="determinate" style="width: 0%"></div>' +
        '</div> ' +
    '</li>');
    
    var uploadsFiles = {};
    
    var uploadContent = new Dropzone(uploadDropzoneId, { 
        url: config.links.upload,
        previewTemplate: '<div id="preview-template" classs="hide"></div>'
    });

    uploadContent.on("addedfile", function(file) {              
        var id = Math.random().toString(36).substr(2, 10);
        var attachTemplate = uploadTemplate.clone();
        attachTemplate.addClass(id);
        attachTemplate.find(".file-name").text(file.name);
        file.classId = id;
        $(config.uploadBlock).append(attachTemplate).removeClass('hide');
        $(config.submitButton).prop('disabled', true);
        attachTemplate.find('.remove-attachment').on('click', function(event) {
            event.preventDefault();
            if(file.remoteIdFile != undefined) {
                var link = config.links.delete.replace('XXXX', file.remoteIdFile);
                $.get( link, function( data ) {
                    if(data.deleted) {
                        var values = $(config.inputWithIds).val();
                        $(config.inputWithIds).val(values.replace("_" + file.remoteIdFile, ""));
                        attachTemplate.remove();
                    }
                });
            } else {
                uploadContent.removeFile(file);
                attachTemplate.remove();
            }
        });
    });

    uploadContent.on("uploadprogress", function(file) {
        uploadsFiles[file.classId] = false;
        $("." + file.classId).find('.determinate').css('width', file.upload.progress + '%');
    });

    uploadContent.on("complete", function(file) {
        uploadsFiles[file.classId] = true;
        var allDone = true;
        for(var i in uploadsFiles) {
            if(!uploadsFiles[i]) {
                allDone = false;
            }
        }
        if(allDone ) {
            $(config.submitButton).prop('disabled', false);
        }
        var uploadedFile = JSON.parse(file.xhr.response);
        var attachTemplate = $("." + file.classId);
        $(config.uploadBlock).find('div.error').remove();
        if(uploadedFile.error) {
            attachTemplate.remove();
            $(config.uploadBlock).append("<div class='error'>" + uploadedFile.message + "</div>");
//            latoShowUpFlashMessages();
        } else {
            attachTemplate.find(".progress").hide();
            attachTemplate.find(".file-name").html(uploadedFile.file.fileName + " <span>" + uploadedFile.file.type + "</span>");
            switch(uploadedFile.file.type) {
                case 1:
                attachTemplate.find(".attachment-icon").addClass('image');
                attachTemplate.find(".attachment-icon .material-icons").text('image');
                break;
                case 3:
                attachTemplate.find(".attachment-icon").addClass('document');
                attachTemplate.find(".attachment-icon .material-icons").text('insert_drive_file');
                break;
                case 4:
                attachTemplate.find(".attachment-icon").addClass('spreadsheet');
                attachTemplate.find(".attachment-icon .material-icons").text('equalizer');
                break;
                case 5:
                attachTemplate.find(".attachment-icon").addClass('presentation');
                attachTemplate.find(".attachment-icon .material-icons").text('videocam');
                break;
                case 6:
                attachTemplate.find(".attachment-icon").addClass('presentation');
                attachTemplate.find(".attachment-icon .material-icons").text('description');
                break;
                default:
                attachTemplate.find(".attachment-icon").addClass('unknown');
                attachTemplate.find(".attachment-icon .material-icons").text('help_outline');
            }

            attachTemplate.find(".attached-file a").attr('href', uploadedFile.file.fullPath);
            $(config.uploadBlock).append(attachTemplate);
            file.remoteIdFile = uploadedFile.file.idFile;
            $(config.inputWithIds).val($(config.inputWithIds).val() + "_" + uploadedFile.file.idFile);
        }
    });
    
    if(!$.nette.ext('deleteAttachmentButton')) {
        $.nette.ext('deleteAttachmentButton', {
            start: function (jqXHR, settings) {
                if(settings.nette !== undefined && $(settings.nette.ui).hasClass('remove-attachment')) { 
                    $('#full-screen-loader-modal').modal('open');
                    jqXHR.done(function( data, textStatus, jqXHR ) {
                        $('#full-screen-loader-modal').modal('close');     
                        if(data.invalidForm === undefined || !data.invalidForm) {
                            $(settings.nette.ui).parents('.attached-file').remove();
                        }
                    });
                } 
            }
        });
    }
}