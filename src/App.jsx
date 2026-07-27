import React, { useState, useEffect } from 'react';
import Navbar from './components/Navbar';
import StatCards from './components/StatCards';
import UserGrid from './components/UserGrid';
import UserDetailModal from './components/UserDetailModal';
import LoginModal from './components/LoginModal';
import SuperAdminPanel from './components/SuperAdminPanel';
import DownloadApkModal from './components/DownloadApkModal';
import { MOCK_USERS } from './mockData';

export default function App() {
  const [adminUser, setAdminUser] = useState(null);
  const [users, setUsers] = useState(MOCK_USERS);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedUser, setSelectedUser] = useState(null);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [showSuperAdminModal, setShowSuperAdminModal] = useState(false);
  const [showApkModal, setShowApkModal] = useState(false);

  // 15-second simulated background auto-refresh loop matching Android MainActivity
  useEffect(() => {
    const timer = setInterval(() => {
      handleRefreshData();
    }, 15000);
    return () => clearInterval(timer);
  }, []);

  const handleRefreshData = () => {
    setIsRefreshing(true);
    setTimeout(() => {
      setUsers((prevUsers) =>
        prevUsers.map((u) => ({
          ...u,
          lastActivityTime: u.isActive ? 'Just now' : u.lastActivityTime
        }))
      );
      setIsRefreshing(false);
    }, 600);
  };

  const handleUpdateUser = (updatedUser) => {
    setUsers((prev) => prev.map((u) => (u.id === updatedUser.id ? updatedUser : u)));
    setSelectedUser(updatedUser);
  };

  // Filter users based on Search Query
  const filteredUsers = users.filter((u) => {
    const q = searchQuery.toLowerCase().trim();
    if (!q) return true;
    return (
      u.fullName.toLowerCase().includes(q) ||
      u.mobileNumber.toLowerCase().includes(q) ||
      u.userId.toLowerCase().includes(q) ||
      (u.stringField && u.stringField.toLowerCase().includes(q))
    );
  });

  if (!adminUser) {
    return <LoginModal onLoginSuccess={(user) => setAdminUser(user)} />;
  }

  return (
    <div>
      <Navbar
        searchQuery={searchQuery}
        setSearchQuery={setSearchQuery}
        onRefresh={handleRefreshData}
        isRefreshing={isRefreshing}
        adminUser={adminUser}
        onLogout={() => {
          setAdminUser(null);
          setShowSuperAdminModal(false);
          setShowApkModal(false);
        }}
        onOpenSuperAdmin={() => setShowSuperAdminModal(true)}
        onOpenDownloadApk={() => setShowApkModal(true)}
      />

      <main className="main-container">
        <StatCards users={users} />

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
          <div>
            <h2 style={{ fontSize: '1.4rem', color: '#fff' }}>User Accounts & Devices</h2>
            <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>
              Showing {filteredUsers.length} of {users.length} registered targets • Connected Project: <code style={{ color: '#93c5fd' }}>{adminUser.firebaseConfig?.projectId || 'Default'}</code>
            </p>
          </div>
        </div>

        <UserGrid
          users={filteredUsers}
          onSelectUser={(user) => setSelectedUser(user)}
        />
      </main>

      {selectedUser && (
        <UserDetailModal
          user={selectedUser}
          onClose={() => setSelectedUser(null)}
          onUpdateUser={handleUpdateUser}
        />
      )}

      {showSuperAdminModal && (
        <SuperAdminPanel
          onClose={() => setShowSuperAdminModal(false)}
        />
      )}

      {showApkModal && (
        <DownloadApkModal
          adminUser={adminUser}
          onClose={() => setShowApkModal(false)}
        />
      )}
    </div>
  );
}
