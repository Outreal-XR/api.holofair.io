<?php

use App\Http\Controllers\{
    Auth\AuthController,
    DashboardSettingController,
    GeneralController,
    LanguageController,
    MetaverseController,
    MetaverseLanguage,
    MetaverseSettingController,
    MetaverseUserController,
    MetaverseUserSettingController,
    NotificationController,
    PaymentController,
    PlanController,
    SettingPerMetaverseController,
    TemplateController,
    UserDashboardSettingController
};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('/props/variables/upload', [GeneralController::class, 'uploadPropVariablesFile']);

        Route::prefix('plans')->group(function () {
            Route::get('/', [PlanController::class, 'getPlans']);
            Route::get('/current', [PlanController::class, 'getUserCurrentPlan']);
            // Route::post('/', [PlanController::class, 'addFreePlanToUsers']);
            Route::put('/{id}', [PlanController::class, 'subscribe'])->where('id', '[0-9]+');
            Route::post('/stripe/create', [PlanController::class, 'createPlanInStripe']);
        });

        Route::prefix('payments')->group(function () {
            Route::post('/subscribe/{plan_id}', [PaymentController::class, 'subscribe']);
            Route::get('/success', [PaymentController::class, 'success'])->name('checkout.success');
            Route::get('/cancel', [PaymentController::class, 'cancel'])->name('checkout.cancel');
        });

        Route::prefix('user')->group(function () {
            Route::get('/', [AuthController::class, 'user']);
            Route::post('/update', [AuthController::class, 'update']);
        });

        Route::prefix('users')->group(function () {
        });

        Route::prefix('metaverses')->group(function () {
            Route::post('/', [MetaverseController::class, 'create']);
            Route::get("/user", [MetaverseController::class, "getMetaversesByUser"]);
            Route::get('/shared', [MetaverseController::class, 'getSharedMetaverses']);
            Route::get('/check-uniqueness', [MetaverseController::class, 'checkUniqueness']);

            Route::middleware(['metaverse.canAccess'])->group(function () {
                Route::get("/{id}", [MetaverseController::class, "getMetaverseById"])->where('id', '[0-9]+');
            });

            Route::middleware(['metaverse.canEdit'])->group(function () {
                Route::post('/{id}/update', [MetaverseController::class, 'updateMetaverse'])->where('id', '[0-9]+');
                Route::post('/{id}/invites/{invite_id}/block', [MetaverseUserController::class, 'blockUser'])->where('id', '[0-9]+')->where('invite_id', '[0-9]+');
                Route::post('/{id}/invites/{invite_id}/unblock', [MetaverseUserController::class, 'unblockUser'])->where('id', '[0-9]+')->where('invite_id', '[0-9]+');
                Route::delete('/{id}/invites/{invite_id}', [MetaverseUserController::class, 'removeUser'])->where('id', '[0-9]+')->where('invite_id', '[0-9]+');
                Route::post('/{id}/links', [MetaverseController::class, 'addLink'])->where('id', '[0-9]+');
                Route::get('/{id}/languages', [MetaverseLanguage::class, 'index'])->where('id', '[0-9]+');
                Route::post('/{id}/languages', [MetaverseLanguage::class, 'create'])->where('id', '[0-9]+');
            });

            Route::middleware(['metaverse.owner'])->group(function () {
                Route::delete('/{id}', [MetaverseController::class, 'deleteMetaverse'])->where('id', '[0-9]+');
            });

            Route::get("/{id}/emails/search", [MetaverseUserController::class, "searchEmails"])->where('id', '[0-9]+');
            Route::post('/{id}/users/invite', [MetaverseUserController::class, 'sendInvite'])->where('id', '[0-9]+');
            Route::get('/{id}/collaborators', [MetaverseUserController::class, 'getCollaborators'])->where('id', '[0-9]+');
            Route::post('/invites/{id}/update', [MetaverseUserController::class, 'updateInvite'])->where('id', '[0-9]+'); //->middleware(['metaverse.canEdit']);
            Route::post('/invites/{id}/resend', [MetaverseUserController::class, 'resendInvite'])->where('id', '[0-9]+')->middleware(['throttle:invites']);
            Route::get('/{id}/invites/{invite_id}/check', [MetaverseUserController::class, 'checkInvite']);
            Route::put('/{id}/invites/{invite_id}/accept', [MetaverseUserController::class, 'acceptInvite']);
            Route::put('/{id}/invites/{invite_id}/reject', [MetaverseUserController::class, 'rejectInvite']);
        });

        Route::get('/invites/pending', [MetaverseUserController::class, 'getPendingInvitations']);

        Route::prefix('settings')->group(function () {
            Route::prefix('metaverse')->group(function () {
                Route::post('/', [MetaverseSettingController::class, 'create']);
                Route::post('/add', [MetaverseSettingController::class, 'addSettings']);

                Route::post('/{id}', [SettingPerMetaverseController::class, 'create']);
                Route::put('/{id}', [SettingPerMetaverseController::class, 'update']);
                Route::get('/{id}', [SettingPerMetaverseController::class, 'index']);
            });

            Route::prefix('dashboard')->group(function () {
                Route::post('/', [DashboardSettingController::class, 'create']);
                Route::get('/', [DashboardSettingController::class, 'index']);

                Route::put('/user', [UserDashboardSettingController::class, 'update']);
            });

            Route::prefix('user')->group(function () {
                Route::get('/metaverses/{id}', [MetaverseUserSettingController::class, 'index']);
                Route::post('/metaverses/{id}', [MetaverseUserSettingController::class, 'createOrUpdate']);
            });
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::put('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
            Route::put('/{id}/mark-as-unread', [NotificationController::class, 'markAsUnread']);
            Route::put('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'delete']);
            Route::delete('/', [NotificationController::class, 'deleteAll']);
        });

        Route::prefix('languages')->group(function () {
            Route::post('/', [LanguageController::class, 'create']);
            Route::get('/', [LanguageController::class, 'getLanguages']);
        });

        Route::prefix('languages')->group(function () {
            Route::get('/', [LanguageController::class, 'index']);
            Route::post('/', [LanguageController::class, 'create']);
        });
    });

    //public routes
    Route::get('/metaverses/{id}/public', [MetaverseController::class, 'getMetaverseById'])->where('id', '[0-9]+');

    Route::prefix("templates")->group(function () {
        Route::get("/", [TemplateController::class, "index"]);
    });

    Route::prefix("props")->group(function () {
        Route::post("/upload", [GeneralController::class, "uploadPropImages"]);
    });
    Route::prefix('metaverses')->group(function () {
        Route::get('/{metaverse_id}/platforms/{platform_id}/addrassables', [MetaverseController::class, 'getMetaverseAddrassablesLinks']);
    });

    Route::post('/add-system-languages', [LanguageController::class, 'addLanguages']);
    Route::post('/add-english-to-metaverses', [LanguageController::class, 'addEnglishToMetaverses']);
});

//load auth routes
require __DIR__ . '/auth.php';
