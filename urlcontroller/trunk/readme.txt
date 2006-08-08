To install NP_UrlController:

1. You need a server with mod_rewrite enabled. Create an .htaccess file with this contents:
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.*)$ index.php?url=$1 [L,QSA]
</IfModule>

2. Upload the .htaccess file to your main directory (the same of index.php).

3. Install the plugin.

4. Have fun.
