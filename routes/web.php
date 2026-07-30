<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DailyReportPdfController;
use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Login Google Workspace. As rotas existem sempre, mas o controller aborta com
// 404 enquanto GOOGLE_AUTH_ENABLED=false - ver config/google.php.
Route::prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('/', [GoogleController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GoogleController::class, 'callback'])->name('callback');
});

// PWA do vigilante. A página em si é pública: quem autentica é a API, e o app
// precisa abrir offline antes de ter qualquer token para mostrar a tela de login.
Route::view('/campo', 'field.app')->name('field');

// Folhas de QR Code. Exigem sessão do painel: o token impresso é o que autentica
// a passagem pelo ponto e não pode ficar exposto publicamente.
Route::middleware(['auth'])->group(function () {
    Route::get('/qr/posto/{post}', [QrCodeController::class, 'post'])->name('qr.post');
    Route::get('/qr/ponto/{checkpoint}', [QrCodeController::class, 'checkpoint'])->name('qr.checkpoint');
    Route::get('/qr/unidade/{unit}/pontos', [QrCodeController::class, 'unitCheckpoints'])->name('qr.unit.checkpoints');

    Route::get('/rdo/{dailyReport}/pdf', DailyReportPdfController::class)->name('rdo.pdf');
});
