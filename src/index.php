<?php

require_once 'common.php';
require_once 'vendor/autoload.php';

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', get_base_url() . 'php_err.txt');

use Hybridauth\Hybridauth; 

if (!empty($_GET)) {
    if ((!empty($_GET['provider'])) && $config['socialLoginEnabled']) {
        $sAuth = new HybridLogin();
        $err = '';
        $provider = trim($_GET['provider']);
        switch ($provider) {
            case 'Twitter':
                $res = $sAuth->connectToTwitter($err);
                break;
            case 'Google':
                $res = $sAuth->connectToGoogle($err);
                break;
            case 'Facebook':
                $res = $sAuth->connectToFacebook($err);
                break;
            case 'Instagram':
                $res = $sAuth->connectToInstagram($err);
                break;
            case 'Amazon':
                $res = $sAuth->connectToAmazon($err);
                break;                                        
            default:

                break;
        }        
        if (!$res) {
            echo "<p>ERROR: $err</p>";
        } 
        exit;       
    }
}

if (!empty($_POST)) {
    $data = [];
    if (!empty($_POST['cmd'])) {
        $cmd = strtolower(trim($_POST['cmd']));
        if ($cmd === 'settings') {
            $data['app'] = $config['app'];
            if (!empty($config['privacyPageUrl']))
                $data['privacyPageUrl'] = $config['privacyPageUrl'];
            $data['socialLoginEnabled'] = $config['socialLoginEnabled'];
            $data['emailLoginEnabled'] = $config['emailLoginEnabled'];
            $data['registerEnabled'] = $config['registerEnabled'];
            $data['resumePasswordEnabled'] = $config['resumePasswordEnabled'];
            $data['template'] = $config['template'];
            $data['registerTemplate'] = $config['registerTemplate'];
            $providers = [];
            if ((!empty($config['providers'])) && is_array($config['providers'])) {
                foreach ($config['providers'] as $key => $value) {
                    if (!empty($value['enabled']))
                        $providers[] = $key;
                }
            }
            $data['providers'] = $providers;
        }
        if (($cmd === 'local') && (!empty($_POST['lang']))) {
            $lang = strtolower(trim($_POST['lang']));
            if (file_exists("languages/$lang.php"))
                include_once "languages/$lang.php";
            if ((!empty($local)) && is_array($local)) {
                $data = $local;
            }
        }
        if (($cmd === 'chkconnection') && (!empty($_POST['email'])) && (!empty($_POST['accountType'])) && (!empty($_POST['password']))) {
            $data = ['connected' => false];
            $db = get_database($err);
            if ($db) {
                $app_code = mysqli_real_escape_string($db, $config['app']);
                $email = trim($_POST['email']);
                $accountType = strtoupper(trim($_POST['accountType']));
                if ((($accountType !== 'EMAIL') && (!$config['socialLoginEnabled'])) || (($accountType === 'EMAIL') && (!$config['emailLoginEnabled']))) {
                    $res = false;    
                } else {
                    $password = $_POST['password'];
                    $sql = "SELECT hl_user.id_hl_user AS id, hl_user.email AS usermail, hl_user_accounts.account_pwd AS pwd, hl_user_accounts.account_data AS acc_data FROM (hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app) INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (appcode = '$app_code') AND (email = '$email') AND (account_type = '$accountType')";
                    $res = $db->query($sql);
                }
                if($res) {
                    foreach ($res as $row) {
                        if ($row['pwd'] === $password) {
                            $config['callback'] = get_base_url() . "callback.php";
                            $hybrid_auth = new Hybridauth($config);
                            $adapter = $hybrid_auth->getAdapter(ucfirst(strtolower($accountType)));
                            try {
                                $adapter->setAccessToken(json_decode($row['acc_data'], true));
                                $data['connected'] = $adapter->isConnected();
                            } catch(\Exception $e) {
    
                            }
                        }
                        if ($data['connected'] === true) {
                            $userProfile = $adapter->getUserProfile();
                            $adapter->disconnect();
                            $email = $row['usermail'];
                            if ($userProfile->email !== $email) {
                                $email = $userProfile->email;
                                try {
                                    $db->query("UPDATE hl_user SET email = '$email' WHERE id_hl_user = {$row['id']}");
                                } catch(\Exception $e) {

                                } 
                            }
                            $accountType = strtoupper($accountType);
                            setcookie   (
                                $config['app'], 
                                "$email|$accountType|{$row['pwd']}", 
                                [
                                    'expires' => time() + (30 * 24 * 60 * 60),
                                    'path' => '/',
                                    'samesite' => 'None',
                                ]
                            );
                        } else {
                            setcookie($config['app'], '', time() - 3600, '/');
                        }
                    }
                }
                $db->close();
            }
        }
        if (($cmd === 'register') && (!empty($_POST['email'])) && (!empty($_POST['pwd'])) && (!empty($_POST['lang'])) && $config['registerEnabled']) {
            $data = ['succeeded' => false, 'err' => []];            
            $email = trim($_POST['email']);
            $pwd = trim($_POST['pwd']);
            $lang = strtolower(trim($_POST['lang']));
        /********************************************************** 
        [START] Validation 
        **********************************************************/
            try {
                if (file_exists("languages/$lang.php"))
                    include_once "languages/$lang.php";
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $data['err'][] = $local['invalidEmailAddress'];
                }
                if (strlen($pwd) < 4) {
                    $data['err'][] = $local['weakPassword'];
                }
            /* [START] Check user alredy exists */
                $err = '';
                $db = get_database($err);
                if ($db) {
                    $app_code = mysqli_real_escape_string($db, $config['app']);
                    $user_exists = false;
                    $res = $db->query("SELECT * FROM (hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app) INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (appcode = '$app_code') AND (email = '$email') AND (account_type = 'EMAIL')");
                    if($res) {
                        foreach ($res as $row) {
                            $user_exists = true;
                        }
                    }
                    if ($user_exists) {
                        $data['err'][] = $local['userExists'];
                    }
                } else {
                    $data['err'][] = $err;
                }
            /* [END] Check user alredy exists */
            } catch(\Exception $e) {
                $data = ['succeeded' => false];
                $data['err'][] = $e->getMessage();
            } 
            $data['succeeded'] = count($data['err']) === 0;
        /********************************************************** 
        [END] Validation 
        **********************************************************/
            if ($data['succeeded']) {
            /********************************************************** 
            [START] Registration
            **********************************************************/
                try {
                    $db->autocommit(false);
                    $db->begin_transaction();
                    $user_exists = false;
                    $sql = "SELECT hl_user.id_hl_user AS id FROM hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app WHERE (appcode = '$app_code') AND (email = '$email')";
                    $res = $db->query($sql);
                    if($res) {
                        foreach ($res as $row) {
                            $id_user = (int)$row['id'];
                            $user_exists = true;
                        }
                    } else {
                        $data = ['succeeded' => false];
                        $data['err'][] = $db->error;
                    }
                    if ($data['succeeded']) {
                        if (!$user_exists) {
                            if ($db->query("INSERT INTO hl_user (id_hl_app, email) VALUES ((SELECT id_hl_app FROM hl_app WHERE (appcode = '$app_code')), '$email')")) {
                                $id_user = (int)$db->insert_id;
                            } else {
                                $data = ['succeeded' => false];
                                $data['err'][] = $db->error;
                            }
                        }
                        if ($data['succeeded']) {
                            $pwd = password_hash($pwd, PASSWORD_BCRYPT);
                            $password = mysqli_real_escape_string($db, $pwd);
                            $sql = "INSERT INTO hl_user_accounts (id_hl_user, account_type, account_pwd) VALUES ($id_user, 'EMAIL', '$password')";
                            if (!$db->query($sql)) {
                                $data = ['succeeded' => false];
                                $data['err'][] = $db->error;
                            }
                        }
                    }
                } catch(\Exception $e) {
                    $data = ['succeeded' => false];
                    $data['err'][] = $e->getMessage();
                } finally {
                    if ($data['succeeded']) {
                        $data['password'] = $pwd;
                        $db->commit();
                    } else {
                        $db->rollback();
                    }
                    $db->close();
                }
            /********************************************************** 
            [END] Registration
            **********************************************************/
            }
        }
        if (($cmd === 'emaillogin') && (!empty($_POST['email'])) && (!empty($_POST['pwd'])) && isset($_POST['remember']) && $config['emailLoginEnabled']) {
            $data = ['succeeded' => false];            
            $email = trim($_POST['email']);
            $pwd = trim($_POST['pwd']);
            $pwd = hl_chk_login($email, 'EMAIL', null, $pwd);
            if ($_POST['remember'] === 'false') {
                $remember = false;
            } else {
                $remember = true;
            }
            if ($pwd) {
                $data['succeeded'] = true;            
                $data['password'] = $pwd;            
                if ($remember) {
                    setcookie   (
                        $config['app'], 
                        "$email|EMAIL|$pwd", 
                        [
                            'expires' => time() + (30 * 24 * 60 * 60),
                            'path' => '/',
                            'samesite' => 'None',
                        ]
                    );
                } else {
                    setcookie   (
                        $config['app'], 
                        "$email|EMAIL|$pwd", 
                        [
                            'path' => '/',
                            'samesite' => 'None',
                        ]
                    );
                }
            }
        }
        if (($cmd === 'deleteemailaccount') && (!empty($_POST['email'])) && (!empty($_POST['pwd']))) {
            $data = ['succeeded' => false];
            try {
                $db = get_database($err);
                if ($db) {
                    $app_code = mysqli_real_escape_string($db, $config['app']);
                    $email = trim($_POST['email']);
                    $pwd = mysqli_real_escape_string($db, trim($_POST['pwd']));
                    $sql = "SELECT hl_user.id_hl_user AS id, id_hl_user_accounts FROM (hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app) INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (appcode = '$app_code') AND (email = '$email') AND (account_type = 'EMAIL') AND (account_pwd = '$pwd')";
                    $res = $db->query($sql);
                    if($res) {
                        foreach ($res as $row) {
                            if ($db->query("DELETE FROM hl_user_accounts WHERE id_hl_user_accounts = {$row['id_hl_user_accounts']}")) {
                                $data['succeeded'] = true;
                            }
                        }
                    }
                    $db->close();
                }                    
            } catch(\Exception $e) {
                $data['succeeded'] = false;
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode((object)$data);
    exit;
}

use Hybridauth\Storage\Session;
 
class HybridLogin {

    public $settings;
    private $hybridauth;
    private $db;
    public $lastError;

    public function __construct() {
        
        global $config;

        $this->settings = $config;  
        $callbackURL = get_base_url();
        $this->settings['callback'] = $callbackURL . "callback.php";
        $this->hybridauth = new Hybridauth($this->settings);      
        $this->db = null;
        $this->lastError = null;

    }

    public function connectToTwitter(&$err = null) {

        try {
            $storage = new Session();
            $storage->set('provider', 'Twitter');
            $adapter = $this->hybridauth->getAdapter('Twitter');
            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
            $adapter->authenticate();
            if ($adapter->isConnected()) {
                $profile = $adapter->getUserProfile();
                return $profile;
            } else {
                return false;
            }         
        } catch(\Exception $e){
            $err = $e->getMessage();
            return false;
        }        

    }

    public function connectToGoogle(&$err = null) {

        try {
            $storage = new Session();
            $storage->set('provider', 'Google');
            $adapter = $this->hybridauth->getAdapter('Google');
            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
            $adapter->authenticate();
            if ($adapter->isConnected()) {
                $profile = $adapter->getUserProfile();
                return $profile;
            } else {
                return false;
            }         
        } catch(\Exception $e){
            $err = $e->getMessage();
            return false;
        }        

    }

    public function connectToFacebook(&$err = null) {

        try {
            $storage = new Session();
            $storage->set('provider', 'Facebook');
            $adapter = $this->hybridauth->getAdapter('Facebook');
            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
            $adapter->authenticate();
            if ($adapter->isConnected()) {
                $profile = $adapter->getUserProfile();
                return $profile;
            } else {
                return false;
            }         
        } catch(\Exception $e){
            $err = $e->getMessage();
            return false;
        }        

    }

    public function connectToInstagram(&$err = null) {

        try {
            $storage = new Session();
            $storage->set('provider', 'Intagram');
            $adapter = $this->hybridauth->getAdapter('Intagram');
            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
            $adapter->authenticate();
            if ($adapter->isConnected()) {
                $profile = $adapter->getUserProfile();
                return $profile;
            } else {
                return false;
            }         
        } catch(\Exception $e){
            $err = $e->getMessage();
            return false;
        }        

    }

    public function connectToAmazon(&$err = null) {

        try {
            $storage = new Session();
            $storage->set('provider', 'Amazon');
            $adapter = $this->hybridauth->getAdapter('Amazon');
            if ($adapter->isConnected()) {
                $adapter->disconnect();
            }
            $adapter->authenticate();
            if ($adapter->isConnected()) {
                $profile = $adapter->getUserProfile();
                return $profile;
            } else {
                return false;
            }         
        } catch(\Exception $e){
            $err = $e->getMessage();
            return false;
        }        

    }

}

?>