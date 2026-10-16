<?php

namespace MediaFlow\Laravel\Tests\Unit;

use Illuminate\Http\UploadedFile;
use MediaFlow\Laravel\Tests\TestCase;
use MediaFlow\Laravel\Upload\ValidationService;

class ValidationServiceTest extends TestCase
{
    protected ValidationService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidationService();
    }

    public function test_valid_image_passes_validation(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400)->size(500);
        $result = $this->validator->validateFile($file);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }

    public function test_executable_script_extension_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('malicious.php', 10, 'text/x-php');
        $result = $this->validator->validateFile($file);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('executable files', $result['error']);
    }

    public function test_oversized_file_is_rejected(): void
    {
        config()->set('mediaflow.max_file_size_kb', 100); // 100 KB limit
        $file = UploadedFile::fake()->image('huge.png', 800, 800)->size(200); // 200 KB

        $result = $this->validator->validateFile($file);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('exceeds maximum allowed threshold', $result['error']);
    }

    public function test_disallowed_mime_type_is_rejected(): void
    {
        config()->set('mediaflow.allowed_mimes', ['image/jpeg', 'image/png']);
        $file = UploadedFile::fake()->create('document.zip', 50, 'application/zip');

        $result = $this->validator->validateFile($file);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('MIME type', $result['error']);
    }
}
