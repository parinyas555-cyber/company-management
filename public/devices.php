<?php
require_once __DIR__ . '/../src/partials.php';
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $action=$_POST['action']??'';
  try{
    if($action==='save'){
      $id=(int)($_POST['id']??0); $name=trim($_POST['device_name']??'');
      $model=trim($_POST['model']??''); $imei=trim($_POST['imei']??''); $serial=trim($_POST['serial_no']??'');
      $vehicle=trim($_POST['vehicle_no']??''); $firmware=trim($_POST['firmware']??''); $status=$_POST['status']??'Active'; $note=trim($_POST['note']??'');
      if($name==='') throw new Exception('กรุณาระบุชื่ออุปกรณ์');
      if(!in_array($status,['Active','Repair','Inactive'],true)) $status='Active';
      if($id){$s=$pdo->prepare('UPDATE devices SET device_name=?,model=?,imei=?,serial_no=?,vehicle_no=?,firmware=?,status=?,note=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$name,$model,$imei,$serial,$vehicle,$firmware,$status,$note,$id]);flash('success','แก้ไขข้อมูลอุปกรณ์เรียบร้อย');}
      else {$s=$pdo->prepare('INSERT INTO devices(device_name,model,imei,serial_no,vehicle_no,firmware,status,note) VALUES(?,?,?,?,?,?,?,?)');$s->execute([$name,$model,$imei,$serial,$vehicle,$firmware,$status,$note]);flash('success','เพิ่มอุปกรณ์เรียบร้อย');}
    }elseif($action==='delete'){
      require_admin(); $id=(int)($_POST['id']??0); $pdo->prepare('DELETE FROM devices WHERE id=?')->execute([$id]); flash('success','ลบอุปกรณ์เรียบร้อย');
    }
  }catch(Throwable $e){flash('danger',$e->getMessage());}
  header('Location: /devices.php');exit;
}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM devices WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$q=trim($_GET['q']??'');$where='';$args=[];if($q!==''){$where='WHERE device_name ILIKE ? OR model ILIKE ? OR imei ILIKE ? OR serial_no ILIKE ? OR vehicle_no ILIKE ?';$like='%'.$q.'%';$args=[$like,$like,$like,$like,$like];}
$s=$pdo->prepare("SELECT * FROM devices $where ORDER BY id DESC");$s->execute($args);$rows=$s->fetchAll();
page_start('จัดการอุปกรณ์');
?>
<div class="grid" style="grid-template-columns:repeat(3,1fr)">
<div class="card stat-card"><div class="muted">อุปกรณ์ทั้งหมด</div><div class="metric"><?=count($rows)?></div></div>
<div class="card stat-card"><div class="muted">กำลังซ่อม</div><div class="metric low"><?=number_format((int)$pdo->query("SELECT COUNT(*) FROM devices WHERE status='Repair'")->fetchColumn())?></div></div>
<div class="card stat-card"><div class="muted">ติดตั้งใช้งาน</div><div class="metric"><?=number_format((int)$pdo->query("SELECT COUNT(*) FROM devices WHERE status='Active'")->fetchColumn())?></div></div>
</div>
<div class="card">
<div class="section-head"><div><h3><?= $edit?'แก้ไขอุปกรณ์':'เพิ่มอุปกรณ์' ?></h3><div class="muted">GPS / MDVR / Sensor / อุปกรณ์อื่น ๆ</div></div></div>
<form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=h($edit['id']??0)?>">
<label>ชื่ออุปกรณ์<input name="device_name" required value="<?=h($edit['device_name']??'')?>"></label><label>รุ่น<input name="model" value="<?=h($edit['model']??'')?>"></label><label>IMEI<input name="imei" value="<?=h($edit['imei']??'')?>"></label><label>Serial Number<input name="serial_no" value="<?=h($edit['serial_no']??'')?>"></label><label>ทะเบียนรถ<input name="vehicle_no" value="<?=h($edit['vehicle_no']??'')?>"></label><label>Firmware<input name="firmware" value="<?=h($edit['firmware']??'')?>"></label><label>สถานะ<select name="status"><option <?=($edit['status']??'Active')==='Active'?'selected':''?>>Active</option><option <?=($edit['status']??'')==='Repair'?'selected':''?>>Repair</option><option <?=($edit['status']??'')==='Inactive'?'selected':''?>>Inactive</option></select></label><label style="grid-column:1/-1">หมายเหตุ<textarea name="note"><?=h($edit['note']??'')?></textarea></label>
<div style="grid-column:1/-1"><button class="btn primary" type="submit">บันทึกข้อมูล</button><?php if($edit):?> <a class="btn" href="/devices.php">ยกเลิก</a><?php endif;?></div></form></div>
<div class="card"><div class="section-head"><div><h3>รายการอุปกรณ์</h3><div class="muted">ค้นหาและติดตามอุปกรณ์ทั้งหมด</div></div><form method="get" style="display:flex;gap:8px"><input name="q" placeholder="ค้นหา IMEI / รุ่น / รถ..." value="<?=h($q)?>"><button class="btn" type="submit">ค้นหา</button></form></div>
<div class="table-wrap"><table><thead><tr><th>อุปกรณ์</th><th>รุ่น</th><th>IMEI / Serial</th><th>รถ</th><th>Firmware</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><strong><?=h($r['device_name'])?></strong></td><td><?=h($r['model'])?></td><td><?=h($r['imei'])?><br><span class="muted"><?=h($r['serial_no'])?></span></td><td><?=h($r['vehicle_no'])?></td><td><?=h($r['firmware'])?></td><td><span class="badge <?= $r['status']==='Active'?'success-bg':($r['status']==='Repair'?'danger-bg':'') ?>"><?=h($r['status'])?></span></td><td><a class="btn small" href="/devices.php?edit=<?=$r['id']?>">แก้ไข</a><?php if(is_admin()):?><form method="post" style="display:inline" onsubmit="return confirm('ยืนยันการลบอุปกรณ์นี้?')"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn small danger" type="submit">ลบ</button></form><?php endif;?></td></tr><?php endforeach;if(!$rows):?><tr><td colspan="7" class="empty">ยังไม่มีข้อมูลอุปกรณ์</td></tr><?php endif;?></tbody></table></div></div>
<?php page_end();
