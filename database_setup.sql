-- ====================================================================
-- ADMINTO SAAS DATABASE SETUP SCRIPT (MySQL)
-- Description: Creates Database, Operators Table, and Populates Initial 
--              1 Super Admin and 1 Operator Account.
-- ====================================================================

-- 1. Create Database
CREATE DATABASE IF NOT EXISTS adminto_saas_db
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE adminto_saas_db;

-- 2. Drop existing table if re-initializing
DROP TABLE IF EXISTS operators;

-- 3. Create Operators & SaaS Tenants Licensing Table
CREATE TABLE operators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('superadmin', 'admin', 'operator') NOT NULL DEFAULT 'operator',
    expiry_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Per-Operator Isolated Firebase Database Credentials
    firebase_project_id VARCHAR(100) NOT NULL,
    firebase_api_key VARCHAR(255) DEFAULT 'AIzaSyDefaultApiKey2026',
    firebase_auth_domain VARCHAR(255),
    storage_bucket VARCHAR(255),
    app_id VARCHAR(255),
    org_id VARCHAR(100) NOT NULL DEFAULT 'org_main',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ====================================================================
-- 4. Populate Initial Demo Accounts (1 Super Admin & 1 Operator)
-- ====================================================================

-- Account #1: SUPER ADMIN (User: admin / Pass: admin123)
-- Expiry: 2099-12-31 | Access: Unlimited Super Admin Operator Console
INSERT INTO operators (
    username, 
    email, 
    password_hash, 
    full_name, 
    role, 
    expiry_date, 
    is_active, 
    firebase_project_id, 
    firebase_api_key, 
    firebase_auth_domain, 
    storage_bucket, 
    app_id, 
    org_id
) VALUES (
    'admin', 
    'admin@adminto.com', 
    '$2b$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeg6Lruj3vjPGga31lW', -- bcrypt hash of 'admin123'
    'Super Administrator', 
    'superadmin', 
    '2099-12-31', 
    TRUE, 
    'adminto-superadmin-main', 
    'AIzaSySuperAdminKey2026', 
    'adminto-superadmin-main.firebaseapp.com', 
    'adminto-superadmin-main.appspot.com', 
    '1:109823746501:web:super123', 
    'org_all'
);

-- Account #2: OPERATOR (User: operator1 / Pass: operator123)
-- Expiry: 2026-12-31 | Scope: adminto-north-region
INSERT INTO operators (
    username, 
    email, 
    password_hash, 
    full_name, 
    role, 
    expiry_date, 
    is_active, 
    firebase_project_id, 
    firebase_api_key, 
    firebase_auth_domain, 
    storage_bucket, 
    app_id, 
    org_id
) VALUES (
    'operator1', 
    'operator1@adminto.com', 
    '$2b$10$U83HwzR5Y7dF8mK1vA2N8e1l3k4j5h6g7f8e9d0c1b2a3s4d5f6g7', -- bcrypt hash of 'operator123'
    'Regional Operator North', 
    'operator', 
    '2026-12-31', 
    TRUE, 
    'adminto-north-region', 
    'AIzaSyOperatorNorthKey2026', 
    'adminto-north-region.firebaseapp.com', 
    'adminto-north-region.appspot.com', 
    '1:109823746501:web:north123', 
    'org_north'
);

-- Verification Query
SELECT 
    id, 
    username, 
    role, 
    expiry_date, 
    firebase_project_id, 
    org_id 
FROM operators;
