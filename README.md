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

```Bash
<RoutingRule>
    <Condition>
        <KeyPrefixEquals>this/directory/</KeyPrefixEquals>
        <HttpErrorCodeReturnedEquals>404</HttpErrorCodeReturnedEquals>
    </Condition>
    <Redirect>
        <Protocol>http</Protocol>
        <HostName>your.site.com</HostName>
        <ReplaceKeyPrefixWith>wp-admin/admin-ajax.php?action=generate_image&provider=aws</ReplaceKeyPrefixWith>
    </Redirect>
</RoutingRule>
```