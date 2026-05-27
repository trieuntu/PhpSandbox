<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Student\HomeController;
use App\Http\Controllers\Student\ClassController as StudentClassController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use App\Http\Controllers\Student\SandboxController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClassController as AdminClassController;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\SettingsController;

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware(['guest', 'registration.enabled']);
Route::post('/register', [RegisterController::class, 'register'])->middleware(['guest', 'registration.enabled']);

// Student routes
Route::middleware(['auth', 'inactivity'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('student.home');
    Route::get('/classes', [StudentClassController::class, 'index'])->name('student.classes.index');
    Route::get('/classes/{class}', [StudentClassController::class, 'show'])->name('student.classes.show');
    Route::get('/classes/{class}/assignments/{assignment}', [StudentClassController::class, 'assignment'])->name('student.classes.assignment');
    Route::get('/sandbox', [SandboxController::class, 'editor'])->name('student.sandbox.editor');

    Route::get('/exams/{exam}', [StudentExamController::class, 'lobby'])->name('student.exams.lobby');
    Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])->name('student.exams.start');
    Route::get('/exams/{exam}/editor', [StudentExamController::class, 'editor'])->name('student.exams.editor')->middleware('exam.access');
    Route::post('/exams/{exam}/submit', [StudentExamController::class, 'submit'])->name('student.exams.submit');
    Route::get('/exams/{exam}/submitted', [StudentExamController::class, 'submitted'])->name('student.exams.submitted');
});

// Sandbox API
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::post('/api/sandbox/execute', [SandboxController::class, 'execute'])->name('sandbox.execute');
    Route::get('/api/sandbox/job/{submissionId}', [SandboxController::class, 'pollJob'])->name('sandbox.poll');
    Route::get('/api/sandbox/preview/{submissionId}', [SandboxController::class, 'preview'])->name('sandbox.preview');
    Route::post('/api/sandbox/execute-form/{submissionId}', [SandboxController::class, 'executeForm'])->name('sandbox.execute_form');
    Route::post('/api/heartbeat', [SandboxController::class, 'heartbeat'])->name('heartbeat');
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/import', [UserController::class, 'importForm'])->name('users.import.form');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/settings/registration-toggle', [UserController::class, 'toggleRegistration'])->name('settings.registration-toggle');

    // Classes
    Route::get('/classes', [AdminClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [AdminClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [AdminClassController::class, 'store'])->name('classes.store');
    Route::get('/classes/{class}', [AdminClassController::class, 'show'])->name('classes.show');
    Route::get('/classes/{class}/edit', [AdminClassController::class, 'edit'])->name('classes.edit');
    Route::put('/classes/{class}', [AdminClassController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{class}', [AdminClassController::class, 'destroy'])->name('classes.destroy');
    Route::patch('/classes/{class}/toggle', [AdminClassController::class, 'toggle'])->name('classes.toggle');

    // Assignments (nested under classes)
    Route::get('/classes/{class}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/classes/{class}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/classes/{class}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::get('/classes/{class}/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('/classes/{class}/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/classes/{class}/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::patch('/classes/{class}/assignments/{assignment}/toggle', [AssignmentController::class, 'toggle'])->name('assignments.toggle');

    // Exams
    Route::get('/exams', [AdminExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/create', [AdminExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [AdminExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{exam}', [AdminExamController::class, 'show'])->name('exams.show');
    Route::get('/exams/{exam}/edit', [AdminExamController::class, 'edit'])->name('exams.edit');
    Route::put('/exams/{exam}', [AdminExamController::class, 'update'])->name('exams.update');
    Route::delete('/exams/{exam}', [AdminExamController::class, 'destroy'])->name('exams.destroy');
    Route::patch('/exams/{exam}/toggle', [AdminExamController::class, 'toggle'])->name('exams.toggle');

    // Submissions
    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])->name('submissions.download');
    Route::get('/submissions/{submission}/download-output', [SubmissionController::class, 'downloadOutput'])->name('submissions.download-output');

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
});
