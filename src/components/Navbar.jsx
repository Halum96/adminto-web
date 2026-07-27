import React from 'react';
import { Search, Shield, RefreshCw, LogOut, Wifi, Server } from 'lucide-react';

export default function Navbar({ 
  searchQuery, 
  setSearchQuery, 
  onRefresh, 
  isRefreshing, 
  adminUser, 
  onLogout,
  onOpenSuperAdmin,
  onOpenDownloadApk
}) {
  const isSuperAdmin = adminUser?.role === 'superadmin';

  return (
    <header className="app-header">
      <div className="navbar-content">
        <div className="brand-logo">
          <div className="brand-icon">
            <Shield size={24} />
          </div>
          <div>
            <div className="brand-title">ADMINTO</div>
            <div className="brand-subtitle">Realtime Monitoring Console</div>
          </div>
        </div>

        <div className="search-box">
          <Search size={18} className="search-icon" />
          <input
            type="text"
            className="search-input"
            placeholder="Search by Name, Mobile Number, or User ID..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
          <div className="pulse-badge active" style={{ cursor: 'pointer' }} onClick={onRefresh}>
            <span className="pulse-dot"></span>
            <Wifi size={12} />
            <span>{isRefreshing ? 'Syncing...' : (adminUser?.firebaseConfig?.projectId || 'Firebase Live')}</span>
          </div>

          <button 
            className="btn-secondary" 
            onClick={onRefresh}
            title="Refresh Data (Auto-refreshes every 15s)"
            style={{ display: 'flex', alignItems: 'center', gap: '6px' }}
          >
            <RefreshCw size={14} className={isRefreshing ? 'spin' : ''} />
            <span>Refresh</span>
          </button>

          {adminUser && (
            <button 
              className="btn-secondary"
              onClick={onOpenDownloadApk}
              style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#10b981', border: '1px solid rgba(16,185,129,0.3)' }}
              title="Download Operator-Tailored Custom Android APK"
            >
              <span>📲 Download Custom APK</span>
            </button>
          )}

          {isSuperAdmin && (
            <button 
              className="btn-secondary"
              onClick={onOpenSuperAdmin}
              style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#ec4899', border: '1px solid rgba(236,72,153,0.3)' }}
              title="Super Admin Operator Console"
            >
              <span>⚙️ Operator Console</span>
            </button>
          )}

          {adminUser && (
            <button 
              className="btn-secondary"
              onClick={onLogout}
              style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#f87171' }}
            >
              <LogOut size={14} />
              <span>Logout ({adminUser.username})</span>
            </button>
          )}
        </div>
      </div>
    </header>
  );
}
