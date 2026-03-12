<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= s($title ?? 'AppMana') ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <?php if (!empty($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/<?= s($css) ?>">
    <?php endforeach; endif; ?>
</head>
<body>
