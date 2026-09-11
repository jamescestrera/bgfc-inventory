<?php
// This repository now runs as the Node/Express application on PORT.
// Keep legacy Apache bookmarks from opening the retired PHP prototype.
$nodePort = getenv('PORT') ?: '3000';
$host = $_SERVER['SERVER_NAME'] ?? 'localhost';
header('Location: http://' . $host . ':' . $nodePort . '/login', true, 302);
exit;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#082d68">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="BGFC IMS">
    <title>BGFC Inventory Management System</title>
    <link rel="icon" type="image/png" href="assets/images/bgfc-logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/bgfc-logo.png">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="apple-touch-icon" href="assets/images/bgfc-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/assistant.css">
    <link rel="stylesheet" href="assets/css/transitions.css">
    <link rel="stylesheet" href="assets/css/reports.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>

<body data-initial-view="<?= htmlspecialchars($initialView, ENT_QUOTES, 'UTF-8') ?>">
    <div id="splash" class="splash">
        <div class="splash-mark"><img src="assets/images/bgfc-logo.png" alt="BGFC"><i></i></div>
        <h1>BGFC Inventory</h1>
        <p>Management System</p><span class="splash-line"></span>
    </div>
    <section id="login" class="login-page hidden">
        <div class="login-art">
            <div><span class="eyebrow">Smart inventory. Better control.</span>
                <h1>Everything your inventory needs, in one place.</h1>
                <p>Track supplies, monitor stock movement, and make faster decisions across every device.</p>
            </div>
        </div>
        <form id="loginForm" class="login-card"><img src="assets/images/bgfc-logo.png" alt="BGFC logo">
            <h2>Welcome back</h2>
            <p>Sign in to BGFC Inventory Management System</p><label>Username<div class="input-icon"><i data-lucide="user"></i><input id="username" value="admin" autocomplete="username" required></div></label><label>Password<div class="input-icon"><i data-lucide="lock-keyhole"></i><input id="password" type="password" value="admin123" autocomplete="current-password" required><button type="button" id="showPassword" class="icon-btn"><i data-lucide="eye"></i></button></div></label>
            <div class="form-row"><label class="check"><input type="checkbox" checked> Remember me</label><a href="#">Forgot password?</a></div><button class="btn primary wide" type="submit">Sign in <i data-lucide="arrow-right"></i></button><small>Demo account: admin / admin123</small>
        </form>
    </section>
    <div id="app" class="app hidden">
        <aside class="sidebar"><a class="brand" href="dashboard"><img src="assets/images/bgfc-logo.png" alt=""><span><b>BGFC</b><small>Inventory System</small></span></a>
            <nav id="nav"></nav>
            <div class="side-user"><span class="avatar">AD</span>
                <div><b>Administrator</b><small>System Admin</small></div><button class="icon-btn" data-action="logout"><i data-lucide="log-out"></i></button>
            </div>
        </aside>
        <header class="topbar"><button class="icon-btn menu-btn" id="menuBtn"><i data-lucide="menu"></i></button>
            <div>
                <p class="crumb">BGFC / <span id="crumb">Dashboard</span></p>
                <h1 id="pageTitle">Dashboard</h1>
            </div>
            <div class="top-actions"><button class="icon-btn notification" data-view="alerts"><i data-lucide="bell"></i><span id="alertDot"></span></button><span class="avatar">AD</span></div>
        </header>
        <main id="content" class="content"></main>
        <footer class="app-footer"><span>Copyright ©️ 2026 BGFC Inventory Management System. All Rights Reserved.</span><span>Developed by <strong>James C. Estrera</strong></span></footer>
        <nav id="mobileNav" class="mobile-nav"></nav>
    </div>
    <div id="modal" class="modal hidden">
        <div class="modal-backdrop" data-close></div>
        <div class="modal-card">
            <div class="modal-head">
                <div><small id="modalEyebrow"></small>
                    <h2 id="modalTitle"></h2>
                </div><button class="icon-btn" data-close><i data-lucide="x"></i></button>
            </div>
            <form id="modalForm" class="modal-body"></form>
        </div>
    </div>
    <div id="toast" class="toast"></div>
    <button id="aiToggle" class="ai-toggle hidden" aria-label="Open inventory assistant"><i data-lucide="bot"></i><span>Ask AI</span></button>
    <aside id="aiChat" class="ai-chat hidden" aria-label="AI inventory assistant">
        <div class="ai-chat-head"><div><span class="ai-bot"><i data-lucide="bot"></i></span><div><b>Inventory Assistant</b><small>Live database insights</small></div></div><button id="aiClose" class="icon-btn"><i data-lucide="x"></i></button></div>
        <div id="aiMessages" class="ai-messages"><div class="message bot">Hi! Ask me about your inventory, stock levels, or transactions.</div><div class="ai-suggestions"><button>How many Bond Paper remain?</button><button>How many stock out this month?</button><button>Which items are low stock?</button></div></div>
        <form id="aiForm" class="ai-form"><input id="aiQuestion" placeholder="Ask about your inventory…" autocomplete="off" required><button class="btn primary" aria-label="Send"><i data-lucide="send"></i></button></form>
    </aside>
    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>

</html>
