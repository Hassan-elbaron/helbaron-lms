<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Events\CourseCompleted;
use App\Contexts\Learning\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a certificate when a course is completed (and only once)', function () {
    CertificateTemplate::factory()->create(['is_active' => true]);

    $user = User::factory()->create();
    $course = Course::factory()->published()->create();
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);

    CourseCompleted::dispatch($enrollment);
    CourseCompleted::dispatch($enrollment); // idempotent

    $certs = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->get();

    expect($certs)->toHaveCount(1)
        ->and($certs->first()->number)->toStartWith('CERT-')
        ->and($certs->first()->verification_code)->toBeString()
        ->and($certs->first()->signature_hash)->toBeString();
});
