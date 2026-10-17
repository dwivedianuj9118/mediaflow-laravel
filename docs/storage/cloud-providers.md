---
title: Cloud Providers
description: Store Laravel uploads on Cloudflare R2, Amazon S3, DigitalOcean Spaces, Wasabi, Backblaze B2, MinIO and other S3-compatible services with MediaFlow's provider presets.
---

# Cloud Providers

MediaFlow can store files on any S3-compatible object store. Pick a provider preset and enter a key, secret and bucket; MediaFlow works out the endpoint, region and addressing style and registers a disk called `mediaflow_cloud` at runtime.

The S3 driver (`league/flysystem-aws-s3-v3`) is installed with MediaFlow, so no extra packages are needed.

## Presets

| ID | Provider | Default region | Endpoint | Path-style |
| --- | --- | --- | --- | --- |
| `cloudflare_r2` | Cloudflare R2 | `auto` | `https://{account_id}.r2.cloudflarestorage.com` | no |
| `aws` | Amazon S3 | `us-east-1` | `https://s3.{region}.amazonaws.com` | no |
| `digitalocean` | DigitalOcean Spaces | `nyc3` | `https://{region}.digitaloceanspaces.com` | no |
| `wasabi` | Wasabi | `us-east-1` | `https://s3.{region}.wasabisys.com` | no |
| `backblaze` | Backblaze B2 | `us-west-004` | `https://s3.{region}.backblazeb2.com` | no |
| `contabo` | Contabo Object Storage | `eu2` | `https://{region}.contabostorage.com` | yes |
| `minio` | MinIO (self-hosted) | `us-east-1` | `http://localhost:9000` | yes |
| `gcs` | Google Cloud Storage | `auto` | `https://storage.googleapis.com` | yes |
| `azure` | Azure Blob Storage | `global` | `https://{account_name}.blob.core.windows.net` | no |
| `cloudinary` | Cloudinary | `global` | `https://api.cloudinary.com/v1_1/{cloud_name}` | no |

::: warning GCS, Azure and Cloudinary
All presets use Laravel's S3 driver. Google Cloud Storage works through its [S3 interoperability](https://cloud.google.com/storage/docs/interoperability) HMAC keys. Azure Blob Storage and Cloudinary do not offer a native S3 API, so those presets only work behind an S3-compatible gateway. Always run **Test connection** before switching uploads to them.
:::

## Configure with `.env`

```dotenv
MEDIAFLOW_PROVIDER=cloudflare_r2
MEDIAFLOW_PROVIDER_ACCOUNT_ID=your-32-character-account-id
MEDIAFLOW_PROVIDER_KEY=your-access-key-id
MEDIAFLOW_PROVIDER_SECRET=your-secret-access-key
MEDIAFLOW_PROVIDER_BUCKET=my-bucket
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://cdn.example.com
```

When a provider other than `local` is set, MediaFlow on boot:

1. registers the `mediaflow_cloud` disk in `filesystems.disks`,
2. makes it the default MediaFlow disk, and
3. if `MEDIAFLOW_PROVIDER_PUBLIC_URL` is set, switches delivery to the `cdn` driver with that URL.

If the S3 driver is missing, uploads stay on the local disk and a warning is logged instead of every upload failing.

### More examples

::: code-group

```dotenv [Amazon S3]
MEDIAFLOW_PROVIDER=aws
MEDIAFLOW_PROVIDER_KEY=AKIA...
MEDIAFLOW_PROVIDER_SECRET=...
MEDIAFLOW_PROVIDER_BUCKET=my-bucket
MEDIAFLOW_PROVIDER_REGION=eu-west-1
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://d1234.cloudfront.net
```

```dotenv [DigitalOcean Spaces]
MEDIAFLOW_PROVIDER=digitalocean
MEDIAFLOW_PROVIDER_KEY=...
MEDIAFLOW_PROVIDER_SECRET=...
MEDIAFLOW_PROVIDER_BUCKET=my-space
MEDIAFLOW_PROVIDER_REGION=fra1
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://my-space.fra1.cdn.digitaloceanspaces.com
```

```dotenv [Contabo]
MEDIAFLOW_PROVIDER=contabo
MEDIAFLOW_PROVIDER_KEY=...
MEDIAFLOW_PROVIDER_SECRET=...
MEDIAFLOW_PROVIDER_BUCKET=my-bucket
MEDIAFLOW_PROVIDER_REGION=eu2
MEDIAFLOW_PROVIDER_PUBLIC_URL=https://eu2.contabostorage.com/my-bucket
```

```dotenv [MinIO]
MEDIAFLOW_PROVIDER=minio
MEDIAFLOW_PROVIDER_KEY=minioadmin
MEDIAFLOW_PROVIDER_SECRET=minioadmin
MEDIAFLOW_PROVIDER_BUCKET=my-bucket
MEDIAFLOW_PROVIDER_ENDPOINT=http://localhost:9000
MEDIAFLOW_PROVIDER_PUBLIC_URL=http://localhost:9000/my-bucket
```

:::

::: tip Cloudflare R2 account ID
R2 needs your account ID to build the endpoint. Set `MEDIAFLOW_PROVIDER_ACCOUNT_ID` (or `CLOUDFLARE_ACCOUNT_ID`), or give the full `MEDIAFLOW_PROVIDER_ENDPOINT`.
:::

## Configure from the Settings page

On `/mediaflow/settings`, choose a provider, fill in the fields and click **Test connection**, then save. The page also generates a matching `.env` snippet. Credentials saved there live in the `mediaflow_settings` table; the secret is never sent back to the browser. Keeping credentials in `.env` is still the safer choice.

## Testing a connection

The test writes a small probe file, reads it back, deletes it and reports the round-trip time:

```js
const res = await fetch('/mediaflow/settings/test-provider', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
  },
  body: JSON.stringify({
    provider: 'cloudflare_r2',
    account_id: 'your-account-id',
    key: 'your-access-key-id',
    secret: 'your-secret-access-key',
    bucket: 'my-bucket',
    public_url: 'https://cdn.example.com',
  }),
})

// { success: true, message: "✓ Successfully connected ...", latency_ms: 142, details: { provider, bucket, endpoint, ... } }
```

Fields: `provider` (or `name`), `key`, `secret`, `bucket`, and optionally `account_id`, `region`, `endpoint`, `public_url`, `path_style`.

Endpoints must be `http` or `https`. Cloud metadata hosts and link-local addresses such as `169.254.169.254` are refused.

## Using your own S3 disk

You can skip the presets and point MediaFlow at a disk you defined in `config/filesystems.php`:

```dotenv
MEDIAFLOW_PROVIDER=local
MEDIAFLOW_DISK=s3
```

Disks with the `s3` driver use MediaFlow's S3 adapter; other drivers use the generic adapter. See [Extending](/reference/extending) for custom adapters.
