<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Konfigurasi Bot Telegram</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #080b11;
            --bg-sidebar: #0f131c;
            --bg-card: rgba(17, 22, 34, 0.65);
            --bg-card-hover: rgba(22, 29, 45, 0.8);
            --border: rgba(255, 255, 255, 0.06);
            --border-hover: rgba(99, 102, 241, 0.25);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.2);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.15);
            --danger: #f43f5e;
            --warning: #f59e0b;
            --tg-bg: #17212b;
            --tg-bubble: #182533;
            --tg-bubble-out: #2b5278;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
        }

        /* App Layout */
        .app-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            position: fixed;
            height: 100vh;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .brand-title {
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--text-primary);
            background-color: rgba(99, 102, 241, 0.08);
            border-color: rgba(99, 102, 241, 0.15);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(79, 70, 229, 0.05) 100%);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: inset 0 0 12px rgba(99, 102, 241, 0.05);
        }

        .nav-item svg {
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }

        .nav-item:hover svg {
            transform: scale(1.1);
        }

        .sidebar-footer {
            border-top: 1px solid var(--border);
            padding-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 14px;
        }

        .admin-details {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .admin-role {
            font-size: 11px;
            color: var(--text-muted);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            background: rgba(244, 63, 94, 0.1);
            color: var(--danger);
            border: 1px solid rgba(244, 63, 94, 0.2);
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--danger);
            color: white;
            box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2);
        }

        /* Main Content */
        .main-wrapper {
            margin-left: 280px;
            flex-grow: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 40px;
            width: calc(100% - 280px);
            transition: all 0.3s ease;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .header-title h2 {
            font-size: 26px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }

        .header-title p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .header-status {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            padding: 10px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--success);
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Form Container */
        form {
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
        }

        /* Tab Content Panel */
        .tab-panel {
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }

        .tab-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Card styles */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: rgba(99, 102, 241, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 16px;
        }

        .card-header-icon {
            width: 36px;
            height: 36px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .card-header-title h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-header-title p {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Form elements */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-grid.single-column {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .label-desc {
            font-size: 12px;
            font-weight: 400;
            color: var(--text-muted);
            margin-left: auto;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        input[type="text"], input[type="number"], textarea {
            width: 100%;
            background-color: rgba(15, 19, 28, 0.8);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
            background-color: rgba(15, 19, 28, 1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            line-height: 1.5;
        }

        .input-icon-left {
            padding-left: 44px !important;
        }

        .input-icon-right {
            padding-right: 44px !important;
        }

        .input-icon {
            position: absolute;
            color: var(--text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 100%;
        }

        .input-icon.left {
            left: 0;
        }

        .input-icon.right {
            right: 0;
            pointer-events: auto;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .input-icon.right:hover {
            color: var(--text-primary);
        }

        /* Toggle switch */
        .toggle-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: rgba(15, 19, 28, 0.4);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .toggle-container:hover {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .toggle-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .toggle-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .toggle-sub {
            font-size: 12px;
            color: var(--text-muted);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.15);
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 34px;
            border: 1px solid transparent;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: #cbd5e1;
            transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(22px);
            background-color: white;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }

        /* Image Preview helper */
        .img-preview-container {
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(15, 19, 28, 0.3);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
            width: 100%;
        }

        .img-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border);
            background: var(--bg-sidebar);
            flex-shrink: 0;
        }

        /* Interactive Caption Templates */
        .caption-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            transition: all 0.3s ease;
        }

        .caption-card:hover {
            border-color: var(--border-hover);
        }

        .caption-editor {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .placeholder-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 4px;
        }

        .placeholder-tag {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Fira Code', monospace;
        }

        .placeholder-tag:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--primary-glow);
        }

        .placeholder-tag:active {
            transform: translateY(0);
        }

        /* Telegram Mockup Bubble */
        .tg-mockup {
            background-color: #0e1621;
            background-image: radial-gradient(rgba(120, 119, 198, 0.08) 1px, transparent 0);
            background-size: 20px 20px;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
            max-height: 400px;
            overflow-y: auto;
            width: 100%;
        }

        .tg-header {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tg-bubble-wrap {
            width: 100%;
            align-self: flex-start;
            position: relative;
        }

        .tg-image-preview {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: block;
        }

        .tg-bubble {
            background-color: var(--tg-bubble);
            color: #f5f5f5;
            border-radius: 0 10px 10px 10px;
            padding: 10px 12px 14px;
            font-size: 13.5px;
            line-height: 1.5;
            word-break: break-word;
            white-space: pre-wrap;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .tg-bubble-wrap.with-image .tg-bubble {
            border-radius: 0 0 10px 10px;
        }

        .tg-bubble b, .tg-bubble strong {
            font-weight: 700;
            color: #fff;
        }

        .tg-bubble i, .tg-bubble em {
            font-style: italic;
            color: #e2e8f0;
        }

        .tg-bubble code {
            font-family: 'Fira Code', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 12px;
            color: #fca5a5;
        }

        .tg-bubble a {
            color: #5288c1;
            text-decoration: none;
        }

        .tg-bubble a:hover {
            text-decoration: underline;
        }

        .tg-time {
            position: absolute;
            right: 8px;
            bottom: 3px;
            font-size: 9px;
            color: var(--text-muted);
        }

        /* Action bar */
        .action-bar {
            position: sticky;
            bottom: 0;
            background: rgba(8, 11, 17, 0.85);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border);
            padding: 20px 40px;
            margin: 20px -40px -40px;
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            z-index: 5;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: white;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5);
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Error element */
        .error-feedback {
            color: var(--danger);
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }

        /* Toast system */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 100;
        }

        .toast {
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            max-width: 400px;
            transform: translateX(120%);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 4px solid var(--success);
        }

        .toast.error {
            border-left: 4px solid var(--danger);
        }

        .toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast.success .toast-icon {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .toast.error .toast-icon {
            background: rgba(244, 63, 94, 0.15);
            color: var(--danger);
        }

        .toast-content {
            flex-grow: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .toast-message {
            font-size: 12.5px;
            color: var(--text-secondary);
        }

        .toast-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .toast-close:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: var(--success);
            width: 100%;
        }

        .toast.error .toast-progress {
            background: var(--danger);
        }

        /* Mobile Sidebar overlay */
        .sidebar-toggle {
            display: none;
            background: var(--bg-sidebar);
            border: 1px solid var(--border);
            padding: 10px;
            border-radius: 10px;
            color: var(--text-primary);
            cursor: pointer;
            z-index: 15;
            position: fixed;
            top: 15px;
            left: 15px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .caption-card {
                grid-template-columns: 1fr;
            }
            .tg-mockup {
                max-height: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-toggle {
                display: block;
            }
            .main-wrapper {
                margin-left: 0;
                width: 100%;
                padding: 80px 20px 40px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .action-bar {
                margin: 20px -20px -40px;
                padding: 16px 20px;
            }
        }

        /* Spinner for saving */
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"></path>
        </svg>
    </button>

    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="brand-section">
                <img src="{{ $config->bot['image'] ?? 'https://dgstoreid.xyz/assets/images/logo/1757737759.png' }}" class="brand-logo" id="sidebar-logo-preview" alt="Bot Logo">
                <span class="brand-title">Bot Config</span>
            </div>

            <nav class="nav-menu">
                <div class="nav-item active" onclick="switchTab('bot-pay', this)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"></path>
                    </svg>
                    <span>🤖 Bot & Gateway</span>
                </div>
                <div class="nav-item" onclick="switchTab('order-sys', this)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"></path>
                    </svg>
                    <span>⚙️ Transaksi & Order</span>
                </div>
                <div class="nav-item" onclick="switchTab('captions-order', this)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"></path>
                    </svg>
                    <span>💬 Template Order</span>
                </div>
                <div class="nav-item" onclick="switchTab('captions-other', this)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"></path>
                    </svg>
                    <span>💬 Template Lainnya</span>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar">
                        {{ substr(session('admin_name', 'AD'), 0, 2) }}
                    </div>
                    <div class="admin-details">
                        <span class="admin-name">{{ session('admin_name', 'Administrator') }}</span>
                        <span class="admin-role">Owner Panel</span>
                    </div>
                </div>
                <a href="{{ route('admin.config.logout') }}" class="btn-logout">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"></path>
                    </svg>
                    <span>Keluar</span>
                </a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-wrapper">
            <header>
                <div class="header-title">
                    <h2 id="header-page-title">🤖 Bot & Gateway</h2>
                    <p>Sesuaikan setelan dasar bot Telegram, parameter keamanan transaksi, dan payment gateway.</p>
                </div>
                <div class="header-status">
                    <span class="status-dot"></span>
                    <span>Bot Status: Online</span>
                </div>
            </header>

            <form id="configForm" onsubmit="saveConfig(event)">
                <!-- TAB 1: BOT & GATEWAY -->
                <div class="tab-panel active" id="panel-bot-pay">
                    <!-- BOT SETTINGS -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm0 0H8.25m4.125 0a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm0 0H12m4.125 0a.625.625 0 11-1.25 0 .625.625 0 011.25 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"></path>
                                </svg>
                            </div>
                            <div class="card-header-title">
                                <h3>Profil & Identitas Bot</h3>
                                <p>Tentukan gambar utama bot dan link bantuan support.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="bot-image">
                                    <span>Link URL Logo Bot</span>
                                    <span class="label-desc">URL gambar JPG/PNG yang valid</span>
                                </label>
                                <div class="img-preview-container">
                                    <img src="{{ $config->bot['image'] ?? 'https://dgstoreid.xyz/assets/images/logo/1757737759.png' }}" class="img-preview" id="bot-logo-preview" alt="Preview logo">
                                    <input type="text" id="bot-image" name="bot[image]" value="{{ $config->bot['image'] ?? '' }}" oninput="updateLogoPreview(this.value)">
                                </div>
                                <span class="error-feedback" id="err-bot-image"></span>
                            </div>

                            <div class="form-group full-width">
                                <label for="bot-contact">
                                    <span>Link Kontak Support Telegram</span>
                                    <span class="label-desc">Link Telegram admin (cth: https://t.me/akiracode)</span>
                                </label>
                                <div class="input-wrapper">
                                    <input type="text" id="bot-contact" name="bot[contact]" value="{{ $config->bot['contact'] ?? '' }}">
                                </div>
                                <span class="error-feedback" id="err-bot-contact"></span>
                            </div>
                        </div>
                    </div>

                    <!-- WIJAYAPAY SETTINGS -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"></path>
                                </svg>
                            </div>
                            <div class="card-header-title">
                                <h3>Integrasi Gateway WijayaPay</h3>
                                <p>Setelan otentikasi payment gateway otomatis untuk menerima transaksi deposit / beli.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group full-width">
                                <div class="toggle-container">
                                    <div class="toggle-info">
                                        <span class="toggle-label">Status Gateway WijayaPay</span>
                                        <span class="toggle-sub">Aktifkan atau nonaktifkan pembayaran otomatis WijayaPay.</span>
                                    </div>
                                    <label class="switch">
                                        <input type="hidden" name="payments[wijayapay][status]" value="0">
                                        <input type="checkbox" id="wijayapay-status" name="payments[wijayapay][status]" value="1" {{ ($config->payments['wijayapay']['status'] ?? false) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="wijayapay-merchant">
                                    <span>Code Merchant</span>
                                    <span class="label-desc">Dapatkan dari dashboard WijayaPay</span>
                                </label>
                                <input type="text" id="wijayapay-merchant" name="payments[wijayapay][code_merchant]" value="{{ $config->payments['wijayapay']['code_merchant'] ?? '' }}">
                                <span class="error-feedback" id="err-payments-wijayapay-code_merchant"></span>
                            </div>

                            <div class="form-group">
                                <label for="wijayapay-key">
                                    <span>API Key Merchant</span>
                                    <span class="label-desc">Sangat rahasia</span>
                                </label>
                                <div class="input-wrapper">
                                    <input type="password" id="wijayapay-key" name="payments[wijayapay][api_key]" value="{{ $config->payments['wijayapay']['api_key'] ?? '' }}" style="width: 100%; background-color: rgba(15, 19, 28, 0.8); border: 1px solid var(--border); border-radius: 12px; padding: 12px 16px; color: var(--text-primary); font-size: 15px; padding-right: 44px;">
                                    <span class="input-icon right" onclick="togglePasswordVisibility('wijayapay-key', this)">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </span>
                                </div>
                                <span class="error-feedback" id="err-payments-wijayapay-api_key"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: TRANSAKSI & ORDER -->
                <div class="tab-panel" id="panel-order-sys">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="card-header-title">
                                <h3>Sistem Order & Keamanan</h3>
                                <p>Setelan generator invoice, limit transaksi pending, jeda anti-spam delay.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="order-prefix">
                                    <span>Prefix Invoice Order</span>
                                    <span class="label-desc">Awalan kode transaksi (cth: KM-)</span>
                                </label>
                                <input type="text" id="order-prefix" name="order[prefix_order]" value="{{ $config->order['prefix_order'] ?? '' }}" oninput="updateInvoicePreview()">
                                <span class="error-feedback" id="err-order-prefix_order"></span>
                            </div>

                            <div class="form-group">
                                <label for="order-length">
                                    <span>Panjang Kode Acak Invoice</span>
                                    <span class="label-desc">Rekomendasi: 8 s/d 16 karakter</span>
                                </label>
                                <input type="number" id="order-length" name="order[length_random_order]" value="{{ $config->order['length_random_order'] ?? '' }}" min="4" max="30" oninput="updateInvoicePreview()">
                                <span class="error-feedback" id="err-order-length_random_order"></span>
                            </div>

                            <div class="form-group full-width">
                                <label>
                                    <span>Karakter Pengacak Invoice</span>
                                    <span class="label-desc">Karakter yang akan diacak untuk membuat kode invoice</span>
                                </label>
                                <input type="text" id="order-string" name="order[string]" value="{{ $config->order['string'] ?? '' }}" oninput="updateInvoicePreview()">
                                <div style="margin-top: 6px; font-size: 13px; color: var(--text-muted); display: flex; gap: 4px;">
                                    <span>Preview Invoice ID Acak:</span>
                                    <strong id="invoice-id-preview" style="color: var(--primary); font-family: 'Fira Code', monospace;">KM-XXXXXX</strong>
                                </div>
                                <span class="error-feedback" id="err-order-string"></span>
                            </div>

                            <div class="form-group">
                                <label for="order-exp">
                                    <span>Masa Berlaku Invoice (Menit)</span>
                                    <span class="label-desc">Waktu tenggat pembayaran sebelum expired</span>
                                </label>
                                <input type="number" id="order-exp" name="order[exp_order]" value="{{ $config->order['exp_order'] ?? '' }}" min="1">
                                <span class="error-feedback" id="err-order-exp_order"></span>
                            </div>

                            <div class="form-group">
                                <label for="order-pending">
                                    <span>Batas Pending Maksimal / User</span>
                                    <span class="label-desc">Mencegah user membuat terlalu banyak invoice palsu</span>
                                </label>
                                <input type="number" id="order-pending" name="order[count_pending]" value="{{ $config->order['count_pending'] ?? '' }}" min="1">
                                <span class="error-feedback" id="err-order-count_pending"></span>
                            </div>

                            <div class="form-group full-width">
                                <label for="order-delay">
                                    <span>Jeda Anti-Spam Transaksi (Detik)</span>
                                    <span class="label-desc">Minimal jeda waktu antar pembuatan pesanan demi melindungi server</span>
                                </label>
                                <input type="number" id="order-delay" name="order[transaksi_delay]" value="{{ $config->order['transaksi_delay'] ?? '' }}" min="0">
                                <span class="error-feedback" id="err-order-transaksi_delay"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: CAPTIONS ORDER -->
                <div class="tab-panel" id="panel-captions-order">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"></path>
                                </svg>
                            </div>
                            <div class="card-header-title">
                                <h3>Template Pesan (Sistem Order)</h3>
                                <p>Sesuaikan isi pesan teks yang dikirimkan bot selama alur pembelian (HTML didukung).</p>
                            </div>
                        </div>

                        <!-- CAPTIONS ACCORDION / LIST -->
                        <div id="captions-order-list">
                            @foreach(($config->captions['orders'] ?? []) as $index => $cap)
                                @php
                                    $friendly = match($cap['key']) {
                                        'menu_start' => ['name' => 'Menu Start (Awal)', 'desc' => 'Muncul ketika mengetik /start atau kembali ke menu awal.', 'placeholders' => ['{greeting}', '{firstname}']],
                                        'menu_order' => ['name' => 'Menu Daftar Game', 'desc' => 'Muncul ketika user menekan Mulai Pesan.', 'placeholders' => []],
                                        'menu_providers' => ['name' => 'Pilih Provider Game', 'desc' => 'Muncul setelah memilih game tertentu.', 'placeholders' => ['{game}']],
                                        'menu_denoms' => ['name' => 'Pilih Durasi / Denom', 'desc' => 'Muncul setelah memilih produk provider game.', 'placeholders' => ['{produk}']],
                                        'menu_confirm_order' => ['name' => 'Konfirmasi Beli', 'desc' => 'Konfirmasi detail pesanan dan meminta metode bayar.', 'placeholders' => ['{produk}', '{denom}', '{price}']],
                                        'invoice_order' => ['name' => 'Invoice Tagihan', 'desc' => 'Format struk / rincian pembayaran untuk user.', 'placeholders' => ['{invoice_id}', '{status_pembayaran}', '{status_proses}', '{expired_at}', '{game}', '{provider}', '{denom}', '{price}', '{payment}', '{name_account}', '{number_account}', '{virtual_account}', '{nomor_pembayaran}', '{instruksi}']],
                                        'cancel_order' => ['name' => 'Berhasil Batal', 'desc' => 'Muncul setelah pesanan berhasil dibatalkan.', 'placeholders' => ['{invoice_id}']],
                                        'confirm_cancel_order' => ['name' => 'Konfirmasi Batal', 'desc' => 'Konfirmasi sebelum memproses pembatalan pesanan.', 'placeholders' => ['{invoice_id}', '{game}', '{denom}', '{price}']],
                                        default => ['name' => $cap['key'], 'desc' => 'Template system.', 'placeholders' => []]
                                    };
                                @endphp
                                <div class="caption-card">
                                    <div class="caption-editor">
                                        <label for="cap-order-{{ $index }}">
                                            <span>{{ $friendly['name'] }}</span>
                                            <span class="label-desc">{{ $friendly['desc'] }}</span>
                                        </label>
                                        <input type="hidden" name="captions[orders][{{ $index }}][key]" value="{{ $cap['key'] }}">

                                        <!-- Toolbar tags -->
                                        @if(!empty($friendly['placeholders']))
                                            <div class="placeholder-toolbar">
                                                @foreach($friendly['placeholders'] as $tag)
                                                    <span class="placeholder-tag" onclick="insertAtCursor('cap-order-{{ $index }}', '{{ $tag }}')">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <textarea id="cap-order-{{ $index }}" name="captions[orders][{{ $index }}][content]" oninput="updateMockup('cap-order-{{ $index }}', 'mock-order-{{ $index }}')">{{ $cap['content'] ?? '' }}</textarea>
                                    </div>
                                    <div class="tg-mockup-wrapper">
                                        <div class="tg-header">
                                            <span>Visualisasi Telegram</span>
                                        </div>
                                        <div class="tg-mockup">
                                            <div class="tg-bubble-wrap {{ in_array($cap['key'], ['menu_start', 'cancel_order']) ? 'with-image' : '' }}">
                                                @if(in_array($cap['key'], ['menu_start', 'cancel_order']))
                                                    <img src="{{ $config->bot['image'] ?? 'https://dgstoreid.xyz/assets/images/logo/1757737759.png' }}" class="tg-image-preview img-mockup-source" alt="Mock Photo">
                                                @endif
                                                <div class="tg-bubble" id="mock-order-{{ $index }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- TAB 4: CAPTIONS OTHER -->
                <div class="tab-panel" id="panel-captions-other">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-header-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 8.25v7.5M10.5 12h4.5M3 12c0-4.97 4.03-9 9-9s9 4.03 9 9-4.03 9-9 9-9-4.03-9-9z"></path>
                                </svg>
                            </div>
                            <div class="card-header-title">
                                <h3>Template Pesan Lainnya</h3>
                                <p>Kustomisasi teks untuk menu profil akun, leaderboard, reset lisensi, dan riwayat belanja.</p>
                            </div>
                        </div>

                        <div id="captions-other-list">
                            @foreach(($config->captions['others_button'] ?? []) as $index => $cap)
                                @php
                                    $friendly = match($cap['key']) {
                                        'menu_history' => ['name' => 'Riwayat Transaksi', 'desc' => 'Daftar transaksi yang pernah dibeli oleh user.', 'placeholders' => ['{page}', '{total_pages}', '{list_transactions}']],
                                        'menu_history_empty' => ['name' => 'Riwayat Kosong', 'desc' => 'Muncul jika user belum memiliki riwayat sama sekali.', 'placeholders' => []],
                                        'menu_account' => ['name' => 'Informasi Akun', 'desc' => 'Menampilkan profil, user id, dan role user.', 'placeholders' => ['{user_id}', '{name}', '{username}', '{role}', '{registered_at}']],
                                        'menu_leaderboard_weekly' => ['name' => 'Leaderboard Mingguan', 'desc' => 'Peringkat top buyer 7 hari terakhir.', 'placeholders' => ['{list_rank}']],
                                        'menu_leaderboard_monthly' => ['name' => 'Leaderboard Bulanan', 'desc' => 'Peringkat top buyer 30 hari terakhir.', 'placeholders' => ['{list_rank}']],
                                        'menu_announcement' => ['name' => 'Info Pengumuman', 'desc' => 'Teks info/promosi yang bisa disiarkan.', 'placeholders' => []],
                                        'menu_resetlicense' => ['name' => 'Menu Reset Lisensi', 'desc' => 'Halaman awal pemilihan produk lisensi.', 'placeholders' => []],
                                        'menu_select_resetlicense' => ['name' => 'Form Reset Lisensi', 'desc' => 'Instruksi user menginput kunci lisensi untuk direset.', 'placeholders' => ['{provider}']],
                                        default => ['name' => $cap['key'], 'desc' => 'Template system.', 'placeholders' => []]
                                    };
                                @endphp
                                <div class="caption-card">
                                    <div class="caption-editor">
                                        <label for="cap-other-{{ $index }}">
                                            <span>{{ $friendly['name'] }}</span>
                                            <span class="label-desc">{{ $friendly['desc'] }}</span>
                                        </label>
                                        <input type="hidden" name="captions[others_button][{{ $index }}][key]" value="{{ $cap['key'] }}">

                                        <!-- Toolbar tags -->
                                        @if(!empty($friendly['placeholders']))
                                            <div class="placeholder-toolbar">
                                                @foreach($friendly['placeholders'] as $tag)
                                                    <span class="placeholder-tag" onclick="insertAtCursor('cap-other-{{ $index }}', '{{ $tag }}')">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <textarea id="cap-other-{{ $index }}" name="captions[others_button][{{ $index }}][content]" oninput="updateMockup('cap-other-{{ $index }}', 'mock-other-{{ $index }}')">{{ $cap['content'] ?? '' }}</textarea>
                                    </div>
                                    <div class="tg-mockup-wrapper">
                                        <div class="tg-header">
                                            <span>Visualisasi Telegram</span>
                                        </div>
                                        <div class="tg-mockup">
                                            <div class="tg-bubble-wrap">
                                                <div class="tg-bubble" id="mock-other-{{ $index }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- SAVE BUTTON BAR -->
                <div class="action-bar">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <span class="spinner" id="submitSpinner"></span>
                        <span id="submitText">💾 Simpan Konfigurasi</span>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <!-- Floating toast notifications container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Sidebar active states
        function switchTab(tabId, el) {
            // Update sidebar navigation active style
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            el.classList.add('active');

            // Show active panel
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            const targetPanel = document.getElementById('panel-' + tabId);
            targetPanel.classList.add('active');

            // Update page title
            const tabTitle = el.querySelector('span').innerText;
            document.getElementById('header-page-title').innerText = tabTitle;

            // Close mobile sidebar if open
            if(window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
            }

            // Sync all mockup values
            initAllMockups();
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Close sidebar if clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Hide/Show keys
        function togglePasswordVisibility(fieldId, iconEl) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                iconEl.innerHTML = `
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"></path>
                    </svg>
                `;
            } else {
                field.type = 'password';
                iconEl.innerHTML = `
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                `;
            }
        }

        // Live Image Preview Update
        function updateLogoPreview(val) {
            const logo = document.getElementById('bot-logo-preview');
            const sidebarLogo = document.getElementById('sidebar-logo-preview');
            const mockupSources = document.querySelectorAll('.img-mockup-source');

            if (val && val.trim() !== '') {
                logo.src = val;
                sidebarLogo.src = val;
                mockupSources.forEach(img => img.src = val);
            }
        }

        // Live Invoice ID Generator Preview
        function updateInvoicePreview() {
            const prefix = document.getElementById('order-prefix').value || '';
            const length = parseInt(document.getElementById('order-length').value) || 8;
            const strSource = document.getElementById('order-string').value || 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            let randomPart = '';
            for(let i = 0; i < length; i++) {
                randomPart += strSource.charAt(Math.floor(Math.random() * strSource.length));
            }
            document.getElementById('invoice-id-preview').innerText = prefix + randomPart;
        }

        // Insert placeholder tag at cursor location in textarea
        function insertAtCursor(textareaId, tagValue) {
            const txtarea = document.getElementById(textareaId);
            const scrollPos = txtarea.scrollTop;
            let strPos = 0;
            const br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ? "ff" : (document.selection ? "ie" : false));

            if (br == "ie") {
                txtarea.focus();
                var range = document.selection.createRange();
                range.moveStart ('character', -txtarea.value.length);
                strPos = range.text.length;
            } else if (br == "ff") {
                strPos = txtarea.selectionStart;
            }

            var front = (txtarea.value).substring(0, strPos);
            var back = (txtarea.value).substring(strPos, txtarea.value.length);
            txtarea.value=front+tagValue+back;
            strPos = strPos + tagValue.length;
            if (br == "ie") {
                txtarea.focus();
                var ieRange = document.selection.createRange();
                ieRange.moveStart ('character', -txtarea.value.length);
                ieRange.moveStart ('character', strPos);
                ieRange.moveEnd ('character', 0);
                ieRange.select();
            } else if (br == "ff") {
                txtarea.selectionStart = strPos;
                txtarea.selectionEnd = strPos;
                txtarea.focus();
            }
            txtarea.scrollTop = scrollPos;

            // Trigger mockup update
            const mockId = textareaId.replace('cap-order-', 'mock-order-').replace('cap-other-', 'mock-other-');
            updateMockup(textareaId, mockId);
        }

        // Convert Telegram HTML tags into real HTML for web mockup rendering
        function formatTelegramHtml(text) {
            if(!text) return '<i>(Pesan kosong)</i>';

            // HTML escape first
            let escaped = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");

            // Un-escape allowed telegram HTML tags
            escaped = escaped
                .replace(/&lt;b&gt;/g, "<b>")
                .replace(/&lt;\/b&gt;/g, "</b>")
                .replace(/&lt;strong&gt;/g, "<strong>")
                .replace(/&lt;\/strong&gt;/g, "</strong>")
                .replace(/&lt;i&gt;/g, "<i>")
                .replace(/&lt;\/i&gt;/g, "</i>")
                .replace(/&lt;em&gt;/g, "<em>")
                .replace(/&lt;\/em&gt;/g, "</em>")
                .replace(/&lt;code&gt;/g, "<code>")
                .replace(/&lt;\/code&gt;/g, "</code>")
                .replace(/&lt;pre&gt;/g, "<pre>")
                .replace(/&lt;\/pre&gt;/g, "</pre>");

            // Replace mock values to make preview look realistic
            const sampleData = {
                '{greeting}': 'Selamat Pagi',
                '{firstname}': 'Yudha',
                '{game}': 'Mobile Legends',
                '{produk}': 'MLBB Weekly Diamond Pass',
                '{denom}': 'Weekly Pass x1',
                '{price}': 'Rp 27.500',
                '{invoice_id}': 'KM-6279BF78X9A2',
                '{status_pembayaran}': 'Pending',
                '{status_proses}': 'Pending',
                '{expired_at}': '31 May 2026, 17:35 GMT+7',
                '{payment}': 'WijayaPay Qris',
                '{name_account}': 'DG STORE ID',
                '{number_account}': '089537612711',
                '{virtual_account}': '8267199182772',
                '{nomor_pembayaran}': '928817299',
                '{instruksi}': '1. Buka aplikasi E-wallet Anda\n2. Scan QRIS yang diberikan\n3. Lakukan pembayaran sebesar nominal yang tertera.',
                '{page}': '1',
                '{total_pages}': '3',
                '{list_transactions}': '<b>1. Invoice:</b> <code>KM-ML1928</code>\n   • MLBB (Weekly Pass)\n   • Total: Rp 27.500 (Paid)\n\n<b>2. Invoice:</b> <code>KM-FF9827</code>\n   • Free Fire (140 Diamonds)\n   • Total: Rp 20.000 (Expired)',
                '{user_id}': '521998277',
                '{name}': 'Yudha Pratama',
                '{username}': '@yudhapra',
                '{role}': 'User',
                '{registered_at}': '28 May 2026, 15:18 GMT+7',
                '{list_rank}': '🥇 <b>Yudha Pratama</b>\n   • Total Belanja: Rp 250.000 (10 Transaksi)\n\n🥈 <b>Akira Code</b>\n   • Total Belanja: Rp 120.000 (4 Transaksi)',
                '{provider}': 'Mobile Legends Resetter'
            };

            for (const [key, val] of Object.entries(sampleData)) {
                escaped = escaped.replaceAll(key, val);
            }

            // Convert newlines to breaks
            escaped = escaped.replace(/\n/g, '<br>');

            return escaped;
        }

        function updateMockup(textareaId, mockupId) {
            const textarea = document.getElementById(textareaId);
            const mockup = document.getElementById(mockupId);
            if(textarea && mockup) {
                mockup.innerHTML = formatTelegramHtml(textarea.value);
            }
        }

        // Initialize all mockups
        function initAllMockups() {
            // Orders
            document.querySelectorAll("[id^='cap-order-']").forEach(txtarea => {
                const idNum = txtarea.id.replace('cap-order-', '');
                updateMockup(txtarea.id, 'mock-order-' + idNum);
            });
            // Others
            document.querySelectorAll("[id^='cap-other-']").forEach(txtarea => {
                const idNum = txtarea.id.replace('cap-other-', '');
                updateMockup(txtarea.id, 'mock-other-' + idNum);
            });
        }

        // Toast Helper
        function showToast(title, message, type = 'success') {
            const container = document.getElementById('toastContainer');

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            let iconSvg = '';
            if (type === 'success') {
                iconSvg = `
                    <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                    </svg>
                `;
            } else {
                iconSvg = `
                    <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path>
                    </svg>
                `;
            }

            toast.innerHTML = `
                <div class="toast-icon">${iconSvg}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">×</button>
                <div class="toast-progress"></div>
            `;

            container.appendChild(toast);

            // Animation slide in
            setTimeout(() => {
                toast.classList.add('show');
            }, 50);

            // Progress bar animation
            const progress = toast.querySelector('.toast-progress');
            let width = 100;
            const interval = setInterval(() => {
                width -= 1;
                progress.style.width = width + '%';
                if (width <= 0) {
                    clearInterval(interval);
                }
            }, 40);

            // Auto remove
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 400);
            }, 4000);
        }

        // Form Submit
        function saveConfig(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const submitSpinner = document.getElementById('submitSpinner');
            const submitText = document.getElementById('submitText');

            // Reset errors
            document.querySelectorAll('.error-feedback').forEach(err => {
                err.style.display = 'none';
                err.innerText = '';
            });

            // Set loading state
            submitBtn.disabled = true;
            submitSpinner.style.display = 'inline-block';
            submitText.innerText = ' Menyimpan...';

            const formData = new FormData(document.getElementById('configForm'));

            // Format data object for json submission
            const object = {};
            formData.forEach((value, key) => {
                // Parse nested input keys: order[prefix_order] -> object.order.prefix_order
                const parts = key.split('[').map(k => k.replace(']', ''));
                let current = object;
                for (let i = 0; i < parts.length; i++) {
                    const part = parts[i];
                    if (i === parts.length - 1) {
                        // Cast status checkbox
                        if (part === 'status') {
                            current[part] = value === '1' ? true : false;
                        } else {
                            current[part] = value;
                        }
                    } else {
                        if (!current[part]) {
                            // Detect if next key is numerical array index
                            current[part] = isNaN(parts[i+1]) ? {} : [];
                        }
                        current = current[part];
                    }
                }
            });

            // Make payments status boolean
            if (object.payments && object.payments.wijayapay) {
                // Since hidden input is 0 and checkbox is 1, let's look at what is checked in DOM
                object.payments.wijayapay.status = document.getElementById('wijayapay-status').checked;
            }

            // Fix arrays format for captions (they are maps currently)
            if (object.captions) {
                if (object.captions.orders) {
                    object.captions.orders = Object.values(object.captions.orders);
                }
                if (object.captions.others_button) {
                    object.captions.others_button = Object.values(object.captions.others_button);
                }
            }

            fetch("{{ route('admin.config.update') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(object)
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(res => {
                submitBtn.disabled = false;
                submitSpinner.style.display = 'none';
                submitText.innerText = '💾 Simpan Konfigurasi';

                if (res.status === 200 && res.body.success) {
                    showToast('Sukses!', res.body.message, 'success');
                } else if (res.status === 422) {
                    showToast('Peringatan!', res.body.message, 'error');

                    // Render validation errors
                    const errors = res.body.errors;
                    for (const [key, msg] of Object.entries(errors)) {
                        // Match key name (e.g. order.string -> order[string])
                        let selectorKey = key.replace(/\./g, '\\.');
                        // Sometimes the dot maps to nested brackets, let's find input by name attribute
                        let nameAttr = key;
                        const parts = key.split('.');
                        if(parts.length > 1) {
                            nameAttr = parts[0] + parts.slice(1).map(p => '[' + p + ']').join('');
                        }

                        // Find element
                        const inputEl = document.querySelector(`[name="${nameAttr}"]`);
                        if (inputEl) {
                            // Find closest error feedback
                            const parent = inputEl.closest('.form-group');
                            if (parent) {
                                const feedback = parent.querySelector('.error-feedback');
                                if (feedback) {
                                    feedback.innerText = msg[0];
                                    feedback.style.display = 'block';
                                }
                            }
                        }
                    }
                } else {
                    showToast('Gagal!', res.body.message || 'Terjadi kesalahan sistem.', 'error');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitSpinner.style.display = 'none';
                submitText.innerText = '💾 Simpan Konfigurasi';
                showToast('Koneksi Gagal!', 'Tidak dapat menghubungi server. Silakan coba lagi.', 'error');
            });
        }

        // On document ready
        document.addEventListener("DOMContentLoaded", function() {
            updateInvoicePreview();
            initAllMockups();
        });
    </script>
</body>
</html>
