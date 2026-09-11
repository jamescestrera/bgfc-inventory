function requireAuth(req, res, next) {
  if (!req.session.user) return res.redirect('/login');
  next();
}

function guestOnly(req, res, next) {
  if (req.session.user) return res.redirect('/dashboard');
  next();
}

module.exports = { requireAuth, guestOnly };
