<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$email = trim($_GET['email'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code']  ?? '');

    try {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, verif_code, verif_expires, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Aucun compte trouvé pour cet email.';
        } elseif ($user['is_verified']) {
            header('Location: /api/login.php?verified=1');
            exit;
        } elseif (new DateTime() > new DateTime($user['verif_expires'])) {
            $error = 'Code expiré. Veuillez vous réinscrire.';
        } elseif (!hash_equals($user['verif_code'], $code)) {
            $error = 'Code incorrect.';
        } else {
            $pdo->prepare('UPDATE users SET is_verified = 1, verif_code = NULL, verif_expires = NULL WHERE id = ?')
                ->execute([$user['id']]);
            header('Location: /api/login.php?verified=1');
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
<title>Vérification du compte</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
  <div class="card card-outline card-success">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Auth</b>App</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Entrez le code envoyé à<br><strong><?= htmlspecialchars($email) ?></strong></p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form action="verify.php" method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

        <div class="input-group mb-3">
          <input type="text" name="code" class="form-control text-center fs-4 fw-bold letter-spacing-3"
            maxlength="6" pattern="\d{6}" placeholder="000000" autocomplete="one-time-code" required>
          <div class="input-group-text"><span class="fas fa-key"></span></div>
        </div>

        <button type="submit" class="btn btn-success w-100">Vérifier mon compte</button>
      </form>

      <p class="mt-3 text-center text-muted small">
        Le code expire dans 30 minutes. <a href="register.php">Renvoyer</a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
