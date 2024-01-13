<?php

require_once 'common.php';
require_once 'vendor/autoload.php';

use Hybridauth\Hybridauth; 

if (!empty($_GET)) {
    if (!empty($_GET['provider'])) {
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
            $data['emailLoginEnabled'] = $config['emailLoginEnabled'];
            $data['template'] = $config['template'];
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
                $password = $_POST['password'];
                $sql = "SELECT hl_user.id_hl_user AS id, hl_user.email AS usermail, hl_user_accounts.account_pwd AS pwd, hl_user_accounts.account_data AS acc_data FROM (hl_user INNER JOIN hl_app ON hl_user.id_hl_app = hl_app.id_hl_app) INNER JOIN hl_user_accounts ON hl_user.id_hl_user = hl_user_accounts.id_hl_user WHERE (appcode = '$app_code') AND (email = '$email') AND (account_type = '$accountType')";
                $res = $db->query($sql);
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