/**
 * Administrator & Operator Database Module
 * Supports Super Admin management, per-operator Firebase configuration, and expiration enforcement.
 */

// Initial pre-configured Operator & Admin Database
let ADMIN_DATABASE = [
  {
    id: "admin_01",
    username: "admin",
    email: "admin@adminto.com",
    password: "admin123",
    fullName: "Super Administrator",
    role: "superadmin",
    expiryDate: "2099-12-31", // Super admin never expires
    isActive: true,
    createdDate: "2026-01-01",
    firebaseConfig: {
      apiKey: "AIzaSySuperAdminKey2026",
      projectId: "adminto-indus",
      authDomain: "adminto-indus.firebaseapp.com",
      orgId: "org_superadmin_all"
    }
  },
  {
    id: "op_101",
    username: "operator1",
    email: "operator1@adminto.com",
    password: "operator123",
    fullName: "Regional Operator North",
    role: "operator",
    expiryDate: "2026-12-31", // Valid until end of 2026
    isActive: true,
    createdDate: "2026-07-01",
    firebaseConfig: {
      apiKey: "AIzaSyOperatorNorthKey",
      projectId: "adminto-north-region",
      authDomain: "adminto-north.firebaseapp.com",
      orgId: "org_north"
    }
  },
  {
    id: "op_102",
    username: "expired_operator",
    email: "expired@adminto.com",
    password: "pass123",
    fullName: "Expired Operator Account",
    role: "operator",
    expiryDate: "2025-01-01", // Past date to test expiration blocking
    isActive: true,
    createdDate: "2024-01-01",
    firebaseConfig: {
      apiKey: "AIzaSyExpiredKey",
      projectId: "adminto-expired-region",
      authDomain: "adminto-expired.firebaseapp.com",
      orgId: "org_expired"
    }
  }
];

/**
 * Checks if an account has passed its assigned expiration date
 * @param {Object} account 
 * @returns {boolean} true if expired, false if active
 */
export function isAccountExpired(account) {
  if (!account || !account.expiryDate) return false;
  
  // Format today's date YYYY-MM-DD
  const todayStr = new Date().toISOString().split('T')[0];
  return account.expiryDate < todayStr;
}

/**
 * Authenticates user and checks active/expired status
 * @param {string} usernameOrEmail 
 * @param {string} password 
 * @returns {Object} { success: boolean, account?: Object, error?: string }
 */
export function authenticateAdmin(usernameOrEmail, password) {
  const query = usernameOrEmail.trim().toLowerCase();
  const inputPass = password.trim();

  const account = ADMIN_DATABASE.find(acc => 
    (acc.username.toLowerCase() === query || acc.email.toLowerCase() === query) &&
    acc.password === inputPass
  );

  if (!account) {
    return { success: false, error: "Invalid username or password." };
  }

  if (!account.isActive) {
    return { success: false, error: "Account has been disabled by Super Admin." };
  }

  if (isAccountExpired(account)) {
    return { 
      success: false, 
      error: `Account expired on ${account.expiryDate}. Please contact Super Admin to extend access.` 
    };
  }

  return { success: true, account };
}

/**
 * Returns all operator and admin accounts (for Super Admin panel)
 */
export function getAllOperators() {
  return [...ADMIN_DATABASE];
}

/**
 * Saves a new operator or updates an existing operator account
 * @param {Object} operatorData 
 */
export function saveOperator(operatorData) {
  const existingIndex = ADMIN_DATABASE.findIndex(op => op.id === operatorData.id);
  
  if (existingIndex >= 0) {
    ADMIN_DATABASE[existingIndex] = { ...ADMIN_DATABASE[existingIndex], ...operatorData };
  } else {
    const newOp = {
      id: `op_${Date.now()}`,
      createdDate: new Date().toISOString().split('T')[0],
      isActive: true,
      role: operatorData.role || 'operator',
      ...operatorData
    };
    ADMIN_DATABASE.push(newOp);
  }
  return [...ADMIN_DATABASE];
}

/**
 * Deletes an operator account by ID
 * @param {string} id 
 */
export function deleteOperator(id) {
  ADMIN_DATABASE = ADMIN_DATABASE.filter(op => op.id !== id);
  return [...ADMIN_DATABASE];
}
