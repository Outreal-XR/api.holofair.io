<?php

use App\Http\Controllers\{
    Auth\AuthController,
    TempController,
    Auth\EmailVerificationController,
    GeneralController,
    MetaverseController,
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

    Route::post("/test/upload", [GeneralController::class, 'test']);
    Route::post("/test-binary/upload", [GeneralController::class, 'testBinary']);

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
            Route::post('/{id}/users/invite', [MetaverseController::class, 'sendInvite'])->where('id', '[0-9]+');
            Route::post('/invites/{id}/update', [MetaverseController::class, 'updateInvite'])->where('id', '[0-9]+');
            Route::post('/invites/{id}/resend', [MetaverseController::class, 'resendInvite'])->where('id', '[0-9]+');
            Route::delete('/invites/{id}', [MetaverseController::class, 'deleteInvite'])->where('id', '[0-9]+');
            Route::get('/{id}/collaborators', [MetaverseController::class, 'getCollaborators'])->where('id', '[0-9]+');
            Route::get('/shared', [MetaverseController::class, 'getSharedMetaverses']);
            Route::get('/{id}/users/emails/', [MetaverseController::class, 'searchEmails']);
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
