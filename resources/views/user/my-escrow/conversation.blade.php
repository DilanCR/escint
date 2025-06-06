@extends('user.layouts.master') 
@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Escrow Conversation")])
@endsection
@push('css')
    <style>
        .text-capitalize{
            text-transform: capitalize;
        }
        .text-right{
            float: right
        }
        .previewImage{
            position: absolute;
            bottom: 74px;
        }
    </style>
@endpush
@section('content')
<div class="body-wrapper">
    <div class="custom-card support-card mt-10">
        <div class="support-card-wrapper">
            <div class="card-header">
                <div class="card-header-user-area"> 
                    <div class="card-header-user-content">
                        <h6 class="title"></h6>
                        <span class="sub-title">{{ __('Escrow ID') }} : <span class="text--warning">#{{ $escrow->escrow_id }}</span></span>
                    </div>
                </div>
                <div class="info-btn">
                    <i class="las la-info-circle"></i>
                </div>
            </div>
            <div class="support-chat-area">
                <div class="chat-container messages">
                    <ul>
                        @foreach ($escrow->conversations ?? [] as $item)
                            <li class="media media-chat @if ($item->sender == auth()->user()->id) media-chat-reverse sent @else replies @endif">
                                <img class="avatar" src="{{ $item->senderImage }}" alt="Profile">
                                <div class="media-body">
                                    @if ($item->message !=null)
                                    <p>{{ $item->message }}</p> 
                                    @endif 
                                    @if ($item->conversationsAttachments != null)
                                    @foreach ($item->conversationsAttachments as $file) 
                                        @php
                                            $file_type = is_object($file->attachment_info) ? explode("/", $file->attachment_info->type)[0] : null;
                                        @endphp
                                        
                                        @if ($file_type == "image")
                                            <img src="{{ get_image($file->attachment, 'escrow-conversation') }}" style="float: right" alt="{{ $item->file_path }}" width="240"> 
                                        @else
                                            <div class="file-attach">
                                                <div class="icon-area">
                                                    <div class="content">
                                                        @if (is_object($file->attachment_info))
                                                            <h6 class="title">{{ $file->attachment_info->original_name }}</h6>
                                                        @endif
                                                    </div>
                                                </div>
                                                <a href="{{ setRoute('file.download', ['escrow-conversation', $file->attachment]) }}" class="download-btn"><i class="las la-cloud-download-alt"></i></a>
                                            </div>
                                        @endif
                                         
                                    @endforeach
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="chat-form">
                    <div class="publisher">
                        <span class="publisher-btn file-group">
                            <input type="file" id="fileUpload" accept=".jpg,.jpeg,.png,.pdf,.doc,.xls,.xlsx">
                            <label for="fileUpload"><i class="fa fa-paperclip"></i></label>
                        </span>
                        <div class="chatbox-message-part">
                            <div id="file-preview"></div>
                            <input class="publisher-input message-input message-input-event" type="text" name="message" placeholder="Write something....">
                        </div>
                        <div class="chatbox-send-part">
                            <button type="button" class="chat-submit-btn chat-submit-btn-event"><i class="lab la-telegram-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>  
        @include('user.my-escrow.escrow-details',['escrow' => $escrow])
    </div>
</div>
@endsection
@push('script')
    @if (isset($escrow) && isset($escrow->id))
        @if ($basic_settings->broadcast_config != null && $basic_settings->broadcast_config->method == "pusher")
            <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
            <script>
                var primaryKey = "{{ $basic_settings->broadcast_config->primary_key ?? '' }}";
                var cluster = "{{ $basic_settings->broadcast_config->cluster ?? "" }}";
                var userProfile = "{{ get_image(auth()->user()->userImage,'user-profile') }}";
                var pusher = new Pusher(primaryKey,{cluster: cluster});
                var token         = "{{ $escrow->id ?? "" }}";
                var URL           = "{{ setRoute('user.escrow-action.message.send') ?? "" }}";
                var fileUploadUrl = "{{ setRoute('user.escrow-action.conversation.file.upload')}}";
                var conversationFilePath = "{{ files_asset_path('escrow-conversation') }}";
                var FileDownloadBaseURL = "{{ setRoute('file.download',['escrow-conversation','']) }}";
                var channel       = pusher.subscribe('support.conversation.'+token);

                channel.bind('escrow-conversation', function(data) {
                    data = JSON.stringify(data);
                    data = JSON.parse(data);
                    var addClass = "";
                    if(data.conversation.sender == {{auth()->user()->id}}) {
                        addClass = "media-chat-reverse";
                    }
                    if(data.attachments.length > 0) {
                        // Message Contain Files
                        var imageFiles = '';
                        var otherFiles = '';
                        $.each(data.attachments,function(index,item) {
                            console.log(item);
                            if(item.attachment_info.type.split("/").shift() == "image") {
                                imageFiles += `
                                    <div class="image-attach">
                                        <img src="${conversationFilePath}/${item.attachment}" alt="image" width="320">
                                    </div>
                                `
                            }else {
                                otherFiles += `
                                    <div class="file-attach">
                                        <div class="icon-area">
                                            <div class="content">
                                                <h6 class="title">${item.attachment}</h6>
                                            </div>
                                        </div>
                                        <a href="${FileDownloadBaseURL}/${item.attachment}" class="download-btn"><i class="las la-cloud-download-alt"></i></a>
                                    </div>
                                `;
                            }

                        });
                        var chatBlock = `
                            <li class="media media-chat ${addClass} replies">
                                <img class="avatar" src="${data.senderImage}" alt="user">
                                <div class="media-body">
                                    ${data.conversation.message !== null ? `<p>${data.conversation.message}</p>` : ''}
                                    <div class="image-attach-wrapper" ${ imageFiles.length > 0 ? `style="min-height:30px"` : ""}>
                                        ${imageFiles}
                                    </div>
                                    ${otherFiles}
                                </div>
                            </li>
                        `;
                    }else {
                        var chatBlock = `
                            <li class="media media-chat ${addClass} replies">
                                <img class="avatar" src="${data.senderImage}" alt="user">
                                <div class="media-body">
                                    <p>${data.conversation.message}</p>
                                </div>
                            </li>
                        `;
                    } 
                    $(".support-chat-area .messages ul").append(chatBlock);
                });
                $(document).on("keyup",".message-input-event",function(e){
                    if(e.which == 13) {
                        $(this).removeClass("message-input-event");
                        eventInit($(this),'message-input-event');
                    }
                });
                $(document).on("click",".chat-submit-btn-event",function(e) {
                    e.preventDefault();
                    $(this).removeClass("chat-submit-btn-event");
                    eventInit($(this),'chat-submit-btn-event');
                });
                function eventInit(e,removeClass) {
                    var CSRF = "{{ csrf_token() }}";
                    var inputValue = $(".message-input").val();
                    var fileInput = $("#fileUpload")[0]; 
                    // Check if a file is selected
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        var formData = new FormData();
                        formData.append('_token', CSRF);
                        formData.append('file', fileInput.files[0]); // Assuming you have a single file input
                        $.ajax({
                            url: fileUploadUrl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                var data = {
                                    _token: CSRF,
                                    escrow_id: token,
                                    message: inputValue,
                                    file: response.data,
                                };
                                $.post(URL,data,function(response) {
                                }).done(function(response){
                                    $(".message-input").val("");
                                    $("#file-preview").html("");
                                    fileInput.value = ""; // Reset the file input value
                                    $(e).addClass(removeClass);
                                }).fail(function(response) {
                                    var response = JSON.parse(response.responseText);
                                    throwMessage(response.type,response.message.error);
                                    $(e).addClass(removeClass);
                                });
                            },
                            error: function(response) {
                                var responseObj = JSON.parse(response.responseText);
                                throwMessage(responseObj.type, responseObj.message.error);
                                $(e).addClass(removeClass);
                            }
                        });
                    }else {
                        var data = {
                            _token: CSRF,
                            escrow_id: token,
                            message: inputValue, 
                        };
                        $.post(URL,data,function(response) {
                        // Executed
                        }).done(function(response){
                            $(".message-input").val("");
                            $(e).addClass(removeClass);
                        }).fail(function(response) {
                            var response = JSON.parse(response.responseText);
                            throwMessage(response.type,response.message.error);
                            $(e).addClass(removeClass);
                        });
                    } 
                }
            </script>
            <script>
                  $('#fileUpload').on('change', function (e) {
                    var fileInput = e.target;
                    var file = fileInput.files[0];
                    if (file) {
                        if (file.type.match('image.*')) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                $('#file-preview').html('<img src="' + e.target.result + '" alt="File Preview" class="previewImage" width="220">');
                            };
                            reader.readAsDataURL(file);
                        } else {
                            // Non-image file
                            $('#file-preview').html('<p class="previewImage">' + file.name + '</p>');
                        }
                    }
                });
            </script>
        @endif
    @endif
@endpush



