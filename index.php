<?php
// chatgpt.php

$api_url = 'https://api.airforce/v1/chat/completions';
$api_key = 'YOUR_API_KEY';

// Initialize chat history from cookies, if available
$chat_history = isset($_COOKIE['chat_history']) ? json_decode($_COOKIE['chat_history'], true) : [];

// Handle API request only on AJAX calls
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Append the user's message to the chat history
    $user_input = $_POST['user_input'];
    $chat_history[] = ['role' => 'user', 'content' => $user_input];

    // Prepare data for API request
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => array_merge([['role' => 'system', 'content' => 'You are ChatGPT.']], $chat_history),
        'max_tokens' => 150,
        'temperature' => 0.7
    ];

    // Send the API request
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Get and close the response
    $response = curl_exec($ch);
    curl_close($ch);
    $decoded_response = json_decode($response, true);

    // Get assistant reply
    $assistant_reply = $decoded_response['choices'][0]['message']['content'] ?? 'No response from ChatGPT.';
    $chat_history[] = ['role' => 'assistant', 'content' => $assistant_reply];

    // Update cookie with new history
    setcookie('chat_history', json_encode($chat_history), time() + 3600);

    // Return response to JavaScript
    echo json_encode(['reply' => $assistant_reply]);
    exit;
}

// Start new chat if requested
if (isset($_POST['start_new_chat'])) {
    $chat_history = [];
    setcookie('chat_history', '', time() - 3600);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>monkey3threeGPT</title>
    <style>
        body { font-family: monospace; background-color: #1a1a1a; color: #00ff00; padding: 20px; }
        #chatbox { max-width: 600px; margin: auto; border: 2px solid #00ff00; padding: 20px; background-color: #000; box-shadow: 0 0 15px #00ff00; }
        .message { padding: 8px; margin: 5px 0; }
        .user { color: #00ff00; text-align: left; }
        .assistant { color: #ff6600; text-align: right; }
        .loading { color: #ff6600; font-style: italic; }
        .loading::after { content: " ."; animation: blink 1s steps(5, start) infinite; }
        input[type="text"] { background-color: #333; color: #00ff00; border: 1px solid #00ff00; padding: 8px; width: 100%; box-shadow: inset 0 0 5px #00ff00; }
        input[type="submit"], .new-chat-btn { background-color: #333; color: #00ff00; border: 1px solid #00ff00; padding: 8px; cursor: pointer; box-shadow: 0 0 5px #00ff00; }
        .new-chat-btn { margin-top: 10px; width: 100%; text-align: center; }

        @keyframes blink {
            0%, 20% { color: #ff6600; }
            50% { color: transparent; }
            100% { color: #ff6600; }
        }
    </style>
    <script>
        async function sendMessage(event) {
            event.preventDefault();
            const userInput = document.getElementById("user_input").value;
            if (!userInput.trim()) return;

            // Display user message instantly
            const chatBox = document.getElementById("chat_history");
            chatBox.innerHTML += `<div class="message user">${userInput}</div>`;
            document.getElementById("user_input").value = "";

            // Display loading animation for assistant's response
            const loadingMessage = document.createElement("div");
            loadingMessage.classList.add("message", "assistant", "loading");
            loadingMessage.textContent = "Assistant is typing";
            chatBox.appendChild(loadingMessage);
            chatBox.scrollTop = chatBox.scrollHeight;

            // Send AJAX request for assistant's response
            const response = await fetch("chatgpt.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ user_input: userInput, ajax: "1" })
            });
            const data = await response.json();

            // Remove loading animation and display the response
            chatBox.removeChild(loadingMessage);
            chatBox.innerHTML += `<div class="message assistant">${data.reply}</div>`;
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</head>
<body>
    <div id="chatbox">
        <h2>Chat with monkey3threeGPT</h2>
        
        <!-- Display chat history -->
        <div id="chat_history">
            <?php if (!empty($chat_history)): ?>
                <?php foreach ($chat_history as $message): ?>
                    <div class="message <?= $message['role'] === 'user' ? 'user' : 'assistant' ?>">
                        <?= htmlspecialchars($message['content']) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Chat input form -->
        <form onsubmit="sendMessage(event)">
            <input type="text" id="user_input" placeholder="Type your message here" required>
            <input type="submit" value="Send">
        </form>
        
        <!-- Start new chat button -->
        <form method="POST" action="chatgpt.php">
            <input type="hidden" name="start_new_chat" value="1">
            <button type="submit" class="new-chat-btn">Start New Chat</button>
        </form>
    </div>
</body>
</html>
