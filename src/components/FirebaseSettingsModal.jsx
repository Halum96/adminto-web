import React, { useState } from 'react';
import { X, Flame, Database, Check, Copy, HelpCircle, Code } from 'lucide-react';

export default function FirebaseSettingsModal({ adminUser, onSaveConfig, onClose }) {
  const currentConfig = adminUser?.firebaseConfig || {};

  const [apiKey, setApiKey] = useState(currentConfig.apiKey || '');
  const [projectId, setProjectId] = useState(currentConfig.projectId || '');
  const [authDomain, setAuthDomain] = useState(currentConfig.authDomain || '');
  const [storageBucket, setStorageBucket] = useState(currentConfig.storageBucket || '');
  const [messagingSenderId, setMessagingSenderId] = useState(currentConfig.messagingSenderId || '');
  const [appId, setAppId] = useState(currentConfig.appId || '');
  const [orgId, setOrgId] = useState(currentConfig.orgId || 'org_default');

  const [jsonPaste, setJsonPaste] = useState('');
  const [showJsonTab, setShowJsonTab] = useState(false);
  const [successMsg, setSuccessMsg] = useState('');

  // Auto-parse raw Firebase config snippet pasted from Firebase Console
  const handleParseJson = () => {
    try {
      let raw = jsonPaste.trim();
      // Extract object inside const firebaseConfig = { ... } if present
      if (raw.includes('{')) {
        raw = raw.substring(raw.indexOf('{'), raw.lastIndexOf('}') + 1);
      }
      
      // Clean JS keys if not strict JSON
      const cleanJson = raw
        .replace(/(['"])?([a-zA-Z0-9_]+)(['"])?:/g, '"$2":')
        .replace(/'/g, '"')
        .replace(/,\s*}/g, '}');

      const parsed = JSON.parse(cleanJson);

      if (parsed.apiKey) setApiKey(parsed.apiKey);
      if (parsed.projectId) setProjectId(parsed.projectId);
      if (parsed.authDomain) setAuthDomain(parsed.authDomain);
      if (parsed.storageBucket) setStorageBucket(parsed.storageBucket);
      if (parsed.messagingSenderId) setMessagingSenderId(parsed.messagingSenderId);
      if (parsed.appId) setAppId(parsed.appId);

      setSuccessMsg('Successfully parsed Firebase snippet!');
      setTimeout(() => setSuccessMsg(''), 3000);
    } catch (e) {
      alert('Could not parse Firebase snippet. Please fill in the fields manually.');
    }
  };

  const handleSave = (e) => {
    e.preventDefault();
    if (!projectId || !apiKey) {
      alert('Firebase Project ID and API Key are required!');
      return;
    }

    const updatedConfig = {
      apiKey,
      projectId,
      authDomain: authDomain || `${projectId}.firebaseapp.com`,
      storageBucket: storageBucket || `${projectId}.appspot.com`,
      messagingSenderId,
      appId,
      orgId
    };

    onSaveConfig(updatedConfig);
    setSuccessMsg('Firebase configuration connected & saved!');
    setTimeout(() => {
      onClose();
    }, 1200);
  };

  return (
    <div className="modal-overlay">
      <div className="glass-panel modal-content" style={{ maxWidth: '650px' }}>
        {/* Header */}
        <div className="modal-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div className="brand-icon" style={{ background: 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)' }}>
              <Flame size={24} />
            </div>
            <div>
              <h3 style={{ color: '#fff', fontSize: '1.2rem' }}>Connect Your Own Firebase Database</h3>
              <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>
                Bring your own Firebase project credentials to load your device & data stream
              </p>
            </div>
          </div>

          <button className="modal-close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        {/* Modal Body */}
        <div className="modal-body">
          {successMsg && (
            <div style={{ background: 'rgba(16, 185, 129, 0.15)', border: '1px solid rgba(16, 185, 129, 0.3)', color: '#34d399', padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1.25rem', textAlign: 'center' }}>
              <Check size={16} style={{ display: 'inline', marginRight: '6px' }} /> {successMsg}
            </div>
          )}

          {/* Quick Paste Snippet Toggle */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
            <span style={{ fontSize: '0.85rem', color: '#9ca3af' }}>Choose Input Method:</span>
            <div style={{ display: 'flex', gap: '8px' }}>
              <button 
                type="button" 
                className={`btn-secondary ${!showJsonTab ? 'btn-primary' : ''}`}
                onClick={() => setShowJsonTab(false)}
                style={{ padding: '4px 12px', fontSize: '0.8rem' }}
              >
                Manual Form
              </button>
              <button 
                type="button" 
                className={`btn-secondary ${showJsonTab ? 'btn-primary' : ''}`}
                onClick={() => setShowJsonTab(true)}
                style={{ padding: '4px 12px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '4px' }}
              >
                <Code size={13} /> Paste Firebase Snippet
              </button>
            </div>
          </div>

          {showJsonTab ? (
            /* Paste Snippet Tab */
            <div className="glass-panel" style={{ padding: '1.25rem' }}>
              <label style={{ display: 'block', fontSize: '0.85rem', color: '#fff', marginBottom: '6px' }}>
                Paste <code>const firebaseConfig = &#123; ... &#125;;</code> Snippet:
              </label>
              <textarea
                rows={6}
                className="search-input"
                style={{ width: '100%', borderRadius: '12px', padding: '0.75rem', fontFamily: 'monospace', fontSize: '0.8rem' }}
                placeholder={`const firebaseConfig = {\n  apiKey: "AIzaSy...",\n  authDomain: "my-app.firebaseapp.com",\n  projectId: "my-app-id",\n  appId: "1:1234:web:abcd"\n};`}
                value={jsonPaste}
                onChange={(e) => setJsonPaste(e.target.value)}
              />
              <button type="button" className="btn-primary" onClick={handleParseJson} style={{ marginTop: '0.75rem', width: '100%', justifyContent: 'center' }}>
                Auto-Parse & Populate Form
              </button>
            </div>
          ) : null}

          {/* Manual Input Form */}
          <form onSubmit={handleSave} style={{ display: 'flex', flexDirection: 'column', gap: '1rem', marginTop: '0.5rem' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div>
                <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Firebase Project ID *</label>
                <input
                  type="text"
                  className="search-input"
                  style={{ paddingLeft: '1rem' }}
                  required
                  placeholder="my-firebase-project-id"
                  value={projectId}
                  onChange={(e) => setProjectId(e.target.value)}
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Firebase Web API Key *</label>
                <input
                  type="text"
                  className="search-input"
                  style={{ paddingLeft: '1rem' }}
                  required
                  placeholder="AIzaSy..."
                  value={apiKey}
                  onChange={(e) => setApiKey(e.target.value)}
                />
              </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div>
                <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Auth Domain</label>
                <input
                  type="text"
                  className="search-input"
                  style={{ paddingLeft: '1rem' }}
                  placeholder="my-project.firebaseapp.com"
                  value={authDomain}
                  onChange={(e) => setAuthDomain(e.target.value)}
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Storage Bucket</label>
                <input
                  type="text"
                  className="search-input"
                  style={{ paddingLeft: '1rem' }}
                  placeholder="my-project.appspot.com"
                  value={storageBucket}
                  onChange={(e) => setStorageBucket(e.target.value)}
                />
              </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div>
                <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>App ID (`appId`)</label>
                <input
                  type="text"
                  className="search-input"
                  style={{ paddingLeft: '1rem' }}
                  placeholder="1:123456789:web:abcd123"
                  value={appId}
                  onChange={(e) => setAppId(e.target.value)}
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Organization ID Scope</label>
                <input
                  type="text"
                  className="search-input"
                  style={{ paddingLeft: '1rem' }}
                  placeholder="org_my_company"
                  value={orgId}
                  onChange={(e) => setOrgId(e.target.value)}
                />
              </div>
            </div>

            <button type="submit" className="btn-primary" style={{ marginTop: '0.75rem', width: '100%', justifyContent: 'center', padding: '0.75rem' }}>
              Connect & Load Firebase Database
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
