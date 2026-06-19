<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeadActivityController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeadStatusController;
use App\Http\Controllers\Api\LeadSourceController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Webhooks\GoogleAdsWebhookController;
use App\Http\Controllers\Webhooks\IvrWebhookController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use Illuminate\Support\Facades\Route;

// Public auth
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Inbound webhooks (no auth, token-validated inside)
Route::prefix('webhooks')->group(function () {
    Route::match(['get', 'post'], 'meta',  [MetaWebhookController::class, 'handle']);
    Route::post('google-ads',              [GoogleAdsWebhookController::class, 'handle']);
    Route::post('ivr',                     [IvrWebhookController::class, 'handle']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard - all roles (legacy aggregate, kept for back-compat)
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Advanced analytics - all roles (scoped); reusable by dashboard & future reports
    Route::prefix('analytics')->group(function () {
        Route::get('dashboard',   [AnalyticsController::class, 'dashboard']);
        Route::get('overview',    [AnalyticsController::class, 'overview']);
        Route::get('funnel',      [AnalyticsController::class, 'funnel']);
        Route::get('sources',     [AnalyticsController::class, 'sources']);
        Route::get('agents',      [AnalyticsController::class, 'agents']);
        Route::get('teams',       [AnalyticsController::class, 'teams']);
        Route::get('aging',       [AnalyticsController::class, 'aging']);
        Route::get('follow-ups',  [AnalyticsController::class, 'followUps']);
        Route::get('trend',       [AnalyticsController::class, 'trend']);
        Route::get('conversion',  [AnalyticsController::class, 'conversion']);
        Route::get('activity',    [AnalyticsController::class, 'activity']);
        Route::get('executive',   [AnalyticsController::class, 'executive']);
    });

    // Leads - all roles, scoped in controller by role
    Route::get('leads/export', [LeadController::class, 'export']);
    Route::get('leads/import-template', [LeadController::class, 'template']);
    Route::post('leads/import', [LeadController::class, 'import']);
    Route::apiResource('leads', LeadController::class);
    Route::post('leads/{lead}/assign',     [LeadController::class, 'assign'])->middleware('role:master,sub_master');
    Route::post('leads/{lead}/status',     [LeadController::class, 'updateStatus']);
    Route::get('leads/{lead}/activities',  [LeadActivityController::class, 'index']);
    Route::post('leads/{lead}/activities', [LeadActivityController::class, 'store']);

    // Statuses (sub_master + master can manage; agents read-only)
    Route::get('lead-statuses', [LeadStatusController::class, 'index']);
    Route::middleware('role:master,sub_master')->group(function () {
        Route::post('lead-statuses',           [LeadStatusController::class, 'store']);
        Route::put('lead-statuses/{status}',   [LeadStatusController::class, 'update']);
        Route::delete('lead-statuses/{status}',[LeadStatusController::class, 'destroy']);
    });

    // Sources - master only manage; everyone read
    Route::get('lead-sources', [LeadSourceController::class, 'index']);
    Route::middleware('role:master')->group(function () {
        Route::post('lead-sources',           [LeadSourceController::class, 'store']);
        Route::put('lead-sources/{source}',   [LeadSourceController::class, 'update']);
        Route::delete('lead-sources/{source}',[LeadSourceController::class, 'destroy']);
    });

    // Projects
    Route::get('projects', [ProjectController::class, 'index']);
    Route::middleware('role:master,sub_master')->group(function () {
        Route::post('projects',             [ProjectController::class, 'store']);
        Route::put('projects/{project}',    [ProjectController::class, 'update']);
        Route::delete('projects/{project}', [ProjectController::class, 'destroy']);
    });

    // Users - master & sub_master can manage their team
    Route::middleware('role:super_master,master,sub_master,agent')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
