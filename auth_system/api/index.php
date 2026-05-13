<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$auth = requireAuth();

$profile = null;
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT email, nationalite, sexe, diplome, photo, photo_mime, created_at FROM users WHERE id = ?');
    $stmt->execute([$auth['sub']]);
    $profile = $stmt->fetch();
} catch (Exception $e) {}

if (isset($_GET['logout'])) {
    setcookie('auth_token', '', ['expires' => time() - 1, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
    header('Location: /api/login.php');
    exit;
}

$avatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode($profile['email'] ?? 'U') . '&background=0d6efd&color=fff';
if ($profile['photo'] && $profile['photo_mime']) {
    $avatarSrc = 'data:' . $profile['photo_mime'] . ';base64,' . base64_encode($profile['photo']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de bord</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">

  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#"><i class="fas fa-bars"></i></a></li>
        <li class="nav-item d-none d-md-block"><span class="nav-link fw-semibold">Tableau de bord</span></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="?logout=1">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
          </a>
        </li>
      </ul>
    </div>
  </nav>

  <aside class="app-sidebar bg-dark sidebar-dark-primary shadow">
    <div class="sidebar-brand">
      <a href="index.php" class="brand-link">
        <span class="brand-text fw-semibold">AuthApp</span>
      </a>
    </div>
    <div class="sidebar-wrapper">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?= $avatarSrc ?>" class="img-circle elevation-2" alt="Avatar" style="width:34px;height:34px;object-fit:cover;">
        </div>
        <div class="info">
          <a href="#" class="d-block text-truncate" style="max-width:150px"><?= htmlspecialchars($profile['email'] ?? '') ?></a>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview">
          <li class="nav-item">
            <a href="index.php" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Accueil</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6"><h3 class="mb-0">Mon Profil</h3></div>
        </div>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-md-6">
            <div class="card card-primary card-outline">
              <div class="card-body text-center">
                <img src="<?= $avatarSrc ?>" class="img-circle img-fluid mb-3"
                  style="width:100px;height:100px;object-fit:cover;" alt="Photo de profil">
                <h5 class="mb-1"><?= htmlspecialchars($profile['email'] ?? '') ?></h5>
                <p class="text-muted mb-3"><?= htmlspecialchars($profile['diplome'] ?? '') ?></p>
                <hr>
                <dl class="row text-start">
                  <dt class="col-sm-4">Nationalité</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars($profile['nationalite'] ?? '') ?></dd>
                  <dt class="col-sm-4">Sexe</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars($profile['sexe'] ?? '') ?></dd>
                  <dt class="col-sm-4">Inscrit le</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars($profile['created_at'] ?? '') ?></dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer class="app-footer">
    <div class="float-end d-none d-sm-inline">AuthApp</div>
    <strong>AuthApp</strong> – Vercel Serverless
  </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc2/dist/js/adminlte.min.js"></script>
</body>
</html>
