<?php
// includes/Auth.php
class Auth {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }

    public static function login($user_id, $username, $email, $balance) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['balance'] = $balance;
    }

    public static function logout() {
        session_destroy();
    }

    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'balance' => $_SESSION['balance']
            ];
        }
        return null;
    }
}
?>