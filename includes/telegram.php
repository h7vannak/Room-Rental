<?php

function sendTelegram($message)
{
    // Configuration
    $BOT_TOKEN = "8538652891:AAGYAY9Lj2ojoyURgjNc4NT5UkTN-aEr5_w";
    $CHAT_ID   = "-5253388167";

    $url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";

    $data = [
        'chat_id' => $CHAT_ID,
        'text' => trim($message),
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_CONNECTTIMEOUT => 5, // Wait max 5 seconds to connect
        CURLOPT_TIMEOUT => 10,        // Wait max 10 seconds for response
        CURLOPT_SSL_VERIFYPEER => true // Ensure secure connection
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // Optional: Log errors for debugging if the message fails to send
    if ($response === false) {
        error_log("Telegram Send Error: " . $error);
        return false;
    }

    return true;
}