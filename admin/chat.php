<?php
defined('ABSPATH') || exit;

$assistant_settings = get_option('assistant_settimgs', []);

if (($assistant_settings['framework'] ?? 'neuron') === 'neuron') {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/agent.php';

    AssistantAgent::make()->resolveChatHistory()->flushAll();
} else {
    // Delete the chat history
    unlink(__DIR__ . '/messages.txt');
}

$category = wp_get_ability_category(sanitize_key($_GET['category'] ?? ''));

$abilites = wp_get_abilities();
if ($category) {
    $category_slug = $category->get_slug();
    $abilites = array_filter($abilites, function ($ability) use ($category_slug) {
        /** @var WP_Ability $ability */
        return $category_slug === $ability->get_category();
    });
}

?>
<script src="https://cdn.jsdelivr.net/npm/marked/lib/marked.umd.js"></script>
<div class="wrap">
    <?php if ($category) { ?>
        <h2>How may I help you with the "<?php echo esc_html($category->get_label()); ?>" tools?</h2>
    <?php } else { ?>
        <h2>How may I help you?</h2>
    <?php } ?>


    <style>
        #container {
            font-family: Arial, sans-serif;
            background:#f5f5f5;
            margin:0;
            display:flex;
            flex-direction:column;
            height:70vh;
        }
        #chat {
            flex:1;
            overflow-y:auto;
            padding:20px;
        }
        .message {
            margin:10px 0;
            max-width:70%;
            padding:10px 15px;
            border-radius:12px;
            line-height:1.4;
        }
        .user   {
            background:#0084ff;
            color:#fff;
            align-self:flex-end;
        }
        .bot    {
            background:#e5e5ea;
            color:#000;
            align-self:flex-start;
        }
        #inputArea {
            display:flex;
            padding:10px;
            background:#fff;
            border-top:1px solid #ccc;
        }
        #msgInput {
            flex:1;
            padding:10px;
            font-size:16px;
            border:1px solid #ccc;
            border-radius:4px;
        }
        #sendBtn  {
            margin-left:10px;
            padding:10px 20px;
            font-size:16px;
            cursor:pointer;
        }

        .message.bot table {
            background-color: #fff;
            border: 1px solid #666;
            border-collapse: collapse;
        }
        .message.bot table td, .message.bot table th {
            padding: 5px;
            border: 1px solid #ddd;
        }
    </style>

    <div id="container">

        <div id="chat">
            <div class="message bot">
                <?php if ($category) { ?>
                    Welcome. Here the available abilities:
                    <ul>
                        <?php
                        foreach ($abilites as $ability) {
                            echo '<li>', esc_html($ability->get_label());
                            echo '<br><span style="font-size: .8em">', esc_html($ability->get_description()), '</span>';
                            echo '</li>';
                        }
                        ?>
                    </ul>
                <?php } else { ?>
                    Welcome. Try asking "available tools" to know the abilities I can use.
                <?php } ?>
            </div>
        </div>

        <div id="inputArea">
            <input type="text" id="msgInput" placeholder="Type a message…" autocomplete="off">
            <button id="micBtn" class="mic-btn">🎤</button>
            <button id="sendBtn">Send</button>
        </div>
        <div id="status"></div>

        <script>
            const micBtn = document.getElementById('micBtn');
            const voiceInput = document.getElementById('msgInput');
            const statusTxt = document.getElementById('status');
            // Check for browser support
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {

                // Initialize Web Speech API
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const recognition = new SpeechRecognition();

                // Configuration
                recognition.continuous = false; // Stop after one sentence
                recognition.lang = 'en-US'; // Set language (optional)
                recognition.interimResults = false; // Only show final results

                // Event: When microphone button is clicked
                micBtn.addEventListener('click', () => {
                    if (micBtn.classList.contains('listening')) {
                        recognition.stop();
                    } else {
                        recognition.start();
                    }
                });

                // Event: Recording started
                recognition.onstart = () => {
                    micBtn.classList.add('listening');
                    statusTxt.innerText = "Listening...";
                    voiceInput.placeholder = "Listening...";
                };

                // Event: Recording ended
                recognition.onend = () => {
                    micBtn.classList.remove('listening');
                    statusTxt.innerText = "";
                    voiceInput.placeholder = "Click the mic and speak...";
                };

                // Event: Result received
                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    voiceInput.value = transcript;
                };

                // Event: Error handling
                recognition.onerror = (event) => {
                    console.error("Speech recognition error", event.error);
                    statusTxt.innerText = "Error: " + event.error;
                    micBtn.classList.remove('listening');
                };

            } else {
                // Fallback for unsupported browsers
                micBtn.style.display = 'none';
                voiceInput.placeholder = "Voice input not supported in this browser.";
                console.log("Web Speech API not supported.");
            }
        </script>

        <script>
            /* This code has been generated by Google Gemini... */
            const chatDiv = document.getElementById('chat');
            const msgInput = document.getElementById('msgInput');
            const sendBtn = document.getElementById('sendBtn');
            const assetsUrl = '<?php echo plugins_url('assets', __FILE__); ?>';
            const category = '<?php echo sanitize_key($category_slug); ?>';

            // -----------------------------------------------------------------
            // Helper: append a bubble (same as before)
            // -----------------------------------------------------------------
            function addMessage(text, sender) {
                const el = document.createElement('div');
                el.className = `message ${sender}`;
                if (sender === 'bot') {
                    console.log(text);
                    el.innerHTML = marked.parse(text);
                } else {
                    el.innerHTML = text;
                }
                chatDiv.appendChild(el);
                chatDiv.scrollTop = chatDiv.scrollHeight;
            }

            // -----------------------------------------------------------------
            // NEW: async call to your backend
            // -----------------------------------------------------------------
            async function getBotResponseFromServer(userMsg) {
                try {
                    const x = new FormData();
                    x.append("action", "assistant_message");
                    x.append("category", category);
                    x.append("message", userMsg);
                    x.append("_wpnonce", '<?php echo esc_js(wp_create_nonce('save')); ?>');
                    const response = await fetch(ajaxurl,
                            {
                                method: 'POST',
                                headers: {
                                    //'Content-Type': 'application/json'
                                },
                                body: x
                            });

                    // ---------------------------------------------------------
                    // 1️⃣ Check HTTP status
                    // ---------------------------------------------------------
                    if (!response.ok) {
                        // Server returned an error code (e.g., 500, 429)
                        console.error('Server error:', response.status);
                        return `⚠️ Oops – the server responded with ${response.status}.`;
                    }

                    const data = await response.json();

                    if (!data || typeof data.reply !== 'string') {
                        console.warn('Unexpected payload:', data);
                        return '🤔 Received an unexpected response from the server.';
                    }

                    return data.reply;   // <-- this string becomes the bot bubble
                } catch (err) {
                    // ---------------------------------------------------------
                    // Network / CORS / parsing errors land here
                    // ---------------------------------------------------------
                    console.error('Fetch failed:', err);
                    return '❌ Could not reach the backend. Check your connection or CORS settings.';
                }
            }

            // -----------------------------------------------------------------
            // Send a message (unchanged UI flow, now async)
            // -----------------------------------------------------------------
            async function sendMessage() {
                const userText = msgInput.value.trim();
                if (!userText)
                    return;

                addMessage(userText, 'user');
                msgInput.value = '';
                msgInput.focus();

                // Show a temporary “typing” placeholder (optional but nice UX)
                const typingId = `typing-${Date.now()}`;
                const typingEl = document.createElement('div');
                typingEl.id = typingId;
                typingEl.className = 'message bot';
                typingEl.innerHTML = '<img src="' + assetsUrl + '/loading.webp" style="width: 50px">';
                chatDiv.appendChild(typingEl);
                chatDiv.scrollTop = chatDiv.scrollHeight;

                // -----------------------------------------------------------------
                // 1️⃣ Get the real reply from the server
                // -----------------------------------------------------------------
                const botReply = await getBotResponseFromServer(userText);

                // -----------------------------------------------------------------
                // Replace the placeholder with the actual reply
                // -----------------------------------------------------------------
                typingEl.remove();                 // drop the “…”
                addMessage(botReply, 'bot');       // render final bubble
            }

            // -----------------------------------------------------------------
            // UI event wiring (same as before)
            // -----------------------------------------------------------------
            sendBtn.addEventListener('click', sendMessage);
            msgInput.addEventListener('keypress', e => {
                if (e.key === 'Enter')
                    sendMessage();
            });
        </script>


    </div>
</div>