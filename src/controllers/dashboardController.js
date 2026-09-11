const db = require('../database/db');
exports.index = async (req, res, next) => {
  try {
    const [[stats], [recent], [low], [chart]] = await Promise.all([
      db.query(`SELECT (SELECT COUNT(*) FROM items WHERE is_active=1) totalItems,(SELECT COALESCE(SUM(quantity),0) FROM items WHERE is_active=1) totalStock,(SELECT COUNT(*) FROM items WHERE is_active=1 AND quantity<=reorder_level) lowStock,(SELECT COUNT(*) FROM inventory_transactions) totalTransactions,(SELECT COUNT(*) FROM categories WHERE is_active=1) totalCategories,(SELECT COUNT(*) FROM suppliers WHERE is_active=1) totalSuppliers`),
      db.query(`SELECT t.*,i.item_name,u.full_name FROM inventory_transactions t JOIN items i ON i.id=t.item_id LEFT JOIN users u ON u.id=t.user_id ORDER BY t.transaction_date DESC LIMIT 8`),
      db.query(`SELECT item_code,item_name,quantity,reorder_level FROM items WHERE is_active=1 AND quantity<=reorder_level ORDER BY quantity ASC LIMIT 8`),
      db.query(`SELECT transaction_type,COALESCE(SUM(ABS(quantity)),0) total FROM inventory_transactions WHERE transaction_type IN ('STOCK_IN','STOCK_OUT') GROUP BY transaction_type`)
    ]);
    const chartData = { STOCK_IN: 0, STOCK_OUT: 0 }; chart.forEach(r => chartData[r.transaction_type] = Number(r.total));
    res.render('dashboard', { title: 'Dashboard', stats, recent, low, chartData });
  } catch(e){ next(e); }
};
