<?php
require_once '../config/database.php';
require_once '../config/firebase-config.php';

function notificarAdminsSobreNovoPedido($pedidoId, $clienteNome) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Buscar tokens de todos os admins
        $query = "SELECT token FROM firebase_tokens WHERE user_type = 'admin' GROUP BY token";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $firebase = new FirebaseNotification();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Enviar notificação para cada admin
                $firebase->sendNewOrderNotification(
                    $row['token'],
                    $pedidoId,
                    $clienteNome
                );
            }
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log('Erro ao enviar notificação para admins: ' . $e->getMessage());
        return false;
    }
}
?>
