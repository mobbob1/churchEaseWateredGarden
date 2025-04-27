<?php
class AuditLogger {
    private $pdo;
    private $user;
    
    public function __construct($pdo, $user = null) {
        $this->pdo = $pdo;
        $this->user = $user;
    }
    
    /**
     * Log an action
     * 
     * @param string $action The action performed (e.g., 'create', 'update', 'delete')
     * @param string $module The module where the action was performed (e.g., 'users', 'members')
     * @param string|int $recordId The ID of the affected record (optional)
     * @param string $description Description of the action
     * @param array $oldValues Old values before change (optional)
     * @param array $newValues New values after change (optional)
     * @param string $status Status of the action ('success' or 'failed')
     * @param string $errorMessage Error message if status is 'failed' (optional)
     * @return bool Whether the log was successfully created
     */
    public function log($action, $module, $recordId = null, $description, 
                       $oldValues = null, $newValues = null, 
                       $status = 'success', $errorMessage = null) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_logs (
                    user_id, action, module, record_id, description, 
                    old_values, new_values, ip_address, user_agent, 
                    status, error_message
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            return $stmt->execute([
                $this->user ? $this->user['id'] : null,
                $action,
                $module,
                $recordId,
                $description,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            // Log to system error log if audit logging fails
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the difference between two arrays
     * 
     * @param array $old Old values
     * @param array $new New values
     * @return array Changed values
     */
    public function getChangedValues($old, $new) {
        $changes = [];
        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => isset($old[$key]) ? $old[$key] : null,
                    'new' => $value
                ];
            }
        }
        return $changes;
    }
}