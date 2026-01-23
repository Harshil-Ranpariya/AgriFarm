-- Add payment columns to orders table
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER remaining_quantity;
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending','completed','failed') DEFAULT 'pending' AFTER payment_method;
ALTER TABLE orders ADD COLUMN payment_transaction_id VARCHAR(100) DEFAULT NULL AFTER payment_status;

