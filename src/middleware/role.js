const permissions = {
  ADMINISTRATOR: ['*'],
  INVENTORY_OFFICER: ['dashboard','items','categories','suppliers','stock','inventory','reports','transactions'],
  STAFF: ['dashboard','inventory','stock-out','own-transactions']
};

function allow(...required) {
  return (req, res, next) => {
    const role = req.session.user?.role;
    const granted = permissions[role] || [];
    if (granted.includes('*') || required.some(p => granted.includes(p))) return next();
    return res.status(403).render('errors/error', { title: 'Access denied', status: 403, message: 'You are not authorized to access this page.' });
  };
}

module.exports = { allow, permissions };
