<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

use PHPMailer\PHPMailer\PHPMailer;

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $nationalite = trim($_POST['nationalite'] ?? '');
    $sexe       = $_POST['sexe'] ?? '';
    $diplome    = trim($_POST['diplome'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } else {
        try {
            $pdo = getPDO();

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $photoData = null;
                $photoMime = null;
                if (!empty($_FILES['photo']['tmp_name'])) {
                    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    $mime = mime_content_type($_FILES['photo']['tmp_name']);
                    if (!in_array($mime, $allowed)) {
                        $error = 'Format de photo non autorisé (JPG, PNG, WEBP).';
                    } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                        $error = 'La photo ne doit pas dépasser 2 Mo.';
                    } else {
                        $photoData = file_get_contents($_FILES['photo']['tmp_name']);
                        $photoMime = $mime;
                    }
                }

                if (!$error) {
                    $hash    = password_hash($password, PASSWORD_BCRYPT);
                    $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', time() + 1800);

                    $stmt = $pdo->prepare('
                        INSERT INTO users (email, password, nationalite, sexe, diplome, photo, photo_mime, verif_code, verif_expires)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([$email, $hash, $nationalite, $sexe, $diplome, $photoData, $photoMime, $code, $expires]);

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $_ENV['SMTP_HOST']     ?? getenv('SMTP_HOST');
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['SMTP_USER']     ?? getenv('SMTP_USER');
                    $mail->Password   = $_ENV['SMTP_PASS']     ?? getenv('SMTP_PASS');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $_ENV['SMTP_PORT']     ?? getenv('SMTP_PORT') ?: 587;
                    $mail->CharSet    = 'UTF-8';
                    $mail->setFrom($_ENV['SMTP_FROM'] ?? getenv('SMTP_FROM'), 'Auth App');
                    $mail->addAddress($email);
                    $mail->Subject = 'Votre code de vérification';
                    $mail->Body    = "Bonjour,\n\nVotre code de vérification est : $code\n\nIl expire dans 30 minutes.";
                    $mail->send();

                    header('Location: /api/verify.php?email=' . urlencode($email));
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur serveur : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
</head>
<body class="register-page bg-body-secondary">
<div class="register-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Auth</b>App</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Créer un nouveau compte</p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form action="register.php" method="POST" enctype="multipart/form-data">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          <div class="input-group-text"><span class="fas fa-envelope"></span></div>
        </div>

        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
          <div class="input-group-text"><span class="fas fa-lock"></span></div>
        </div>

        <div class="input-group mb-3">
          <input type="text" name="nationalite" class="form-control" placeholder="Nationalité" value="<?= htmlspecialchars($_POST['nationalite'] ?? '') ?>" required>
          <div class="input-group-text"><span class="fas fa-flag"></span></div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Sexe</label>
          <div class="d-flex gap-3">
            <?php foreach (['M' => 'Homme', 'F' => 'Femme', 'Autre' => 'Autre'] as $val => $label): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="sexe" value="<?= $val ?>"
                  id="sexe_<?= $val ?>" <?= ($_POST['sexe'] ?? '') === $val ? 'checked' : '' ?> required>
                <label class="form-check-label" for="sexe_<?= $val ?>"><?= $label ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3">
          <select name="diplome" class="form-select" required>
            <option value="" disabled <?= empty($_POST['diplome']) ? 'selected' : '' ?>>-- Diplôme --</option>
            <?php foreach (['Bac','Bac+2','Bac+3 (Licence)','Bac+5 (Master)','Doctorat','Autre'] as $d): ?>
              <option value="<?= $d ?>" <?= ($_POST['diplome'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Photo de profil</label>
          <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
          <small class="text-muted">JPG/PNG/WEBP · max 2 Mo</small>
        </div>

        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
          </div>
        </div>
      </form>

      <p class="mb-1 mt-3 text-center">
        <a href="login.php">Déjà un compte ? Se connecter</a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
