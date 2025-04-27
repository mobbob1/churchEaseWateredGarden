let currentConversationId = null;
let messagePollingInterval = null;

// Initialize tooltips and popovers
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
});

// Initialize select2 with custom template
$('#participant-select').select2({
    theme: 'bootstrap',
    placeholder: 'Select participants',
    allowClear: true,
    templateResult: formatUserOption,
    templateSelection: formatUserOption
});

// Custom template for user options
function formatUserOption(user) {
    if (!user.id) return user.text;
    
    const role = $(user.element).text().match(/\((.*?)\)/)[1];
    const name = $(user.element).text().replace(/\s*\(.*?\)$/, '');
    
    return $(`
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm mr-2">
                <span class="avatar-title rounded-circle border border-white bg-info">
                    ${name.charAt(0)}
                </span>
            </div>
            <div>
                <span class="d-block">${name}</span>
                <small class="text-muted">${role}</small>
            </div>
        </div>
    `);
}

function loadConversations() {
    $.get('api.php?action=get_conversations')
        .done(function(conversations) {
            const list = $('#conversations-list');
            list.empty();
            
            if (conversations.length === 0) {
                list.append(`
                    <div class="text-center p-4">
                        <div class="empty-img">
                            <i class="fas fa-comments fa-3x text-muted"></i>
                        </div>
                        <p class="mt-3">No conversations yet</p>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newConversationModal">
                            <i class="fas fa-plus"></i> Start a Chat
                        </button>
                    </div>
                `);
                return;
            }
            
            conversations.forEach(function(conv) {
                const participants = conv.participant_names.split(',')
                    .filter(name => name !== CURRENT_USER_NAME)
                    .join(', ');
                
                const lastMessageTime = conv.last_message_time 
                    ? moment(conv.last_message_time).fromNow()
                    : 'No messages';
                    
                list.append(`
                    <div class="chat-conversation ${conv.id === currentConversationId ? 'active' : ''}" 
                         data-id="${conv.id}">
                        <div class="conversation-info">
                            <h5 class="text-truncate" title="${participants}">${participants}</h5>
                            <small>${lastMessageTime}</small>
                        </div>
                    </div>
                `);
            });
        })
        .fail(function(xhr) {
            showNotification('Error loading conversations', 'danger');
        });
}

function loadMessages(conversationId) {
    $.get(`api.php?action=get_messages&conversation_id=${conversationId}`)
        .done(function(messages) {
            const container = $('#chat-messages');
            container.empty();
            
            if (messages.length === 0) {
                container.append(`
                    <div class="text-center p-4">
                        <div class="empty-img">
                            <i class="fas fa-paper-plane fa-3x text-muted"></i>
                        </div>
                        <p class="mt-3">No messages yet</p>
                        <p class="text-muted">Start the conversation by sending a message</p>
                    </div>
                `);
            } else {
                let lastDate = '';
                
                messages.forEach(function(msg) {
                    const isOwn = msg.sender_id == CURRENT_USER_ID;
                    const messageDate = moment(msg.created_at).format('YYYY-MM-DD');
                    
                    // Add date separator if it's a new day
                    if (messageDate !== lastDate) {
                        container.append(`
                            <div class="message-date-separator">
                                <span>${moment(msg.created_at).format('MMMM D, YYYY')}</span>
                            </div>
                        `);
                        lastDate = messageDate;
                    }
                    
                    container.append(`
                        <div class="message ${isOwn ? 'own' : ''}">
                            <div class="message-content">
                                <small class="sender">${msg.sender_name}</small>
                                <div class="text">${msg.message}</div>
                                <small class="time" title="${moment(msg.created_at).format('MMMM D, YYYY h:mm A')}">
                                    ${moment(msg.created_at).format('h:mm A')}
                                </small>
                            </div>
                        </div>
                    `);
                });
            }
            
            container.scrollTop(container[0].scrollHeight);
        })
        .fail(function(xhr) {
            showNotification('Error loading messages', 'danger');
        });
}

function startMessagePolling() {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    if (currentConversationId) {
        messagePollingInterval = setInterval(() => {
            loadMessages(currentConversationId);
        }, 3000);
    }
}

function showNotification(message, type = 'success') {
    $.notify({
        icon: type === 'success' ? 'fas fa-check' : 'fas fa-exclamation-triangle',
        message: message
    }, {
        type: type,
        placement: {
            from: 'top',
            align: 'right'
        },
        time: 3000
    });
}

$(document).ready(function() {
    // Load initial conversations
    loadConversations();
    setInterval(loadConversations, 10000);

    // Handle conversation selection
    $('#conversations-list').on('click', '.chat-conversation', function() {
        currentConversationId = $(this).data('id');
        $('.chat-conversation').removeClass('active');
        $(this).addClass('active');
        
        $('#chat-title').text('Loading...');
        $('#message-input, #message-form button').prop('disabled', false);
        
        loadMessages(currentConversationId);
        startMessagePolling();
    });

    // Handle message sending
    $('#message-form').on('submit', function(e) {
        e.preventDefault();
        
        const messageInput = $('#message-input');
        const message = messageInput.val().trim();
        const sendButton = $(this).find('button[type="submit"]');
        
        if (!message || !currentConversationId) return;
        
        // Disable input and button while sending
        messageInput.prop('disabled', true);
        sendButton.prop('disabled', true);
        
        $.post('api.php?action=send_message', {
            conversation_id: currentConversationId,
            message: message
        })
        .done(function(response) {
            if (response.success) {
                messageInput.val('');
                loadMessages(currentConversationId);
            }
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to send message';
            showNotification(error, 'danger');
        })
        .always(function() {
            messageInput.prop('disabled', false).focus();
            sendButton.prop('disabled', false);
        });
    });

    // Handle new conversation creation
    $('#create-conversation').on('click', function() {
        const participants = $('#participant-select').val();
        const button = $(this);
        
        if (!participants.length) {
            showNotification('Please select at least one participant', 'warning');
            return;
        }
        
        // Disable button while creating conversation
        button.prop('disabled', true);
        
        $.post('api.php?action=create_conversation', {
            participant_ids: participants
        })
        .done(function(response) {
            if (response.success) {
                $('#newConversationModal').modal('hide');
                $('#participant-select').val(null).trigger('change');
                loadConversations();
                
                // Switch to new conversation
                currentConversationId = response.conversation_id;
                loadMessages(currentConversationId);
                startMessagePolling();
                
                showNotification('Conversation created successfully');
            }
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to create conversation';
            showNotification(error, 'danger');
        })
        .always(function() {
            button.prop('disabled', false);
        });
    });

    // Clear polling interval when leaving the page
    $(window).on('beforeunload', function() {
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
        }
    });
});