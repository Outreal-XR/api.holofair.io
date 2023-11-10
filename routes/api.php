<?php

use App\Http\Controllers\{
    Auth\AuthController,
    Auth\VerifyEmailController,
    Auth\EmailVerificationNotificationController,
    GeneralController,
    MetaverseController,
    MetaverseUserController,
    SettingsController,
    TemplateController
};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'login']);

    Route::get("/test", function () {
        return response()->json([
            "message" => "Hello World"
        ]);
    });

    Route::get("/add-settings", [SettingsController::class, "addSettings"]);
    Route::get("/test-observer", [SettingsController::class, "testObserver"]);


    //public routes
    Route::get('/metaverses/{ismtpd}/public', [MetaverseController::class, 'getMetaverseById'])->where('id', '[0-9]+');

    Route::get("/testEmail", [GeneralController::class, "getEmailTemplate"]);
    Route::post("/smtp/email", [GeneralController::class, "testEmail"]);

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware(['throttle:1,5'])
            ->name('verification.send');
    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {

        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/user/update', [AuthController::class, 'update']);

        Route::prefix('users')->group(function () {
        });

        Route::prefix('metaverses')->group(function () {
            Route::post('/', [MetaverseController::class, 'createMetaverseFromTemplate']);
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
            Route::get('/metaverse/{id}', [SettingsController::class, 'getMetaverseSettings'])->where('id', '[0-9]+');
            Route::put('/{id}/metaverse/{metaverse_id}', [SettingsController::class, 'updateMetaverseSetting'])->middleware(['metaverse.canEdit'])->where('id', '[0-9]+')->where('metaverse_id', '[0-9]+');
        });

        Route::prefix("templates")->group(function () {
            Route::get("/", [TemplateController::class, "index"]);
        });
    });
    Route::prefix("props")->group(function () {
        Route::post("/upload", [GeneralController::class, "uploadPropImages"]);
    });
    Route::prefix('metaverses')->group(function () {
        Route::get('/{metaverse_id}/platforms/{platform_id}/addrassables', [MetaverseController::class, 'getMetaverseAddrassablesLinks']);
    });
});
