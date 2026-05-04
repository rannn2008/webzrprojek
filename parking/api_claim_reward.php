<?php
// c:/xampp/htdocs/parking/api_claim_reward.php
include 'config.php';
include 'auth.php';
restrictToClient();

header('Content-Type: application/json');

$response = ["success" => false, "message" => "Unknown error"];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['client_id'];
    
    $points_needed = 1000;
    $reward_amount = 10000;
    
    try {
        $conn->begin_transaction();
        
        // Check points
        $stmt = $conn->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && $user['points'] >= $points_needed) {
            // Deduct points, add balance
            $stmt = $conn->prepare("UPDATE users SET points = points - ?, balance = balance + ? WHERE id = ?");
            $stmt->bind_param("iii", $points_needed, $reward_amount, $userId);
            
            if ($stmt->execute()) {
                // Log in history
                $stmt_hist = $conn->prepare("INSERT INTO parking_history (user_id, action, fee) VALUES (?, 'TOPUP', ?)");
                $fee_neg = $reward_amount; // History usually shows positive for additions in my fee logic? Wait, history logic: IN=0, OUT=fee, TOPUP=amount.
                $stmt_hist->bind_param("ii", $userId, $fee_neg);
                $stmt_hist->execute();
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Selamat! Reward Rp " . number_format($reward_amount, 0, ',', '.') . " telah ditambahkan ke saldo Anda.";
                $response['available_points'] = $user['points'] - $points_needed;
            } else {
                throw new Exception("Update failed.");
            }
        } else {
            throw new Exception("Poin tidak cukup. Anda butuh minimal " . $points_needed . " poin.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = "Invalid request.";
}

echo json_encode($response);
?>
