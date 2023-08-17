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
            Route::get("/{id}", [MetaverseController::class, "getMetaverseById"]);
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
