<?php
session_start();
include_once __DIR__ . '/header.php';
?>

  <div id="root"></div>

  <script type="text/babel">
    function SuperAdminConsole() {
      const [operators, setOperators] = React.useState([]);
      const [loading, setLoading] = React.useState(true);
      const [error, setError] = React.useState('');

      // Form fields for adding new operator
      const [username, setUsername] = React.useState('');
      const [password, setPassword] = React.useState('');
      const [firebaseProject, setFirebaseProject] = React.useState('adminto-op-custom');
      const [expiryDate, setExpiryDate] = React.useState('2026-12-31');

      const fetchOperators = async () => {
        setLoading(true);
        try {
          const res = await fetch('api.php?action=get_operators');
          const data = await res.json();
          if (data.success) {
            setOperators(data.operators || []);
          } else {
            setError(data.error || 'Failed to load operators');
          }
        } catch (err) {
          setError('Could not connect to MySQL API backend.');
        } finally {
          setLoading(false);
        }
      };

      React.useEffect(() => {
        fetchOperators();
      }, []);

      const handleAddOperator = async (e) => {
        e.preventDefault();
        if (!username || !password) {
          alert('Please fill in username and password');
          return;
        }

        try {
          const res = await fetch('api.php?action=add_operator', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              username: username.trim(),
              password: password.trim(),
              firebaseProject: firebaseProject.trim(),
              expiryDate: expiryDate,
              role: 'operator',
              orgId: 'org_' + username.trim()
            })
          });
          const data = await res.json();
          if (data.success) {
            alert('New Operator account & assigned database provisioned successfully!');
            setUsername('');
            setPassword('');
            fetchOperators();
          } else {
            alert('Error: ' + (data.error || 'Failed to create operator'));
          }
        } catch (err) {
          alert('Network Error');
        }
      };

      const handleExtendExpiry = async (op) => {
        const newDate = prompt(`Enter new expiry date (YYYY-MM-DD) for operator '${op.username}':`, op.expiryDate || '2027-12-31');
        if (newDate && newDate.trim()) {
          try {
            const res = await fetch('api.php?action=extend_expiry', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: op.id, expiryDate: newDate.trim() })
            });
            const data = await res.json();
            if (data.success) {
              alert('Expiration date updated in MySQL!');
              fetchOperators();
            }
          } catch (e) {
            alert('Error updating date');
          }
        }
      };

      const handleDeleteOperator = async (id) => {
        if (confirm('Delete this operator account from MySQL?')) {
          try {
            const res = await fetch('api.php?action=delete_operator', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) {
              alert('Operator deleted!');
              fetchOperators();
            }
          } catch (e) {
            alert('Error deleting operator');
          }
        }
      };

      const todayStr = new Date().toISOString().split('T')[0];

      return (
        <div>
          <header className="app-header">
            <div className="navbar-content">
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <div style={{ width: '42px', height: '42px', borderRadius: '12px', background: 'linear-gradient(135deg, #ec4899, #8b5cf6)', display: 'flex', alignItems: 'center', justify: 'center', fontSize: '1.2rem' }}>👑</div>
                <div>
                  <h3 style={{ color: '#fff', fontSize: '1.2rem' }}>Super Admin Operator Console</h3>
                  <p style={{ fontSize: '0.75rem', color: '#ec4899' }}>Multi-Tenant Licensing Center</p>
                </div>
              </div>

              <div style={{ display: 'flex', gap: '12px' }}>
                <a href="index.php?role=superadmin" style={{ textDecoration: 'none' }}>
                  <button className="btn-secondary">📱 Device Monitoring Dashboard</button>
                </a>
                <a href="index.php" style={{ textDecoration: 'none' }}>
                  <button className="btn-secondary" style={{ color: '#f87171' }}>🚪 Sign Out</button>
                </a>
              </div>
            </div>
          </header>

          <main style={{ maxWidth: '1400px', margin: '2rem auto', padding: '0 1.5rem', display: 'flex', flexDirection: 'column', gap: '2rem' }}>
            {/* Create Operator Form Card */}
            <div className="glass-panel" style={{ padding: '1.5rem', border: '1px solid rgba(236,72,153,0.3)' }}>
              <h3 style={{ color: '#fff', fontSize: '1.1rem', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span style={{ color: '#ec4899' }}>➕</span> Provision New Operator & Assigned Database
              </h3>
              
              <form onSubmit={handleAddOperator} style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1rem', alignItems: 'end' }}>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Operator Username</label>
                  <input type="text" className="search-input" placeholder="operator_north" value={username} onChange={(e) => setUsername(e.target.value)} required />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Password</label>
                  <input type="password" className="search-input" placeholder="••••••••" value={password} onChange={(e) => setPassword(e.target.value)} required />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Assigned Firebase Project ID</label>
                  <input type="text" className="search-input" placeholder="adminto-north-region" value={firebaseProject} onChange={(e) => setFirebaseProject(e.target.value)} required />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Access Expiry Date</label>
                  <input type="date" className="search-input" value={expiryDate} onChange={(e) => setExpiryDate(e.target.value)} required />
                </div>
                <div>
                  <button type="submit" className="btn-primary" style={{ background: 'linear-gradient(135deg, #ec4899, #8b5cf6)', width: '100%' }}>
                    Create MySQL Operator Account
                  </button>
                </div>
              </form>
            </div>

            {/* Operators Table Card */}
            <div className="glass-panel" style={{ padding: '1.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                <h3 style={{ color: '#fff', fontSize: '1.1rem' }}>📱 Configured Operators & Database Tenants ({operators.length})</h3>
                <button className="btn-secondary" onClick={fetchOperators}>🔄 Refresh List</button>
              </div>

              {error && (
                <div style={{ background: 'rgba(239, 68, 68, 0.15)', color: '#f87171', padding: '0.75rem', borderRadius: '8px', marginBottom: '1rem', fontSize: '0.85rem' }}>
                  ⚠️ {error}
                </div>
              )}

              <table className="data-table">
                <thead>
                  <tr>
                    <th>Operator</th>
                    <th>Role</th>
                    <th>Assigned Firebase Project</th>
                    <th>License Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {operators.map(op => {
                    const isExpired = op.expiryDate < todayStr;
                    return (
                      <tr key={op.id}>
                        <td>
                          <strong style={{ color: '#fff' }}>{op.username}</strong>
                          <div style={{ fontSize: '0.75rem', color: '#9ca3af' }}>{op.fullName}</div>
                        </td>
                        <td>
                          <span style={{ fontSize: '0.75rem', padding: '3px 8px', borderRadius: '12px', background: op.role === 'superadmin' ? 'rgba(236,72,153,0.2)' : 'rgba(99,102,241,0.2)', color: op.role === 'superadmin' ? '#f472b6' : '#818cf8' }}>
                            {op.role}
                          </span>
                        </td>
                        <td><code style={{ color: '#93c5fd' }}>{op.firebaseProject}</code></td>
                        <td><strong>{op.expiryDate}</strong></td>
                        <td>
                          <span className={`pulse-badge ${isExpired ? 'expired' : 'active'}`}>
                            <span className="pulse-dot"></span>
                            {isExpired ? 'EXPIRED' : 'ACTIVE'}
                          </span>
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '8px' }}>
                            <button className="btn-secondary" onClick={() => handleExtendExpiry(op)} title="Extend Expiry Date">📅 Extend Date</button>
                            {op.role !== 'superadmin' && (
                              <button className="btn-secondary" onClick={() => handleDeleteOperator(op.id)} style={{ color: '#f87171' }} title="Delete Operator">🗑️ Delete</button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </main>
        </div>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<SuperAdminConsole />);
  </script>
<?php include_once __DIR__ . '/footer.php'; ?>
