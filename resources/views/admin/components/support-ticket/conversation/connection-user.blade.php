
@push('script')
    @if (isset($support_ticket) && isset($support_ticket->token))
        @if ($basic_settings->broadcast_config != null && $basic_settings->broadcast_config->method == "pusher")

            <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
            <script>
                var primaryKey = "{{ $basic_settings->broadcast_config->primary_key ?? '' }}";
                var cluster = "{{ $basic_settings->broadcast_config->cluster ?? "" }}";
                var userProfile = "{{ get_image(auth()->user()->userImage,'user-profile') }}";

                var pusher = new Pusher(primaryKey,{cluster: cluster});

                var token = "{{ $support_ticket->token ?? "" }}";
                var URL = "{{ $route ?? "" }}";
                var channel = pusher.subscribe('support.conversation.'+token);
                channel.bind('support-conversation', function(data) {
                    data = JSON.stringify(data);
                    data = JSON.parse(data);
                    // console.log(data);
                    var addClass = "";
                    if(data.conversation.sender_type == "USER") {
                        addClass = "media-chat-reverse";
                    }
                    var chatBlock = `
                        <li class="media media-chat ${addClass} replies">
                            <img class="avatar" src="${data.conversation.senderImage}" alt="user">
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
                    var CSRF = "{{ csrf_token() }}";
                    //message submit
                    if(inputValue.length == 0) return false; 
                    var data = {
                        _token       : CSRF,
                        support_token: token,
                        message      : inputValue, 
                        file_path      : null, 
                    };
                    $.post(URL,data,function(response) {
                        // Executed
                    }).done(function(response){
                        console.log(response);
                        $(".message-input").val("");
                        $(e).addClass(removeClass);
                    }).fail(function(response) {
                        var response = JSON.parse(response.responseText);
                        throwMessage(response.type,response.message.error);
                        $(e).addClass(removeClass);
                    });
                }
            </script>
        @endif
    @endif
@endpush