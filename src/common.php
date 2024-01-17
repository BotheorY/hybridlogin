<?php

require_once 'settings.php';

function hl_chk_login($email, $account, $hashed_password, $password = null) {

    global $config;
    
    $result = false;
    $db = get_database();

    if ($db) {
        $app_code = mysqli_real_escape_string($db, $config['app']);
        $email = mysqli_real_escape_string($db, trim($email));
        $account = strtoupper(trim($account));
        if ($hashed_password) {
            $pwd = mysqli_real_escape_string($db, trim($hashed_password));
            $sql = "SELECT * FROM (hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app) INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (appcode = '$app_code') AND (email = '$email') AND (account_type = '$account') AND (account_pwd = '$pwd')";
            $res = $db->query($sql);
            if($res) {
                foreach ($res as $row) {
                    $result = true;
                }
            }
        } else {
            if ($password) {
                $sql = "SELECT account_pwd FROM (hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app) INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (appcode = '$app_code') AND (email = '$email') AND (account_type = '$account')";
                $res = $db->query($sql);
                if($res) {
                    foreach ($res as $row) {
                        if (password_verify($password, $row['account_pwd'])) {
                            $result = $row['account_pwd'];
                        }
                    }
                }
            }
        }
        $db->close();
    }

    return $result;

}

function get_base_url() {

    $protocol = 'http://';

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = 'https://';
    }

    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME']; 
    $url = $protocol . $host . $script;
    $lastSlash = strrpos($url, '/');

    if ($lastSlash !== false) {
        $stringaModificata = substr($url, 0, $lastSlash + 1);
    } else {
        $stringaModificata = $url;
    }

    return $stringaModificata;    

}

function get_database(&$err = null) {

    try {
        global $config;
        $host = $config['dbHost'];
        $name = $config['dbName'];
        $user = $config['dbUser'];
        $password = $config['dbPassword'];
        $db = new mysqli($host, $user, $password, $name);
        $err = $db->connect_error;
        if ($err) {
            return null;
        } else {
            $db->autocommit(true);
            $app_code = mysqli_real_escape_string($db, $config['app']);
            if (file_exists('database.sql')) {
                $sql = file_get_contents('database.sql');
                $queryArray = explode(';', $sql);
                foreach ($queryArray as $query) {
                    if (trim($query)) {
                        if (!$db->query($query)) {
                            $err = "{$db->error} ($query)";
                            return null;
                        }
                    }
                }
            }
            $sql = "INSERT INTO hl_app (appcode) VALUES ('$app_code')";
            try {
                $db->query($sql);
            } catch(\Exception $e){
            }
            return $db;
        }    
    } catch(\Exception $e){
        $err = $e->getMessage();
        return null;
    }        

}

?>