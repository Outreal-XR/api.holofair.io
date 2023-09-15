<?php

use App\Http\Controllers\{
    Auth\AuthController,
    TempController,
    Auth\EmailVerificationController,
    GeneralController,
    MetaverseController,
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

    // Route::get("/add-settings", function () {
    //     $metaverses = \App\Models\Metaverse::all();

    //     foreach ($metaverses as $metaverse) {
    //         $generalSettings = new \App\Models\GeneralSettings();
    //         $generalSettings->metaverse_id = $metaverse->id;
    //         $generalSettings->save();
    //     }
    // });

    Route::get("/add-settings", [SettingsController::class, "addSettings"]);
    Route::get("/test-observer", [SettingsController::class, "testObserver"]);

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::middleware('auth:sanctum')->group(function () {

        // Route::middleware('verified')->group(function () {
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/user/update', [AuthController::class, 'update']);

        Route::prefix('users')->group(function () {
        });

        Route::prefix('temps')->group(function () {
            Route::post('/', [TempController::class, 'store']);
            Route::get('/{id}', [TempController::class, 'show']);
            Route::post('/{id}/update', [TempController::class, 'update']);
            Route::get('/', [TempController::class, 'index']);
            Route::delete('/{id}', [TempController::class, 'destroy']);
        });

        Route::prefix('metaverses')->group(function () {
            Route::post('/', [MetaverseController::class, 'createMetaverseFromTemplate']);
            Route::get("/user", [MetaverseController::class, "getMetaversesByUser"]);
            Route::get("/{id}", [MetaverseController::class, "getMetaverseById"])->where('id', '[0-9]+');
            Route::post('/{id}/update', [MetaverseController::class, 'updateMetaverse'])->where('id', '[0-9]+');
            Route::get("/{id}/emails/search", [MetaverseController::class, "searchEmails"])->where('id', '[0-9]+');
            Route::post('/{id}/users/invite', [MetaverseController::class, 'sendInvite'])->where('id', '[0-9]+');
            Route::get('/{id}/collaborators', [MetaverseController::class, 'getCollaborators'])->where('id', '[0-9]+');

            Route::prefix("invites")->group(function () {
                Route::post('/{id}/update', [MetaverseController::class, 'updateInvite'])->where('id', '[0-9]+');
                Route::post('/{id}/resend', [MetaverseController::class, 'resendInvite'])->where('id', '[0-9]+');
                Route::delete('/{id}', [MetaverseController::class, 'deleteInvite'])->where('id', '[0-9]+');
            });

            Route::get('/shared', [MetaverseController::class, 'getSharedMetaverses']);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/metaverse/{id}', [SettingsController::class, 'getMetaverseSettings'])->where('id', '[0-9]+');
            Route::put('/{id}/metaverse/{metaverse_id}', [SettingsController::class, 'updatedMetaverseSetting'])->where('id', '[0-9]+')->where('metaverse_id', '[0-9]+');
        });

        Route::prefix("templates")->group(function () {
            Route::get("/", [TemplateController::class, "index"]);
        });

        // });
    });
    Route::prefix("props")->group(function () {
        Route::post("/upload", [GeneralController::class, "uploadPropImages"]);
    });
    Route::prefix('metaverses')->group(function () {
        Route::get('/{metaverse_id}/platforms/{platform_id}/addrassables', [MetaverseController::class, 'getMetaverseAddrassablesLinks']);
    });
});
