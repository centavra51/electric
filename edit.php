<?php
// Simple CMS for Article editing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$password = "admin"; // Highly secret password :D
$dir = "articles/";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: edit.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['auth'] = true;
    } else {
        $error = "Неверный пароль / Invalid password";
    }
}

if (!isset($_SESSION['auth'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход / Login</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #1E3A8A; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Редактор Статей</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// Ensure articles dir exists
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Handle Saving
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['file']) && isset($_POST['content'])) {
    $file = basename($_POST['file']); // sanitize
    if (file_exists($dir . $file) || isset($_POST['new_file'])) {
        file_put_contents($dir . $file, $_POST['content']);
        $message = "Файл '$file' успешно сохранен.";
    }
}

// Handle Creating new article
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_article_name'])) {
    $newName = str_replace(' ', '-', strtolower(preg_replace('/[^a-zA-Z0-9а-яА-Я -]/u', '', trim($_POST['new_article_name']))));
    if(!empty($newName)) {
        $newName .= '.html';
        $defaultContent = "<!DOCTYPE html>\n<html lang=\"ru\">\n<head>\n  <meta charset=\"UTF-8\">\n  <title>Новая статья</title>\n  <link rel=\"stylesheet\" href=\"../style.css\">\n</head>\n<body>\n  <div class=\"container section-padding\">\n    <h1>Новая статья</h1>\n    <p>Текст статьи...</p>\n    <a href=\"../index.html\">⬅ На главную</a>\n  </div>\n</body>\n</html>";
        file_put_contents($dir . $newName, $defaultContent);
        $message = "Создан файл '$newName'";
    }
}

// Get all articles
$files = array_diff(scandir($dir), array('.', '..'));
$currentFile = isset($_GET['file']) ? basename($_GET['file']) : "";
$content = "";

if ($currentFile && file_exists($dir . $currentFile)) {
    $content = file_get_contents($dir . $currentFile);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CMS Статей</title>
    <style>
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; background: #f8f9fa; }
        .sidebar { width: 300px; background: #1E3A8A; color: white; padding: 20px; overflow-y: auto; }
        .sidebar a { color: #DBEAFE; text-decoration: none; display: block; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; }
        .main { flex: 1; padding: 20px; display: flex; flex-direction: column; overflow-y: auto;}
        textarea { width: 100%; flex: 1; font-family: monospace; font-size: 14px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: none; margin-bottom: 15px;}
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        button { padding: 10px 20px; background: #F59E0B; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;}
        button.btn-blue { background: #3B82F6; }
        .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .new-file-form { margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); }
        .new-file-form input { width: 100%; padding: 8px; margin-bottom: 10px; border: none; border-radius: 4px; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Статьи</h2>
        <a href="?logout=1" style="color: #F87171; font-size: 0.9em;">[ Выйти / Logout ]</a>
        <br>
        <?php foreach ($files as $f): ?>
            <a href="?file=<?= urlencode($f) ?>" class="<?= $f === $currentFile ? 'active' : '' ?>">
                📄 <?= htmlspecialchars($f) ?>
            </a>
        <?php endforeach; ?>
        
        <div class="new-file-form">
            <h4>Создать статью</h4>
            <form method="POST" action="edit.php">
                <input type="text" name="new_article_name" placeholder="Название (eng/latin)" required>
                <button type="submit" class="btn-blue" style="width: 100%;">Создать</button>
            </form>
        </div>
    </div>
    
    <div class="main">
        <div class="top-bar">
            <h2>Редактирование: <?= $currentFile ? htmlspecialchars($currentFile) : 'Выберите файл' ?></h2>
            <?php if ($currentFile): ?>
                <a href="<?= $dir . $currentFile ?>" target="_blank" style="color: #3B82F6;">Посмотреть на сайте ↗</a>
            <?php endif; ?>
        </div>
        
        <?php if ($message) echo "<div class='msg'>$message</div>"; ?>
        
        <?php if ($currentFile): ?>
            <form method="POST" style="display: flex; flex-direction: column; flex: 1;">
                <input type="hidden" name="file" value="<?= htmlspecialchars($currentFile) ?>">
                <textarea name="content" required><?= htmlspecialchars($content) ?></textarea>
                <div>
                    <button type="submit">💾 Сохранить изменения</button>
                </div>
            </form>
        <?php else: ?>
            <div style="text-align: center; color: #666; margin-top: 100px;">
                <h3>Выберите статью слева для редактирования</h3>
                <p>Здесь вы можете изменять HTML код статей. Используйте структуру <strong>&lt;p&gt;</strong> для абзацев и заголовки <strong>&lt;h2&gt;</strong>.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
