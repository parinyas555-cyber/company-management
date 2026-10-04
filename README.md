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


## Render deployment — สำคัญ

โปรเจกต์นี้ใช้ Render Postgres แบบเสียเงิน **$6/month** (`0.1c-256mb`) สำหรับ `company-management-db` และใช้ Internal Database URL สำหรับ Web Service

### ถ้าสร้าง Web Service และ Database แยกกันใน Render Dashboard

1. เปิด `company-management-db` → **Connect** → คัดลอก **Internal Database URL**
2. เปิด `company-management` → **Environment**
3. สร้าง/แก้ไขตัวแปรชื่อ `DATABASE_URL`
4. วางค่า **Internal Database URL ทั้งเส้น** ซึ่งต้องขึ้นต้นด้วย `postgresql://` หรือ `postgres://`
5. กด **Save Changes** และเลือก **Save and deploy**
6. ตรวจสอบ `/health.php` ต้องขึ้น `status: ok` และ `database: connected`

> ห้ามนำคำว่า `Internal Database URL`, `External Database URL` หรือชื่อฐานข้อมูลมาใส่แทน URL จริง และห้ามใช้ URL ของ `stock-management-db`

### ถ้าใช้ Blueprint (`render.yaml`)

`render.yaml` จะผูก `DATABASE_URL` ให้กับ `company-management-db` ผ่าน `connectionString` ซึ่งเป็น Internal Database URL โดยอัตโนมัติเมื่อ Blueprint ถูก Sync และฐานข้อมูลอยู่ใน workspace/region ที่เข้ากันได้

### ชื่อทรัพยากรที่ต้องตรงกัน

- Web Service: `company-management`
- PostgreSQL: `company-management-db`
- Database name: `company_management`
- PostgreSQL plan: `0.1c-256mb` ($6/month)
- Environment variable: `DATABASE_URL`
- App name: `Company Management`

### Default Admin

- Username: `admin`
- Password: `Admin@123`

เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก
