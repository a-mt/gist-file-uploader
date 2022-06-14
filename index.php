<?php
session_start();
require __DIR__ . '/inc.config.php';

define("DEBUG", false);

//+--------------------------------------------------------
//| AUTH
//+--------------------------------------------------------

// Ask API for login
if(isset($_POST["login"])) {
    unset($_SESSION["error"]);
    unset($_SESSION["success"]);

    $_SESSION["tmpcode"] = md5(PASSPHRASE . time());

    // https://developer.github.com/apps/building-oauth-apps/scopes-for-oauth-apps/
    header('Location:https://github.com/login/oauth/authorize' .
            '?client_id=' . CLIENT_ID .
            '&state=' . $_SESSION["tmpcode"] .
            '&scope=gist');
    exit;
}

// Check token is valid
// https://developer.github.com/changes/2020-02-14-deprecating-oauth-app-endpoint/
if(isset($_SESSION["token"]) && !isset($_SESSION["username"])) {
    $c = curl_init('https://api.github.com/applications/' . CLIENT_ID . '/token');

    $data = json_encode([
      "access_token" => $_SESSION["token"]
    ]);

    curl_setopt($c, CURLOPT_HTTPHEADER, array(
      'User-Agent: File uploader',
    ));
    curl_setopt($c, CURLOPT_USERPWD, CLIENT_ID . ":" . CLIENT_SECRET);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);

    if($response = curl_exec($c)) {
        //$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
        $json = json_decode($response, true);

        if(isset($json["user"])) {
            $_SESSION["username"] = $json["user"]["login"];
            $_SESSION["token"] = $json["token"];
        } else {
            unset($_SESSION["token"]);
        }
    } else {
      echo 'Something went wrong';
      echo curl_error($c);
    }
}

//+--------------------------------------------------------
//| FORM SUBMIT
//+--------------------------------------------------------

if(DEBUG) {
    function do_exec($list) {
        foreach($list as $instruction) {
            echo "$ " . htmlspecialchars($instruction) . "\n";
            echo htmlspecialchars(shell_exec($instruction . ' 2>&1'));
            ob_flush();
            flush();
        }
    }
} else {
    function do_exec($list) {
        foreach($list as $instruction) {
            exec($instruction);
        }
    }
}

if(isset($_SESSION["token"]) && isset($_FILES["file"])) {
    $fileinfo = $_FILES['file'];

    // Check file upload status
    $err = false;
    switch($fileinfo['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:   $err = "The uploaded file exceeds the max size (" . ini_get("upload_max_filesize") . ")"; break;
        case UPLOAD_ERR_FORM_SIZE:  $err = "The uploaded file exceeds the max size"; /* MAX_FILE_SIZE, spécifiée dans le formulaire HTML */ break;
        case UPLOAD_ERR_PARTIAL:    $err = "The uploaded file was only partially uploaded."; break;
        case UPLOAD_ERR_NO_FILE:    $err = "No file was uploaded."; break;
        case UPLOAD_ERR_NO_TMP_DIR: $err = "Missing a temporary folder."; break;
        case UPLOAD_ERR_CANT_WRITE: $err = "Failed to write file to disk."; break;
        case UPLOAD_ERR_EXTENSION:  $err = "File extension not accepted."; break;
    }
    if($err) {
        $_SESSION['error'] = $err;
    } else {

        // Submit gist
        // https://developer.github.com/v3/gists/#create-a-gist
        $data = '{
          "description": "' . trim($_POST["description"]) . '",
          "public": ' . (isset($_POST["secret"]) ? 'false' : 'true') .  ',
          "files": {
            "' . $fileinfo['name'] . '": {
              "content": "' . $fileinfo['name'] . '"
            }
          }
        }';
        $c = curl_init('https://api.github.com/gists');
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        if(isset($_SESSION["token"])) {
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
              'User-Agent: File uploader',
              'Authorization: token ' . $_SESSION["token"]
            ));
        }

        $response = curl_exec($c);

        if(!$response) {
            echo curl_error($c);
            die;
        }
        curl_close($c);
        $json = json_decode($response, true);

        // Check if user has revoked permission
        if(!isset($json['id'])) {
            $_SESSION["error"] = $json['message'];

            if($json['message'] == "Bad credentials") {
                unset($_SESSION["token"]);
                unset($_SESSION["username"]);
            }
        } else {

            // Upload file (via clone & push)
            chdir('/tmp');
            $id   = $json['id'];
            $path = '/tmp/' . $id;
            $url  = str_replace('https://', 'https://' . $_SESSION["username"] . ':' . $_SESSION['token'] . '@', $json['git_push_url']);

            // Clone gist & move to dir
            if(DEBUG) {
                echo '<pre>';
            }
            do_exec(['git clone ' . $url]);
    
            if(chdir($path)) {
    
                // Upload file in here
                move_uploaded_file($fileinfo["tmp_name"], $path . '/' . $fileinfo['name']);

                do_exec([
                    'git config user.name `git log -1 --pretty=format:\'%an\'`',
                    'git config user.email `git log -1 --pretty=format:\'%ae\'`',
                    'git add "' . $fileinfo['name'] . '"',
                    'GIT_TRACE=1 git commit -m "Update file content"',
                    'GIT_TRACE=1 git push origin master'
                ]);
                chdir('/tmp');

                do_exec([
                    'rm -rf "' . $path . '"'
                ]);
            }
            if(DEBUG) {
                echo '</pre>';
            }
            $_SESSION["success"] = 'File uploaded: <a href="' . $json["html_url"] . '" target="_blank">' . $json["html_url"] . '</a>';
        }
    }
    if(!DEBUG) {
      header('Location: /');
    } else {
        echo '<a href="/">Refresh the page</a>';
    }
    die;
}

//+--------------------------------------------------------
//| RENDER HTML
//+--------------------------------------------------------

require __DIR__ . '/inc.tpl.php';
unset($_SESSION["error"]);
unset($_SESSION["success"]);
