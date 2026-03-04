<?php
// form.php - Submission handler

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize inputs
    $name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : 'Не указано';
    $phone = isset($_POST['phone']) ? htmlspecialchars(strip_tags(trim($_POST['phone']))) : 'Не указано';
    $address = isset($_POST['address']) ? htmlspecialchars(strip_tags(trim($_POST['address']))) : 'Не указано';
    $problem = isset($_POST['problem']) ? htmlspecialchars(strip_tags(trim($_POST['problem']))) : '';
    $calc_result = isset($_POST['calc_result']) ? htmlspecialchars(strip_tags(trim($_POST['calc_result']))) : '';
    
    $message = "🚨 НОВАЯ ЗАЯВКА - Электрик Кишинев\n\n";
    $message .= "👤 Имя: $name\n";
    $message .= "📞 Телефон: $phone\n";
    $message .= "🏠 Адрес: $address\n";
    if ($problem) $message .= "❓ Проблема: $problem\n";
    if ($calc_result) $message .= "🧮 Калькулятор: $calc_result\n";

    // Handle Photo Upload
    $photoPath = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = time() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . sanitizeFile($filename);
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $photoPath = $targetPath;
            $message .= "📸 Фото прикреплено (сохранено на сервере: $photoPath)\n";
        }
    }
    
    // Save to text file as fallback
    $logEntry = "[" . date("Y-m-d H:i:s") . "]\n" . $message . "-----------------------\n";
    file_put_contents('leads.txt', $logEntry, FILE_APPEND);

    // --- TELEGRAM NOTIFICATION ---
    // User needs to fill these constants or they can be passed as hidden fields (though not secure)
    $botToken = ''; // e.g. '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11'
    $chatId = ''; // e.g. '123456789'
    
    if (!empty($botToken) && !empty($chatId)) {
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage?chat_id=" . $chatId;
        $url .= "&text=" . urlencode($message);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // If there's a photo, send it too
        if($photoPath) {
             $photoUrl = "https://api.telegram.org/bot" . $botToken . "/sendPhoto?chat_id=" . $chatId;
             $cFile = new CURLFile(realpath($photoPath));
             $post = ['photo'=> $cFile, 'caption' => "Фото к заявке от $name"];
             
             $chPhoto = curl_init();
             curl_setopt($chPhoto, CURLOPT_URL, $photoUrl);
             curl_setopt($chPhoto, CURLOPT_POST, 1);
             curl_setopt($chPhoto, CURLOPT_POSTFIELDS, $post);
             curl_setopt($chPhoto, CURLOPT_RETURNTRANSFER, true);
             curl_exec($chPhoto);
             curl_close($chPhoto);
        }
        
        curl_exec($ch);
        curl_close($ch);
    }

    // --- EMAIL NOTIFICATION (Optional Setup) ---
    // $to = "your-email@example.com";
    // $subject = "Новая заявка - Электрик";
    // $headers = "From: noreply@yoursite.com\r\nReply-To: $phone\r\n";
    // mail($to, $subject, $message, $headers);

    echo "OK";
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}

function sanitizeFile($filename) {
    return preg_replace('/[^a-zA-Z0-9-_\.]/', '', $filename);
}
?>
