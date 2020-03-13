UPGRADE FROM 5.x to 6.0
=======================

Configurations
--------------

* Remove from `config/packages/ped_storage.yaml` the `parameters` section:

```yaml
parameters:
    ped_storage.uploader: "ped_storage.%env(STORAGE_ADAPTER)%_media_handler"
```

* Now `ped_storage.local.web_root_dir` in `config/packages/ped_storage.yaml` is optional. Remove it
* Add in `config/packages/ped_storage.yaml` the `ped_storage.adapter` parameter

```yaml
    ped_storage:
        adapter: local # or amazon_s3
```

* Keep only `local` or `amazon_s3` section based on `ped_storage.adapter` parameter. Eg.

```yaml
ped_storage:
    adapter:       local
    directory:     "%env(STORAGE_DIRECTORY)%"
    thumbs_prefix: "%env(STORAGE_THUMBS_PREFIX)%"
    local:
        endpoint: "%env(STORAGE_LOCAL_ENDPOINT)%"
```

or

```yaml
ped_storage:
    adapter:           amazon_s3
    amazon_s3:
        key:           "%env(STORAGE_AMAZON_S3_KEY)%"
        secret:        "%env(STORAGE_AMAZON_S3_SECRET)%"
        region:        "%env(STORAGE_AMAZON_S3_REGION)%"
        endpoint:      "%env(STORAGE_AMAZON_S3_ENDPOINT)%"
        bucket_name:   "%env(STORAGE_AMAZON_S3_BUCKET_NAME)%"
        expire_at:     "%env(STORAGE_AMAZON_S3_EXPIRE_AT)%"
```

* Move `directory` and `thumbs_prefix` on root of configuration:

```yaml
ped_storage:
    adapter:         "%env(STORAGE_ADAPTER)%"
    directory:       "%env(STORAGE_DIRECTORY)%"
    thumbs_prefix:   "%env(STORAGE_THUMBS_PREFIX)%"
```

* Delete `config/packages/knp_gaufrette.yaml` file
* Remove from `config/packages/liip_imagine.yaml` file

```yaml
    data_loader: "loader_%env(STORAGE_ADAPTER)%_data"
    cache: "%env(STORAGE_ADAPTER)%_fs"
```

* Replace `@=service(container.getParameter('ped_storage.uploader'))` with `@ped_storage.uploader`
* Replace:

```php
$service = $this->getParameter('ped_storage.uploader');

/* @var MediaHandler $uploader */
$uploader = $this->get($service);
```

with:

```php
$uploader = $this->get('ped_storage.uploader');
```
