<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

if (getAuthUser()) {
    header('Location: /api/index.php');
    exit;
}

$error   = '';
$success = '';

if (isset($_GET['verified'])) {
    $success = 'Compte vérifié avec succès ! Vous pouvez vous connecter.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, email, password, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Email ou mot de passe incorrect.';
        } elseif (!$user['is_verified']) {
            $error = 'Compte non vérifié. <a href="/api/verify.php?email=' . urlencode($email) . '">Vérifier maintenant</a>.';
        } else {
            setAuthCookie($user);
            header('Location: /api/index.php');
            exit;
        }
    } catch (Exception $e) {
        $error = 'Erreur serveur : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Auth</b>App</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Connectez-vous pour continuer</p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= $error ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($success) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          <div class="input-group-text"><span class="fas fa-envelope"></span></div>
        </div>

        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
          <div class="input-group-text"><span class="fas fa-lock"></span></div>
        </div>

        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
          </div>
        </div>
      </form>

      <p class="mb-1 mt-3 text-center">
        <a href="register.php">Créer un compte</a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
