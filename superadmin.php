<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Super Admin Operator Console (PHP MySQL Edition)</title>
  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- React & Babel CDNs -->
  <script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

  <style>
    :root {
      --bg-dark: #090d16;
      --bg-card: rgba(17, 24, 39, 0.75);
      --border-color: rgba(255, 255, 255, 0.08);
      --primary: #ec4899;
      --accent: #8b5cf6;
      --text-main: #f9fafb;
      --text-muted: #9ca3af;
      --font-heading: 'Outfit', sans-serif;
      --font-body: 'Inter', sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-body);
      min-height: 100vh;
      background-image: 
        radial-gradient(at 0% 0%, rgba(236, 72, 153, 0.15) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.15) 0px, transparent 50%);
    }

    h1, h2, h3, h4 { font-family: var(--font-heading); }

    .glass-panel {
      background: var(--bg-card);
      backdrop-filter: blur(16px);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .app-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(9, 13, 22, 0.85);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border-color);
      padding: 1rem 2rem;
    }

    .navbar-content {
      max-width: 1400px; margin: 0 auto;
      display: flex; align-items: center; justify-content: space-between; gap: 1.5rem;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer;
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-color);
      color: var(--text-main);
      padding: 0.5rem 1rem;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.85rem;
    }

    .search-input {
      width: 100%;
      padding: 0.65rem 1.2rem;
      background: rgba(17, 24, 39, 0.6);
      border: 1px solid var(--border-color);
      border-radius: 8px; color: #fff; font-size: 0.9rem;
    }

    .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
    .data-table th, .data-table td { padding: 0.85rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }

    .pulse-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .pulse-badge.active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .pulse-badge.expired { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
  </style>
</head>
<body>
  <div id="root"></div>

  <script type="text/babel">
    function SuperAdminConsole() {
      const [operators, setOperators] = React.useState([]);
      const [loading, setLoading] = React.useState(true);
      const [newOp, setNewOp] = React.useState({
        username: '', password: '', fullName: '', role: 'operator', expiryDate: '2026-12-31',
        firebaseProject: 'adminto-op-custom', orgId: 'org_custom'
      });

      const fetchOperators = async () => {
        try {
          const res = await fetch('api.php?action=get_operators');
          const data = await res.json();
          if (data.success) {
            setOperators(data.operators);
          }
        } catch (err) {
          console.error(err);
        } finally {
          setLoading(false);
        }
      };

      React.useEffect(() => {
        fetchOperators();
      }, []);

      const handleAddOperatorSubmit = async (e) => {
        e.preventDefault();
        if (!newOp.username || !newOp.password) return;

        try {
          const res = await fetch('api.php?action=add_operator', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newOp)
          });
          const data = await res.json();
          if (data.success) {
            alert(`Operator '${newOp.username}' created successfully in MySQL!`);
            setNewOp({ username: '', password: '', fullName: '', role: 'operator', expiryDate: '2026-12-31', firebaseProject: 'adminto-op-custom', orgId: 'org_custom' });
            fetchOperators();
          } else {
            alert('Error: ' + data.error);
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
                  <p style={{ fontSize: '0.75rem', color: '#ec4899' }}>MySQL Database Multi-Tenant Licensing Center (PHP)</p>
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
              <h3 style={{ color: '#ec4899', marginBottom: '1rem' }}>➕ Provision New Operator & Assigned Database</h3>
              <form onSubmit={handleAddOperatorSubmit} style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Operator Username</label>
                  <input type="text" className="search-input" value={newOp.username} onChange={e => setNewOp({...newOp, username: e.target.value})} placeholder="operator_north" required />
                </div>
                <div>
                  <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Password</label>
                  <input type="password" className="search-input" value={newOp.password} onChange={e => setNewOp({...newOp, password: e.target.value})} placeholder="••••••••" required />
                </div>
                <div>
                  <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Assigned Firebase Project ID</label>
                  <input type="text" className="search-input" value={newOp.firebaseProject} onChange={e => setNewOp({...newOp, firebaseProject: e.target.value})} placeholder="adminto-north-prod" required />
                </div>
                <div>
                  <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Access Expiry Date</label>
                  <input type="date" className="search-input" value={newOp.expiryDate} onChange={e => setNewOp({...newOp, expiryDate: e.target.value})} required />
                </div>
                <div style={{ gridColumn: '1 / -1', marginTop: '0.5rem' }}>
                  <button type="submit" className="btn-primary" style={{ padding: '0.75rem 1.5rem' }}>
                    Create MySQL Operator Account
                  </button>
                </div>
              </form>
            </div>

            {/* Operator Licensing List */}
            <div className="glass-panel" style={{ padding: '1.5rem' }}>
              <h3 style={{ color: '#fff', marginBottom: '1rem' }}>📋 Configured Operators & Database Tenants ({operators.length})</h3>
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
                    const isExpired = op.expiryDate && op.expiryDate < todayStr;
                    return (
                      <tr key={op.id}>
                        <td>
                          <div style={{ fontWeight: 600, color: '#fff' }}>{op.username}</div>
                          <div style={{ fontSize: '0.8rem', color: '#9ca3af' }}>{op.email}</div>
                        </td>
                        <td>
                          <span style={{ color: op.role === 'superadmin' ? '#ec4899' : '#93c5fd', fontWeight: 600 }}>{op.role}</span>
                        </td>
                        <td><code style={{ color: '#34d399' }}>{op.firebaseProject || 'Default'}</code></td>
                        <td><strong style={{ color: isExpired ? '#f87171' : '#f3f4f6' }}>{op.expiryDate}</strong></td>
                        <td>
                          <span className={`pulse-badge ${isExpired ? 'expired' : 'active'}`}>
                            {isExpired ? 'EXPIRED' : 'ACTIVE'}
                          </span>
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '6px' }}>
                            <button className="btn-secondary" onClick={() => handleExtendExpiry(op)} title="Extend Expiry Date">📅 Extend</button>
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
</body>
</html>
