<style>
/* Chatbot Scoped CSS */
#ev-chatbot-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    font-family: 'Inter', sans-serif;
}

/* Toggle Button */
#ev-chatbot-toggle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 50%;
    color: white;
    box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
    cursor: pointer;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

#ev-chatbot-toggle:hover {
    transform: scale(1.1);
}

#ev-chatbot-toggle svg {
    width: 30px;
    height: 30px;
    fill: currentColor;
}

/* Chat Window */
#ev-chatbot-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 550px;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    display: none;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    animation: ev-chat-open 0.3s ease-out;
}

@keyframes ev-chat-open {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

/* Header */
.ev-chat-header {
    padding: 20px;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(124, 58, 237, 0.9));
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.ev-chat-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ev-chat-avatar {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.ev-chat-title h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: white;
}

.ev-chat-title p {
    margin: 0;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    gap: 5px;
}

.ev-status-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
}

#ev-chatbot-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    opacity: 0.7;
    cursor: pointer;
}

#ev-chatbot-close:hover {
    opacity: 1;
}

/* Messages */
#ev-chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.ev-message {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 15px;
    font-size: 14px;
    line-height: 1.5;
    word-wrap: break-word;
    animation: ev-msg-pop 0.3s ease-out;
}

@keyframes ev-msg-pop {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.ev-message.bot {
    align-self: flex-start;
    background: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.ev-message.user {
    align-self: flex-end;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
}

/* Input Area */
.ev-chat-input-area {
    padding: 20px;
    background: rgba(15, 23, 42, 0.8);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    gap: 10px;
}

#ev-chatbot-input {
    flex: 1;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 12px 15px;
    color: white;
    font-size: 14px;
    outline: none;
    transition: all 0.3s;
}

#ev-chatbot-input:focus {
    background: rgba(255, 255, 255, 0.1);
    border-color: #6366f1;
}

#ev-chatbot-send {
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 12px;
    width: 45px;
    height: 45px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

#ev-chatbot-send:hover {
    background: #4338ca;
}
</style>

<div id="ev-chatbot-widget">
    <!-- Toggle Button -->
    <button id="ev-chatbot-toggle">
        <svg viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
        </svg>
    </button>

    <!-- Chat Window -->
    <div id="ev-chatbot-window">
        <!-- Header -->
        <div class="ev-chat-header">
            <div class="ev-chat-info">
                <div class="ev-chat-avatar">🤖</div>
                <div class="ev-chat-title">
                    <h3>Evolentra Support</h3>
                    <p><span class="ev-status-dot"></span> Online Now</p>
                </div>
            </div>
            <button id="ev-chatbot-close">×</button>
        </div>

        <!-- Messages -->
        <div id="ev-chatbot-messages">
            <div class="ev-message bot">
                Hi there! 👋 Welcome to Evolentra. <br><br>
                How can I assist you today?<br>
                Try asking about <b>Investments</b>, <b>Withdrawals</b>, or type <b>ticket: [your issue]</b> to contact support.
            </div>
        </div>

        <!-- Input -->
        <div class="ev-chat-input-area">
            <input type="text" id="ev-chatbot-input" placeholder="Type your message...">
            <button id="ev-chatbot-send">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('ev-chatbot-toggle');
    const closeBtn = document.getElementById('ev-chatbot-close');
    const windowEl = document.getElementById('ev-chatbot-window');
    const messagesEl = document.getElementById('ev-chatbot-messages');
    const inputEl = document.getElementById('ev-chatbot-input');
    const sendBtn = document.getElementById('ev-chatbot-send');

    // State
    let isOpen = false;

    // Toggle Window with animation logic
    function toggleChat() {
        isOpen = !isOpen;
        if (isOpen) {
            windowEl.style.display = 'flex';
            setTimeout(() => inputEl.focus(), 100);
        } else {
            windowEl.style.display = 'none';
        }
    }
    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    // Add Message to UI
    function addMessage(text, isUser = false) {
        const div = document.createElement('div');
        div.className = `ev-message ${isUser ? 'user' : 'bot'}`;
        div.innerHTML = text; 
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // Send Message Logic
    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text) return;

        addMessage(text, true);
        inputEl.value = '';

        // Typing Indicator
        const typingId = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.id = typingId;
        typingDiv.className = 'ev-message bot';
        typingDiv.style.fontStyle = 'italic';
        typingDiv.style.opacity = '0.7';
        typingDiv.innerText = 'Typing...';
        messagesEl.appendChild(typingDiv);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        try {
            const res = await fetch('api/chat_response.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            
            // Remove Typing Indicator
            const tDiv = document.getElementById(typingId);
            if(tDiv) tDiv.remove();
            
            addMessage(data.reply);
        } catch (e) {
            const tDiv = document.getElementById(typingId);
            if(tDiv) tDiv.remove();
            addMessage("⚠️ Connection error. Please try again.");
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    inputEl.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});
</script>
