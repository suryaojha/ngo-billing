-- Stock supply billing and payment ledger (run once before deploying PHP changes).
CREATE TABLE IF NOT EXISTS stock_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  batch VARCHAR(100) DEFAULT '',
  requested_qty INT NOT NULL,
  note TEXT NULL,
  status ENUM('pending','fulfilled','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fulfilled_at TIMESTAMP NULL,
  INDEX idx_stock_requests_admin_status (admin_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS supply_invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(30) NOT NULL,
  admin_id INT NOT NULL,
  created_by INT NOT NULL,
  stock_request_id INT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_supply_invoice_no (invoice_no),
  INDEX idx_supply_invoice_admin (admin_id,created_at),
  CONSTRAINT fk_supply_invoice_admin FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS supply_invoice_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supply_invoice_id INT NOT NULL,
  source_product_id INT NULL,
  admin_product_id INT NULL,
  product_name VARCHAR(255) NOT NULL,
  batch VARCHAR(100) DEFAULT '',
  expiry VARCHAR(50) DEFAULT '',
  capacity VARCHAR(50) DEFAULT '',
  qty INT NOT NULL,
  rate DECIMAL(12,2) NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  INDEX idx_supply_items_invoice (supply_invoice_id),
  CONSTRAINT fk_supply_items_invoice FOREIGN KEY (supply_invoice_id) REFERENCES supply_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS supply_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supply_invoice_id INT NOT NULL,
  admin_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(40) NOT NULL DEFAULT 'Cash',
  reference_note VARCHAR(255) DEFAULT '',
  received_by INT NOT NULL,
  paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_supply_payments_invoice (supply_invoice_id),
  INDEX idx_supply_payments_admin (admin_id),
  CONSTRAINT fk_supply_payment_invoice FOREIGN KEY (supply_invoice_id) REFERENCES supply_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
