<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config/database.php';

function reply(array $data, int $status = 200): never { http_response_code($status); echo json_encode($data); exit; }
function body(): array { return json_decode((string) file_get_contents('php://input'), true) ?: []; }
function requireAuth(): void { if (empty($_SESSION['user'])) reply(['ok'=>false,'message'=>'Unauthenticated'],401); }
function inventoryInsights(PDO $pdo): array {
    $items=$pdo->query("SELECT i.id,i.name,i.stock,i.reorder,COALESCE(SUM(CASE WHEN t.type='Stock Out' AND t.created_at>=DATE_SUB(NOW(),INTERVAL 90 DAY) THEN ABS(t.qty) ELSE 0 END),0) issued90,MAX(CASE WHEN t.type='Stock Out' THEN t.created_at END) last_issue FROM items i LEFT JOIN transactions t ON t.item=i.name GROUP BY i.id,i.name,i.stock,i.reorder")->fetchAll();
    $forecast=[];$overstock=[];$slow=[];$fast=[];$predictive=[];$reorders=[];
    foreach($items as $x){$issued=(int)$x['issued90'];$daily=$issued/90;$days=$daily>0?(int)floor($x['stock']/$daily):null;$movement=$issued>=30?'Fast Moving':($issued>0?'Normal Moving':'Slow Moving');$suggest=max(0,(int)ceil(max($x['reorder']*2-$x['stock'],$daily*30-$x['stock'])));$row=['id'=>$x['id'],'name'=>$x['name'],'stock'=>(int)$x['stock'],'reorder'=>(int)$x['reorder'],'issued90'=>$issued,'monthly'=>(int)ceil($daily*30),'days'=>$days,'movement'=>$movement,'suggested'=>$suggest];$forecast[]=$row;if($movement==='Fast Moving')$fast[]=$row;if($movement==='Slow Moving')$slow[]=$row;if($suggest>0)$reorders[]=$row;if($days!==null&&$days<=30)$predictive[]=$row;if($x['stock']>max($x['reorder']*4,$daily*180)&&$x['stock']>20)$overstock[]=$row;}
    $anomalies=$pdo->query("SELECT transaction_date date,item,qty,reference_no reference FROM transactions WHERE type='Stock Out' AND ABS(qty)>=100 ORDER BY id DESC LIMIT 10")->fetchAll();
    $low=count(array_filter($items,fn($x)=>(int)$x['stock']<=(int)$x['reorder']));$score=max(0,100-$low*8-count($overstock)*5-count($anomalies)*3);$grade=$score>=85?'Good':($score>=65?'Needs Attention':'At Risk');
    return compact('forecast','overstock','slow','fast','predictive','reorders','anomalies','score','grade');
}

try {
    $action = $_GET['action'] ?? '';
    if ($action === 'login') {
        $input=body(); $stmt=db()->prepare("SELECT * FROM users WHERE username=? AND status='Active' LIMIT 1"); $stmt->execute([$input['username']??'']); $user=$stmt->fetch();
        if (!$user || !password_verify($input['password']??'', $user['password_hash'])) reply(['ok'=>false,'message'=>'Invalid username or password'],422);
        $_SESSION['user']=['id'=>$user['id'],'name'=>$user['name'],'role'=>$user['role']]; reply(['ok'=>true,'user'=>$_SESSION['user']]);
    }
    if ($action === 'session') reply(['ok'=>true,'authenticated'=>!empty($_SESSION['user']),'user'=>$_SESSION['user']??null]);
    if ($action === 'logout') { session_destroy(); reply(['ok'=>true]); }
    requireAuth();
    if ($action === 'assistant') {
        $question=trim((string)(body()['question']??'')); $q=mb_strtolower($question); $pdo=db();
        if ($question==='') reply(['ok'=>false,'message'=>'Please enter a question.'],422);
        if (str_contains($q,'low stock') || str_contains($q,'need reorder')) {
            $rows=$pdo->query('SELECT name,stock,reorder FROM items WHERE stock <= reorder ORDER BY stock')->fetchAll();
            $answer=$rows ? count($rows).' low-stock item(s): '.implode(', ',array_map(fn($x)=>$x['name'].' ('.$x['stock'].' remaining, reorder at '.$x['reorder'].')',$rows)).'.' : 'No items are currently at or below their reorder level.';
        } elseif (str_contains($q,'stock out') || str_contains($q,'stockout') || str_contains($q,'issued')) {
            $period=str_contains($q,'this month'); $where="type='Stock Out'".($period?' AND YEAR(created_at)=YEAR(CURRENT_DATE()) AND MONTH(created_at)=MONTH(CURRENT_DATE())':'');
            $row=$pdo->query("SELECT COUNT(*) transactions,COALESCE(SUM(ABS(qty)),0) units FROM transactions WHERE $where")->fetch(); $answer=($period?'This month, ':'').'a total of '.$row['units'].' unit(s) were stocked out across '.$row['transactions'].' transaction(s).';
        } elseif (str_contains($q,'stock in') || str_contains($q,'stockin') || str_contains($q,'received')) {
            $period=str_contains($q,'this month'); $where="type='Stock In'".($period?' AND YEAR(created_at)=YEAR(CURRENT_DATE()) AND MONTH(created_at)=MONTH(CURRENT_DATE())':'');
            $row=$pdo->query("SELECT COUNT(*) transactions,COALESCE(SUM(qty),0) units FROM transactions WHERE $where")->fetch(); $answer=($period?'This month, ':'').'a total of '.$row['units'].' unit(s) were stocked in across '.$row['transactions'].' transaction(s).';
        } else {
            $items=$pdo->query('SELECT name,unit,stock,reorder FROM items')->fetchAll(); $best=null; $score=0;
            foreach($items as $item){$hits=0;foreach(preg_split('/\s+/',mb_strtolower($item['name'])) as $token)if(mb_strlen($token)>2&&str_contains($q,$token))$hits++;if($hits>$score){$score=$hits;$best=$item;}}
            if($best)$answer=$best['name'].' has '.$best['stock'].' '.$best['unit'].'(s) remaining. Its reorder level is '.$best['reorder'].'.'.($best['stock']<=$best['reorder']?' This item needs reordering.':'');
            elseif(str_contains($q,'how many item')||str_contains($q,'total item')){$row=$pdo->query('SELECT COUNT(*) types,COALESCE(SUM(stock),0) units FROM items')->fetch();$answer='There are '.$row['types'].' item types with '.$row['units'].' total units currently in inventory.';}
            else $answer='I can answer questions about remaining item quantities, stock in or stock out this month, total inventory, and low-stock items. Try asking “How many Bond Paper remain?”';
        }
        reply(['ok'=>true,'answer'=>$answer]);
    }
    if ($action === 'bootstrap') {
        $pdo=db();
        reply(['ok'=>true,'data'=>[
            'categories'=>$pdo->query('SELECT id,name,description FROM categories ORDER BY id')->fetchAll(),
            'items'=>$pdo->query('SELECT id,name,category,unit,stock,reorder FROM items ORDER BY id')->fetchAll(),
            'suppliers'=>$pdo->query('SELECT id,name,contact,email FROM suppliers ORDER BY id')->fetchAll(),
            'transactions'=>$pdo->query('SELECT transaction_date date,type,item,qty,reference_no reference,user_name user FROM transactions ORDER BY id DESC')->fetchAll(),
            'users'=>$pdo->query('SELECT id,username,name,role,status FROM users ORDER BY id')->fetchAll(),
            'audit'=>$pdo->query('SELECT log_date date,user_name user,action,details FROM audit_logs ORDER BY id DESC')->fetchAll()
        ]]);
    }
    if ($action === 'sync' && $_SERVER['REQUEST_METHOD']==='POST') {
        $data=body()['data']??[]; $pdo=db(); $pdo->beginTransaction();
        foreach(['categories','items','suppliers','transactions','audit_logs'] as $t)$pdo->exec("DELETE FROM $t");
        $s=$pdo->prepare('INSERT INTO categories(id,name,description) VALUES(?,?,?)'); foreach($data['categories']??[] as $x)$s->execute([$x['id'],$x['name'],$x['description']??'']);
        $s=$pdo->prepare('INSERT INTO items(id,name,category,unit,stock,reorder) VALUES(?,?,?,?,?,?)'); foreach($data['items']??[] as $x)$s->execute([$x['id'],$x['name'],$x['category'],$x['unit'],(int)$x['stock'],(int)$x['reorder']]);
        $s=$pdo->prepare('INSERT INTO suppliers(id,name,contact,email) VALUES(?,?,?,?)'); foreach($data['suppliers']??[] as $x)$s->execute([$x['id'],$x['name'],$x['contact']??'',$x['email']??'']);
        $s=$pdo->prepare("INSERT INTO users(id,username,name,role,status,password_hash) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE username=VALUES(username),name=VALUES(name),role=VALUES(role),status=VALUES(status)"); foreach($data['users']??[] as $x)$s->execute([$x['id'],$x['username'],$x['name'],$x['role'],$x['status'],password_hash('changeme123',PASSWORD_DEFAULT)]);
        $s=$pdo->prepare('INSERT INTO transactions(transaction_date,type,item,qty,reference_no,user_name) VALUES(?,?,?,?,?,?)'); foreach(array_reverse($data['transactions']??[]) as $x)$s->execute([$x['date'],$x['type'],$x['item'],(int)$x['qty'],$x['reference'],$x['user']]);
        $s=$pdo->prepare('INSERT INTO audit_logs(log_date,user_name,action,details) VALUES(?,?,?,?)'); foreach(array_reverse($data['audit']??[]) as $x)$s->execute([$x['date'],$x['user'],$x['action'],$x['details']]);
        $pdo->commit(); reply(['ok'=>true]);
    }
    reply(['ok'=>false,'message'=>'Unknown action'],404);
} catch(Throwable $e) { if(db()->inTransaction())db()->rollBack(); reply(['ok'=>false,'message'=>'Database request failed'],500); }
