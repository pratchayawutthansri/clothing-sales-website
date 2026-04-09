<?php
require_once 'includes/config.php'; // Admin config
checkAdminAuth();
require_once '../includes/db.php'; // Main DB

// Fetch active sessions (users who messaged) for initial load
$stmt = $pdo->query("
    SELECT 
        c1.session_id, 
        MAX(c1.created_at) as last_msg, 
        COUNT(*) as msg_count,
        (SELECT is_admin FROM chat_messages c2 WHERE c2.session_id = c1.session_id ORDER BY created_at DESC LIMIT 1) as last_is_admin
    FROM chat_messages c1 
    GROUP BY c1.session_id 
    ORDER BY last_is_admin ASC, last_msg DESC
");
$sessions = $stmt->fetchAll();

$currentSession = $_GET['session'] ?? ($sessions[0]['session_id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Chat - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Modern Chat Layout */
        body { background: #f3f4f6; font-family: 'Kanit', sans-serif; margin: 0; }
        
        .chat-container { display: flex; height: 100vh; width: 100%; background: #f3f4f6; overflow: hidden; }
        
        /* Sidebar Inbox */
        .chat-sidebar {
            width: 320px; background: white; border-right: 1px solid #e5e7eb;
            display: flex; flex-direction: column; z-index: 10;
        }
        .chat-sidebar-header {
            padding: 24px; border-bottom: 1px solid #e5e7eb;
            display: flex; justify-content: space-between; align-items: center;
        }
        .chat-sidebar-header h2 { font-size: 1.25rem; font-family: 'Outfit', sans-serif; margin: 0; font-weight: 600; color: #111827; }
        .btn-icon { background: none; border: none; cursor: pointer; color: #6b7280; padding: 5px; border-radius: 50%; transition: 0.2s; display: flex; align-items: center; justify-content: center;}
        .btn-icon:hover { background: #f3f4f6; color: #111827; }

        /* Session Links */
        .session-link {
            display: flex; flex-direction: column; padding: 16px 24px;
            border-bottom: 1px solid #f9fafb; text-decoration: none; color: #374151;
            transition: all 0.2s; border-left: 3px solid transparent;
        }
        .session-link:hover { background: #f9fafb; }
        .session-link.active {
            background: #f9fafb; border-left-color: #000;
        }
        .session-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .session-name { font-weight: 600; font-family: 'Outfit', sans-serif; color: #111827; }
        .session-time { font-size: 0.75rem; color: #9ca3af; }
        .session-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #6b7280; }
        .msg-badge { background: #e5e7eb; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; color: #374151; }
        .session-link.active .msg-badge { background: #000; color: white; }

        /* Chat Main Area */
        .chat-main { flex: 1; display: flex; flex-direction: column; background: #f3f4f6; position: relative; }
        .chat-header {
            background: white; padding: 20px 30px; border-bottom: 1px solid #e5e7eb;
            display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            z-index: 5;
        }
        .avatar {
            width: 44px; height: 44px; border-radius: 50%; background: #f3f4f6;
            display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-weight: 700; color: #6b7280; font-size: 1.1rem;
        }
        .chat-header-info h3 { margin: 0 0 2px 0; font-size: 1.1rem; font-family: 'Outfit', sans-serif; color: #111827; }
        .status-dot { display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-right: 5px; }
        .status-text { font-size: 0.8rem; color: #6b7280; display: flex; align-items: center; }

        /* Messages */
        .chat-messages {
            flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px;
        }
        .msg-bubble {
            padding: 14px 20px; border-radius: 18px; max-width: 65%; line-height: 1.5; word-break: break-word;
            font-size: 0.95rem; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .msg-admin {
            background: #000; color: white; align-self: flex-end; border-bottom-right-radius: 4px;
        }
        .msg-guest {
            background: white; color: #111827; align-self: flex-start; border-bottom-left-radius: 4px;
            border: 1px solid #e5e7eb;
        }

        /* Input Form */
        .chat-input-area {
            background: white; padding: 20px 30px; border-top: 1px solid #e5e7eb; z-index: 5;
        }
        .chat-form {
            display: flex; gap: 12px; align-items: center; 
            background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 6px 6px 20px; border-radius: 40px;
            transition: all 0.2s;
        }
        .chat-form:focus-within { background: white; border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        .chat-input {
            flex: 1; border: none; background: transparent; padding: 10px 0; font-family: inherit; font-size: 1rem; outline: none; color: #111827;
        }
        .chat-input::placeholder { color: #9ca3af; }
        .btn-send {
            background: #000; color: white; border: none; width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;
        }
        .btn-send:hover { background: #374151; transform: scale(1.05); }

        .empty-state {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #9ca3af;
        }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: 15px; color: #d1d5db; }
        .empty-state p { font-size: 1.1rem; font-family: 'Outfit', sans-serif; margin: 0; }
        
        /* Scrollbar cleanup */
        #sessionList::-webkit-scrollbar, #adminChatMessages::-webkit-scrollbar { width: 6px; }
        #sessionList::-webkit-scrollbar-thumb, #adminChatMessages::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 10px; }
    </style>
</head>
<body>

<div class="chat-container">
    <!-- Main Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Chat Inbox Sidebar -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2 id="inboxCount">Inbox (<?= count($sessions) ?>)</h2>
            <button onclick="fetchSidebarSessions()" class="btn-icon" title="Refresh">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
        <div id="sessionList" style="flex: 1; overflow-y: auto;">
            <?php foreach ($sessions as $s): ?>
                <a href="?session=<?= htmlspecialchars($s['session_id'], ENT_QUOTES, 'UTF-8') ?>" class="session-link <?= $currentSession === $s['session_id'] ? 'active' : '' ?>">
                    <div class="session-info">
                        <span class="session-name">Guest #<?= htmlspecialchars(substr($s['session_id'], 0, 6)) ?></span>
                        <span class="session-time"><?= date('H:i', strtotime($s['last_msg'])) ?></span>
                    </div>
                    <div class="session-meta">
                        <span>Customer Support</span>
                        <?php if ((int)$s['last_is_admin'] === 0): ?>
                            <span class="msg-badge"><?= (int)$s['msg_count'] ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-main">
        <?php if ($currentSession): ?>
            <div class="chat-header">
                <div class="avatar">G</div>
                <div class="chat-header-info">
                    <h3>Guest #<?= htmlspecialchars(substr($currentSession, 0, 6)) ?></h3>
                    <div class="status-text"><span class="status-dot"></span> Online</div>
                </div>
            </div>
            
            <div id="adminChatMessages" class="chat-messages">
                <!-- Messages loaded via JS -->
            </div>

            <div class="chat-input-area">
                <form id="adminChatForm" class="chat-form">
                    <input type="text" id="adminMsgInput" class="chat-input" placeholder="พิมพ์ข้อความตอบกลับ..." autocomplete="off">
                    <button type="submit" class="btn-send" title="Send message">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <p>เลือกแชทเพื่อเริ่มต้นสนทนา</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const currentSession = "<?= htmlspecialchars($currentSession ?? '', ENT_QUOTES, 'UTF-8') ?>";
    
    // Auto-refresh sidebar every 5 seconds
    setInterval(fetchSidebarSessions, 5000);

    async function fetchSidebarSessions() {
        try {
            const res = await fetch('chat_api.php?action=get_sessions&t=' + Date.now());
            const data = await res.json();
            
            if (data.status === 'success' && data.sessions) {
                document.getElementById('inboxCount').textContent = `Inbox (${data.sessions.length})`;
                const sessionList = document.getElementById('sessionList');
                sessionList.innerHTML = ''; // Clear current
                
                data.sessions.forEach(s => {
                    const isActive = currentSession === s.session_id ? 'active' : '';
                    const shortId = s.session_id.substring(0, 6);
                    
                    // Format time
                    const d = new Date(s.last_msg.replace(' ', 'T'));
                    const timeStr = d.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit', hour12: false});
                    
                    const link = document.createElement('a');
                    link.href = `?session=${s.session_id}`;
                    link.className = `session-link ${isActive}`;
                    link.innerHTML = `
                        <div class="session-info">
                            <span class="session-name">Guest #${shortId}</span>
                            <span class="session-time">${timeStr}</span>
                        </div>
                        <div class="session-meta">
                            <span>Customer Support</span>
                            ${s.last_is_admin == 0 ? `<span class="msg-badge">${s.msg_count}</span>` : ''}
                        </div>
                    `;
                    sessionList.appendChild(link);
                });
            }
        } catch (e) {
            console.error("Failed to fetch sessions", e);
        }
    }

    if (currentSession) {
        // Poll for current conversation messages every 2 seconds
        setInterval(fetchAdminMessages, 2000);
        fetchAdminMessages(); // Initial fetch
        
        document.getElementById('adminChatForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('adminMsgInput');
            const msg = input.value.trim();
            if(!msg) return;
            
            // Optimistic rendering
            appendAdminMessage(msg, 1); // 1 = admin
            input.value = '';
            
            await fetch('../api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ message: msg, target_session: currentSession })
            });
            
            // Re-fetch to ensure sync
            fetchAdminMessages();
        });
    }

    async function fetchAdminMessages() {
        try {
            const res = await fetch(`chat_api.php?session=${currentSession}&t=${Date.now()}`);
            const data = await res.json();
            
            if (data.status === 'success' && data.messages) {
                const container = document.getElementById('adminChatMessages');
                
                // Only rewrite if count changed to avoid flickering (simple check)
                if (container.children.length !== data.messages.length) {
                    container.innerHTML = '';
                    data.messages.forEach(msg => {
                        appendAdminMessage(msg.message, msg.is_admin);
                    });
                }
            }
        } catch (e) {
            console.error("Failed to fetch admin messages", e);
        }
    }

    function appendAdminMessage(text, isAdmin) {
        const div = document.createElement('div');
        div.className = isAdmin == 1 ? 'msg-bubble msg-admin' : 'msg-bubble msg-guest';
        div.textContent = text;
        
        const container = document.getElementById('adminChatMessages');
        const isScrolledToBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 10;
        
        container.appendChild(div);
        
        // Auto scroll if already at bottom or if it's our own message
        if (isScrolledToBottom || isAdmin == 1) {
            container.scrollTop = container.scrollHeight;
        }
    }
</script>

</body>
</html>
