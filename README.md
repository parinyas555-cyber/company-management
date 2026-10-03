# Company Management V1

โปรเจกต์แยกที่ต่อยอดโครงสร้างจาก Stock Management V14 โดยไม่ใช้ฐานข้อมูลเดียวกับระบบเดิม

## Modules
- Stock Management: สินค้า, รับเข้า, เบิกออก, รายงาน, Factory Reset
- Device Management: GPS, MDVR, Sensor และอุปกรณ์อื่น ๆ
- Maintenance / Repair: เปิดงานซ่อม, สถานะงาน, อาการเสีย, วิธีแก้ไข, ค่าใช้จ่าย
- User & Admin: สิทธิ์ Admin / Staff

## New tables
- `devices`
- `maintenance_jobs`

## Render
โปรเจกต์นี้ตั้งชื่อ Web Service และ Database แยกเป็น `company-management` และ `company-management-db` เพื่อไม่กระทบระบบ Stock Management V14 เดิม

## Default Admin
Username: `admin`
Password: `Admin@123`

ควรเปลี่ยนรหัสผ่านหลัง Login ครั้งแรก
