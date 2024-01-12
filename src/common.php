<?php

require_once 'settings.php';

function hl_chk_login($email, $account, $hashed_password) {

    $result = false;
    $db = get_database();

    if ($db) {
        $email = mysqli_real_escape_string($db, trim($email));
        $account = strtoupper(trim($account));
        $pwd = mysqli_real_escape_string($db, trim($hashed_password));
        $sql = "SELECT * FROM hl_user INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (email = '$email') AND (account_type = '$account') AND (account_pwd = '$pwd')";
        $res = $db->query($sql);
        if($res) {
            foreach ($res as $row) {
                $result = true;
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

    $host = $_SERVER['HTTP_HOST']; // Gets the domain name
    $script = $_SERVER['SCRIPT_NAME']; 
    $url = $protocol . $host . $script;

    // Trova la posizione dell'ultimo carattere '/'
    $ultimaBarra = strrpos($url, '/');

    // Se il carattere '/' è stato trovato nella stringa
    if ($ultimaBarra !== false) {
        // Taglia la stringa fino all'ultimo '/'
        $stringaModificata = substr($url, 0, $ultimaBarra + 1);
    } else {
        // Nessuna '/' trovata, la stringa rimane invariata
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
            return $db;
        }    
    } catch(\Exception $e){
        $err = $e->getMessage();
        return null;
    }        

}

?>