<?php

use App\Enums\PowerXRole;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Support\PowerXForm;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('shared media upload fields are private and constrained', function (): void {
    $cover = PowerXForm::imageUpload('cover_image', 'cover-image');
    $materials = PowerXForm::mediaUpload('learning_materials', 'learning-materials', [
        'application/pdf',
        'application/zip',
    ], maxSize: 51200, multiple: true, maxFiles: 20);
    $generatedPdf = PowerXForm::readOnlyPdfUpload('invoice_pdf', 'invoice-pdf');

    expect($cover)->toBeInstanceOf(SpatieMediaLibraryFileUpload::class)
        ->and($cover->getCollection())->toBe('cover-image')
        ->and($cover->getAcceptedFileTypes())->toBe([
            'image/jpeg',
            'image/png',
            'image/webp',
        ])
        ->and($cover->getMaxSize())->toBe(4096)
        ->and($cover->getVisibility())->toBe('private')
        ->and($cover->isDownloadable())->toBeTrue()
        ->and($materials->isMultiple())->toBeTrue()
        ->and($materials->isReorderable())->toBeTrue()
        ->and($materials->getMaxFiles())->toBe(20)
        ->and($generatedPdf->isDisabled())->toBeTrue()
        ->and($generatedPdf->isDeletable())->toBeFalse();
});

test('course form stores cover image and syllabus media uploads', function (): void {
    Storage::fake('local');
    $user = User::factory()->create();
    grantPowerXRole($user, PowerXRole::Management);

    $this->actingAs($user);

    Livewire::test(CreateCourse::class)
        ->fillForm([
            'title' => 'Media Ready Course',
            'slug' => 'media-ready-course',
            'delivery_mode' => 'blended',
            'currency' => 'QAR',
            'base_price' => 1500,
            'status' => 'draft',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'syllabus' => UploadedFile::fake()->create('syllabus.pdf', 64, 'application/pdf'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $course = Course::query()->where('slug', 'media-ready-course')->firstOrFail();

    expect($course->team_id)->toBe($user->current_team_id)
        ->and($course->getMedia('cover-image'))->toHaveCount(1)
        ->and($course->getFirstMedia('cover-image')?->disk)->toBe('local')
        ->and($course->getFirstMedia('cover-image')?->mime_type)->toBe('image/jpeg')
        ->and($course->getMedia('syllabus'))->toHaveCount(1)
        ->and($course->getFirstMedia('syllabus')?->file_name)->toEndWith('.pdf');
});
