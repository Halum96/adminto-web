<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Mobile-first viewport optimization -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Adminto - Realtime Monitoring Console</title>
  
  <!-- Google Fonts: Outfit & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Fast React & Babel CDNs -->
  <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

  <style>
    :root {
      --bg-dark: #090d16;
      --bg-card: rgba(17, 24, 39, 0.85);
      --border-color: rgba(255, 255, 255, 0.1);
      --primary: #6366f1;
      --accent: #ec4899;
      --text-main: #f9fafb;
      --text-muted: #9ca3af;
      --status-active: #10b981;
      --status-inactive: #ef4444;
      --font-heading: 'Outfit', sans-serif;
      --font-body: 'Inter', sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-body);
      min-height: 100vh;
      overflow-x: hidden;
      background-image: 
        radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.18) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.18) 0px, transparent 50%);
    }

    h1, h2, h3, h4 { font-family: var(--font-heading); }

    .glass-panel {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    }

    /* Mobile First Navbar & Responsive Header */
    .app-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(9, 13, 22, 0.92);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border-color);
      padding: 0.85rem 1.25rem;
    }

    .navbar-content {
      max-width: 1400px; margin: 0 auto;
      display: flex; align-items: center; justify-content: space-between; gap: 1rem;
      flex-wrap: wrap;
    }

    .brand-logo { display: flex; align-items: center; gap: 10px; }
    .brand-icon {
      width: 40px; height: 40px; border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;
    }

    .search-input {
      width: 100%; max-width: 450px;
      padding: 0.75rem 1.2rem;
      background: rgba(17, 24, 39, 0.7);
      border: 1px solid var(--border-color);
      border-radius: 24px; color: #fff; font-size: 0.9rem;
      min-height: 44px; /* Touch-friendly tap target */
    }

    .pulse-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .pulse-badge.active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .pulse-badge.inactive { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

    /* Mobile Responsive Metrics Grid */
    .metrics-grid {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem; margin-bottom: 1.5rem;
    }
    @media (min-width: 768px) {
      .metrics-grid { grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
    }
    .metric-card { padding: 1rem; display: flex; justify-content: space-between; align-items: center; }

    /* Mobile Responsive Target Device Cards Grid */
    .user-cards-grid {
      display: grid; grid-template-columns: 1fr; gap: 1rem;
    }
    @media (min-width: 640px) {
      .user-cards-grid { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem; }
    }
    .user-card { 
      padding: 1.25rem; cursor: pointer; transition: transform 0.15s ease, border-color 0.15s ease;
      touch-action: manipulation;
    }
    .user-card:active, .user-card:hover { transform: scale(0.99); border-color: rgba(99, 102, 241, 0.6); }

    /* Mobile Full-Screen Responsive Modals */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 1000;
      background: rgba(0, 0, 0, 0.82); backdrop-filter: blur(12px);
      display: flex; align-items: flex-end; justify-content: center; padding: 0;
    }
    @media (min-width: 640px) {
      .modal-overlay { align-items: center; padding: 1.5rem; }
    }
    .modal-content { 
      width: 100%; max-width: 850px; max-height: 92vh; border-radius: 24px 24px 0 0;
      overflow: hidden; display: flex; flex-direction: column; animation: slideUp 0.25s ease-out;
    }
    @media (min-width: 640px) {
      .modal-content { border-radius: 20px; max-height: 90vh; }
    }

    @keyframes slideUp {
      from { transform: translateY(100%); }
      to { transform: translateY(0); }
    }

    .tab-btn {
      padding: 0.65rem 1.1rem; border-radius: 10px; background: transparent; border: none;
      color: var(--text-muted); font-weight: 600; cursor: pointer; min-height: 44px;
    }
    .tab-btn.active { background: linear-gradient(135deg, var(--primary), var(--accent)); color: #fff; }

    .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
    .data-table th, .data-table td { padding: 0.85rem 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.06); }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid var(--border-color);
      color: var(--text-main);
      padding: 0.65rem 1.1rem;
      border-radius: 12px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 500;
      min-height: 44px;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .btn-secondary:active { background: rgba(255, 255, 255, 0.15); }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff; border: none; padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 600; cursor: pointer;
      min-height: 44px; display: inline-flex; align-items: center; justify-content: center;
    }

    /* Mobile Responsive Utilities */
    @media (max-width: 640px) {
      .navbar-content { flex-direction: column; align-items: stretch; gap: 0.85rem; }
      .search-input { max-width: 100%; }
      .navbar-actions { justify-content: space-between; overflow-x: auto; padding-bottom: 4px; }
    }
  </style>
</head>
<body>
