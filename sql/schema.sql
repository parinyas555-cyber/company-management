CREATE TABLE IF NOT EXISTS users (
 id SERIAL PRIMARY KEY,
 username VARCHAR(100) UNIQUE NOT NULL,
 password_hash TEXT NOT NULL,
 full_name VARCHAR(150) NOT NULL,
 role VARCHAR(20) NOT NULL DEFAULT 'staff' CHECK (role IN ('staff','admin')),
 active BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
 id SERIAL PRIMARY KEY,
 code VARCHAR(100) NOT NULL,
 name VARCHAR(200) NOT NULL,
 unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
 min_stock INTEGER NOT NULL DEFAULT 500 CHECK (min_stock >= 0),
 current_stock INTEGER NOT NULL DEFAULT 0 CHECK (current_stock >= 0),
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 active BOOLEAN NOT NULL DEFAULT TRUE,
 UNIQUE(code, name)
);

CREATE TABLE IF NOT EXISTS stock_movements (
 id BIGSERIAL PRIMARY KEY,
 product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
 movement_type VARCHAR(10) NOT NULL CHECK (movement_type IN ('IN','OUT')),
 quantity INTEGER NOT NULL CHECK (quantity > 0),
 reference_no VARCHAR(100),
 note TEXT,
 user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_movements_created ON stock_movements(created_at);
CREATE INDEX IF NOT EXISTS idx_movements_product ON stock_movements(product_id);
CREATE INDEX IF NOT EXISTS idx_movements_type ON stock_movements(movement_type);
CREATE INDEX IF NOT EXISTS idx_products_code ON products(code);
CREATE INDEX IF NOT EXISTS idx_products_name ON products(name);

ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE products ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE products ADD COLUMN IF NOT EXISTS active BOOLEAN NOT NULL DEFAULT TRUE;
CREATE INDEX IF NOT EXISTS idx_products_active ON products(active);


CREATE TABLE IF NOT EXISTS devices (
 id SERIAL PRIMARY KEY,
 device_name VARCHAR(150) NOT NULL,
 model VARCHAR(150),
 imei VARCHAR(100),
 serial_no VARCHAR(100),
 vehicle_no VARCHAR(100),
 firmware VARCHAR(150),
 status VARCHAR(30) NOT NULL DEFAULT 'Active' CHECK (status IN ('Active','Repair','Inactive')),
 note TEXT,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_devices_imei ON devices(imei);
CREATE INDEX IF NOT EXISTS idx_devices_vehicle ON devices(vehicle_no);
CREATE INDEX IF NOT EXISTS idx_devices_status ON devices(status);

CREATE TABLE IF NOT EXISTS maintenance_jobs (
 id BIGSERIAL PRIMARY KEY,
 device_id INTEGER NOT NULL REFERENCES devices(id) ON DELETE RESTRICT,
 title VARCHAR(200) NOT NULL,
 problem TEXT,
 solution TEXT,
 status VARCHAR(30) NOT NULL DEFAULT 'Open' CHECK (status IN ('Open','In Progress','Waiting Parts','Completed','Cancelled')),
 cost NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (cost >= 0),
 note TEXT,
 opened_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_maintenance_device ON maintenance_jobs(device_id);
CREATE INDEX IF NOT EXISTS idx_maintenance_status ON maintenance_jobs(status);
CREATE INDEX IF NOT EXISTS idx_maintenance_created ON maintenance_jobs(created_at);
