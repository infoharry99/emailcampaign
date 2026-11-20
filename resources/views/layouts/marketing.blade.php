<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI Marketing Manager') - TheNexteck</title>

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --app-bg: #f5f7fb;
            --sidebar-bg: #ffffff;
            --sidebar-border: #eaedf3;
            --topbar-bg: #ffffff;
            --topbar-border: #eaedf3;
            --card-bg: #ffffff;
            --card-border: #eaedf3;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --text-heading: #0f172a;
            --primary-blue: #0284c7;
            --primary-indigo: #4f46e5;
            --nav-active-bg: #eef2ff;
            --nav-active-text: #4f46e5;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--app-bg);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            font-size: 0.88rem;
        }

        /* ── Sidebar (Clean White Style) ── */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            z-index: 1020;
        }

        .sidebar-brand-wrapper {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-heading);
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: -0.2px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: linear-gradient(135deg, #0284c7 0%, #4f46e5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.05rem;
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.25);
        }

        .sidebar-search-box {
            padding: 14px 18px 8px;
        }

        .sidebar-search-input {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 7px 12px 7px 32px;
            font-size: 0.82rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.15s ease;
        }
        .sidebar-search-input:focus {
            background-color: #ffffff;
            border-color: #c7d2fe;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        .sidebar-menu-scroll {
            flex-grow: 1;
            overflow-y: auto;
            padding: 8px 12px;
            list-style: none;
            margin: 0;
        }

        .sidebar-category {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #94a3b8;
            padding: 12px 10px 4px;
        }

        .sidebar-item {
            margin-bottom: 2px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 8px 12px;
            color: #475569;
            border-radius: 7px;
            font-weight: 500;
            font-size: 0.84rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .sidebar-link i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
            color: #64748b;
        }

        .sidebar-link:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .sidebar-link.active {
            background-color: var(--nav-active-bg);
            color: var(--nav-active-text);
            font-weight: 600;
        }
        .sidebar-link.active i {
            color: var(--nav-active-text);
        }

        .sidebar-pill-badge {
            margin-left: auto;
            font-size: 0.68rem;
            padding: 2px 7px;
            border-radius: 6px;
            font-weight: 700;
            background: #0f172a;
            color: #ffffff;
        }

        .sidebar-user-footer {
            border-top: 1px solid var(--sidebar-border);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #ffffff;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Top Header Bar ── */
        .topbar {
            margin-left: 250px;
            height: 64px;
            background-color: var(--topbar-bg);
            border-bottom: 1px solid var(--topbar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 1010;
        }

        .breadcrumb-label {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .page-title-header {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.3px;
            margin: 0;
            line-height: 1.2;
        }

        .header-action-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-chip-btn {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .header-chip-btn:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .btn-header-purple {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.82rem;
            border-radius: 8px;
            padding: 7px 16px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-header-purple:hover {
            background-color: #4338ca;
            color: #ffffff;
        }

        .btn-header-dark {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.82rem;
            border-radius: 8px;
            padding: 7px 16px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-header-dark:hover {
            background-color: #1e293b;
            color: #ffffff;
        }

        /* ── Main Content Area ── */
        .main-wrapper {
            margin-left: 250px;
            padding: 24px 28px;
            min-height: calc(100vh - 64px);
        }

        /* Clean White Flat Cards */
        .flat-card {
            background-color: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        /* Table Styling */
        .clean-table-card {
            background-color: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .clean-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .clean-table thead th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px 18px;
            border-bottom: 1px solid #eaedf3;
            white-space: nowrap;
        }

        .clean-table tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
            font-size: 0.84rem;
        }

        .clean-table tbody tr:hover {
            background-color: #fbfcfe;
        }

        .clean-table tbody tr:last-child td {
            border-bottom: none;
        }

        .voucher-pill {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s;
        }
        .voucher-pill:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .pill-badge {
            display: inline-block;
            padding: 3px 9px;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 6px;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- ── 1. LEFT CLEAN WHITE SIDEBAR ── -->
    <aside class="sidebar">
        <!-- Brand Header -->
        <div class="sidebar-brand-wrapper">
            <a href="{{ route('admin.marketing.dashboard') }}" class="brand-logo">
                <div class="brand-icon">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <div>
                    <div style="line-height: 1.1;">TheNexteck</div>
                    <small style="font-size: 0.68rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Marketing AI</small>
                </div>
            </a>
            <span class="text-muted" style="cursor: pointer; font-size: 0.9rem;"><i class="fa-solid fa-bars-staggered"></i></span>
        </div>

        <!-- Sidebar Quick Filter/Search -->
        <div class="sidebar-search-box position-relative">
            <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 30px; top: 23px; font-size: 0.8rem;"></i>
            <input type="text" class="sidebar-search-input" placeholder="Search menu..." id="menuSearchInput" onkeyup="filterMenu()">
        </div>

        <!-- Sidebar Navigation Items -->
        <ul class="sidebar-menu-scroll" id="sidebarMenu">
            <li class="sidebar-category">OVERVIEW</li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-grid-2 me-1"></i> Dashboard
                </a>
            </li>

            <li class="sidebar-category">AUDIENCE & PERMISSIONS</li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.contacts.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.contacts.*') && !request()->routeIs('admin.marketing.contacts.import') ? 'active' : '' }}">
                    <i class="fa-solid fa-user-group"></i> Contacts Master
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.lists.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.lists.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check text-primary"></i> Contact Lists
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.contacts.import') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.contacts.import') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-import"></i> CSV Bulk Import
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.suppressions.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.suppressions.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-ban text-danger"></i> Suppression List
                </a>
            </li>

            <li class="sidebar-category">CAMPAIGNS & DISPATCH</li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.campaigns.*') && !request()->routeIs('admin.marketing.campaigns.create') ? 'active' : '' }}">
                    <i class="fa-solid fa-bullhorn"></i> Campaigns
                    <span class="sidebar-pill-badge">PRO</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.ai-campaigns.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.ai-campaigns.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-robot text-primary"></i> AI Personalized
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem; padding: 2px 6px;">AI</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.campaigns.create') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.campaigns.create') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-plus"></i> New Campaign
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.templates.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.templates.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-envelope-open-text"></i> Email Templates
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.ai-writer.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.ai-writer.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-warning"></i> AI Email Writer
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.logs.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i> Email Dispatch Logs
                </a>
            </li>

            <li class="sidebar-category">INFRASTRUCTURE</li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.settings.ses') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.settings.ses') ? 'active' : '' }}">
                    <i class="fa-brands fa-aws text-warning"></i> AWS SES Settings
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('admin.marketing.settings.openai') }}" class="sidebar-link {{ request()->routeIs('admin.marketing.settings.openai') ? 'active' : '' }}">
                    <i class="fa-solid fa-brain text-success"></i> OpenAI Settings
                </a>
            </li>
            <li class="sidebar-item">
                <a href="{{ route('artisan.migrate') }}" class="sidebar-link" target="_blank">
                    <i class="fa-solid fa-terminal text-info"></i> Database & Routes (Artisan)
                </a>
            </li>
        </ul>

        <!-- Bottom User Info -->
        <div class="sidebar-user-footer">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-circle">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                </div>
                <div>
                    <div class="fw-bold" style="font-size: 0.8rem; line-height: 1.1;">{{ auth()->user()->name ?? 'Admin' }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">Super Admin</small>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-link text-muted p-0" title="Logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ── 2. TOP MODERN NAVBAR ── -->
    <header class="topbar">
        <div>
            <div class="breadcrumb-label">Dashboard / @yield('breadcrumb', 'Marketing Platform')</div>
            <h1 class="page-title-header">@yield('page_title', 'Email Settlements & Campaigns')</h1>
        </div>

        <div class="header-action-group">
            <!-- Region Badge -->
            <div class="header-chip-btn">
                <i class="fa-brands fa-aws text-warning"></i>
                <span>ap-south-1 (Mumbai)</span>
            </div>

            <!-- Role Selector Pill -->
            <div class="header-chip-btn">
                <i class="fa-solid fa-shield-halved text-primary"></i>
                <span>Role: <strong>Admin</strong></span>
            </div>

            <!-- Header Action Buttons (Matching image style) -->
            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn-header-purple">
                <i class="fa-solid fa-layer-group"></i> Bulk Payout / Send
            </a>

            <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn-header-dark">
                <i class="fa-solid fa-plus"></i> New Campaign
            </a>
        </div>
    </header>

    <!-- ── 3. MAIN CONTENT CONTAINER ── -->
    <main class="main-wrapper">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-3" style="border-radius: 10px; background-color: #ecfdf5; color: #065f46;" role="alert">
                <i class="fa-solid fa-circle-check me-2 fs-5"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center border-0 shadow-sm mb-3" style="border-radius: 10px; background-color: #fef2f2; color: #991b1b;" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                <div class="fw-semibold">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filterMenu() {
        const input = document.getElementById('menuSearchInput').value.toLowerCase();
        const items = document.querySelectorAll('#sidebarMenu .sidebar-item');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(input) ? '' : 'none';
        });
    }
    </script>
    @stack('scripts')
</body>
</html>
