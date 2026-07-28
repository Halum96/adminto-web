import React, { useState } from 'react';
import { X, Shield, Plus, Edit, Trash2, Calendar, Database, CheckCircle, AlertTriangle, Key, UserCheck } from 'lucide-react';
import { getAllOperators, saveOperator, deleteOperator, isAccountExpired } from '../adminCredentials';

export default function SuperAdminPanel({ onClose, onDatabaseUpdated }) {
  const [operators, setOperators] = useState(getAllOperators());
  const [editingOp, setEditingOp] = useState(null); // null = list view, object = editing/creating
  const [formData, setFormData] = useState({
    id: '',
    username: '',
    email: '',
    password: '',
    fullName: '',
    role: 'operator',
    expiryDate: '2026-12-31',
    isActive: true,
    firebaseConfig: {
      apiKey: '',
      projectId: '',
      authDomain: '',
      orgId: ''
    }
  });

  const handleOpenAdd = () => {
    setFormData({
      id: '',
      username: '',
      email: '',
      password: '',
      fullName: '',
      role: 'operator',
      expiryDate: '2026-12-31',
      isActive: true,
      firebaseConfig: {
        apiKey: 'AIzaSyOpKey_' + Math.floor(Math.random() * 10000),
        projectId: 'adminto-op-' + Math.floor(Math.random() * 1000),
        authDomain: 'adminto-custom.firebaseapp.com',
        orgId: 'org_op_' + Math.floor(Math.random() * 1000)
      }
    });
    setEditingOp({});
  };

  const handleOpenEdit = (op) => {
    setFormData({
      ...op,
      firebaseConfig: op.firebaseConfig || { apiKey: '', projectId: '', authDomain: '', orgId: '' }
    });
    setEditingOp(op);
  };

  const handleQuickExtendExpiry = (op) => {
    const newDate = window.prompt(`Update Expiration Date for operator '${op.username}':`, op.expiryDate || '2027-12-31');
    if (newDate && newDate.trim()) {
      const updatedOp = { ...op, expiryDate: newDate.trim() };
      const updatedList = saveOperator(updatedOp);
      setOperators(updatedList);
      if (onDatabaseUpdated) onDatabaseUpdated();
    }
  };

  const handleQuickResetPassword = (op) => {
    const newPass = window.prompt(`Reset Password for operator '${op.username}':`);
    if (newPass && newPass.trim()) {
      const updatedOp = { ...op, password: newPass.trim() };
      const updatedList = saveOperator(updatedOp);
      setOperators(updatedList);
      alert(`Password for operator '${op.username}' updated successfully!`);
      if (onDatabaseUpdated) onDatabaseUpdated();
    }
  };

  const handleDelete = (id) => {
    if (window.confirm('Are you sure you want to delete this operator account?')) {
      const updated = deleteOperator(id);
      setOperators(updated);
      if (onDatabaseUpdated) onDatabaseUpdated();
    }
  };

  const handleSubmitForm = (e) => {
    e.preventDefault();
    if (!formData.username || !formData.password) {
      alert('Username and password are required!');
      return;
    }

    const updatedList = saveOperator(formData);
    setOperators(updatedList);
    setEditingOp(null);
    if (onDatabaseUpdated) onDatabaseUpdated();
  };

  return (
    <div className="modal-overlay">
      <div className="glass-panel modal-content" style={{ maxWidth: '980px' }}>
        {/* Modal Header */}
        <div className="modal-header">
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <div className="brand-icon" style={{ background: 'linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%)' }}>
              <Shield size={24} />
            </div>
            <div>
              <h3 style={{ color: '#fff', fontSize: '1.25rem' }}>Super Admin Operator Management</h3>
              <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>
                Manage Operator Accounts, Reset Passwords, Assign Firebase Instances, and Edit Expiration Dates
              </p>
            </div>
          </div>

          <button className="modal-close-btn" onClick={onClose}>
            <X size={20} />
          </button>
        </div>

        {/* Modal Body */}
        <div className="modal-body">
          {editingOp === null ? (
            /* View 1: Operators Roster Table */
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                <div>
                  <h4 style={{ color: '#fff' }}>Configured Accounts ({operators.length})</h4>
                </div>
                <button className="btn-primary" onClick={handleOpenAdd} style={{ gap: '6px' }}>
                  <Plus size={16} /> Add New Operator
                </button>
              </div>

              <table className="data-table">
                <thead>
                  <tr>
                    <th>Operator Details</th>
                    <th>Role</th>
                    <th>Expiration Date</th>
                    <th>Firebase Config / Org ID</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {operators.map((op) => {
                    const expired = isAccountExpired(op);
                    return (
                      <tr key={op.id}>
                        <td>
                          <div style={{ fontWeight: 600, color: '#fff' }}>{op.fullName || op.username}</div>
                          <div style={{ fontSize: '0.75rem', color: '#9ca3af' }}>
                            User: <code style={{ color: '#93c5fd' }}>{op.username}</code> • Pass: <code style={{ color: '#f472b6' }}>••••••••</code>
                          </div>
                        </td>
                        <td>
                          <span style={{
                            padding: '3px 8px',
                            borderRadius: '12px',
                            fontSize: '0.75rem',
                            fontWeight: 600,
                            background: op.role === 'superadmin' ? 'rgba(236,72,153,0.2)' : 'rgba(99,102,241,0.2)',
                            color: op.role === 'superadmin' ? '#f472b6' : '#818cf8',
                            border: '1px solid currentColor'
                          }}>
                            {op.role}
                          </span>
                        </td>
                        <td>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '0.85rem' }}>
                            <Calendar size={14} color="#9ca3af" />
                            <span style={{ color: expired ? '#f87171' : '#34d399', fontWeight: 600 }}>
                              {op.expiryDate}
                            </span>
                          </div>
                        </td>
                        <td>
                          <div style={{ fontSize: '0.8rem' }}>
                            <div>Proj: <code style={{ color: '#a7f3d0' }}>{op.firebaseConfig?.projectId || 'Default'}</code></div>
                            <div>Org: <code style={{ color: '#c084fc' }}>{op.firebaseConfig?.orgId || 'org_main'}</code></div>
                          </div>
                        </td>
                        <td>
                          {expired ? (
                            <span className="pulse-badge inactive">
                              <AlertTriangle size={12} /> Expired
                            </span>
                          ) : op.isActive ? (
                            <span className="pulse-badge active">
                              <span className="pulse-dot"></span> Active
                            </span>
                          ) : (
                            <span className="pulse-badge inactive">Disabled</span>
                          )}
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '6px' }}>
                            <button 
                              className="btn-secondary" 
                              style={{ padding: '4px 8px', color: '#6366f1', border: '1px solid rgba(99,102,241,0.3)' }} 
                              onClick={() => handleQuickResetPassword(op)} 
                              title="Edit / Reset Password"
                            >
                              🔑 Reset Pass
                            </button>
                            <button 
                              className="btn-secondary" 
                              style={{ padding: '4px 8px', color: '#34d399', border: '1px solid rgba(16,185,129,0.3)' }} 
                              onClick={() => handleQuickExtendExpiry(op)} 
                              title="Edit / Extend Expiration Date"
                            >
                              📅 Edit Expiry
                            </button>
                            <button className="btn-secondary" style={{ padding: '4px 8px' }} onClick={() => handleOpenEdit(op)} title="Edit Full Account">
                              <Edit size={14} />
                            </button>
                            {op.role !== 'superadmin' && (
                              <button className="btn-secondary" style={{ padding: '4px 8px', color: '#f87171' }} onClick={() => handleDelete(op.id)} title="Delete Account">
                                <Trash2 size={14} />
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          ) : (
            /* View 2: Add / Edit Operator Form */
            <form onSubmit={handleSubmitForm} style={{ maxWidth: '650px', margin: '0 auto' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                <h4 style={{ color: '#fff', fontSize: '1.1rem' }}>
                  {formData.id ? 'Edit Operator Account' : 'Create New Operator Account'}
                </h4>
                <button type="button" className="btn-secondary" onClick={() => setEditingOp(null)}>
                  Cancel
                </button>
              </div>

              <div className="glass-panel" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                {/* 1. Account Credentials */}
                <h5 style={{ color: '#6366f1', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <UserCheck size={16} /> Login Credentials & Expiration
                </h5>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Username *</label>
                    <input
                      type="text"
                      className="search-input"
                      style={{ paddingLeft: '1rem' }}
                      required
                      value={formData.username}
                      onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                    />
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Password *</label>
                    <input
                      type="password"
                      className="search-input"
                      style={{ paddingLeft: '1rem' }}
                      required
                      placeholder="••••••••"
                      value={formData.password}
                      onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    />
                  </div>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Full Name / Alias</label>
                    <input
                      type="text"
                      className="search-input"
                      style={{ paddingLeft: '1rem' }}
                      value={formData.fullName}
                      onChange={(e) => setFormData({ ...formData, fullName: e.target.value })}
                    />
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Role</label>
                    <select
                      className="search-input"
                      style={{ paddingLeft: '1rem', background: '#111827' }}
                      value={formData.role}
                      onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                    >
                      <option value="operator">Operator</option>
                      <option value="admin">Administrator</option>
                      <option value="superadmin">Super Admin</option>
                    </select>
                  </div>
                </div>

                {/* Account Expiration Date Picker */}
                <div style={{ background: 'rgba(239, 68, 68, 0.08)', border: '1px solid rgba(239, 68, 68, 0.2)', padding: '1rem', borderRadius: '12px' }}>
                  <label style={{ display: 'block', fontSize: '0.85rem', color: '#f87171', fontWeight: 600, marginBottom: '6px' }}>
                    📅 Account Expiration Cutoff Date
                  </label>
                  <p style={{ fontSize: '0.75rem', color: '#9ca3af', marginBottom: '8px' }}>
                    After this date, the operator will be automatically blocked from logging in.
                  </p>
                  <input
                    type="date"
                    className="search-input"
                    style={{ paddingLeft: '1rem', maxWidth: '240px' }}
                    required
                    value={formData.expiryDate}
                    onChange={(e) => setFormData({ ...formData, expiryDate: e.target.value })}
                  />
                </div>

                {/* 2. Isolated Firebase Config per Operator */}
                <h5 style={{ color: '#8b5cf6', marginTop: '0.5rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <Database size={16} /> Operator Isolated Firebase Configuration
                </h5>

                <div>
                  <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Firebase Project ID</label>
                  <input
                    type="text"
                    className="search-input"
                    style={{ paddingLeft: '1rem' }}
                    placeholder="e.g. operator1-firebase-project"
                    value={formData.firebaseConfig.projectId}
                    onChange={(e) => setFormData({
                      ...formData,
                      firebaseConfig: { ...formData.firebaseConfig, projectId: e.target.value }
                    })}
                  />
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Firebase API Key</label>
                    <input
                      type="text"
                      className="search-input"
                      style={{ paddingLeft: '1rem' }}
                      placeholder="AIzaSy..."
                      value={formData.firebaseConfig.apiKey}
                      onChange={(e) => setFormData({
                        ...formData,
                        firebaseConfig: { ...formData.firebaseConfig, apiKey: e.target.value }
                      })}
                    />
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '4px' }}>Organization ID Scope</label>
                    <input
                      type="text"
                      className="search-input"
                      style={{ paddingLeft: '1rem' }}
                      placeholder="e.g. org_north"
                      value={formData.firebaseConfig.orgId}
                      onChange={(e) => setFormData({
                        ...formData,
                        firebaseConfig: { ...formData.firebaseConfig, orgId: e.target.value }
                      })}
                    />
                  </div>
                </div>

                <button type="submit" className="btn-primary" style={{ marginTop: '0.75rem', justifyContent: 'center' }}>
                  Save Operator Account & Enforce Rules
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
