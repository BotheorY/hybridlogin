<?php

use Hybridauth\Hybridauth; 
use Hybridauth\Storage\Session;

try {

    require_once 'vendor/autoload.php';
    require_once 'common.php';

    $callbackURL = get_base_url();
    $config['callback'] = $callbackURL . "callback.php";
    $hybridauth = new Hybridauth($config);   
    $storage = new Session();

    if ($provider = $storage->get('provider')) {
        $account_type = strtoupper($provider);
        if ($account_type === 'EMAIL') {





            $account_data = '';
        } else {
            $hybridauth->authenticate($provider);
            $adapter = $hybridauth->getAdapter($provider);
            if ($adapter->isConnected()) {
                $profile = $adapter->getUserProfile();
                $accessToken = $adapter->getAccessToken();
                $adapter->disconnect();

//file_put_contents('log.txt', json_encode($profile) . "\n\n", FILE_APPEND);  // debug
//file_put_contents('log.txt', json_encode($accessToken) . "\n\n****************\n\n", FILE_APPEND);  // debug

                $email = $profile->email;
                $account_data = json_encode($accessToken);
                $account_pwd = password_hash($account_data, PASSWORD_BCRYPT);
            } else {
                echo "<div><h2>ERROR</h2>Not connected.</div>";
                exit;
            }     
        }
        
        if (!empty($account_pwd)) {
            $err = '';
            $db = get_database($err);
            if ($db) {
                $app_code = mysqli_real_escape_string($db, $config['app']);
                $user_exists = false;
                $res = $db->query("SELECT hl_user.id_hl_user AS id FROM hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app WHERE (appcode = '$app_code') AND (email = '$email')");
                if($res) {
                    foreach ($res as $row) {
                        $id_user = (int)$row['id'];
                        $user_exists = true;
                    }
                }
                if (!$user_exists) {
                    $res = $db->query("INSERT INTO hl_user (id_hl_app, email) VALUES ((SELECT id_hl_app FROM hl_app WHERE (appcode = '$app_code')), '$email')");
                    $id_user = (int)$db->insert_id;
                }
                $account_exists = false;
                $res = $db->query("SELECT * FROM hl_user_accounts WHERE (id_hl_user = $id_user) AND (account_type = '$account_type')");
                if($res) {
                    foreach ($res as $row) {
                        $id_hl_user_accounts = (int)$row['id_hl_user_accounts'];
                        $account_exists = true;
                    }
                }
                $account_data = mysqli_real_escape_string($db, $account_data);
                $accountpwd = mysqli_real_escape_string($db, $account_pwd);
                if ($account_exists) {
                    $sql = "UPDATE hl_user_accounts SET account_pwd = '$accountpwd', account_data = '$account_data' WHERE id_hl_user_accounts = $id_hl_user_accounts";                
                } else {
                    $sql = "INSERT INTO hl_user_accounts (id_hl_user, account_type, account_pwd, account_data) VALUES ($id_user, '$account_type', '$accountpwd', '$account_data')";
                }
                $db->query($sql);
                setcookie   (
                                $config['app'], 
                                "$email|$account_type|$account_pwd", 
                                [
                                    'expires' => time() + (30 * 24 * 60 * 60),
                                    'path' => '/',
                                    'samesite' => 'None',
                                ]
                            );
                echo    '
                            <!DOCTYPE html>
                            <html>
                            
                                <head>
                                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                </head>
                            
                                <body style="height: 100%; margin: 0; display: flex; justify-content: center; align-items: center;">                
                                    <img style="vertical-align: middle; width: 50%;" src="' . get_base_url() . 'images/wait.gif">
                                </body>                
                
                            <html>
                        ';
            } else {
                echo "<div><h2>ERROR</h2>$err</div>";
            }
        }

    }     
} catch(\Exception $e) {
    $err = $e->getMessage();
    echo "<div><h2>ERROR</h2>$err</div>";
} 

?>