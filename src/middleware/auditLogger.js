const db = require('../database/db');

async function audit(req, action, module, description) {
  try {
    await db.execute('INSERT INTO audit_logs (user_id, action, module, description, ip_address) VALUES (?, ?, ?, ?, ?)', [req.session.user?.id || null, action, module, description, req.ip]);
  } catch (error) { console.error('Audit log failed:', error.message); }
}

module.exports = audit;
