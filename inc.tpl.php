<!DOCTYPE html>
<html>
  <head>
     <meta charset="UTF-8">
     <title>Upload a file to Gist</title>
     <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <h1>
      <div class="container"><strong>Gist</strong> file uploader</div>
    </h1>

    <div class="container">
      <form method="POST" enctype="multipart/form-data" class="form">
  
        <?php if(isset($_SESSION["success"])) { ?>
          <div class="alert alert-success"><?= $_SESSION["success"] ?></div>
        <?php } ?>

        <?php if(isset($_SESSION["error"])) { ?>
          <div class="alert alert-danger"><?= $_SESSION["error"] ?></div>
        <?php } ?>

        <div>
          <input class="form-control" name="description" placeholder="Gist description...">
        </div>
        <div class="file-header">
          <input id="file" name="file" type="file" required>
          <small>Max size: 2M</small>
        </div>
        <div class="actions">
          <button type="submit" name="secret" class="btn btn-secret" id="btn-secret" disabled>Create secret gist</button>
          <button type="submit" name="public" class="btn btn-gist" id="btn-gist" disabled>Create public gist</button>
        </div>
      </form>

      <div id="login">
        <script>var isLoggedIn = <?= ($_SESSION["username"] ? 1 : 0) ?>;</script>

        <?php if(!isset($_SESSION["username"])) { ?>
          <form method="POST">
            <button class="btn" name="login" type="submit">Login</button>
          </form>
        <?php } else { ?>
          Logged in as <?= $_SESSION["username"] ?>
        <?php } ?>
      </div>
    </div>

     <script src="main.js"></script>
  </body>
</html>