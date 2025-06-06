@extends('admin.layouts.master')

@push('css')

@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Support Chat")])
@endsection

@section('content')
    <div class="custom-card support-card">
        <div class="support-card-wrapper">
            <div class="card-header">
                <div class="card-header-user-area"> 
                    <div class="card-header-user-content">
                        <h6 class="title"></h6>
                        <span class="sub-title">{{ __("Escrow ID") }} : <span class="text--danger">#{{ $escrows->escrow_id}}</span></span>
                    </div>
                </div>
                <div class="info-btn">
                    <i class="las la-info-circle"></i>
                </div>
            </div>
            <div class="support-chat-area">
                <div class="chat-container messages">
                    <ul>
                        @foreach ($escrows->conversations ?? [] as $item)
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
                            <input type="file" name="image" id="data">
                            <label for="data"><i class="fa fa-paperclip"></i></label>
                        </span>
                        <div class="chatbox-message-part">
                            <input class="publisher-input message-input message-input-event" type="text" name="message" placeholder="Write something....">
                        </div>
                        <div class="chatbox-send-part">
                            <button type="button" class="chat-submit-btn chat-submit-btn-event"><i class="lab la-telegram-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.sections.escrow.conversation-details',['escrows' => $escrows])
    </div>
@endsection

@push('script')
    @if (isset($escrows) && isset($escrows->id))
        @if ($basic_settings->broadcast_config != null && $basic_settings->broadcast_config->method == "pusher")

            <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
            <script>
                var primaryKey = "{{ $basic_settings->broadcast_config->primary_key ?? '' }}";
                var cluster = "{{ $basic_settings->broadcast_config->cluster ?? "" }}";
                var userProfile = "{{ get_image(auth()->user()->userImage,'user-profile') }}";

                var pusher = new Pusher(primaryKey,{cluster: cluster});

                var token = "{{ $escrows->id ?? "" }}";
                var URL = "{{ setRoute('admin.escrow.message.send') ?? "" }}";
                var channel = pusher.subscribe('support.conversation.'+token); 

                channel.bind('escrow-conversation', function(data) {
                    data = JSON.stringify(data);
                    data = JSON.parse(data);
                    // console.log(data.senderImage);
                    var addClass = "";
                    if(data.conversation.sender == {{auth()->user()->id}}) {
                        addClass = "media-chat-reverse";
                    }
                    var chatBlock = `
                        <li class="media media-chat ${addClass} replies">
                            <img class="avatar" src="${data.senderImage}" alt="Admin">
                            <div class="media-body">
                                <p>${data.conversation.message}</p>
                            </div>
                        </li>
                    `;
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
                    var inputValue = $(".message-input").val();
                    if(inputValue.length == 0) return false;
                    var CSRF = "{{ csrf_token() }}";
                    var data = {
                        _token: CSRF,
                        message: inputValue,
                        escrow_id: token,
                    };

                    $.post(URL,data,function(response) {
                        // Executed
                    }).done(function(response){
                        console.log(response);
                        $(".message-input").val("");
                        $(e).addClass(removeClass);
                    }).fail(function(response) {
                        // alert('fail')
                        var response = JSON.parse(response.responseText);
                        throwMessage(response.type,response.message.error);
                        $(e).addClass(removeClass);
                    });
                }

            </script>
        @endif
    @endif
@endpush