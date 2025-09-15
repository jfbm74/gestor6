<?php
$pageTitle = "Iniciar Sesión";
include 'templates/layout/header.php';
?>

<div class="login-container">
    <div class="login-card">
        <img src="<?php echo $config['app']['logo_path']; ?>"
             alt="<?php echo $config['app']['clinic_name']; ?>"
             class="login-logo">

        <h2 class="login-title"><?php echo $config['app']['name']; ?></h2>
        <p class="login-subtitle"><?php echo $config['app']['clinic_name']; ?></p>

        <?php if (isset($loginError)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($loginError); ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username" class="form-label">Usuario</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       placeholder="Ingrese su usuario"
                       required
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       placeholder="Ingrese su contraseña"
                       required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>
        </form>
    </div>
</div>

<?php include 'templates/layout/footer.php'; ?>