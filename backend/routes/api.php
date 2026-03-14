<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\PictureBookController;
use App\Http\Controllers\Api\ReadRecordController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

Route::prefix('v1')->group(function () {
    // 招待情報取得（認証不要）
    Route::get('/invitations/{token}', [InvitationController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Family
    Route::post('/families', [FamilyController::class, 'store']);
    Route::get('/families/{family}', [FamilyController::class, 'show']);
    Route::put('/families/{family}', [FamilyController::class, 'update']);
    Route::get('/families/{family}/members', [FamilyController::class, 'members']);

    // Children
    Route::get('/families/{family}/children', [ChildController::class, 'index']);
    Route::post('/families/{family}/children', [ChildController::class, 'store']);
    Route::put('/families/{family}/children/{child}', [ChildController::class, 'update']);
    Route::delete('/families/{family}/children/{child}', [ChildController::class, 'destroy']);

    // Google Books search
    Route::get('/books/search', [PictureBookController::class, 'search']);

    // Bookshelf (picture books)
    Route::prefix('/families/{family}/books')->scopeBindings()->group(function () {
        Route::get('/', [PictureBookController::class, 'index']);
        Route::post('/', [PictureBookController::class, 'store']);
        Route::get('/{pictureBook}', [PictureBookController::class, 'show']);
        Route::put('/{pictureBook}', [PictureBookController::class, 'update']);
        Route::delete('/{pictureBook}', [PictureBookController::class, 'destroy']);
    });

    // Read records (読み聞かせ記録)
    Route::prefix('/families/{family}/records')->scopeBindings()->group(function () {
        Route::get('/', [ReadRecordController::class, 'index']);
        Route::post('/', [ReadRecordController::class, 'store']);
        Route::get('/{readRecord}', [ReadRecordController::class, 'show']);
        Route::put('/{readRecord}', [ReadRecordController::class, 'update']);
        Route::delete('/{readRecord}', [ReadRecordController::class, 'destroy']);
    });

    // Invitations (招待)
    Route::prefix('/families/{family}/invitations')->group(function () {
        Route::post('/', [InvitationController::class, 'store']);
        Route::get('/', [InvitationController::class, 'index']);
        Route::delete('/{invitation}', [InvitationController::class, 'destroy']);
    });

    // Accept invitation (トークンで特定)
    Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept']);

    // Tags (タグ検索)
    Route::get('/tags', [TagController::class, 'index']);
});
