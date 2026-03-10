# Apache + MySQL setup (Ubuntu)

Use this when serving the app with **Apache2** and **MySQL** instead of PHP’s built-in server.

---

## 1. MySQL

- **Start (if needed):** `sudo systemctl start mysql`
- **Create DB and import schema:**
  ```bash
  sudo mysql -e "CREATE DATABASE IF NOT EXISTS ehist_sabs;"
  sudo mysql ehist_sabs < /home/tita-tera-official/dev/ehist-sabs/database/schema.sql
  ```
- **Optional:** Create a DB user and set its credentials in `.env` (see project root).

---

## 2. Apache: point to the project

You want the **document root** to be the project’s `public/` folder so only public files are exposed.

**Option A – VirtualHost (recommended)**

1. Create a site config:
   ```bash
   sudo nano /etc/apache2/sites-available/ehist-sabs.conf
   ```
2. Paste (adjust paths if your project is elsewhere):

   ```apache
   <VirtualHost *:80>
       ServerName ehist-sabs.local
       DocumentRoot /home/tita-tera-official/dev/ehist-sabs/public

       <Directory /home/tita-tera-official/dev/ehist-sabs/public>
           AllowOverride All
           Require all granted
           DirectoryIndex index.php
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/ehist-sabs_error.log
       CustomLog ${APACHE_LOG_DIR}/ehist-sabs_access.log combined
   </VirtualHost>
   ```

3. Enable the site and restart Apache:
   ```bash
   sudo a2ensite ehist-sabs.conf
   sudo systemctl restart apache2
   ```
4. Add to your hosts file so the name resolves:
   ```bash
   echo "127.0.0.1 ehist-sabs.local" | sudo tee -a /etc/hosts
   ```
5. Open in browser: **http://ehist-sabs.local**  
   API: **http://ehist-sabs.local/api.php/auth/login** (etc.)

**Option B – Use default localhost**

1. Symlink the project’s `public` folder into the default web root:
   ```bash
   sudo ln -s /home/tita-tera-official/dev/ehist-sabs/public /var/www/html/ehist-sabs
   ```
2. Open: **http://localhost/ehist-sabs**  
   API: **http://localhost/ehist-sabs/api.php/...**

---

## 3. PHP and Apache modules

- PHP with MySQL: `sudo apt install php libapache2-mod-php php-mysql`
- Enable mod_rewrite (for .htaccess): `sudo a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`

---

## 4. Permissions

Apache runs as `www-data`. It must be able to **read** the project (so `index.php` can `require` files under `app/`).

If you get “Permission denied” or blank pages:

```bash
# Make project readable by Apache (adjust path if needed)
sudo chown -R $USER:www-data /home/tita-tera-official/dev/ehist-sabs
chmod -R 750 /home/tita-tera-official/dev/ehist-sabs
chmod -R 755 /home/tita-tera-official/dev/ehist-sabs/public
```

If `.env` is present, ensure it’s readable by the web server (e.g. same group as above).

---

## 5. Checklist

- [ ] MySQL running: `sudo systemctl status mysql`
- [ ] Database created and schema imported
- [ ] `.env` in project root with correct `DB_*` (or defaults in config)
- [ ] Apache site enabled and restarted
- [ ] Browser: open site URL; Postman: use same base URL + `/api.php/...`
