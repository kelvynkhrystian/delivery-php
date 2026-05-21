CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    subscription TEXT NOT NULL,
    active BOOLEAN DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);
