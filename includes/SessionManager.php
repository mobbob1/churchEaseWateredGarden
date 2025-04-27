<?php
class SessionManager {
    private $db;
    private $session_timeout = 1800; // 30 minutes
    
    public function __construct($pdo) {
        $this->db = $pdo;
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function createSession($user_id) {
        $session_id = session_id();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Clear any existing sessions for this user
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Create new session
        $stmt = $this->db->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity) 
                                  VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $session_id, $ip_address, $user_agent]);
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['last_activity'] = time();
    }
    
    public function validateSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > $this->session_timeout) {
            $this->destroySession();
            return false;
        }
        
        // Validate session in database
        $stmt = $this->db->prepare("SELECT * FROM user_sessions 
                                  WHERE user_id = ? AND session_id = ?");
        $stmt->execute([$_SESSION['user_id'], session_id()]);
        $session = $stmt->fetch();
        
        if (!$session) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $this->updateLastActivity();
        return true;
    }
    
    public function updateLastActivity() {
        $_SESSION['last_activity'] = time();
        
        $stmt = $this->db->prepare("UPDATE user_sessions 
                                  SET last_activity = NOW() 
                                  WHERE user_id = ? AND session_id = ?");
        $stmt->execute([$_SESSION['user_id'], session_id()]);
    }
    
    public function destroySession() {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("DELETE FROM user_sessions 
                                      WHERE user_id = ? AND session_id = ?");
            $stmt->execute([$_SESSION['user_id'], session_id()]);
        }
        
        session_unset();
        session_destroy();
    }
}