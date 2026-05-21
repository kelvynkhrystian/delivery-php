// Configuração do Firebase (substitua com suas credenciais)
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_AUTH_DOMAIN",
    projectId: "YOUR_PROJECT_ID",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// Inicializar Firebase
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Solicitar permissão para notificações
async function requestNotificationPermission() {
    try {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            // Obter token do FCM
            const token = await messaging.getToken();
            // Enviar token para o servidor
            await saveTokenToServer(token);
        }
    } catch (error) {
        console.error('Erro ao solicitar permissão:', error);
    }
}

// Salvar token no servidor
async function saveTokenToServer(token) {
    const userId = document.body.dataset.userId; // Adicione data-user-id no body
    const userType = document.body.dataset.userType; // Adicione data-user-type no body
    
    try {
        const response = await fetch('/api/save-firebase-token.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token: token,
                user_id: userId,
                user_type: userType
            })
        });
        
        if (!response.ok) {
            throw new Error('Erro ao salvar token');
        }
    } catch (error) {
        console.error('Erro ao salvar token:', error);
    }
}

// Lidar com mensagens em primeiro plano
messaging.onMessage((payload) => {
    const notification = payload.notification;
    
    // Criar notificação personalizada
    const notifOptions = {
        body: notification.body,
        icon: '/assets/img/icon.png', // Adicione um ícone apropriado
        data: payload.data
    };
    
    new Notification(notification.title, notifOptions);
});
