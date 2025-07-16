<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use App\Validators\StudentAccessValidator;
use App\Observers\ActionLogObserver;
use App\Models\User;
use App\Models\Student;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Level;
use App\Models\Term;
use App\Models\Enrollment;
use App\Models\AvailableCourse;
use App\Models\CourseEligibility;
use App\Models\CoursePrerequisite;
use App\Models\AcademicAdvisorAccess;
use App\Models\CreditHoursException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('student_access', StudentAccessValidator::class);

        User::observe(ActionLogObserver::class);
        Student::observe(ActionLogObserver::class);
        Course::observe(ActionLogObserver::class);
        Faculty::observe(ActionLogObserver::class);
        Program::observe(ActionLogObserver::class);
        Level::observe(ActionLogObserver::class);
        Term::observe(ActionLogObserver::class);
        Enrollment::observe(ActionLogObserver::class);
        AvailableCourse::observe(ActionLogObserver::class);
        CourseEligibility::observe(ActionLogObserver::class);
        CoursePrerequisite::observe(ActionLogObserver::class);
        AcademicAdvisorAccess::observe(ActionLogObserver::class);
        CreditHoursException::observe(ActionLogObserver::class);
    }
}
