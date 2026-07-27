const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());

// Serve static frontend files (index.html, superadmin.html, app-debug.apk)
app.use(express.static(path.join(__dirname)));

// MySQL Database Connection Pool
// Configure via environment variables or default to Render MySQL connection
const db = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'adminto_saas_db',
  port: process.env.DB_PORT || 3306,
  waitForConnections: true,
  connectionLimit: 10
});

// 1. API Endpoint: Authenticate Operator against MySQL Database
app.post('/api/login', async (req, res) => {
  const { username, password } = req.body;

  if (!username || !password) {
    return res.status(400).json({ success: false, error: 'Username and password required.' });
  }

  try {
    const q = username.trim().toLowerCase();
    const [rows] = await db.execute(
      'SELECT * FROM operators WHERE LOWER(username) = ? OR LOWER(email) = ?',
      [q, q]
    );

    if (rows.length === 0) {
      return res.status(401).json({ success: false, error: 'Invalid credentials! Operator account not found.' });
    }

    const operator = rows[0];

    // Simple password match (or bcrypt match in production)
    if (operator.password_hash !== password && !operator.password_hash.includes(password)) {
      return res.status(401).json({ success: false, error: 'Invalid password! Access denied.' });
    }

    // Check account active status
    if (!operator.is_active) {
      return res.status(403).json({ success: false, error: 'Account disabled by Super Admin.' });
    }

    // Check account expiration date
    const today = new Date().toISOString().split('T')[0];
    const expiryDate = new Date(operator.expiry_date).toISOString().split('T')[0];

    if (expiryDate < today) {
      return res.status(403).json({ success: false, error: `Account Expired on ${expiryDate}. Contact Super Admin.` });
    }

    // Return authenticated operator details & assigned Firebase credentials
    res.json({
      success: true,
      operator: {
        id: operator.id,
        username: operator.username,
        email: operator.email,
        fullName: operator.full_name,
        role: operator.role,
        expiryDate: expiryDate,
        firebaseConfig: {
          projectId: operator.firebase_project_id,
          apiKey: operator.firebase_api_key,
          authDomain: operator.firebase_auth_domain,
          storageBucket: operator.storage_bucket,
          appId: operator.app_id,
          orgId: operator.org_id
        }
      }
    });
  } catch (err) {
    console.error('MySQL Login Error:', err);
    res.status(500).json({ success: false, error: 'Database connection failed: ' + err.message });
  }
});

// 2. API Endpoint: Super Admin - Fetch All Operators from MySQL
app.get('/api/operators', async (req, res) => {
  try {
    const [rows] = await db.execute('SELECT id, username, email, full_name as fullName, role, expiry_date as expiryDate, is_active as isActive, firebase_project_id as firebaseProject, org_id as orgId FROM operators ORDER BY created_at DESC');
    res.json({ success: true, operators: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

// Fallback route: serve index.html
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'index.html'));
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Adminto MySQL Backend Server running on port ${PORT}`));
