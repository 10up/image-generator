# Image Generator

## Nginx config

```Bash
location ~ \.(gif|jpg|png) {
	try_files $uri @img_proxy;
}

location @img_proxy {
	rewrite ^(.*)$ /wp-admin/admin-ajax.php?action=ten_generate_image;
}
```