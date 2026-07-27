import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";
import { getFirestore } from "firebase/firestore";

// Firebase Config initialized from google-services parameters or fallback demo config
const firebaseConfig = {
  apiKey: "AIzaSyDemoKeyAdmintoWebDashboard2026",
  authDomain: "adminto-indus.firebaseapp.com",
  projectId: "adminto-indus",
  storageBucket: "adminto-indus.appspot.com",
  messagingSenderId: "109823746501",
  appId: "1:109823746501:web:8f3c7a912b4e0d"
};

let app, auth, firestore;

try {
  app = initializeApp(firebaseConfig);
  auth = getAuth(app);
  firestore = getFirestore(app);
} catch (e) {
  console.warn("Firebase initialized in offline demo mode:", e);
}

export { app, auth, firestore };
