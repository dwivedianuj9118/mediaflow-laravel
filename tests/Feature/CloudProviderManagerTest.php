<?php

namespace MediaFlow\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Config;
use MediaFlow\Laravel\Storage\CloudProviderManager;
use MediaFlow\Laravel\Support\MediaFlowSettingsManager;
use MediaFlow\Laravel\Tests\TestCase;

class CloudProviderManagerTest extends TestCase
{
    protected CloudProviderManager $providerManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->providerManager = app(CloudProviderManager::class);
    }

    public function test_manager_provides_ten_cloud_providers(): void
    {
        $providers = $this->providerManager->getProviders();

        $this->assertCount(10, $providers);

        $expectedIds = [
            'cloudflare_r2',
            'aws',
            'contabo',
            'digitalocean',
            'wasabi',
            'backblaze',
            'gcs',
            'azure',
            'cloudinary',
            'minio',
        ];

        foreach ($expectedIds as $id) {
            $this->assertArrayHasKey($id, $providers, "Missing expected provider: {$id}");
            $preset = $providers[$id];
            $this->assertNotEmpty($preset['name']);
            $this->assertNotEmpty($preset['icon']);
            $this->assertNotEmpty($preset['badge']);
            $this->assertNotEmpty($preset['key_label']);
            $this->assertNotEmpty($preset['secret_label']);
            $this->assertNotEmpty($preset['bucket_label']);
            $this->assertNotEmpty($preset['endpoint_template']);
            $this->assertIsBool($preset['path_style']);
        }
    }

    public function test_resolve_endpoint_substitutes_placeholders(): void
    {
        // Cloudflare R2: uses account_id in endpoint template
        $r2Endpoint = $this->providerManager->resolveEndpoint('cloudflare_r2', [
            'key' => 'account_xyz_123',
            'bucket' => 'photos',
        ]);
        $this->assertEquals('https://account_xyz_123.r2.cloudflarestorage.com', $r2Endpoint);

        // Contabo: uses region in endpoint template
        $contaboEndpoint = $this->providerManager->resolveEndpoint('contabo', [
            'region' => 'eu2',
        ]);
        $this->assertEquals('https://eu2.contabostorage.com', $contaboEndpoint);

        // DigitalOcean Spaces: uses region in endpoint template
        $doEndpoint = $this->providerManager->resolveEndpoint('digitalocean', [
            'region' => 'sfo3',
        ]);
        $this->assertEquals('https://sfo3.digitaloceanspaces.com', $doEndpoint);

        // Custom override endpoint
        $customEndpoint = $this->providerManager->resolveEndpoint('minio', [
            'endpoint' => 'https://custom-minio.internal:9000/',
        ]);
        $this->assertEquals('https://custom-minio.internal:9000', $customEndpoint);
    }

    public function test_build_disk_config_creates_s3_compatible_config(): void
    {
        $settings = [
            'name' => 'contabo',
            'key' => 'contabo_user_key',
            'secret' => 'contabo_secret_pass',
            'bucket' => 'storage-bucket',
            'region' => 'eu2',
            'public_url' => 'https://cdn.example.com',
        ];

        $diskConfig = $this->providerManager->buildDiskConfig($settings);

        $this->assertEquals('s3', $diskConfig['driver']);
        $this->assertEquals('contabo_user_key', $diskConfig['key']);
        $this->assertEquals('contabo_secret_pass', $diskConfig['secret']);
        $this->assertEquals('storage-bucket', $diskConfig['bucket']);
        $this->assertEquals('eu2', $diskConfig['region']);
        $this->assertEquals('https://eu2.contabostorage.com', $diskConfig['endpoint']);
        $this->assertTrue($diskConfig['use_path_style_endpoint']);
        $this->assertEquals('https://cdn.example.com', $diskConfig['url']);
    }

    public function test_build_disk_config_path_style_flag_per_provider(): void
    {
        // AWS must have path_style = false
        $awsConfig = $this->providerManager->buildDiskConfig([
            'name' => 'aws',
            'key' => 'ak',
            'secret' => 'sk',
            'bucket' => 'b',
            'region' => 'us-east-1',
        ]);
        $this->assertFalse($awsConfig['use_path_style_endpoint']);

        // MinIO must have path_style = true
        $minioConfig = $this->providerManager->buildDiskConfig([
            'name' => 'minio',
            'key' => 'minioadmin',
            'secret' => 'minioadmin',
            'bucket' => 'b',
        ]);
        $this->assertTrue($minioConfig['use_path_style_endpoint']);
    }

    public function test_generate_env_snippet_outputs_formatted_code_block(): void
    {
        $snippet = $this->providerManager->generateEnvSnippet([
            'name' => 'cloudflare_r2',
            'key' => 'r2_key_val',
            'secret' => 'r2_sec_val',
            'bucket' => 'my-app-uploads',
            'public_url' => 'https://media.company.com',
        ]);

        $this->assertStringContainsString('MEDIAFLOW_PROVIDER=cloudflare_r2', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_PROVIDER_KEY=r2_key_val', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_PROVIDER_SECRET=r2_sec_val', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_PROVIDER_ACCOUNT_ID=', $snippet);
        $this->assertStringContainsString('CLOUDFLARE_ACCOUNT_ID=', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_PROVIDER_BUCKET=my-app-uploads', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_PROVIDER_PUBLIC_URL=https://media.company.com', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_DISK=mediaflow_cloud', $snippet);
        $this->assertStringContainsString('MEDIAFLOW_DELIVERY_DRIVER=cdn', $snippet);
    }

    public function test_settings_page_displays_all_ten_provider_presets(): void
    {
        $response = $this->get(route('mediaflow.settings'));

        $response->assertStatus(200);
        $response->assertSee('Cloud Storage');
        $response->assertSee('CDN Providers');
        $response->assertSee('Cloudflare R2');
        $response->assertSee('AWS S3');
        $response->assertSee('Contabo');
        $response->assertSee('DigitalOcean');
        $response->assertSee('Wasabi');
        $response->assertSee('Backblaze B2');
        $response->assertSee('Google Cloud Storage');
        $response->assertSee('Azure Blob Storage');
        $response->assertSee('Cloudinary');
        $response->assertSee('MinIO');
        $response->assertSee('Test Provider Connection');
        $response->assertSee('Copy .env');
    }

    public function test_api_get_providers_endpoint_returns_json_presets(): void
    {
        $response = $this->getJson(route('mediaflow.settings.providers'));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(10, 'providers');
        $response->assertJsonPath('providers.cloudflare_r2.name', 'Cloudflare R2');
        $response->assertJsonPath('providers.contabo.name', 'Contabo Object Storage');
    }

    public function test_test_provider_endpoint_validates_required_fields(): void
    {
        $response = $this->postJson(route('mediaflow.settings.test-provider'), [
            'provider' => 'cloudflare_r2',
            // Missing key, secret, bucket
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Please provide the Key, Secret, and Bucket name', $response->json('message'));
    }

    public function test_test_provider_endpoint_gracefully_handles_invalid_credentials_and_redacts(): void
    {
        $response = $this->postJson(route('mediaflow.settings.test-provider'), [
            'provider' => 'cloudflare_r2',
            'key' => 'dummy_invalid_key_123',
            'secret' => 'dummy_super_secret_456',
            'bucket' => 'test-unreachable-bucket',
            'endpoint' => 'https://00000000000000000000000000000000.r2.cloudflarestorage.com',
            'public_url' => 'https://cdn.example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        // Error message must not contain raw secret
        $this->assertStringNotContainsString('dummy_super_secret_456', $response->json('message'));
    }

    public function test_saving_provider_settings_updates_database_and_runtime_config(): void
    {
        $response = $this->postJson(route('mediaflow.settings.update'), [
            'provider_name' => 'contabo',
            'provider_key' => 'contabo_acc_key_999',
            'provider_secret' => 'contabo_secret_token_888',
            'provider_bucket' => 'production-media-assets',
            'provider_region' => 'eu2',
            'provider_public_url' => 'https://eu2.contabostorage.com/production-media-assets',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $settingsManager = app(MediaFlowSettingsManager::class);
        $providerSettings = $settingsManager->get('provider');

        $this->assertEquals('contabo', $providerSettings['name']);
        $this->assertEquals('contabo_acc_key_999', $providerSettings['key']);
        $this->assertEquals('contabo_secret_token_888', $providerSettings['secret']);
        $this->assertEquals('production-media-assets', $providerSettings['bucket']);
        $this->assertEquals('eu2', $providerSettings['region']);

        if (!\MediaFlow\Laravel\Storage\CloudProviderManager::s3DriverInstalled()) {
            $this->markTestSkipped('league/flysystem-aws-s3-v3 not installed; the cloud disk is intentionally not activated.');
        }

        // Check runtime disk config in Config::get('filesystems.disks.mediaflow_cloud')
        $diskConfig = Config::get('filesystems.disks.mediaflow_cloud');
        $this->assertNotNull($diskConfig);
        $this->assertEquals('s3', $diskConfig['driver']);
        $this->assertEquals('contabo_acc_key_999', $diskConfig['key']);
        $this->assertEquals('production-media-assets', $diskConfig['bucket']);
        $this->assertTrue($diskConfig['use_path_style_endpoint']);
    }

    public function test_cloudflare_r2_endpoint_and_region_resolution(): void
    {
        $accountId = 'd41d8cd98f00b204e9800998ecf8427e';

        // 1. Account ID passed in explicit account_id field
        $endpointExplicit = $this->providerManager->resolveEndpoint('cloudflare_r2', [
            'key' => 'r2_access_key_123',
            'account_id' => $accountId,
            'bucket' => 'papayacoders',
        ]);
        $this->assertEquals("https://{$accountId}.r2.cloudflarestorage.com", $endpointExplicit);

        // 2. Account ID passed in region field fallback
        $endpoint = $this->providerManager->resolveEndpoint('cloudflare_r2', [
            'key' => 'r2_access_key_123',
            'region' => $accountId,
            'bucket' => 'papayacoders',
        ]);
        $this->assertEquals("https://{$accountId}.r2.cloudflarestorage.com", $endpoint);

        // 3. buildDiskConfig always keeps AWS SigV4 region as 'auto' for R2
        $config = $this->providerManager->buildDiskConfig([
            'name' => 'cloudflare_r2',
            'key' => 'r2_access_key_123',
            'secret' => 'r2_secret_456',
            'account_id' => $accountId,
            'bucket' => 'papayacoders',
        ]);
        $this->assertEquals('auto', $config['region']);
        $this->assertEquals("https://{$accountId}.r2.cloudflarestorage.com", $config['endpoint']);

        // 4. User passes bare account ID in endpoint field
        $endpointFromBare = $this->providerManager->resolveEndpoint('cloudflare_r2', [
            'endpoint' => $accountId,
            'bucket' => 'papayacoders',
        ]);
        $this->assertEquals("https://{$accountId}.r2.cloudflarestorage.com", $endpointFromBare);

        // 5. User accidentally enters bucket in endpoint URL (e.g. https://.../papayacoders)
        $endpointWithBucket = $this->providerManager->resolveEndpoint('cloudflare_r2', [
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com/papayacoders/",
            'bucket' => 'papayacoders',
        ]);
        $this->assertEquals("https://{$accountId}.r2.cloudflarestorage.com", $endpointWithBucket);
    }

    public function test_cloudflare_r2_connection_missing_account_id_returns_actionable_warning(): void
    {
        $response = $this->postJson(route('mediaflow.settings.test-provider'), [
            'provider' => 'cloudflare_r2',
            'key' => 'dummy_key',
            'secret' => 'dummy_secret',
            'bucket' => 'papayacoders',
            'region' => 'auto', // default placeholder, no account ID provided
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Cloudflare Account ID', $response->json('message'));
        $this->assertStringContainsString('r2.cloudflarestorage.com', $response->json('message'));
    }

    public function test_saving_provider_account_id_persists_in_database(): void
    {
        $accountId = '0123456789abcdef0123456789abcdef';

        $response = $this->postJson(route('mediaflow.settings.update'), [
            'provider_name' => 'cloudflare_r2',
            'provider_key' => 'r2_key_example',
            'provider_secret' => 'r2_secret_example',
            'provider_account_id' => $accountId,
            'provider_bucket' => 'papayacoders',
            'provider_region' => 'auto',
            'provider_public_url' => 'https://media.papayacoders.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $settingsManager = app(MediaFlowSettingsManager::class);
        $providerSettings = $settingsManager->get('provider');

        $this->assertEquals('cloudflare_r2', $providerSettings['name']);
        $this->assertEquals($accountId, $providerSettings['account_id']);
        $this->assertEquals('papayacoders', $providerSettings['bucket']);
    }
}
