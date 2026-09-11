const bcrypt = require('bcrypt');
const db = require('../database/db');
const audit = require('../middleware/auditLogger');

exports.loginPage = (req, res) => res.render('auth/login', { title: 'Login' });
exports.login = async (req, res, next) => {
  try {
    const { username, password, remember } = req.body;
    const [rows] = await db.execute("SELECT id, full_name, username, password, role, status FROM users WHERE username = ? LIMIT 1", [username]);
    const user = rows[0];
    if (!user || user.status !== 'ACTIVE' || !(await bcrypt.compare(password || '', user.password))) {
      req.flash('error', 'Invalid username or password.'); return res.redirect('/login');
    }
    req.session.regenerate(err => {
      if (err) return next(err);
      req.session.user = { id: user.id, fullName: user.full_name, username: user.username, role: user.role };
      if (remember) req.session.cookie.maxAge = 30 * 24 * 60 * 60 * 1000;
      audit(req, 'USER_LOGIN', 'Authentication', `${user.username} logged in`);
      res.redirect('/dashboard');
    });
  } catch (e) { next(e); }
};
exports.logout = async (req, res) => {
  await audit(req, 'USER_LOGOUT', 'Authentication', `${req.session.user?.username || 'User'} logged out`);
  req.session.destroy(() => res.redirect('/login'));
};
