<?php
// Auto-migrate MySQL schema on every page load (silent — never breaks the page)
include_once __DIR__ . '/migrate.php';

// HTTP performance headers — allow browsers to cache this page for 5 minutes
header('Cache-Control: public, max-age=300, stale-while-revalidate=60');
header('X-Content-Type-Options: nosniff');
header('Vary: Accept-Encoding');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <!-- Mobile-first viewport -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Adminto - Realtime Monitoring Console</title>

  <!-- ─── DNS Prefetch & Preconnect (reduce CDN latency) ─────────────────── -->
  <link rel="dns-prefetch" href="https://unpkg.com">
  <link rel="dns-prefetch" href="https://fonts.googleapis.com">
  <link rel="dns-prefetch" href="https://fonts.gstatic.com">
  <link rel="preconnect" href="https://unpkg.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- ─── Google Fonts (async non-blocking load) ──────────────────────────── -->
  <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap"
        onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  </noscript>

  <!-- ─── React 18 + Babel (pinned versions = reliable browser cache) ──────── -->
  <!-- Pinned versions ensure browser caches these forever (immutable CDN URLs) -->
  <script src="https://unpkg.com/react@18.3.1/umd/react.production.min.js"
          crossorigin="anonymous"></script>
  <script src="https://unpkg.com/react-dom@18.3.1/umd/react-dom.production.min.js"
          crossorigin="anonymous"></script>
  <script src="https://unpkg.com/@babel/standalone@7.25.6/babel.min.js"
          crossorigin="anonymous"></script>

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
    }

    .navbar-header-top {
      display: flex; align-items: center; justify-content: space-between; width: auto;
    }

    .brand-logo { display: flex; align-items: center; gap: 10px; }
    .brand-icon {
      width: 40px; height: 40px; border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;
    }

    .search-container { flex: 1; max-width: 450px; width: 100%; }

    .search-input {
      width: 100%;
      padding: 0.75rem 1.2rem;
      background: rgba(17, 24, 39, 0.7);
      border: 1px solid var(--border-color);
      border-radius: 24px; color: #fff; font-size: 0.9rem;
      min-height: 44px; /* Touch-friendly tap target */
    }

    .navbar-actions {
      display: flex; align-items: center; gap: 0.75rem;
    }

    .nav-user-group {
      display: flex; align-items: center; gap: 0.75rem;
    }

    /* Triple Dash Hamburger Toggle Button */
    .triple-dash-btn {
      display: none;
      flex-direction: column;
      justify-content: space-between;
      width: 42px;
      height: 42px;
      padding: 10px 9px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      backdrop-filter: blur(10px);
    }
    .triple-dash-btn:active, .triple-dash-btn:hover {
      background: rgba(99, 102, 241, 0.25);
      border-color: rgba(99, 102, 241, 0.5);
    }
    .triple-dash-btn .dash-line {
      display: block; width: 100%; height: 2.5px;
      background-color: #fff; border-radius: 4px;
      transition: all 0.3s ease; transform-origin: center;
    }
    /* Active triple dash morphing into X cross */
    .triple-dash-btn.active .dash-line:nth-child(1) {
      transform: translateY(7px) rotate(45deg);
      background-color: var(--accent);
    }
    .triple-dash-btn.active .dash-line:nth-child(2) {
      opacity: 0; transform: scaleX(0);
    }
    .triple-dash-btn.active .dash-line:nth-child(3) {
      transform: translateY(-7px) rotate(-45deg);
      background-color: var(--accent);
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
      padding: 0.5rem 0.95rem; border-radius: 10px; background: transparent; border: 1px solid transparent;
      color: var(--text-muted); font-weight: 600; cursor: pointer; min-height: 38px;
      white-space: nowrap; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
      font-size: 0.82rem; transition: all 0.2s ease;
    }
    .tab-btn:hover { color: #fff; background: rgba(255,255,255,0.06); }
    .tab-btn.active { background: linear-gradient(135deg, var(--primary), var(--accent)); color: #fff; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35); }

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

    /* Filter dropdown controls */
    .filter-select {
      padding: 0.6rem 1rem;
      border-radius: 12px;
      background: rgba(17, 24, 39, 0.7);
      border: 1px solid var(--border-color);
      color: var(--text-main);
      font-size: 0.85rem;
      font-weight: 500;
      outline: none;
      cursor: pointer;
      min-height: 40px;
    }
    .filter-select:focus {
      border-color: var(--primary);
    }

    /* Device Action Controls */
    .device-control-btn {
      padding: 0.4rem 0.7rem;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s ease;
    }
    .device-control-btn:active {
      transform: scale(0.96);
    }

    /* SIM Selection & Forward Pills */
    .sim-select-pill {
      padding: 0.5rem 1rem;
      border-radius: 10px;
      border: 1px solid var(--border-color);
      background: rgba(17, 24, 39, 0.6);
      color: var(--text-muted);
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .sim-select-pill.active {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #fff;
      border-color: transparent;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }

    /* Mobile Floating Action Button (FAB) */
    .mobile-fab {
      display: none;
      position: fixed;
      bottom: 76px;
      right: 20px;
      z-index: 200;
      width: 52px;
      height: 52px;
      border-radius: 16px;
      border: 1.5px solid rgba(255, 255, 255, 0.2);
      background: linear-gradient(135deg, var(--primary), var(--accent));
      align-items: center;
      justify-content: center;
      color: #fff;
      cursor: pointer;
      box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5);
      font-size: 24px;
      font-weight: 800;
    }

    /* Mobile Fixed Bottom Navigation Bar */
    .mobile-bottom-nav {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 150;
      background: rgba(9, 13, 22, 0.96);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-top: 1px solid var(--border-color);
      padding: 6px 12px 10px;
      justify-content: space-around;
      align-items: center;
    }
    .mobile-nav-item {
      background: transparent;
      border: none;
      color: var(--text-muted);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 2px;
      font-size: 0.72rem;
      font-weight: 600;
      cursor: pointer;
      padding: 4px 10px;
    }
    .mobile-nav-item.active {
      color: #fff;
    }

    /* Mobile Responsive Utilities (Phone Optimization) */
    @media (max-width: 768px) {
      .navbar-content { flex-direction: column; align-items: stretch; gap: 0.75rem; }
      .navbar-header-top { width: 100%; }
      .triple-dash-btn { display: flex; }
      .search-container { max-width: 100%; }
      .search-input { max-width: 100%; }
      .mobile-fab { display: flex; }
      .mobile-bottom-nav { display: flex; }
      
      .navbar-actions {
        display: none;
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        padding: 1.1rem;
        margin-top: 0.25rem;
        background: rgba(13, 18, 30, 0.96);
        border: 1px solid rgba(99, 102, 241, 0.35);
        border-radius: 16px;
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 12px 36px rgba(0, 0, 0, 0.75);
        gap: 0.85rem;
        animation: tripleDashMenuSlide 0.25s ease-out forwards;
      }
      .navbar-actions.mobile-expanded {
        display: flex !important;
      }
      .nav-user-group {
        flex-direction: column;
        width: 100%;
        gap: 0.85rem;
      }
      .nav-action-btn, .pulse-badge {
        width: 100%;
        justify-content: center;
        min-height: 46px;
        font-size: 0.9rem;
      }
    }

    @keyframes tripleDashMenuSlide {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>
