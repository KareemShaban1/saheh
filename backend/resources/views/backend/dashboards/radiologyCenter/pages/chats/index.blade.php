@extends('backend.dashboards.radiologyCenter.layouts.master')

@push('styles')
<style>
    .message {
        font-size: 16px;
        font-weight: 500;
        color: white;
        padding: 10px;
        border-radius: 5px;
        word-wrap: break-word;
        white-space: pre-wrap;
        max-width: 75%;
        display: inline-block;
        line-height: 1.5;
    }

    #messages li {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .message small {
        font-size: 11px;
        opacity: 0.8;
        margin-left: 10px;
    }

    .patient-item {
        cursor: pointer;
    }

    .patient-item.active {
        background-color: #007bff;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="row p-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">{{ __('Patients') }}</div>
            <ul class="list-group list-group-flush">
                @foreach ($patients as $patient)
                <li class="list-group-item patient-item" data-patient-id="{{ $patient->id }}">
                    {{ $patient->name }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card" id="chat-container" style="display: none;">
            <div class="card-header">{{ __('Chat') }}</div>

            <div class="card-body" id="chat-box" style="height: 400px; overflow-y: scroll; background: #f9f9f9;">
                <ul id="messages" class="list-unstyled mb-0"></ul>
            </div>

            <div class="card-footer">
                <form id="message-form" enctype="multipart/form-data">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light" type="button" id="upload-image">📷</button>
                        <input type="file" id="image" accept="image/*" style="display: none;">
                        <input type="text" id="message" class="form-control" placeholder="Type a message..." autocomplete="off">
                        <button class="btn btn-primary" type="submit">{{ __('Send') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="https://unpkg.com/laravel-echo/dist/echo.iife.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emojionearea@3.4.1/dist/emojionearea.min.css">
<script src="https://cdn.jsdelivr.net/npm/emojionearea@3.4.1/dist/emojionearea.min.js"></script>

<script>
    $(document).ready(function() {
        const messageInput = $("#message").emojioneArea({
            pickerPosition: "top",
            tonesStyle: "bullet"
        });

        window.Echo = new window.Echo.default({
            broadcaster: 'pusher',
            key: 'local',
            wsHost: window.location.hostname,
            wsPort: 8080,
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws'],
            authEndpoint: "{{ url('/broadcasting/auth') }}",
            withCredentials: true
        });

        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleString();
        }

        let currentChatId = null;
        let currentChannel = null;

        function displayMessage(msg, imageUrl) {
            console.log(msg);
            const li = $('<li>').addClass('mb-2')
                .addClass(msg.sender_type === 'App\\Models\\User' ? 'text-end' : 'text-start');

            let html = '';
            if (msg.message) html += `<div>${msg.message}</div>`;
            if (imageUrl) html += `<img src="${imageUrl}" class="img-fluid mt-2 rounded" style="max-width: 200px;">`;
            html += `<small class="text-light d-block mt-1">${formatDate(msg.created_at)}</small>`;

            const badge = $('<div>')
                .addClass('badge bg-' + (msg.sender_type === 'App\\Models\\User' ? 'primary' : 'secondary'))
                .addClass('message')
                .html(html);

            li.append(badge);
            $('#messages').append(li);
            $('#chat-box').scrollTop($('#messages').prop("scrollHeight"));
        }

        function loadChat(patientId, element) {
            $.ajax({
                url: `/radiology-center/chats/patient/${patientId}`,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    const messagesList = $('#messages');
                    messagesList.empty();
                    currentChatId = data.chat?.id || null;

                    $('.patient-item').removeClass('active');
                    if (element) element.addClass('active');

                    if (currentChannel) {
                        Echo.leave(`private-chat.${currentChannel}`);
                    }
                    currentChannel = currentChatId;

                    data.messages.forEach(msg => {
                        displayMessage(msg, msg.image_url ?? null);
                    });

                    $('#chat-container').show();
                    $('#message-form').attr('data-patient-id', patientId);

                    Echo.private(`chat.${currentChatId}`).listen('MessageSent', (e) => {
                        if (e.message.chat_id !== currentChatId) return;
                        displayMessage(e.message, e.image_url ?? null);
                    });
                }
            });
        }

        $('.patient-item').on('click', function() {
            const patientId = $(this).data('patient-id');
            loadChat(patientId, $(this));
        });

        const firstPatient = $('.patient-item').first();
        if (firstPatient.length) {
            const patientId = firstPatient.data('patient-id');
            loadChat(patientId, firstPatient);
        }

        $('#upload-image').on('click', function() {
            $('#image').click();
        });

        $('#message-form').on('submit', function(e) {
            e.preventDefault();

            const patientId = $(this).data('patient-id');
            const message = $("#message").data("emojioneArea").getText().trim();
            const image = $('#image')[0].files[0] || null;
            const formData = new FormData();

            if (!message && !image) return;

            formData.append('message', message);
            formData.append('chat_id', currentChatId);
            formData.append('patient_id', patientId);
            if (image) {
                formData.append('image', image);
            }

            $.ajax({
                url: `/radiology-center/messages/send-message/${currentChatId}`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    $("#message").data("emojioneArea").setText('');
                    $('#image').val('');
                }
            });
        });
    });
</script>
@endpush