<?php
$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle ?? 'Red Social Educativa') ?></title>

    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/global.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/inicio.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/pages.css">

</head>
<body>
    <?php if (!in_array($currentPage, ['login', 'perfil'], true)): ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>
    
    <main class="main-content">
        <?php require $contentView; ?>
    </main>

    <script src="<?= $baseUrl ?>/public/js/inicio.js"></script>
</body>
</html>