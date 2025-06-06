
var xhrRecords             = [];
var uploadedFilesResponse  = [];
var uploadedFiles          = [];
var uploadProcessIsRunning = false;

function getFile(file,uniqueID = null) {
    return new Promise(function(resolve,exception) {
        var fileReader        = new FileReader();
            fileReader.onload = function() {
            resolve({
                blob    : fileReader.result,
                uniqueID: uniqueID,
            });
        }
        fileReader.readAsDataURL(file);
    });
}

function uploadFileToServer(URL,file,fileInput,orderId = null,htmlDomClass = null, RemoveURL = null) {
    uploadProcessIsRunning = true;

    let htmlPreviewElement = $("." + htmlDomClass);

    var laravelMetaCSRF = laravelCsrf();
    let formData        = new FormData();
    formData.append('file',file);
    formData.append('order',orderId);
    formData.append('_token',laravelMetaCSRF);

    let formxhr = new XMLHttpRequest();
    formxhr.open("POST",URL);
    xhrRecords.push(formxhr);

    formxhr.onload = function() {
        let response               = JSON.parse(this.responseText);
            uploadProcessIsRunning = false;

        if(this.status == 200) {
            uploadedFiles[response.data.order]         = response.data.dev_path;
            uploadedFilesResponse[response.data.order] = response.data;
            htmlPreviewElement.find(".image-attach,.file-attach").addClass("success");
        }else {
            htmlPreviewElement.find(".image-attach,.file-attach").removeClass("success").addClass("danger");
            htmlPreviewElement.find(".upload-progress-wrapper").append(`<span class="text-danger d-block text-center">${response.message.error[0]}</span>`);
        }
    }

    formxhr.upload.addEventListener("progress", ({loaded, total}) => {
        uploadProcessIsRunning = true;
        if(htmlDomClass != null) {
            let totalSize   = readableFileSize((total / 1024));
            let totalLoaded = readableFileSize((loaded / 1024));

            let loadedPercentage = Math.floor((loaded / total) * 100);

            htmlPreviewElement.find(".upload-progress-wrapper").html(`<div class="progress upload-progress">
                                <div class = "progress-bar" role = "progressbar" style = "width: ${loadedPercentage}%;" aria-valuenow = "${loadedPercentage}" aria-valuemin = "0" aria-valuemax = "100"></div>
                            </div>
                            <div class = "progress-count">${loadedPercentage}%</div>
            `);

            htmlPreviewElement.find(".image-attach,.file-attach").addClass("upload");
        }
    });

      // Close button click
    if(RemoveURL != null) {
        htmlPreviewElement.find(".file-remove-btn").on("click",function() {

              // find this image uploaded path
            var uploadedPath = uploadedFiles[orderId];
            if(uploadedPath != undefined) {
                  // File already uploaded to server make request to remove this from server
                removeFileFromServer(RemoveURL,uploadedPath).then((response) => {
    
                    htmlPreviewElement.find(".upload-progress-wrapper span").remove();
                    htmlPreviewElement.find(".image-attach,.file-attach").removeClass("danger");
    
                    removeElement(htmlPreviewElement);
    
                      // Remove this item from uploaded array list also
                    uploadedFiles.splice(orderId,1);
                    
                }).catch((response) => {
                    htmlPreviewElement.find(".image-attach,.file-attach").removeClass("success").addClass("danger");
                    htmlPreviewElement.find(".upload-progress-wrapper").append(`<span class="text-danger d-block text-center">${response.message.error[0]}</span>`);
                });
    
                  // Make a loading in html preview element
    
            }else {
                removeElement(htmlPreviewElement);
            }
    
            uploadProcessIsRunning = false;
            formxhr.abort();
        });
    }

    formxhr.send(formData);
}

function removeElement(element) {
    element.slideUp(300);
    setTimeout(() => {
        element.remove();
    }, 300);
}

function removeFileFromServer(URL,filePath) {
    return new Promise(function(resolve,exception) {
        var formData = {
            _token: laravelCsrf(),
            path  : filePath,
        };
        $.post(URL,formData,function(response) {
              // Success
        }).done(function(response) {
            resolve(response);
        }).fail(function(response) {
            var responseText = response.responseJSON;
            exception(responseText);
        });
    })
}
function readableFileSize(sizeInKB) {
    let readableSize = sizeInKB + " KB";
    if(sizeInKB < 1024) {
        readableSize = sizeInKB + " KB";
    }else if(sizeInKB >= 1024 && sizeInKB < 1000000) {
        readableSize = (sizeInKB / 1024) + " MB";
    }else if(sizeInKB >= 1000000) {
        readableSize = (sizeInKB / 1000000) + " GB";
    }
    return Math.ceil(readableSize);
}
function sendMessageEvent(URL,formData) {
    return new Promise(function(resolve,exception) {
        $.post(URL,formData,function(response) {
              // Success
        }).done(function(response) {
            resolve(response);
        }).fail(function(response) {
            let responseText = response.responseJSON;
            exception(responseText);
        });
    });
}

function singleMessageMarkup(data = {image:"",message:"",class:"",attribute:""}) {

    return `<li class="media media-chat ${data.class}" style="display:none" ${data.attribute}>
        <img class = "avatar" src = "${data.image}" alt = "user">
        <div class = "media-content">
        <div class = "media-body">
                <p>${data.message}</p>
            </div>
        </div>
    </li>`;
    
}

function updateConversationSeenStatus(URL,requestData) {
      // console.log(URL,requestData);
    $.post(URL,requestData,function(response) {
          // Success
    }).done(function(response) {
          // console.log(response);
    }).fail(function(response) {
          // console.log("fail",response);
    });
}