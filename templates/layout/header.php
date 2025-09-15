<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? $config['app']['name']; ?> - <?php echo $config['app']['clinic_name']; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if ($authManager->isAuthenticated()): ?>
    <div class="main-container">
        <header class="header">
            <div class="header-left">
                <img src="<?php echo $config['app']['logo_path']; ?>" alt="<?php echo $config['app']['clinic_name']; ?>" class="header-logo">
                <div>
                    <h1 class="header-title"><?php echo $config['app']['name']; ?></h1>
                    <small class="text-muted"><?php echo $config['app']['clinic_name']; ?></small>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <span>Hola, <strong><?php echo htmlspecialchars($authManager->getCurrentUser()); ?></strong></span>
                    <a href="?logout=true" class="btn btn-outline-primary btn-sm">Cerrar Sesión</a>
                </div>
            </div>
        </header>

        <?php if (isset($showNavTabs) && $showNavTabs): ?>
        <nav class="nav-tabs">
            <?php foreach ($config['document_bases'] as $key => $details): ?>
                <a href="?base=<?php echo $key; ?>"
                   class="nav-tab <?php echo ($key === $activeBaseKey) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($details['name']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>