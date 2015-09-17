# Image Generator

## Nginx config

```Bash
location ~ \.(gif|jpg|png) {
	try_files $uri @img_proxy;
}

location @img_proxy {
	rewrite ^(.*)$ /wp-admin/admin-ajax.php?action=generate_image;
}
```

## Amazon S3 config

To make it working with Amazon S3 you need to enable website hosting for your bucket in S3 console and update redirection rules by adding following snippet:

```Bash
<RoutingRules>
	<RoutingRule>
		<Condition>
			<HttpErrorCodeReturnedEquals>403</HttpErrorCodeReturnedEquals >
		</Condition>
		<Redirect>
			<HostName>yousite.com</HostName>
			<ReplaceKeyPrefixWith>wp-admin/admin-ajax.php?action=generate_image&amp;provider=aws&amp;image=</ReplaceKeyPrefixWith>
		</Redirect>
	</RoutingRule>
</RoutingRules>
```

Please, pay attention to the *HttpErrorCodeReturnedEquals* parameter. It might accept either 403 or 404 code depending on a bucket settings. Also if you use [S3 Uploads](https://github.com/humanmade/S3-Uploads) plugin, then you need to use `S3Uploads` provider name instead of `aws` as shown above. Finally if you stick with `aws` provider, then you need to define ACCESS KEY, SECRET, REGION and BUCKET constants in your wp-config.php file:

```PHP
define( 'AWS_ACCESS_KEY_ID', '...' );
define( 'AWS_SECRET_ACCESS_KEY', '...' );
define( 'AWS_S3_REGION', '...' );
define( 'AWS_S3_BUCKET', '...' );
```