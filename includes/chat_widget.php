<!-- Chat Widget -->
<style>
    /* Chat Button */
    .chat-toggle-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: #000;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 9999;
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .chat-toggle-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    }
    .chat-icon img { width: 30px; filter: invert(1); }

    /* Chat Box */
    .chat-box {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 350px;
        height: 500px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .chat-box.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    /* Header */
    .chat-header {
        background: #000;
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-title h4 {
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: 1px;
    }
    .chat-title span {
        font-size: 0.8rem;
        color: #888;
        display: block;
        font-family: 'Kanit', sans-serif;
    }
    .close-chat {
        cursor: pointer;
        opacity: 0.7;
        transition: 0.2s;
    }
    .close-chat:hover { opacity: 1; }

    /* Messages Area */
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #fcfcfc;
        display: flex;
        flex-direction: column;
        gap: 15px;
        scroll-behavior: smooth;
    }
    
    /* Message Bubbles */
    .message {
        max-width: 80%;
        padding: 12px 16px;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.5;
        font-family: 'Kanit', sans-serif;
        position: relative;
        animation: popIn 0.3s ease;
    }
    @keyframes popIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .message.user {
        align-self: flex-end;
        background: #000;
        color: white;
        border-bottom-right-radius: 4px;
    }
    .message.admin {
        align-self: flex-start;
        background: #f0f0f0;
        color: #333;
        border-bottom-left-radius: 4px;
    }
    
    /* Input Area */
    .chat-input-area {
        padding: 15px;
        background: white;
        border-top: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .chat-input {
        flex: 1;
        border: none;
        background: #f5f5f5;
        padding: 12px 15px;
        border-radius: 25px;
        font-family: 'Kanit', sans-serif;
        font-size: 0.95rem;
    }
    .chat-input:focus { outline: none; background: #eee; }
    .send-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: #000;
        font-size: 1.2rem;
        transition: 0.2s;
    }
    .send-btn:hover { transform: scale(1.1); }
</style>

<!-- Toggle Button -->
<div class="chat-toggle-btn" onclick="toggleChat()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
    </svg>
</div>

<!-- Chat Interface -->
<div class="chat-box" id="chatBox">
    <div class="chat-header">
        <div class="chat-title">
            <h4>XIVEX SUPPORT</h4>
            <span>Chat with Admin</span>
        </div>
        <div class="close-chat" onclick="toggleChat()">✕</div>
    </div>
    
    <div class="chat-messages" id="chatMessages">
        <!-- Default Welcome Message -->
        <div class="message admin">
            Hello! How can we help you today? 👋
        </div>
    </div>
    
    <div class="chat-input-area">
        <input type="text" class="chat-input" id="chatInput" placeholder="Type here...">
        <button class="send-btn" onclick="sendMessage()">➤</button>
    </div>
</div>

<script>
let isChatOpen = false;
let pollingInterval;

function toggleChat() {
    const chatBox = document.getElementById('chatBox');
    isChatOpen = !isChatOpen;
    
    if (isChatOpen) {
        chatBox.classList.add('active');
        fetchMessages();
        pollingInterval = setInterval(fetchMessages, 3000); // Poll every 3s
        scrollToBottom();
    } else {
        chatBox.classList.remove('active');
        clearInterval(pollingInterval);
    }
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    
    // Optimistic UI update
    appendMessage(message, 'user');
    input.value = '';
    scrollToBottom();

    try {
        await fetch('/Xivex/api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ message: message })
        });
    } catch (e) {
        console.error('Send failed', e);
    }
}

async function fetchMessages() {
    if (!isChatOpen) return;
    
    try {
        const res = await fetch('/Xivex/api/chat.php?action=fetch&t=' + Date.now());
        const data = await res.json();
        
        if (data.status === 'success') {
            const chatContainer = document.getElementById('chatMessages');
            // Store existing messages to avoid full re-render flickering (simple implementation here clears though)
            // Ideally should diff, but for simplicity we'll just re-render if count changes or just clear-render
            
            // Keep the "Welcome" message? Maybe not needed if backend has history.
            // Let's just clear for now to ensure consistency with backend state
            chatContainer.innerHTML = `
                <div class="message admin">
                    Hello! How can we help you today? 👋
                </div>
            `;
            
            data.messages.forEach(msg => {
                appendMessage(msg.message, msg.is_admin == 1 ? 'admin' : 'user');
            });
        }
    } catch (e) {
        console.error('Fetch error', e);
    }
}

function appendMessage(text, type) {
    const div = document.createElement('div');
    div.classList.add('message', type);
    div.textContent = text; // Secure text insertion
    document.getElementById('chatMessages').appendChild(div);
}

function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    container.scrollTop = container.scrollHeight;
}

// Enter key to send
document.getElementById('chatInput').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') sendMessage();
});
</script>
