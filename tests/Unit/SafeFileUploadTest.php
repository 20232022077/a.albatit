<?php

namespace Tests\Unit;

use App\Support\SafeFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SafeFileUploadTest extends TestCase
{
    public function test_accepts_a_genuine_image(): void
    {
        $file = UploadedFile::fake()->image('cover.jpg', 200, 200);

        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 4096);
        $this->assertTrue(true); // no exception thrown
    }

    public function test_accepts_a_genuine_pdf(): void
    {
        $file = UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.4\n%%EOF");

        SafeFileUpload::assertSafe($file, SafeFileUpload::PDF_EXTENSIONS, 51200);
        $this->assertTrue(true);
    }

    public function test_rejects_a_php_file_disguised_with_an_image_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('shell.jpg', "<?php system(\$_GET['c']); ?>");

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 4096);
    }

    public function test_rejects_a_double_extension_filename(): void
    {
        $file = UploadedFile::fake()->createWithContent('cover.php.jpg', "%PDF-1.4\n%%EOF");

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 4096);
    }

    public function test_rejects_a_pdf_containing_a_javascript_auto_action(): void
    {
        $file = UploadedFile::fake()->createWithContent('evil.pdf', "%PDF-1.4\n/OpenAction << /S /JavaScript /JS (app.alert('x')) >>\n%%EOF");

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::PDF_EXTENSIONS, 51200);
    }

    public function test_rejects_a_pdf_containing_a_launch_action(): void
    {
        $file = UploadedFile::fake()->createWithContent('evil.pdf', "%PDF-1.4\n/OpenAction << /S /Launch /Win << /F (cmd.exe) >> >>\n%%EOF");

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::PDF_EXTENSIONS, 51200);
    }

    public function test_rejects_a_file_that_does_not_start_with_the_pdf_magic_header(): void
    {
        $file = UploadedFile::fake()->createWithContent('fake.pdf', 'this is not really a pdf');

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::PDF_EXTENSIONS, 51200);
    }

    public function test_rejects_a_file_exceeding_the_size_limit(): void
    {
        $file = UploadedFile::fake()->image('big.jpg', 50, 50)->size(5000); // KB

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 1024);
    }

    public function test_rejects_an_extension_not_in_the_allow_list(): void
    {
        $file = UploadedFile::fake()->createWithContent('archive.exe', 'MZ...');

        $this->expectException(ValidationException::class);
        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 4096);
    }

    public function test_classify_maps_extensions_to_the_correct_type(): void
    {
        $this->assertSame('image', SafeFileUpload::classify('jpg'));
        $this->assertSame('image', SafeFileUpload::classify('WEBP'));
        $this->assertSame('pdf', SafeFileUpload::classify('pdf'));
        $this->assertSame('video', SafeFileUpload::classify('mp4'));
        $this->assertNull(SafeFileUpload::classify('exe'));
        $this->assertNull(SafeFileUpload::classify('php'));
    }

    public function test_pdfs_are_stored_on_the_private_disk_and_everything_else_on_public(): void
    {
        $this->assertSame('local', SafeFileUpload::diskFor('pdf'));
        $this->assertSame('public', SafeFileUpload::diskFor('image'));
        $this->assertSame('public', SafeFileUpload::diskFor('video'));
    }
}
