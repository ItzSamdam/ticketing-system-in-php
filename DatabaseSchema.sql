-- Drop database if exists and create a new one
DROP DATABASE IF EXISTS EventTicketingSystemMVP;
CREATE DATABASE EventTicketingSystemMVP;
USE EventTicketingSystemMVP;

-- Enable UUID functions in MySQL
SET GLOBAL log_bin_trust_function_creators = 1;

-- Create UUID functions if using MySQL (MariaDB has built-in UUID_TO_BIN)
DELIMITER //
CREATE FUNCTION IF NOT EXISTS UUID_TO_BIN(_uuid CHAR(36))
RETURNS BINARY(16)
DETERMINISTIC
BEGIN
    RETURN UNHEX(REPLACE(_uuid, '-', ''));
END//

CREATE FUNCTION IF NOT EXISTS BIN_TO_UUID(_bin BINARY(16))
RETURNS CHAR(36)
DETERMINISTIC
BEGIN
    DECLARE hex CHAR(32);
    SET hex = HEX(_bin);
    RETURN CONCAT(
        SUBSTR(hex, 1, 8), '-',
        SUBSTR(hex, 9, 4), '-',
        SUBSTR(hex, 13, 4), '-',
        SUBSTR(hex, 17, 4), '-',
        SUBSTR(hex, 21)
    );
END//
DELIMITER ;

-- Users table - with UUID
CREATE TABLE Users (
    user_id BINARY(16) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    address VARCHAR(255),
    verified BOOLEAN DEFAULT FALSE,
    is_organizer BOOLEAN DEFAULT TRUE,
    profile_image_url VARCHAR(255),
    token_version INT DEFAULT 0,
    status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table - with UUID
CREATE TABLE Events (
    event_id BINARY(16) PRIMARY KEY,
    organizer_id BINARY(16) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    location VARCHAR(255),
    event_type ENUM('Physical', 'Virtual') NOT NULL DEFAULT 'Physical',
    virtual_url VARCHAR(255),  -- Only used for virtual events
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME,
    timezone VARCHAR(50) DEFAULT 'UTC',
    status ENUM('Draft', 'Published', 'Cancelled', 'Deleted') NOT NULL DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Ticket Types table - with UUID
CREATE TABLE Ticket_Types (
    ticket_type_id BINARY(16) PRIMARY KEY,
    event_id BINARY(16) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    quantity_available INT NOT NULL,
    quantity_sold INT DEFAULT 0,
    sales_start_datetime DATETIME,
    sales_end_datetime DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE
);

-- Orders table - with UUID
CREATE TABLE Orders (
    order_id BINARY(16) PRIMARY KEY,
    order_reference VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    status ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order Items - with UUID
CREATE TABLE Order_Items (
    item_id BINARY(16) PRIMARY KEY,
    order_id BINARY(16) NOT NULL,
    ticket_type_id BINARY(16) NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_type_id) REFERENCES Ticket_Types(ticket_type_id) ON DELETE CASCADE
);

-- Tickets - individual ticket instances with UUID
CREATE TABLE Tickets (
    ticket_id BINARY(16) PRIMARY KEY,
    order_item_id BINARY(16) NOT NULL,
    ticket_code VARCHAR(100) UNIQUE NOT NULL,
    attendee_name VARCHAR(200),
    attendee_email VARCHAR(255),
    is_checked_in BOOLEAN DEFAULT FALSE,
    checked_in_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES Order_Items(item_id) ON DELETE CASCADE
);

-- Payments - with UUID
CREATE TABLE Payments (
    payment_id BINARY(16) PRIMARY KEY,
    order_id BINARY(16) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Card', 'Transfer') NOT NULL,
    status ENUM('Pending', 'Completed', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
    transaction_reference VARCHAR(255),
    meta_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE
);

-- Basic promo codes - with UUID
CREATE TABLE Promo_Codes (
    promo_code_id BINARY(16) PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_percentage DECIMAL(5,2) NOT NULL,
    valid_until DATETIME NOT NULL,
    max_uses INT,
    times_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create essential indexes
CREATE INDEX idx_events_start_datetime ON Events(start_datetime);
CREATE INDEX idx_events_status ON Events(status);
CREATE INDEX idx_users_email ON Users(email);
CREATE INDEX idx_tickets_code ON Tickets(ticket_code);

-- Create a view for available tickets
CREATE VIEW available_tickets AS
SELECT 
    BIN_TO_UUID(e.event_id) AS event_id,
    e.title AS event_title,
    e.start_datetime,
    BIN_TO_UUID(tt.ticket_type_id) AS ticket_type_id,
    tt.name AS ticket_name,
    tt.price,
    (tt.quantity_available - tt.quantity_sold) AS tickets_remaining
FROM Events e
JOIN Ticket_Types tt ON e.event_id = tt.event_id
WHERE 
    e.status = 'Published' 
    AND e.start_datetime > NOW()
    AND (tt.quantity_available - tt.quantity_sold) > 0
    AND (tt.sales_end_datetime IS NULL OR tt.sales_end_datetime > NOW());

-- Create a view for upcoming events
CREATE VIEW upcoming_events AS
SELECT 
    BIN_TO_UUID(e.event_id) AS event_id,
    e.title,
    e.description,
    e.location,
    e.start_datetime,
    e.end_datetime,
    e.status,
    COUNT(tt.ticket_type_id) AS ticket_types_count,
    SUM(tt.quantity_sold) AS tickets_sold
FROM Events e
LEFT JOIN Ticket_Types tt ON e.event_id = tt.event_id
WHERE 
    e.status = 'Published'
    AND e.start_datetime > NOW()
GROUP BY e.event_id, e.title, e.description, e.location, e.start_datetime, e.end_datetime, e.status
ORDER BY e.start_datetime;

-- Add basic trigger to update ticket quantities when purchased
DELIMITER //

CREATE TRIGGER after_order_completed
AFTER UPDATE ON Orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'Completed' AND OLD.status = 'Pending' THEN
        -- Update ticket quantities for all items in this order
        UPDATE Ticket_Types tt
        JOIN Order_Items oi ON tt.ticket_type_id = oi.ticket_type_id
        SET tt.quantity_sold = tt.quantity_sold + oi.quantity
        WHERE oi.order_id = NEW.order_id;
    END IF;
    
    IF NEW.status = 'Cancelled' AND OLD.status = 'Completed' THEN
        -- Revert ticket quantities if order cancelled
        UPDATE Ticket_Types tt
        JOIN Order_Items oi ON tt.ticket_type_id = oi.ticket_type_id
        SET tt.quantity_sold = tt.quantity_sold - oi.quantity
        WHERE oi.order_id = NEW.order_id;
    END IF;
END //

-- Add triggers to automatically generate UUIDs when inserting new records
CREATE TRIGGER before_insert_users
BEFORE INSERT ON Users
FOR EACH ROW
BEGIN
    IF NEW.user_id IS NULL THEN
        SET NEW.user_id = UUID_TO_BIN(UUID());
    END IF;
END //

CREATE TRIGGER before_insert_events
BEFORE INSERT ON Events
FOR EACH ROW
BEGIN
    IF NEW.event_id IS NULL THEN
        SET NEW.event_id = UUID_TO_BIN(UUID());
    END IF;
END //

CREATE TRIGGER before_insert_ticket_types
BEFORE INSERT ON Ticket_Types
FOR EACH ROW
BEGIN
    IF NEW.ticket_type_id IS NULL THEN
        SET NEW.ticket_type_id = UUID_TO_BIN(UUID());
    END IF;
END //

CREATE TRIGGER before_insert_orders
BEFORE INSERT ON Orders
FOR EACH ROW
BEGIN
    IF NEW.order_id IS NULL THEN
        SET NEW.order_id = UUID_TO_BIN(UUID());
    END IF;
    -- Generate a readable order reference
    IF NEW.order_reference IS NULL OR NEW.order_reference = '' THEN
        SET NEW.order_reference = CONCAT('ORD-', SUBSTRING(UUID(), 1, 8));
    END IF;
END //

CREATE TRIGGER before_insert_order_items
BEFORE INSERT ON Order_Items
FOR EACH ROW
BEGIN
    IF NEW.item_id IS NULL THEN
        SET NEW.item_id = UUID_TO_BIN(UUID());
    END IF;
END //

CREATE TRIGGER before_insert_tickets
BEFORE INSERT ON Tickets
FOR EACH ROW
BEGIN
    IF NEW.ticket_id IS NULL THEN
        SET NEW.ticket_id = UUID_TO_BIN(UUID());
    END IF;
    -- Generate a readable ticket code if not provided
    IF NEW.ticket_code IS NULL OR NEW.ticket_code = '' THEN
        SET NEW.ticket_code = CONCAT('TKT-', SUBSTRING(UUID(), 1, 12));
    END IF;
END //

CREATE TRIGGER before_insert_payments
BEFORE INSERT ON Payments
FOR EACH ROW
BEGIN
    IF NEW.payment_id IS NULL THEN
        SET NEW.payment_id = UUID_TO_BIN(UUID());
    END IF;
END //

CREATE TRIGGER before_insert_promo_codes
BEFORE INSERT ON Promo_Codes
FOR EACH ROW
BEGIN
    IF NEW.promo_code_id IS NULL THEN
        SET NEW.promo_code_id = UUID_TO_BIN(UUID());
    END IF;
END //

DELIMITER ;